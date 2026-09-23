<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
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

    public function show(string $slug): View
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

        return view('events.show', [
            'event' => $event,
            'movie' => $event,
            // View đang dùng !empty($showtimes) và $showtimes[0] nên truyền array, không truyền Collection.
            'showtimes' => $showtimes->all(),
            'ticketTiers' => TicketTiers::for($event->base_price ?? 180000, $event->is_seated),
        ]);
    }
}
