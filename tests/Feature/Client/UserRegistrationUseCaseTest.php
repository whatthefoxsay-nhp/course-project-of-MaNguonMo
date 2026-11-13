<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

if (! function_exists('configureGoogleOAuthForRegistration')) {
    function configureGoogleOAuthForRegistration(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }
}

if (! function_exists('mockGoogleUserForRegistration')) {
    function mockGoogleUserForRegistration(array $overrides = []): GoogleUser
    {
        $data = array_merge([
            'id' => 'google-user-reg-1',
            'name' => 'Nguyễn Văn Google',
            'email' => 'google.user@example.com',
            'avatar' => 'https://lh3.googleusercontent.com/default-avatar',
            'email_verified' => true,
        ], $overrides);

        $user = (new GoogleUser)->setRaw($data)->map([
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'avatar' => $data['avatar'] ?? null,
        ]);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        return $user;
    }
}

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

test('TC_UC012_001: registration screen displays with all required input fields and google sign up button', function () {
    configureGoogleOAuthForRegistration();

    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('Tạo Tài Khoản');
    $response->assertSee('name="name"', false);
    $response->assertSee('name="email"', false);
    $response->assertSee('name="phone"', false);
    $response->assertSee('name="password"', false);
    $response->assertSee('name="password_confirmation"', false);
    $response->assertSee('Tiếp tục với Google');
});

test('TC_UC012_002: user registers successfully with valid data and password is securely hashed', function () {
    $response = $this->post(route('register'), [
        'name' => 'Lê Hoàng Nam',
        'email' => 'hoangnam@ticketbox.vn',
        'phone' => '0901234567',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect(route('home'));
    $user = User::where('email', 'hoangnam@ticketbox.vn')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Lê Hoàng Nam')
        ->and($user->phone)->toBe('0901234567')
        ->and((bool) $user->is_active)->toBeTrue()
        ->and($user->password)->not->toBe('Password123!')
        ->and(Hash::check('Password123!', $user->password))->toBeTrue();
});

test('TC_UC012_003: newly registered account is automatically assigned user role without admin role', function () {
    $this->post(route('register'), [
        'name' => 'Trần Thị Thu',
        'email' => 'thutran@ticketbox.vn',
        'phone' => '0912345678',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = User::where('email', 'thutran@ticketbox.vn')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('user'))->toBeTrue()
        ->and($user->hasRole('admin'))->toBeFalse();
});

test('TC_UC012_004: registration automatically logs in user and redirects to home with welcome notice', function () {
    $response = $this->followingRedirects()->post(route('register'), [
        'name' => 'Nguyễn Minh Tuấn',
        'email' => 'minhtuan@ticketbox.vn',
        'phone' => '0988776655',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertOk();
    $this->assertAuthenticated();

    $user = Auth::user();
    expect($user->email)->toBe('minhtuan@ticketbox.vn');

    $response->assertSee('Đăng ký tài khoản thành công');
    $response->assertSee('Nguyễn Minh Tuấn');
});

test('TC_UC012_005: redirects to google oauth authorization screen on redirect route', function () {
    configureGoogleOAuthForRegistration();

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('redirect')->once()->andReturn(new SymfonyRedirectResponse('https://accounts.google.com/o/oauth2/auth?test=1'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('auth.google.redirect'));
    $response->assertRedirect('https://accounts.google.com/o/oauth2/auth?test=1');
});

test('TC_UC012_006: fast register via google oauth creates new user with google_id, avatar and logs in', function () {
    configureGoogleOAuthForRegistration();

    $googleData = [
        'id' => 'google-user-999',
        'name' => 'Phạm Quang Google',
        'email' => 'quanggoogle@example.com',
        'avatar' => 'https://lh3.googleusercontent.com/avatar999',
        'email_verified' => true,
    ];
    mockGoogleUserForRegistration($googleData);

    $response = $this->get(route('auth.google.callback'));
    $response->assertRedirect(route('home', absolute: false));
    $this->assertAuthenticated();

    $user = User::where('email', 'quanggoogle@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->google_id)->toBe('google-user-999')
        ->and($user->name)->toBe('Phạm Quang Google')
        ->and($user->avatar)->toBe('https://lh3.googleusercontent.com/avatar999')
        ->and((bool) $user->is_active)->toBeTrue()
        ->and($user->hasRole('user'))->toBeTrue()
        ->and($user->hasRole('admin'))->toBeFalse();
});

test('TC_UC012_007: google oauth links google_id to existing account with same email without duplicates', function () {
    configureGoogleOAuthForRegistration();

    $existingUser = User::factory()->create([
        'email' => 'existing@ticketbox.vn',
        'google_id' => null,
    ]);

    mockGoogleUserForRegistration([
        'id' => 'google-linked-id',
        'email' => 'existing@ticketbox.vn',
    ]);

    $response = $this->get(route('auth.google.callback'));
    $response->assertRedirect(route('home', absolute: false));
    $this->assertAuthenticatedAs($existingUser);

    expect(User::where('email', 'existing@ticketbox.vn')->count())->toBe(1);

    $existingUser->refresh();
    expect($existingUser->google_id)->toBe('google-linked-id');
});

test('TC_UC012_008: handles google oauth callback cancellation or exception safely', function () {
    configureGoogleOAuthForRegistration();

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException('State mismatch'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('auth.google.callback'));
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Không thể đăng nhập bằng Google');
    $this->assertGuest();
});

test('TC_UC012_009: registration fails when required fields are empty', function () {
    $response = $this->post(route('register'), []);

    $response->assertSessionHasErrors(['name', 'email', 'password']);
    $this->assertGuest();
});

test('TC_UC012_010: rejects malformed email strings during registration', function (string $invalidEmail) {
    $response = $this->post(route('register'), [
        'name' => 'Test Email',
        'email' => $invalidEmail,
        'phone' => '0901234567',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
})->with([
    'user@',
    'user.com',
    'user@.com',
    '@domain.com',
    'plainaddress',
]);

test('TC_UC012_011: rejects registration with case-insensitive duplicate email', function () {
    User::factory()->create(['email' => 'user@gmail.com']);

    $response = $this->post(route('register'), [
        'name' => 'Trùng Email',
        'email' => 'User@Gmail.com',
        'phone' => '0901234567',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('TC_UC012_012: validates vietnamese phone number prefixes and 10 digits length', function () {
    // 1. Số điện thoại hợp lệ (đầu số 03, 10 chữ số)
    $validResponse = $this->post(route('register'), [
        'name' => 'Phone Hop Le',
        'email' => 'phonehople@ticketbox.vn',
        'phone' => '0381234567',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);
    $validResponse->assertRedirect(route('home'));
    $this->assertAuthenticated();

    Auth::logout();

    // 2. Các trường hợp số điện thoại không hợp lệ
    $invalidPhones = [
        '0901abc456',   // chứa chữ cái
        '0123456789',   // đầu số 01 không phải di động VN
        '090123456',    // 9 số (thiếu số)
        '09012345678',  // 11 số (thừa số)
        'abcdefghij',   // hoàn toàn là chữ
    ];

    foreach ($invalidPhones as $invalidPhone) {
        $res = $this->post(route('register'), [
            'name' => 'Phone Sai',
            'email' => 'phone_invalid_'.uniqid().'@ticketbox.vn',
            'phone' => $invalidPhone,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $res->assertSessionHasErrors('phone');
        $this->assertGuest();
    }
});

test('TC_UC012_013: boundary test: rejects 7-character password and accepts 8-character password', function () {
    // 7 ký tự -> thất bại
    $res7 = $this->post(route('register'), [
        'name' => 'Pass 7 Char',
        'email' => 'pass7@ticketbox.vn',
        'password' => '1234567',
        'password_confirmation' => '1234567',
    ]);
    $res7->assertSessionHasErrors('password');
    $this->assertGuest();

    // 8 ký tự -> thành công
    $res8 = $this->post(route('register'), [
        'name' => 'Pass 8 Char',
        'email' => 'pass8@ticketbox.vn',
        'password' => '12345678',
        'password_confirmation' => '12345678',
    ]);
    $res8->assertRedirect(route('home'));
    $this->assertAuthenticated();
});

test('TC_UC012_014: registration fails when password confirmation does not match', function () {
    $response = $this->post(route('register'), [
        'name' => 'Mismatched User',
        'email' => 'mismatch@ticketbox.vn',
        'password' => 'Password123!',
        'password_confirmation' => 'DifferentPass456!',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('TC_UC012_015: prevents xss payload in user name on registration and output', function () {
    $xssPayload = "<script>alert('XSS')</script>";

    $response = $this->followingRedirects()->post(route('register'), [
        'name' => $xssPayload,
        'email' => 'xss_user@ticketbox.vn',
        'phone' => '0901234567',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertOk();
    $response->assertDontSee("<script>alert('XSS')</script>", false);
    $response->assertSee(e($xssPayload), false);
});

test('TC_UC012_016: authenticated user or admin accessing register routes is redirected by guest middleware', function () {
    // User thường
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)->get(route('register'))->assertRedirect();
    $this->actingAs($user)->post(route('register'), [
        'name' => 'Another User',
        'email' => 'another@ticketbox.vn',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertRedirect();

    // Admin
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('register'))->assertRedirect();
});

test('TC_UC012_017: locked account attempting google oauth is refused and shown locked account error', function () {
    configureGoogleOAuthForRegistration();

    User::factory()->create([
        'email' => 'locked@ticketbox.vn',
        'is_active' => false,
        'google_id' => 'locked-google-id',
    ]);

    mockGoogleUserForRegistration([
        'id' => 'locked-google-id',
        'email' => 'locked@ticketbox.vn',
    ]);

    $response = $this->get(route('auth.google.callback'));
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Tài khoản của bạn đã bị khóa');
    $this->assertGuest();
});

