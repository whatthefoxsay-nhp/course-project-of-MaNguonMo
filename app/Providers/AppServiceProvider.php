<?php

namespace App\Providers;

use App\Mail\Transport\GmailApiTransport;
use App\Models\Booking;
use App\Notifications\UpcomingShowtimeNotification;
use App\Services\GoogleDriveService;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Avoid Laravel auto-resolving empty Google Client/Drive objects into this service.
        $this->app->bind(GoogleDriveService::class, static fn () => new GoogleDriveService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.custom');

        View::composer('layouts.site', function ($view) {
            $user = auth()->user();
            $upcomingBookings = $user
                ? Booking::query()->where('user_id', $user->id)->where('status', 'confirmed')
                    ->whereHas('items.showtimeSeat.showtime', fn ($query) => $query->whereBetween('start_time', [now(), now()->addHours(24)]))
                    ->with(['items.showtimeSeat.showtime.event', 'items.showtimeSeat.showtime.room'])
                    ->get()->flatMap(fn ($booking) => $booking->items)
                    ->map(fn ($item) => $item->showtimeSeat?->showtime)
                    ->filter(fn ($showtime) => $showtime && $showtime->start_time->between(now(), now()->addHours(24)))
                    ->unique('id')->take(5)->values()
                : collect();

            if ($user) {
                $knownReminderIds = $user->notifications()
                    ->where('type', UpcomingShowtimeNotification::class)
                    ->get()->map(fn ($notification) => (int) ($notification->data['showtime_id'] ?? 0))->all();

                foreach ($upcomingBookings as $showtime) {
                    if (! in_array($showtime->id, $knownReminderIds, true)) {
                        $user->notify(new UpcomingShowtimeNotification($showtime));
                    }
                }
            }

            $notifications = $user?->unreadNotifications()->latest()->limit(10)->get() ?? collect();
            $unreadNotificationCount = $user?->unreadNotifications()->count() ?? 0;
            $view->with(compact('notifications', 'unreadNotificationCount'));
        });

        Mail::extend('gmail_api', function (array $config) {
            $gmailConfig = config('services.gmail_api');
            $credentialsConfigured = (bool) $gmailConfig['enabled']
                && filled($gmailConfig['sender_email'])
                && filled($gmailConfig['client_id'])
                && filled($gmailConfig['client_secret'])
                && filled($gmailConfig['refresh_token']);

            if (! $credentialsConfigured) {
                return app('mail.manager')->createSymfonyTransport(config('mail.mailers.smtp'));
            }

            $client = new GoogleClient;
            $client->setClientId($gmailConfig['client_id']);
            $client->setClientSecret($gmailConfig['client_secret']);
            $client->setScopes([Gmail::GMAIL_SEND]);
            $token = $client->fetchAccessTokenWithRefreshToken($gmailConfig['refresh_token']);

            if (isset($token['error'])) {
                throw new \RuntimeException('Could not refresh Gmail API access token.');
            }

            return new GmailApiTransport($client, $gmailConfig['sender_email']);
        });
    }
}
