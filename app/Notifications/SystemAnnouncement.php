<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemAnnouncement extends Notification
{
    use Queueable;

    public function __construct(public readonly string $title, public readonly string $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => '/',
            'kind' => 'announcement',
        ];
    }
}
