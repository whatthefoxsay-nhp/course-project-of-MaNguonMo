<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\ShowtimeSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'items.showtimeSeat.seat',
            'items.showtimeSeat.showtime.movie.category',
            'items.showtimeSeat.showtime.room',
        ]);

        if ($request->wantsJson()) {
            return response()->json($booking);
        }

        return view('admin.bookings.show', compact('booking'));
    }

    /** Trạng thái được phép chuyển tới; "cancelled" là trạng thái cuối. */
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['cancelled'],
        'cancelled' => [],
    ];

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,cancelled,pending',
        ]);
        $newStatus = $validated['status'];

        if (! in_array($newStatus, self::TRANSITIONS[$booking->status] ?? [], true)) {
            return back()->with('error', "Không thể chuyển đơn [{$booking->booking_code}] từ trạng thái \"{$booking->status}\" sang \"{$newStatus}\".");
        }

        $conflict = DB::transaction(function () use ($booking, $newStatus) {
            $seats = ShowtimeSeat::whereIn('id', $booking->items()->pluck('showtime_seat_id'))
                ->lockForUpdate()
                ->get();

            if ($newStatus === 'confirmed') {
                $takenByOtherBooking = BookingItem::whereIn('showtime_seat_id', $seats->pluck('id'))
                    ->where('booking_id', '!=', $booking->id)
                    ->whereHas('booking', fn ($query) => $query->where('status', '!=', 'cancelled'))
                    ->exists();
                $heldByOtherUser = $seats->contains(fn (ShowtimeSeat $seat) => $seat->status === 'held'
                    && $seat->held_by_user_id !== null
                    && $seat->held_by_user_id !== $booking->user_id
                    && $seat->held_until?->isFuture());

                if ($takenByOtherBooking || $heldByOtherUser) {
                    return true;
                }
            }

            $booking->update(['status' => $newStatus]);

            $seatState = $newStatus === 'cancelled'
                ? ['status' => 'available', 'held_by_user_id' => null, 'held_until' => null, 'price_override' => null]
                : ['status' => 'booked', 'held_by_user_id' => null, 'held_until' => null];
            ShowtimeSeat::whereIn('id', $seats->pluck('id'))->update($seatState);

            return false;
        });

        if ($conflict) {
            return back()->with('error', "Không thể xác nhận đơn [{$booking->booking_code}]: có ghế đã thuộc về đơn khác hoặc đang được người khác giữ.");
        }

        $message = match ($newStatus) {
            'cancelled' => "Đã hủy đơn vé [{$booking->booking_code}] và giải phóng toàn bộ ghế liên quan.",
            default => "Đã xác nhận thanh toán đơn vé [{$booking->booking_code}].",
        };

        return back()->with('success', $message);
    }
}
