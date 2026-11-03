<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\SeatHoldException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Discount;
use App\Models\ShowtimeSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Booking::with(['user', 'items.showtimeSeat.showtime.movie', 'items.showtimeSeat.seat', 'items.showtimeSeat.showtime.room']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $bookings = $query->latest()->paginate(10)->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Request $request, Booking $booking): View|JsonResponse
    {
        $booking->load([
            'user',
            'items.showtimeSeat.seat.zone',
            'items.showtimeSeat.showtime.movie.category',
            'items.showtimeSeat.showtime.room',
        ]);

        if ($request->wantsJson()) {
            return response()->json($booking);
        }

        return view('admin.bookings.show', compact('booking'));
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,cancelled',
        ]);

        $newStatus = $validated['status'];

        if ($booking->status === $newStatus) {
            return back()->with('warning', "Đơn vé [{$booking->booking_code}] đã ở trạng thái này, không có thay đổi.");
        }

        try {
            DB::transaction(function () use ($booking, $newStatus) {
                $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
                if ($locked->status === $newStatus) {
                    return;
                }

                $items = $locked->items()->get();
                $seatIds = $items->pluck('showtime_seat_id')->filter()->unique()->values();
                $seats = ShowtimeSeat::with('seat')->whereIn('id', $seatIds)->lockForUpdate()->get();

                // Ghế đang thuộc một đơn "confirmed" KHÁC (vd. ghế đã bán lại sau khi đơn này bị hủy).
                $claimedSeatIds = $this->claimedByOtherBookings($locked, $seatIds);

                if ($newStatus === 'cancelled') {
                    foreach ($seats as $seat) {
                        if ($seat->status === 'booked' && ! $claimedSeatIds->contains($seat->id)) {
                            $seat->update([
                                'status' => 'available',
                                'held_by_user_id' => null,
                                'held_until' => null,
                                'price_override' => null,
                            ]);
                        }
                    }
                } else {
                    $this->assertSeatsFree($locked, $items, $seats, $claimedSeatIds);

                    foreach ($seats as $seat) {
                        $seat->update(['status' => 'booked', 'held_by_user_id' => null, 'held_until' => null]);
                    }
                }

                $this->syncDiscountUsage($locked, $newStatus);

                $locked->update(['status' => $newStatus]);
            });
        } catch (SeatHoldException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = $newStatus === 'cancelled'
            ? "Đã hủy đơn vé [{$booking->booking_code}] và giải phóng toàn bộ ghế liên quan."
            : "Đã xác nhận thanh toán đơn vé [{$booking->booking_code}].";

        return back()->with('success', $message);
    }

    /**
     * Hủy đơn thì trả lại 1 lượt dùng mã giảm giá; xác nhận lại thì dùng lại 1 lượt (hết lượt → từ chối).
     * Ngày hiệu lực không kiểm tra lại: mã đã hợp lệ tại thời điểm khách thanh toán.
     *
     * @throws SeatHoldException
     */
    private function syncDiscountUsage(Booking $booking, string $newStatus): void
    {
        if (! $booking->discount_id) {
            return;
        }

        $discount = Discount::whereKey($booking->discount_id)->lockForUpdate()->first();
        if (! $discount) {
            return;
        }

        if ($newStatus === 'cancelled') {
            if ($discount->used_count > 0) {
                $discount->decrement('used_count');
            }

            return;
        }

        if ($discount->max_uses !== null && $discount->used_count >= $discount->max_uses) {
            throw new SeatHoldException("Mã giảm giá {$discount->code} của đơn đã hết lượt sử dụng, không thể xác nhận lại đơn.");
        }
        $discount->increment('used_count');
    }

    /** Id ghế (trong $ids) đang nằm trong một đơn "confirmed" khác đơn hiện tại. */
    private function claimedByOtherBookings(Booking $booking, Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return BookingItem::whereIn('showtime_seat_id', $ids)
            ->where('booking_id', '!=', $booking->id)
            ->whereHas('booking', fn ($query) => $query->where('status', 'confirmed'))
            ->pluck('showtime_seat_id')
            ->unique()
            ->values();
    }

    /**
     * Chỉ xác nhận lại đơn khi mọi ghế vẫn còn trống: không ai khác đang giữ và không thuộc đơn khác.
     * (Khu đứng cũng là ghế ẩn trong showtime_seats nên dùng chung kiểm tra này.)
     *
     * @throws SeatHoldException
     */
    private function assertSeatsFree(Booking $booking, Collection $items, Collection $seats, Collection $claimedSeatIds): void
    {
        $prefix = "Không thể xác nhận lại đơn [{$booking->booking_code}]";

        $missing = $items->contains(fn (BookingItem $item) => $item->showtime_seat_id === null
            || ! $seats->contains('id', $item->showtime_seat_id));
        if ($missing) {
            throw new SeatHoldException("{$prefix}: một số ghế của đơn không còn tồn tại.");
        }

        $taken = $seats->filter(fn (ShowtimeSeat $seat) => $claimedSeatIds->contains($seat->id)
            || ! ($seat->status === 'booked' || $seat->isHoldable()));

        if ($taken->isNotEmpty()) {
            $codes = $taken->map(fn (ShowtimeSeat $seat) => $seat->seat?->full_code)->filter()->unique()->implode(', ');

            throw new SeatHoldException("{$prefix}: ghế {$codes} đã được người khác giữ hoặc mua.");
        }
    }
}
