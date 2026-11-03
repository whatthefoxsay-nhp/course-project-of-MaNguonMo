<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Support\TicketTiers;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function history(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->with([
                'discount',
                'items.showtimeSeat.seat.zone',
                'items.showtimeSeat.showtime.room',
                'items.showtimeSeat.showtime.event.category',
            ])
            ->latest()
            ->get()
            ->map(function (Booking $booking) {
                // Mỗi đơn luôn thuộc 1 suất (SeatHoldService chỉ cho giữ ghế của 1 suất tại 1 thời điểm).
                $showtime = $booking->items->first()?->showtimeSeat?->showtime;

                return (object) [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'movie' => $showtime?->event,
                    'showtime' => $showtime,
                    'seats' => $booking->items->map(fn ($item) => $this->itemLabel($item))->filter()->all(),
                    'total_price' => $booking->total_price,
                    'discount_amount' => $booking->discount_amount,
                    'discount_code' => $booking->discount?->code,
                    'status' => $booking->status,
                    'status_label' => $booking->statusLabel(),
                    'buyer_name' => $booking->buyer_name,
                    'buyer_phone' => $booking->buyer_phone,
                    'buyer_email' => $booking->buyer_email,
                    'created_at' => $booking->created_at,
                ];
            })
            ->filter(fn (object $booking) => $booking->showtime !== null)
            ->values()
            ->all();

        return view('bookings.history', ['bookings' => $bookings]);
    }

    /** Nhãn thật của vé: "A5 (VIP)" cho ghế, "Khu đứng GA — vé số 12" cho khu bán theo số lượng. */
    private function itemLabel(BookingItem $item): string
    {
        $seat = $item->showtimeSeat?->seat;
        if (! $seat) {
            return '';
        }

        if ($seat->zone) {
            return $seat->zone->isStanding()
                ? "{$seat->zone->name} — vé số {$seat->seat_number}"
                : "{$seat->full_code} ({$seat->zone->name})";
        }

        // Ghế cũ chưa thuộc khu nào: nhãn theo loại ghế.
        return $seat->full_code.' ('.TicketTiers::seatName((string) $seat->type).')';
    }
}
