<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\GoogleOAuthConfiguration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    private const GOOGLE_UNAVAILABLE = 'Đăng nhập Google hiện chưa được cấu hình. Bạn có thể đăng nhập bằng email và mật khẩu.';

    private const GENERIC_FAILURE = 'Không thể đăng nhập bằng Google. Vui lòng thử lại hoặc đăng nhập bằng email và mật khẩu.';

    private const UNVERIFIED_EMAIL = 'Google chưa xác minh địa chỉ email này. Hãy xác minh email với Google rồi thử lại.';

    public function redirect(): SymfonyRedirectResponse|RedirectResponse
    {
        if (! GoogleOAuthConfiguration::isConfigured()) {
            return $this->fail(self::GOOGLE_UNAVAILABLE);
        }

        try {
            return Socialite::driver('google')->redirect();
        } catch (Throwable $exception) {
            Log::warning('Could not start Google OAuth', ['exception' => $exception::class]);

            return $this->fail(self::GENERIC_FAILURE);
        }
    }

    public function callback(): RedirectResponse
    {
        if (! GoogleOAuthConfiguration::isConfigured()) {
            return $this->fail(self::GOOGLE_UNAVAILABLE);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            // Includes OAuth state mismatch, denied consent and provider/network errors.
            Log::warning('Google OAuth callback failed', ['exception' => $exception::class]);

            return $this->fail(self::GENERIC_FAILURE);
        }

        $googleId = trim((string) $googleUser->getId());
        $email = Str::lower(trim((string) $googleUser->getEmail()));

        if ($googleId === '' || mb_strlen($googleId) > 255 || ! Validator::make(
            ['email' => $email],
            ['email' => ['required', 'string', 'email', 'max:255']],
        )->passes()) {
            return $this->fail(self::GENERIC_FAILURE);
        }

        if (! $this->emailIsVerified($googleUser)) {
            return $this->fail(self::UNVERIFIED_EMAIL);
        }

        try {
            $user = DB::transaction(function () use ($googleUser, $googleId, $email): User {
                $user = User::query()->where('google_id', $googleId)->lockForUpdate()->first();

                if ($user) {
                    if (! $user->is_active) {
                        throw new \DomainException('locked');
                    }

                    return $user;
                }

                $user = User::query()->where('email', $email)->lockForUpdate()->first();

                if ($user) {
                    if (! $user->is_active) {
                        throw new \DomainException('locked');
                    }

                    if ($user->google_id !== null && $user->google_id !== $googleId) {
                        throw new \DomainException('This account is linked to a different Google account.');
                    }

                    $user->forceFill([
                        'google_id' => $googleId,
                        'email_verified_at' => $user->email_verified_at ?? now(),
                    ])->save();

                    return $user;
                }

                $name = trim((string) $googleUser->getName());
                if ($name === '') {
                    $name = Str::before($email, '@');
                }

                $name = mb_substr($name, 0, 255);
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'password' => Hash::make(Str::random(64)),
                    'is_active' => true,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();

                Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
                $user->assignRole('user');

                event(new Registered($user));

                return $user;
            });
        } catch (\DomainException $exception) {
            if ($exception->getMessage() === 'locked') {
                return $this->fail('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để được hỗ trợ.');
            }

            return $this->fail('Email này đã được liên kết với một tài khoản Google khác. Hãy đăng nhập bằng phương thức đã liên kết.');
        } catch (Throwable $exception) {
            Log::warning('Could not complete Google OAuth login', ['exception' => $exception::class]);

            return $this->fail(self::GENERIC_FAILURE);
        }

        if (! $user->is_active) {
            return $this->fail('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên để được hỗ trợ.');
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }

    private function emailIsVerified(SocialiteUser $googleUser): bool
    {
        $raw = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : [];
        $verified = $raw['email_verified'] ?? $raw['verified_email'] ?? false;

        return filter_var($verified, FILTER_VALIDATE_BOOLEAN);
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
