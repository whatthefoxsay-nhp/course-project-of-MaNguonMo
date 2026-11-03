<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestionController extends Controller
{
    public const LIMIT = 6;

    public const MIN_LENGTH = 2;

    /**
     * Gợi ý tìm kiếm cho modal tìm kiếm ở header.
     * q >= 2 ký tự: sự kiện đã xuất bản có tiêu đề khớp (tối đa 6).
     * q ngắn hơn: results rỗng, kèm `popular` (sự kiện nổi bật) để hiện khi chưa gõ.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = trim((string) ($validated['q'] ?? ''));

        if (mb_strlen($query) < self::MIN_LENGTH) {
            return response()->json([
                'results' => [],
                'popular' => $this->present(
                    Event::published()->latest('release_date')->take(self::LIMIT)
                ),
            ]);
        }

        // Escape ký tự đại diện của LIKE để "%" hay "_" được tìm như chữ thường.
        // Dùng "!" làm ký tự escape vì MySQL và SQLite hiểu giống nhau.
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query).'%';

        return response()->json([
            'results' => $this->present(
                Event::published()
                    ->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                    ->orderBy('title')
                    ->take(self::LIMIT)
            ),
        ]);
    }

    private function present($builder): array
    {
        return $builder
            ->with('category')
            ->withMin('showtimes', 'base_price')
            ->get()
            ->map(fn (Event $event) => [
                'title' => $event->title,
                'url' => route('events.show', $event->slug),
                'category' => $event->category?->name,
                'poster_url' => $event->poster_url,
                'price_from' => $event->base_price === null
                    ? null
                    : number_format($event->base_price, 0, ',', '.').'₫',
            ])
            ->all();
    }
}
