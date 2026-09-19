<?php

namespace App\Http\Controllers;

use App\Support\DemoCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q'));
        $categoryId = $request->query('category');

        $events = collect(DemoCatalog::events())
            ->when($query !== '', fn ($items) => $items->filter(
                fn ($event) => str_contains(mb_strtolower($event->title), mb_strtolower($query))
            ))
            ->when($categoryId, fn ($items) => $items->filter(
                fn ($event) => (string) $event->category->id === (string) $categoryId
            ))
            ->values();

        $viewName = view()->exists('events.index') ? 'events.index' : 'movies.index';

        return view($viewName, [
            'events' => $events,
            'movies' => $events,
            'categories' => DemoCatalog::categories(),
            'query' => $query,
            'categoryId' => $categoryId,
        ]);
    }

    public function show(string $slug): View
    {
        $event = DemoCatalog::eventBySlug($slug);

        abort_if($event === null, 404);

        $isSeatedConcert = $event->is_seated_concert ?? true;
        $viewName = view()->exists('events.show') ? 'events.show' : 'movies.show';

        return view($viewName, [
            'event' => $event,
            'movie' => $event,
            'showtimes' => DemoCatalog::showtimesForEvent($event->id),
            'ticketTiers' => DemoCatalog::ticketTiers($event->base_price ?? 180000, $isSeatedConcert),
        ]);
    }
}
