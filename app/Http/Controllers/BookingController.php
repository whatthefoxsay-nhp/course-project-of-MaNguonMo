<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function history(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->with([
                'discount',
                'items.showtimeSeat.seat',
                'items.showtimeSeat.showtime.room',
                'items.showtimeSeat.showtime.event.category',
                'items.zoneTicket.zone',
                'items.zoneTicket.showtime.room',
                'items.zoneTicket.showtime.event.category',
            ])
            ->latest()
            ->get()
            ->map(function (Booking $booking) {
                // Mỗi đơn luôn thuộc 1 suất (SeatHoldService chỉ cho giữ ghế của 1 suất tại 1 thời điểm).
                $firstItem = $booking->items->first();
                $showtime = $firstItem?->showtimeSeat?->showtime ?? $firstItem?->zoneTicket?->showtime;

                return (object) [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'movie' => $showtime?->event,
                    'showtime' => $showtime,
                    'seats' => $booking->items->map(fn ($item) => $item->showtimeSeat?->seat?->full_code
                        ?? ($item->zoneTicket ? $item->zoneTicket->zone->name.' — vé số '.$item->zoneTicket->id : ''))->filter()->all(),
                    'total_price' => $booking->total_price,
                    'discount_amount' => $booking->discount_amount,
                    'discount_code' => $booking->discount?->code,
                    'status' => $booking->status,
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
}
