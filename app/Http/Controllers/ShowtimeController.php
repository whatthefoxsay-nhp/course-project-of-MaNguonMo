<?php

namespace App\Http\Controllers;

use App\Models\Showtime;
use App\Support\SeatMapPresenter;
use App\Support\TicketTiers;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function seats(Showtime $showtime): View
    {
        $showtime->load(['room', 'event.category']);
        $event = $showtime->event;

        abort_unless($event->status === 'published', 404);

        return view('showtimes.seats', [
            'showtime' => $showtime,
            'movie' => $event,
            'seats' => $event->is_seated ? SeatMapPresenter::forShowtime($showtime) : [],
            'ticketTiers' => TicketTiers::for($showtime->base_price, $event->is_seated),
        ]);
    }
}
