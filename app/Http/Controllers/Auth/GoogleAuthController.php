<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    private const MSG_FAILED = 'Đăng nhập Google thất bại, vui lòng thử lại.';

    private const MSG_LOCKED = 'Tài khoản của bạn đã bị tạm khóa bởi Quản trị viên. Vui lòng liên hệ ban quản trị để được hỗ trợ.';

    private const MSG_UNVERIFIED = 'Email Google của bạn chưa được xác minh nên không thể liên kết với tài khoản đã có. Vui lòng đăng nhập bằng mật khẩu.';

    /**
     * Chuyển người dùng sang trang đồng ý của Google.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google gọi lại sau khi người dùng đồng ý.
     *
     * Thứ tự tìm tài khoản:
     *  1. Theo google_id (đã từng đăng nhập Google).
     *  2. Theo email (đã đăng ký bằng mật khẩu) → liên kết nếu Google xác nhận email.
     *  3. Không có → tạo tài khoản mới với role "user".
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::warning('Google OAuth callback failed', ['error' => $e->getMessage()]);

            return $this->fail(self::MSG_FAILED);
        }

        $email = Str::lower((string) $googleUser->getEmail());

        if ($email === '' || ! $googleUser->getId()) {
            return $this->fail(self::MSG_FAILED);
        }

        $verified = $this->emailIsVerified($googleUser);

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $email)->first();

            if ($user) {
                if (! $verified) {
                    return $this->fail(self::MSG_UNVERIFIED);
                }

                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            } else {
                $user = $this->createUser($googleUser, $email, $verified);
            }
        }

        if (! $user->is_active) {
            return $this->fail(self::MSG_LOCKED);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }

    private function createUser(SocialiteUser $googleUser, string $email, bool $verified): User
    {
        $user = DB::transaction(function () use ($googleUser, $email, $verified) {
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleUser->getId(),
                // Cột password NOT NULL: dùng mật khẩu ngẫu nhiên, người dùng
                // có thể đặt mật khẩu thật qua "Quên mật khẩu".
                'password' => Hash::make(Str::random(40)),
                'is_active' => true,
            ]);

            if ($verified) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
            $user->assignRole('user');

            return $user;
        });

        event(new Registered($user));

        return $user;
    }

    private function emailIsVerified(SocialiteUser $googleUser): bool
    {
        $raw = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : ($googleUser->user ?? []);
        $flag = $raw['email_verified'] ?? $raw['verified_email'] ?? false;

        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
