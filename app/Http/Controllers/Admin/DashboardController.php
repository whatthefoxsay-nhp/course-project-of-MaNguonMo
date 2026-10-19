<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\SalesReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(SalesReportService $sales): View
    {
        // 1. KPI Statistics
        $totalRevenue = Booking::where('status', 'confirmed')->sum('total_price');
        $totalBookings = Booking::count();
        $confirmedBookings = Booking::where('status', 'confirmed')->count();
        $activeEventsCount = Event::where('status', 'published')->count();
        $activeShowtimesCount = Showtime::where('start_time', '>=', now()->startOfDay())->count();

        // Calculate overall seat occupancy
        $totalShowtimeSeats = ShowtimeSeat::count();
        $bookedShowtimeSeats = ShowtimeSeat::where('status', 'booked')->count();
        $occupancyRate = $totalShowtimeSeats > 0 ? round(($bookedShowtimeSeats / $totalShowtimeSeats) * 100, 1) : 0;

        // 2. Room Occupancy Rates
        $roomOccupancy = $sales->seatOccupancyBy('room_id');
        $rooms = Room::query()->get()->map(function ($room) use ($roomOccupancy) {
            $totalShowtimeSeatsCount = $roomOccupancy[$room->id]['total'] ?? 0;
            $bookedSeatsCount = $roomOccupancy[$room->id]['booked'] ?? 0;

            $rate = $totalShowtimeSeatsCount > 0 ? round(($bookedSeatsCount / $totalShowtimeSeatsCount) * 100, 1) : 0;

            return [
                'name' => $room->name,
                'capacity' => $room->capacity,
                'booked' => $bookedSeatsCount,
                'total' => $totalShowtimeSeatsCount ?: $room->capacity,
                'rate' => $rate,
            ];
        });

        // 3. Category Revenue Distribution (vé chọn ghế + vé theo khu của đơn đã xác nhận)
        $categories = $sales->categoriesRevenue();

        // 4. Top Performing Events
        $eventOccupancy = $sales->seatOccupancyBy('event_id');
        $topEvents = Event::with('category')->get()->map(function ($event) use ($eventOccupancy) {
            $totalEventSeats = $eventOccupancy[$event->id]['total'] ?? 0;
            $bookedEventSeats = $eventOccupancy[$event->id]['booked'] ?? 0;

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
        $recentBookings = Booking::with(['user', 'items.showtimeSeat.showtime.movie', 'items.showtimeSeat.seat', 'items.zoneTicket.zone', 'items.zoneTicket.showtime.movie'])
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
