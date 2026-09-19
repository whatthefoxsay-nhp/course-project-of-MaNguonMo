<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Showtime::with(['movie.category', 'room', 'showtimeSeats']);

        if ($request->filled('movie_id')) {
            $query->where('movie_id', $request->input('movie_id'));
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->input('room_id'));
        }

        $showtimes = $query->orderBy('start_time', 'asc')->paginate(10)->withQueryString();
        $events = Movie::orderBy('title')->get();
        $rooms = Room::orderBy('name')->get();

        return view('admin.showtimes.index', compact('showtimes', 'events', 'rooms'));
    }
}
