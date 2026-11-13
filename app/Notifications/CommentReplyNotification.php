<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentReplyNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Comment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'TicketBox đã phản hồi bình luận',
            'message' => 'Bạn có phản hồi mới cho bình luận về sự kiện '.($this->comment->event?->title ?? 'của bạn').': "'.$this->comment->admin_reply.'"',
            'url' => '/events/'.$this->comment->event?->slug,
            'event_id' => $this->comment->event_id,
            'event_slug' => $this->comment->event?->slug,
            'reply' => $this->comment->admin_reply,
            'kind' => 'comment_reply',
        ];
    }
}
