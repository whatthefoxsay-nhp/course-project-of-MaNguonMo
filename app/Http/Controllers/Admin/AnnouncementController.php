<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SystemAnnouncement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = \DB::table('notifications')
            ->where('type', SystemAnnouncement::class)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->unique(function ($notification) {
                $data = json_decode($notification->data, true) ?? [];
                return ($data['title'] ?? '').'|'.($data['message'] ?? '').'|'.substr($notification->created_at, 0, 16);
            })->values();

        return view('admin.notifications.index', compact('announcements'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'title' => is_string($request->input('title')) ? trim($request->input('title')) : $request->input('title'),
            'message' => is_string($request->input('message')) ? trim($request->input('message')) : $request->input('message'),
        ]);

        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        $sent = 0;
        User::query()->where('is_active', true)->whereHas('roles', fn ($query) => $query->where('name', 'user'))
            ->orderBy('id')->chunkById(200, function ($users) use ($data, &$sent) {
                Notification::send($users, new SystemAnnouncement($data['title'], $data['message']));
                $sent += $users->count();
            });

        return back()->with('success', "Đã gửi thông báo đến {$sent} tài khoản khách hàng đang hoạt động.");
    }
}
