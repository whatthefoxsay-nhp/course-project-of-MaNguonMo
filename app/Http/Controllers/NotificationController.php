<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function open(DatabaseNotification $notification): RedirectResponse
    {
        abort_unless($notification->notifiable_id === auth()->id()
            && $notification->notifiable_type === auth()->user()->getMorphClass(), 404);

        $notification->markAsRead();
        $path = $notification->data['url'] ?? '/';
        abort_unless(is_string($path) && str_starts_with($path, '/') && ! str_starts_with($path, '//'), 400);

        return redirect($path);
    }
}
