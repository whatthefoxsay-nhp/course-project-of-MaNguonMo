<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'movie_id',
        'content',
        'rating',
    ];

    protected $casts = [
        'rating' => 'integer',
        'admin_replied_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function movie(): BelongsTo
    {
        return $this->event();
    }

    /** Dữ liệu gửi cho trang chi tiết / API bình luận (nội dung luôn render bằng x-text). */
    public function toPublicArray(?User $viewer): array
    {
        return [
            'id' => $this->id,
            'user' => $this->user?->name ?? 'Người dùng',
            'avatar_url' => $this->user?->avatar_url,
            'time' => $this->created_at?->locale('vi')->diffForHumans() ?? 'Vừa xong',
            'text' => $this->content,
            'rating' => $this->rating,
            'admin_reply' => $this->admin_reply,
            'admin_reply_time' => $this->admin_replied_at?->locale('vi')->diffForHumans(),
            'can_delete' => $viewer !== null && $viewer->can('delete', $this),
        ];
    }
}
