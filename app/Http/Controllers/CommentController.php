<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public const PER_PAGE = 10;

    /**
     * Danh sách bình luận (mới nhất trước), phân trang JSON theo con trỏ (id giảm dần)
     * để việc thêm/xóa bình luận trong lúc xem không làm lệch hay trùng trang sau.
     */
    public function index(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->status === 'published', 404);

        $cursor = $request->query('cursor');

        return response()->json(self::page($event, $request->user(), is_string($cursor) ? $cursor : ''));
    }

    /**
     * Một trang bình luận; $cursor rỗng = trang đầu (trang chi tiết sự kiện dùng).
     *
     * @return array{data: list<array>, total: int, next_page_url: ?string}
     */
    public static function page(Event $event, ?User $viewer, string $cursor = ''): array
    {
        $page = $event->comments()
            ->with('user')
            ->orderByDesc('id')
            ->cursorPaginate(self::PER_PAGE, ['*'], 'cursor', $cursor)
            ->withPath(route('comments.index', $event));

        return [
            'data' => $page->getCollection()
                ->map(fn (Comment $comment) => $comment->toPublicArray($viewer))
                ->values()
                ->all(),
            'total' => $event->comments()->count(),
            'next_page_url' => $page->nextPageUrl(),
        ];
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
