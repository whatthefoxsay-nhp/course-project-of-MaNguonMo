<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Services\WeatherService;
use App\Support\MapEmbed;
use App\Support\TicketTiers;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $categoryId = $validated['category'] ?? null;

        $events = Event::published()
            ->with('category')
            ->withMin('showtimes', 'base_price')
            ->when($query !== '', fn ($builder) => $builder->where(function ($where) use ($query) {
                $where->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            }))
            ->when($categoryId, fn ($builder) => $builder->where('category_id', $categoryId))
            ->latest('release_date')
            ->paginate(9)
            ->withQueryString();

        return view('events.index', [
            'events' => $events,
            'movies' => $events,
            'categories' => Category::orderBy('name')->get(),
            'query' => $query,
            'categoryId' => $categoryId,
        ]);
    }

    public function show(Request $request, string $slug, WeatherService $weatherService): View
    {
        $event = Event::published()
            ->with('category')
            ->withMin('showtimes', 'base_price')
            ->where('slug', $slug)
            ->firstOrFail();

        $showtimes = $event->showtimes()
            ->with('room')
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->get();

        // Bản đồ + thời tiết theo khán phòng của suất gần nhất.
        // Thời tiết chỉ cho địa điểm ngoài trời; không có dự báo thật thì view ẩn hẳn khối thời tiết.
        $nextShowtime = $showtimes->first();
        $venue = $nextShowtime?->room;

        // Trang đầu bình luận render sẵn; các trang sau tải AJAX qua route comments.index.
        $commentsPage = CommentController::page($event, $request->user());

        return view('events.show', [
            'comments' => $commentsPage['data'],
            'commentsTotal' => $commentsPage['total'],
            'commentsNextUrl' => $commentsPage['next_page_url'],
            'event' => $event,
            'movie' => $event,
            // View đang dùng !empty($showtimes) và $showtimes[0] nên truyền array, không truyền Collection.
            'showtimes' => $showtimes->all(),
            'ticketTiers' => TicketTiers::for($event->base_price ?? 180000, $event->is_seated),
            'venue' => $venue,
            'mapUrl' => $venue ? MapEmbed::url($venue) : null,
            'directionsUrl' => $venue ? MapEmbed::directionsUrl($venue) : null,
            'weather' => $venue?->isOutdoor() ? $weatherService->forecastFor($venue, $nextShowtime->start_time) : null,
            'weatherShowtime' => $nextShowtime,
        ]);
    }
}
