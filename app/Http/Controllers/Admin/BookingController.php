<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\SeatHoldException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomZone;
use App\Models\ShowtimeSeat;
use App\Models\ZoneTicket;
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
        $query = Booking::with(['user', 'items.showtimeSeat.showtime.movie', 'items.showtimeSeat.seat', 'items.showtimeSeat.showtime.room', 'items.zoneTicket.zone', 'items.zoneTicket.showtime.movie', 'items.zoneTicket.showtime.room']);

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
            'items.zoneTicket.zone',
            'items.zoneTicket.showtime.movie.category',
            'items.zoneTicket.showtime.room',
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
                $ticketIds = $items->pluck('zone_ticket_id')->filter()->unique()->values();

                $seats = ShowtimeSeat::with('seat')->whereIn('id', $seatIds)->lockForUpdate()->get();
                $tickets = ZoneTicket::with('zone')->whereIn('id', $ticketIds)->lockForUpdate()->get();

                // Ghế/vé đang thuộc một đơn "confirmed" KHÁC (vd. ghế đã bán lại sau khi đơn này bị hủy).
                $claimedSeatIds = $this->claimedByOtherBookings($locked, 'showtime_seat_id', $seatIds);
                $claimedTicketIds = $this->claimedByOtherBookings($locked, 'zone_ticket_id', $ticketIds);

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
                    foreach ($tickets as $ticket) {
                        if ($ticket->status === 'booked' && ! $claimedTicketIds->contains($ticket->id)) {
                            // Giữ dòng vé gắn với đơn để tra cứu; hold đã hết hạn không còn chiếm sức chứa khu.
                            $ticket->update(['status' => 'held', 'held_by_user_id' => null, 'held_until' => now()->subSecond()]);
                        }
                    }
                } else {
                    $this->assertInventoryFree($locked, $items, $seats, $tickets, $claimedSeatIds, $claimedTicketIds);

                    foreach ($seats as $seat) {
                        $seat->update(['status' => 'booked', 'held_by_user_id' => null, 'held_until' => null]);
                    }
                    foreach ($tickets as $ticket) {
                        $ticket->update(['status' => 'booked', 'held_by_user_id' => null, 'held_until' => null]);
                    }
                }

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

    /** Id ghế/vé (trong $ids) đang nằm trong một đơn "confirmed" khác đơn hiện tại. */
    private function claimedByOtherBookings(Booking $booking, string $column, Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return BookingItem::whereIn($column, $ids)
            ->where('booking_id', '!=', $booking->id)
            ->whereHas('booking', fn ($query) => $query->where('status', 'confirmed'))
            ->pluck($column)
            ->unique()
            ->values();
    }

    /**
     * Chỉ xác nhận lại đơn khi mọi ghế/vé vẫn còn trống: không ai khác đang giữ, không thuộc
     * đơn khác và khu bán theo số lượng còn đủ sức chứa.
     *
     * @throws SeatHoldException
     */
    private function assertInventoryFree(Booking $booking, Collection $items, Collection $seats, Collection $tickets, Collection $claimedSeatIds, Collection $claimedTicketIds): void
    {
        $prefix = "Không thể xác nhận lại đơn [{$booking->booking_code}]";

        $missing = $items->contains(fn (BookingItem $item) => ($item->showtime_seat_id === null && $item->zone_ticket_id === null)
            || ($item->showtime_seat_id !== null && ! $seats->contains('id', $item->showtime_seat_id))
            || ($item->zone_ticket_id !== null && ! $tickets->contains('id', $item->zone_ticket_id)));
        if ($missing) {
            throw new SeatHoldException("{$prefix}: một số ghế/vé của đơn không còn tồn tại.");
        }

        $takenSeats = $seats->filter(fn (ShowtimeSeat $seat) => $claimedSeatIds->contains($seat->id)
            || ! ($seat->status === 'booked' || $seat->isHoldable()));
        $takenTickets = $tickets->filter(fn (ZoneTicket $ticket) => $claimedTicketIds->contains($ticket->id)
            || $ticket->isActiveHold());

        if ($takenSeats->isNotEmpty() || $takenTickets->isNotEmpty()) {
            $codes = $takenSeats->map(fn (ShowtimeSeat $seat) => $seat->seat?->full_code)
                ->merge($takenTickets->map(fn (ZoneTicket $ticket) => $ticket->zone?->name))
                ->filter()->unique()->implode(', ');

            throw new SeatHoldException("{$prefix}: ghế/vé {$codes} đã được người khác giữ hoặc mua.");
        }

        // Khu bán theo số lượng: sức chứa còn lại phải đủ cho toàn bộ vé của đơn.
        foreach ($tickets->groupBy(fn (ZoneTicket $ticket) => $ticket->showtime_id.'-'.$ticket->zone_id) as $group) {
            $first = $group->first();
            $zone = RoomZone::whereKey($first->zone_id)->lockForUpdate()->first();
            if (! $zone) {
                throw new SeatHoldException("{$prefix}: khu vé không còn tồn tại.");
            }

            $occupiedByOthers = ZoneTicket::where('showtime_id', $first->showtime_id)
                ->where('zone_id', $zone->id)
                ->whereNotIn('id', $group->pluck('id'))
                ->where(fn ($query) => $query->where('status', 'booked')
                    ->orWhere(fn ($held) => $held->where('status', 'held')->where('held_until', '>', now())))
                ->count();

            if ($occupiedByOthers + $group->count() > $zone->capacity) {
                throw new SeatHoldException("{$prefix}: khu {$zone->name} không còn đủ vé.");
            }
        }
    }
}

