<?php

namespace App\Notifications;

use App\Models\Showtime;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UpcomingShowtimeNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Showtime $showtime)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Suất diễn sắp bắt đầu',
            'message' => ($this->showtime->event?->title ?? 'Sự kiện').' · '.$this->showtime->start_time->format('H:i d/m/Y').' · '.($this->showtime->room?->name ?? 'Địa điểm xem vé').'. Mở mã QR vé điện tử để làm thủ tục check-in.',
            'url' => '/bookings',
            'kind' => 'showtime_reminder',
            'showtime_id' => $this->showtime->id,
        ];
    }
}
