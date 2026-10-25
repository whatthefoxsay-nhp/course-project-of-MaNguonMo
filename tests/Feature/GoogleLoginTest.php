<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;

/**
 * Giả lập Google trả về một người dùng, không gọi mạng thật.
 */
function fakeGoogleUser(array $overrides = []): GoogleUser
{
    $data = array_merge([
        'id' => 'google-123',
        'name' => 'Nguyễn Văn Google',
        'email' => 'khach@gmail.com',
        'email_verified' => true,
    ], $overrides);

    $user = (new GoogleUser)->setRaw($data)->map([
        'id' => $data['id'],
        'name' => $data['name'],
        'email' => $data['email'],
        'avatar' => null,
    ]);

    $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($user);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    return $user;
}

it('redirects to google', function () {
    $this->get(route('auth.google.redirect'))
        ->assertRedirect()
        ->assertRedirectContains('accounts.google.com');
});

it('creates a new user with the user role on first google login', function () {
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))->assertRedirect(route('home', absolute: false));

    $user = User::where('email', 'khach@gmail.com')->firstOrFail();
    expect($user->google_id)->toBe('google-123')
        ->and($user->hasRole('user'))->toBeTrue()
        ->and($user->hasRole('admin'))->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('links google to an existing password account with the same email', function () {
    $existing = User::factory()->create([
        'email' => 'khach@gmail.com',
        'password' => Hash::make('mat-khau-cu'),
        'email_verified_at' => null,
    ]);

    fakeGoogleUser(['email' => 'KHACH@gmail.com']);

    $this->get(route('auth.google.callback'))->assertRedirect();

    expect(User::where('email', 'khach@gmail.com')->count())->toBe(1);
    $existing->refresh();
    expect($existing->google_id)->toBe('google-123')
        ->and($existing->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($existing);

    // Mật khẩu cũ vẫn dùng được.
    auth()->logout();
    $this->post(route('login'), ['email' => 'khach@gmail.com', 'password' => 'mat-khau-cu']);
    $this->assertAuthenticatedAs($existing);
});

it('logs in a user that already has the google id', function () {
    $existing = User::factory()->create(['email' => 'khac@gmail.com', 'google_id' => 'google-123']);

    fakeGoogleUser(['email' => 'khac@gmail.com']);

    $this->get(route('auth.google.callback'))->assertRedirect();

    $this->assertAuthenticatedAs($existing);
    expect(User::count())->toBe(1);
});

it('refuses to link when google has not verified the email', function () {
    $existing = User::factory()->create(['email' => 'khach@gmail.com']);

    fakeGoogleUser(['email_verified' => false]);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect($existing->refresh()->google_id)->toBeNull();
});

it('refuses locked accounts', function () {
    User::factory()->create(['email' => 'khach@gmail.com', 'is_active' => false]);

    fakeGoogleUser();

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('returns to login when google fails', function () {
    $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
    $provider->shouldReceive('user')->andThrow(new InvalidStateException);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('shows google login button on login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('auth.google.redirect'))
        ->assertSee('Tiếp tục với Google');
});

it('shows google register button on register page', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee(route('auth.google.redirect'))
        ->assertSee('Tiếp tục với Google');
});
