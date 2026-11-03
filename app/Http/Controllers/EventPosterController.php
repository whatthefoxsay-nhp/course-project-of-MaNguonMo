<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\GoogleDriveService;
use Symfony\Component\HttpFoundation\Response;

class EventPosterController extends Controller
{
    public function __invoke(Event $event, GoogleDriveService $drive): Response
    {
        $fileId = $drive->extractFileId((string) $event->getRawOriginal('poster_path'));
        abort_if($fileId === null, 404);

        $image = $drive->downloadImage($fileId);
        abort_if($image === null, 404);

        return response($image['content'], Response::HTTP_OK, [
            'Content-Type' => $image['mime_type'],
            'Cache-Control' => 'public, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
