<?php

namespace App\Support;

final class GoogleOAuthConfiguration
{
    public static function isConfigured(): bool
    {
        $clientId = trim((string) config('services.google.client_id'));
        $clientSecret = trim((string) config('services.google.client_secret'));
        $redirect = trim((string) config('services.google.redirect'));
        $scheme = strtolower((string) parse_url($redirect, PHP_URL_SCHEME));

        if ($clientId === '' || $clientSecret === '' || filter_var($redirect, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return $scheme === 'https' || in_array(config('app.env'), ['local', 'testing'], true);
    }
}
