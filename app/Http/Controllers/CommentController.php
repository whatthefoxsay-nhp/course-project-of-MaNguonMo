<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Event $event): JsonResponse
    {
        abort_unless($event->status === 'published', 404);

        $comment = $event->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'),
        ]);

        return response()->json(['comment' => $comment->load('user')->toPublicArray($request->user())], 201);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json(['message' => 'Đã xóa bình luận.']);
    }
}
