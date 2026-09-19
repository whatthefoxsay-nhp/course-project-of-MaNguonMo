<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        // 1. KPI Statistics
        $totalRevenue = Booking::where('status', 'confirmed')->sum('total_price');
        $totalBookings = Booking::count();
        $confirmedBookings = Booking::where('status', 'confirmed')->count();
        $activeEventsCount = Movie::where('status', 'published')->count();
        $activeShowtimesCount = Showtime::where('start_time', '>=', now()->startOfDay())->count();

        // Calculate overall seat occupancy
        $totalShowtimeSeats = ShowtimeSeat::count();
        $bookedShowtimeSeats = ShowtimeSeat::where('status', 'booked')->count();
        $occupancyRate = $totalShowtimeSeats > 0 ? round(($bookedShowtimeSeats / $totalShowtimeSeats) * 100, 1) : 0;

        // 2. Room Occupancy Rates
        $rooms = Room::with(['seats', 'showtimes.showtimeSeats'])->get()->map(function ($room) {
            $totalSeats = $room->seats->count();
            $roomShowtimes = $room->showtimes;
            $totalShowtimeSeatsCount = 0;
            $bookedSeatsCount = 0;

            foreach ($roomShowtimes as $st) {
                $totalShowtimeSeatsCount += $st->showtimeSeats->count();
                $bookedSeatsCount += $st->showtimeSeats->where('status', 'booked')->count();
            }

            $rate = $totalShowtimeSeatsCount > 0 ? round(($bookedSeatsCount / $totalShowtimeSeatsCount) * 100, 1) : 0;

            return [
                'name' => $room->name,
                'capacity' => $room->capacity,
                'booked' => $bookedSeatsCount,
                'total' => $totalShowtimeSeatsCount ?: $room->capacity,
                'rate' => $rate,
            ];
        });

        // 3. Category Revenue Distribution
        $categories = Category::with(['movies.showtimes.showtimeSeats.bookingItems.booking'])->get()->map(function ($cat) {
            $catRevenue = 0;
            $ticketCount = 0;
            foreach ($cat->movies as $movie) {
                foreach ($movie->showtimes as $st) {
                    foreach ($st->showtimeSeats as $stSeat) {
                        if ($stSeat->status === 'booked' && $stSeat->bookingItems->isNotEmpty()) {
                            foreach ($stSeat->bookingItems as $item) {
                                if ($item->booking && $item->booking->status === 'confirmed') {
                                    $catRevenue += $item->price;
                                    $ticketCount++;
                                }
                            }
                        }
                    }
                }
            }
            return [
                'name' => $cat->name,
                'revenue' => $catRevenue,
                'tickets' => $ticketCount,
            ];
        });

        // 4. Top Performing Events
        $topEvents = Movie::with(['category', 'showtimes.showtimeSeats'])->get()->map(function ($event) {
            $totalEventSeats = 0;
            $bookedEventSeats = 0;
            foreach ($event->showtimes as $st) {
                $totalEventSeats += $st->showtimeSeats->count();
                $bookedEventSeats += $st->showtimeSeats->where('status', 'booked')->count();
            }

            $fillRate = $totalEventSeats > 0 ? round(($bookedEventSeats / $totalEventSeats) * 100, 1) : 0;

            return [
                'id' => $event->id,
                'title' => $event->title,
                'category' => $event->category?->name ?? 'Sự kiện',
                'booked_seats' => $bookedEventSeats,
                'total_seats' => $totalEventSeats,
                'fill_rate' => $fillRate,
                'poster' => $event->poster_path,
            ];
        })->sortByDesc('booked_seats')->values()->take(4);

        // 5. Recent Bookings Log
        $recentBookings = Booking::with(['user', 'items.showtimeSeat.showtime.movie', 'items.showtimeSeat.seat'])
            ->latest()
            ->take(6)
            ->get();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'totalBookings',
            'confirmedBookings',
            'activeEventsCount',
            'activeShowtimesCount',
            'occupancyRate',
            'rooms',
            'categories',
            'topEvents',
            'recentBookings'
        ));
    }
}
