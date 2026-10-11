<?php

namespace App\Http\Controllers;

use App\Models\Showtime;
use App\Support\SeatMapPresenter;
use App\Support\TicketTiers;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function seats(Showtime $showtime): View
    {
        $showtime->load(['room', 'event.category']);
        $event = $showtime->event;

        abort_unless($event->status === 'published', 404);

        $useZoneMap = $showtime->room->isZoned();

        $allShowtimes = $event->showtimes()
            ->with('room')
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->get();

        return view('showtimes.seats', [
            'showtime' => $showtime,
            'allShowtimes' => $allShowtimes,
            'movie' => $event,
            'seats' => ($event->is_seated || $useZoneMap) ? SeatMapPresenter::forShowtime($showtime) : [],
            'venueZones' => $useZoneMap ? SeatMapPresenter::zonesForShowtime($showtime) : [],
            'useZoneMap' => $useZoneMap,
            'ticketTiers' => TicketTiers::for($showtime->base_price, $event->is_seated),
        ]);
    }

    public function seatStatus(Showtime $showtime): JsonResponse
    {
        $unavailable = $showtime->showtimeSeats()
            ->where(fn ($query) => $query->where('status', 'booked')
                ->orWhere(fn ($held) => $held->where('status', 'held')->where('held_until', '>', now())))
            ->pluck('id');

        return response()->json([
            'unavailable' => $unavailable,
            'zones_available' => collect(SeatMapPresenter::zonesForShowtime($showtime))->pluck('available', 'id'),
        ]);
    }
}
