<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Movie;
use App\Models\ShowtimeSeat;
use App\Models\ZoneTicket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $totalRevenue = Booking::where('status', 'confirmed')->sum('total_price');
        $totalTicketsSold = ShowtimeSeat::where('status', 'booked')->count() + ZoneTicket::where('status', 'booked')->count();
        $totalBookings = Booking::count();

        // Revenue by Event
        $zoneItems = BookingItem::with(['booking', 'zoneTicket.showtime'])->whereNotNull('zone_ticket_id')->get();
        $eventsRevenue = Movie::with(['category', 'showtimes.showtimeSeats.bookingItems.booking'])
            ->get()
            ->map(function ($event) use ($zoneItems) {
                $rev = 0;
                $tickets = 0;
                foreach ($event->showtimes as $st) {
                    foreach ($st->showtimeSeats as $stSeat) {
                        if ($stSeat->status === 'booked') {
                            foreach ($stSeat->bookingItems as $item) {
                                if ($item->booking && $item->booking->status === 'confirmed') {
                                    $rev += $item->price;
                                    $tickets++;
                                }
                            }
                        }
                    }
                }
                $zoneRevenue = $zoneItems->filter(fn ($item) => $item->booking?->status === 'confirmed' && $item->zoneTicket?->showtime?->event_id === $event->id);
                $rev += $zoneRevenue->sum('price');
                $tickets += $zoneRevenue->count();
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'category' => $event->category?->name ?? 'Sự kiện',
                    'revenue' => $rev,
                    'tickets' => $tickets,
                ];
            })->sortByDesc('revenue')->values();

        // Revenue by Category
        $categoriesRevenue = Category::with(['movies.showtimes.showtimeSeats.bookingItems.booking'])
            ->get()
            ->map(function ($cat) use ($zoneItems) {
                $rev = 0;
                $tickets = 0;
                foreach ($cat->movies as $m) {
                    foreach ($m->showtimes as $st) {
                        foreach ($st->showtimeSeats as $stSeat) {
                            if ($stSeat->status === 'booked') {
                                foreach ($stSeat->bookingItems as $item) {
                                    if ($item->booking && $item->booking->status === 'confirmed') {
                                        $rev += $item->price;
                                        $tickets++;
                                    }
                                }
                            }
                        }
                    }
                }
                $eventIds = $cat->movies->pluck('id');
                $zoneRevenue = $zoneItems->filter(fn ($item) => $item->booking?->status === 'confirmed' && $eventIds->contains($item->zoneTicket?->showtime?->event_id));
                $rev += $zoneRevenue->sum('price');
                $tickets += $zoneRevenue->count();
                return [
                    'name' => $cat->name,
                    'revenue' => $rev,
                    'tickets' => $tickets,
                ];
            });

        return view('admin.reports.index', compact('totalRevenue', 'totalTicketsSold', 'totalBookings', 'eventsRevenue', 'categoriesRevenue'));
    }

    public function pdf(Request $request): View
    {
        $totalRevenue = Booking::where('status', 'confirmed')->sum('total_price');
        $totalTicketsSold = ShowtimeSeat::where('status', 'booked')->count() + ZoneTicket::where('status', 'booked')->count();
        $totalBookings = Booking::count();

        $zoneItems = BookingItem::with(['booking', 'zoneTicket.showtime'])->whereNotNull('zone_ticket_id')->get();
        $eventsRevenue = Movie::with(['category', 'showtimes.showtimeSeats.bookingItems.booking'])
            ->get()
            ->map(function ($event) use ($zoneItems) {
                $rev = 0;
                $tickets = 0;
                foreach ($event->showtimes as $st) {
                    foreach ($st->showtimeSeats as $stSeat) {
                        if ($stSeat->status === 'booked') {
                            foreach ($stSeat->bookingItems as $item) {
                                if ($item->booking && $item->booking->status === 'confirmed') {
                                    $rev += $item->price;
                                    $tickets++;
                                }
                            }
                        }
                    }
                }
                $zoneRevenue = $zoneItems->filter(fn ($item) => $item->booking?->status === 'confirmed' && $item->zoneTicket?->showtime?->event_id === $event->id);
                $rev += $zoneRevenue->sum('price');
                $tickets += $zoneRevenue->count();
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'category' => $event->category?->name ?? 'Sự kiện',
                    'revenue' => $rev,
                    'tickets' => $tickets,
                ];
            })->sortByDesc('revenue')->values();

        $categoriesRevenue = Category::with(['movies.showtimes.showtimeSeats.bookingItems.booking'])
            ->get()
            ->map(function ($cat) use ($zoneItems) {
                $rev = 0;
                $tickets = 0;
                foreach ($cat->movies as $m) {
                    foreach ($m->showtimes as $st) {
                        foreach ($st->showtimeSeats as $stSeat) {
                            if ($stSeat->status === 'booked') {
                                foreach ($stSeat->bookingItems as $item) {
                                    if ($item->booking && $item->booking->status === 'confirmed') {
                                        $rev += $item->price;
                                        $tickets++;
                                    }
                                }
                            }
                        }
                    }
                }
                $eventIds = $cat->movies->pluck('id');
                $zoneRevenue = $zoneItems->filter(fn ($item) => $item->booking?->status === 'confirmed' && $eventIds->contains($item->zoneTicket?->showtime?->event_id));
                $rev += $zoneRevenue->sum('price');
                $tickets += $zoneRevenue->count();
                return [
                    'name' => $cat->name,
                    'revenue' => $rev,
                    'tickets' => $tickets,
                ];
            });

        return view('admin.reports.pdf', compact('totalRevenue', 'totalTicketsSold', 'totalBookings', 'eventsRevenue', 'categoriesRevenue'));
    }
}
