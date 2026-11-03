<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, GoogleDriveService $googleDrive): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->except('avatar'));
        $oldAvatar = null;

        if ($request->hasFile('avatar')) {
            if (! $googleDrive->isEnabled()) {
                throw ValidationException::withMessages([
                    'avatar' => 'Google Drive chưa được cấu hình; không thể lưu ảnh đại diện.',
                ]);
            }

            $newAvatar = $googleDrive->upload($request->file('avatar'), 'avatars');
            if ($newAvatar === null) {
                throw ValidationException::withMessages([
                    'avatar' => 'Không tải được ảnh đại diện lên Google Drive. Vui lòng thử lại.',
                ]);
            }

            $oldAvatar = $user->avatar;
            $user->avatar = $newAvatar;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($oldAvatar && Str::startsWith($oldAvatar, ['http://', 'https://'])) {
            $fileId = $googleDrive->extractFileId($oldAvatar);
            if ($fileId !== null) {
                $googleDrive->delete($fileId);
            }
        } elseif ($oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
