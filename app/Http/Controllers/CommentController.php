<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Event $event): JsonResponse
    {
        abort_unless($event->status === 'published', 404);

        $comment = $event->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'),
            'rating' => $request->validated('rating'),
        ]);

        return response()->json([
            'comment' => $comment->load('user')->toPublicArray($request->user()),
            'rating_average' => round((float) $event->comments()->whereNotNull('rating')->avg('rating'), 1),
            'rating_count' => $event->comments()->whereNotNull('rating')->count(),
        ], 201);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $event = $comment->event;
        $comment->delete();

        return $request->expectsJson()
            ? response()->json([
                'message' => 'Đã xóa bình luận.',
                'rating_average' => round((float) $event->comments()->whereNotNull('rating')->avg('rating'), 1),
                'rating_count' => $event->comments()->whereNotNull('rating')->count(),
            ])
            : back()->with('success', 'Đã xóa bình luận.');
    }
}
