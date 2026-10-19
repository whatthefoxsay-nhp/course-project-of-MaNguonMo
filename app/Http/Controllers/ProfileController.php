<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->except('avatar'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $oldAvatar = null;
        if ($request->hasFile('avatar')) {
            $oldAvatar = $user->avatar;
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        // Chỉ xóa file cũ sau khi lưu thành công; bỏ qua URL ngoài (dữ liệu seed cũ).
        if ($oldAvatar && ! Str::startsWith($oldAvatar, ['http://', 'https://'])) {
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

        // bookings.user_id cascade -> xóa tài khoản sẽ xóa luôn đơn/vé đã thanh toán (ghế kẹt "booked").
        if ($user->bookings()->exists()) {
            return back()->withErrors([
                'password' => 'Tài khoản đã có lịch sử đặt vé nên không thể tự xóa. Vui lòng liên hệ quản trị viên để được hỗ trợ.',
            ], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        if ($user->avatar && ! Str::startsWith($user->avatar, ['http://', 'https://'])) {
            Storage::disk('public')->delete($user->avatar);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
