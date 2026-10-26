<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Gợi ý tìm kiếm theo từng ký tự gõ (ô tìm kiếm toàn trang). */
class SearchSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = trim((string) ($validated['q'] ?? ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = Event::published()
            ->with('category')
            ->withMin('showtimes', 'base_price')
            ->where('title', 'like', "%{$query}%")
            ->orderBy('title')
            ->take(6)
            ->get()
            ->map(fn (Event $event) => [
                'title' => $event->title,
                'url' => route('events.show', $event->slug),
                'category' => $event->category?->name,
                'poster_url' => $event->poster_url,
                'price_from' => $event->base_price === null ? null : number_format($event->base_price, 0, ',', '.').'₫',
            ]);

        return response()->json(['results' => $results]);
    }
}
