<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Services\WeatherService;
use App\Support\MapEmbed;
use App\Support\SeatMap;
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

        $comments = $event->comments()
            ->with('user')
            ->latest()
            ->take(30)
            ->get()
            ->map(fn ($comment) => $comment->toPublicArray($request->user()))
            ->all();

        $nextShowtime = $showtimes->first();
        $venue = $nextShowtime?->room;

        return view('events.show', [
            'event' => $event,
            'movie' => $event,
            // View đang dùng !empty($showtimes) và $showtimes[0] nên truyền array, không truyền Collection.
            'showtimes' => $showtimes->all(),
            'comments' => $comments,
            'mapUrl' => $venue ? MapEmbed::url($venue) : null,
            'weather' => $venue ? $weatherService->forecastFor($venue, $nextShowtime->start_time) : null,
            'weatherShowtime' => $nextShowtime,
            'zoneCards' => $showtimes->isEmpty() ? [] : SeatMap::zoneCards($showtimes->first()),
        ]);
    }
}
