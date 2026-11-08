<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Notifications\CommentReplyNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Comment::with(['user:id,name', 'event:id,title'])
            ->when($validated['rating'] ?? null, fn ($query, $rating) => $query->where('rating', $rating))
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('content', 'like', "%{$search}%")
                        ->orWhereHas('event', fn ($event) => $event->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            });

        $reviews = $query->latest()->paginate(15)->withQueryString();
        $stats = [
            'total' => Comment::count(),
            'rated' => Comment::whereNotNull('rating')->count(),
            'average' => round((float) Comment::whereNotNull('rating')->avg('rating'), 1),
        ];

        return view('admin.reviews.index', compact('reviews', 'stats'));
    }

    public function reply(Request $request, Comment $comment): RedirectResponse
    {
        $validated = $request->validate([
            'reply' => ['required', 'string', 'min:2', 'max:1000'],
        ]);

        DB::transaction(function () use ($comment, $validated) {
            $comment->forceFill([
                'admin_reply' => trim($validated['reply']),
                'admin_replied_at' => now(),
            ])->save();

            $comment->loadMissing('event', 'user')->user?->notify(new CommentReplyNotification($comment));
        });

        return back()->with('success', 'Đã gửi phản hồi cho khách và thông báo trong chuông.');
    }
}
