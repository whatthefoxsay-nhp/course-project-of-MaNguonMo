<?php

namespace App\Providers;

use App\Mail\Transport\GmailApiTransport;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.custom');

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
