<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public const PER_PAGE = 10;

    /** Danh sách bình luận (mới nhất trước), phân trang JSON. */
    public function index(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->status === 'published', 404);

        $page = $event->comments()
            ->with('user')
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return response()->json([
            'data' => $page->getCollection()
                ->map(fn (Comment $comment) => $comment->toPublicArray($request->user()))
                ->values(),
            'total' => $page->total(),
            'current_page' => $page->currentPage(),
            'next_page_url' => $page->nextPageUrl(),
        ]);
    }

    public function store(StoreCommentRequest $request, Event $event): JsonResponse
    {
        abort_unless($event->status === 'published', 404);

        $comment = $event->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'),
        ]);

        return response()->json([
            'comment' => $comment->load('user')->toPublicArray($request->user()),
        ], 201);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json(['message' => 'Đã xóa bình luận.']);
    }
}
