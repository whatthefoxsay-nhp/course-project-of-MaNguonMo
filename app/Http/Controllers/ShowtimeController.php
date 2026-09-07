<?php

namespace App\Http\Controllers;

use App\Support\DemoCatalog;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function seats(int $showtime): View
    {
        $showtimeData = DemoCatalog::showtimeById($showtime);

        abort_if($showtimeData === null, 404);

        $movie = collect(DemoCatalog::movies())->firstWhere('id', $showtimeData->movie_id);

        return view('showtimes.seats', [
            'showtime' => $showtimeData,
            'movie' => $movie,
            'seats' => DemoCatalog::seatsForShowtime($showtime),
        ]);
    }
}
