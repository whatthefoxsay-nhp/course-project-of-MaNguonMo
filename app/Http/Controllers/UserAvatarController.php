<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleDriveService;
use Symfony\Component\HttpFoundation\Response;

class UserAvatarController extends Controller
{
    public function __invoke(User $user, GoogleDriveService $drive): Response
    {
        $fileId = $drive->extractFileId((string) $user->getRawOriginal('avatar'));
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
