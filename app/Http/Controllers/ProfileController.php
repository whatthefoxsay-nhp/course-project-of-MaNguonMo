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

        // Chỉ xóa ảnh cũ sau khi lưu thành công.
        $this->deleteAvatar($oldAvatar, $googleDrive);

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /** Ảnh trên Drive xóa theo file id; ảnh cũ trên disk public xóa file; URL ngoài khác thì bỏ qua. */
    private function deleteAvatar(?string $avatar, GoogleDriveService $googleDrive): void
    {
        if (! $avatar) {
            return;
        }

        if (Str::startsWith($avatar, ['http://', 'https://'])) {
            $fileId = $googleDrive->extractFileId($avatar);
            if ($fileId !== null) {
                $googleDrive->delete($fileId);
            }

            return;
        }

        Storage::disk('public')->delete($avatar);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, GoogleDriveService $googleDrive): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // bookings.user_id cascade -> xóa tài khoản sẽ xóa luôn đơn/vé đã thanh toán (ghế kẹt "booked").
        if ($user->bookings()->exists()) {
            return back()->withErrors([
                'password' => 'Tài khoản đã có lịch sử đặt vé nên không thể tự xóa. Vui lòng liên hệ quản trị viên để được hỗ trợ.',
            ], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $this->deleteAvatar($user->avatar, $googleDrive);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
