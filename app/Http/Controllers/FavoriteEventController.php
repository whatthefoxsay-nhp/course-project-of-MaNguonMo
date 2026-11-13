<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteEventController extends Controller
{
    /**
     * Hiển thị trang danh sách sự kiện yêu thích của người dùng.
     */
    public function index(Request $request): View
    {
        $favorites = $request->user()
            ->favoriteEvents()
            ->with(['category', 'showtimes'])
            ->latest('event_user_favorites.created_at')
            ->paginate(12);

        return view('profile.favorites', [
            'favorites' => $favorites,
        ]);
    }

    /**
     * Bật / Tắt lưu sự kiện vào danh sách yêu thích (AJAX / Web).
     */
    public function toggle(Request $request, string $eventIdentifier): JsonResponse|RedirectResponse
    {
        $event = is_numeric($eventIdentifier)
            ? Event::findOrFail($eventIdentifier)
            : Event::where('slug', $eventIdentifier)->firstOrFail();

        // Chặn lưu sự kiện nháp
        if ($event->status === 'draft') {
            abort(422, 'Không thể lưu sự kiện đang ở trạng thái bản nháp.');
        }

        $user = $request->user();
        $isFavorited = $user->favoriteEvents()->where('events.id', $event->id)->exists();

        if ($isFavorited) {
            $user->favoriteEvents()->detach($event->id);
            $favorited = false;
            $message = 'Đã bỏ lưu sự kiện khỏi danh sách yêu thích.';
        } else {
            // syncWithoutDetaching đảm bảo tính idempotent / chống race-condition trùng lặp bản ghi
            $user->favoriteEvents()->syncWithoutDetaching([$event->id]);
            $favorited = true;
            $message = 'Đã thêm sự kiện vào danh sách yêu thích thành công!';
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'favorited' => $favorited,
                'message' => $message,
                'event_id' => $event->id,
                'event_title' => $event->title,
                'favorites_count' => $user->favoriteEvents()->count(),
            ], 200);
        }

        return back()->with('success', $message);
    }

    /**
     * Xóa trực tiếp một sự kiện khỏi danh sách yêu thích.
     */
    public function destroy(Request $request, string $eventIdentifier): JsonResponse|RedirectResponse
    {
        $event = is_numeric($eventIdentifier)
            ? Event::findOrFail($eventIdentifier)
            : Event::where('slug', $eventIdentifier)->firstOrFail();

        $user = $request->user();
        $user->favoriteEvents()->detach($event->id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'favorited' => false,
                'message' => 'Đã xóa sự kiện khỏi danh sách yêu thích.',
                'event_id' => $event->id,
                'favorites_count' => $user->favoriteEvents()->count(),
            ], 200);
        }

        return back()->with('success', 'Đã xóa sự kiện khỏi danh sách yêu thích.');
    }
}

