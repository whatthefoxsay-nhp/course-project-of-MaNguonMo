<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EventPosterController extends Controller
{
    public function __invoke(Event $event, GoogleDriveService $drive): Response
    {
        $fileId = $drive->extractFileId((string) $event->getRawOriginal('poster_path'));
        if ($fileId === null) {
            return redirect("https://picsum.photos/seed/{$event->slug}/480/720");
        }

        $cacheKey = "event_poster_{$event->id}_{$fileId}";
        $image = Cache::remember($cacheKey, 86400, function () use ($drive, $fileId) {
            return $drive->downloadImage($fileId);
        });

        if ($image === null) {
            return redirect("https://picsum.photos/seed/{$event->slug}/480/720");
        }

        return response($image['content'], Response::HTTP_OK, [
            'Content-Type' => $image['mime_type'],
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
