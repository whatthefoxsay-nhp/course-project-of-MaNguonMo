<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UserAvatarController extends Controller
{
    public function __invoke(User $user, GoogleDriveService $drive): Response
    {
        $fileId = $drive->extractFileId((string) $user->getRawOriginal('avatar'));
        if ($fileId === null) {
            return redirect('https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=0D8ABC&color=fff');
        }

        $cacheKey = "user_avatar_{$user->id}_{$fileId}";
        $image = Cache::remember($cacheKey, 86400, function () use ($drive, $fileId) {
            return $drive->downloadImage($fileId);
        });

        if ($image === null) {
            return redirect('https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=0D8ABC&color=fff');
        }

        return response($image['content'], Response::HTTP_OK, [
            'Content-Type' => $image['mime_type'],
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
