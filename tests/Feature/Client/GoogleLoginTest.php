<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;

function configureGoogleOAuth(): void
{
    config([
        'services.google.client_id' => 'test-client-id',
        'services.google.client_secret' => 'test-client-secret',
        'services.google.redirect' => 'http://localhost/auth/google/callback',
    ]);
}

function fakeGoogleUser(array $overrides = []): GoogleUser
{
    $data = array_merge([
        'id' => 'google-user-1',
        'name' => 'Nguyễn Văn Google',
        'email' => 'google@example.com',
        'email_verified' => true,
    ], $overrides);

    $user = (new GoogleUser)->setRaw($data)->map([
        'id' => $data['id'] ?? null,
        'name' => $data['name'] ?? null,
        'email' => $data['email'] ?? null,
        'avatar' => null,
    ]);

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($user);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    return $user;
}

it('keeps google login optional when credentials are missing', function () {
    config(['services.google' => ['client_id' => '', 'client_secret' => '', 'redirect' => '']]);

    $this->get(route('login'))->assertOk()->assertDontSee('Tiếp tục với Google');
    $this->get(route('register'))->assertOk()->assertDontSee('Tiếp tục với Google');
    $this->get(route('auth.google.redirect'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});

it('shows google login and registration only when the callback configuration is valid', function () {
    configureGoogleOAuth();

    $this->get(route('login'))->assertOk()->assertSee('Tiếp tục với Google');
    $this->get(route('register'))->assertOk()->assertSee('Tiếp tục với Google');

    config(['services.google.redirect' => 'not-a-url']);
    $this->get(route('login'))->assertDontSee('Tiếp tục với Google');

    config([
        'app.env' => 'production',
        'services.google.redirect' => 'http://ticketbox.example/auth/google/callback',
    ]);
    $this->get(route('login'))->assertDontSee('Tiếp tục với Google');

    config(['services.google.redirect' => 'https://ticketbox.example/auth/google/callback']);
    $this->get(route('login'))->assertSee('Tiếp tục với Google');
});

it('redirects a configured user to google', function () {
    configureGoogleOAuth();
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get(route('auth.google.redirect'))
        ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
});

it('creates a verified customer account on the first google login', function () {
    configureGoogleOAuth();
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))->assertRedirect(route('home', absolute: false));

    $user = User::where('email', 'google@example.com')->firstOrFail();
    expect($user->google_id)->toBe('google-user-1')
        ->and($user->hasRole('user'))->toBeTrue()
        ->and($user->hasRole('admin'))->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('rejects missing or malformed google identity data', function (array $data) {
    configureGoogleOAuth();
    fakeGoogleUser($data);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(User::count())->toBe(0);
})->with([
    'missing provider id' => [['id' => null]],
    'missing email' => [['email' => null]],
    'invalid email' => [['email' => 'not-an-email']],
]);

it('rejects an unverified google email before creating or linking an account', function () {
    configureGoogleOAuth();
    fakeGoogleUser(['email_verified' => false]);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

it('does not link an unverified google email to an existing account', function () {
    configureGoogleOAuth();
    $existing = createCustomer(['email' => 'google@example.com']);
    fakeGoogleUser(['email_verified' => false]);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect($existing->fresh()->google_id)->toBeNull();
});

it('links a verified google identity to an existing password account without replacing its password', function () {
    configureGoogleOAuth();
    $existing = createCustomer([
        'email' => 'google@example.com',
        'password' => Hash::make('existing-password'),
        'email_verified_at' => null,
    ]);
    fakeGoogleUser(['email' => 'GOOGLE@example.com']);

    $this->get(route('auth.google.callback'))->assertRedirect();

    expect(User::count())->toBe(1)
        ->and($existing->fresh()->google_id)->toBe('google-user-1')
        ->and($existing->fresh()->email_verified_at)->not->toBeNull()
        ->and(Hash::check('existing-password', $existing->fresh()->password))->toBeTrue();
    $this->assertAuthenticatedAs($existing);
});

it('refuses to replace a different google identity already linked to an email', function () {
    configureGoogleOAuth();
    $existing = createCustomer([
        'email' => 'google@example.com',
        'google_id' => 'another-google-id',
    ]);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect($existing->fresh()->google_id)->toBe('another-google-id');
});

it('refuses login to a locked account even when google identity is valid', function () {
    configureGoogleOAuth();
    $existing = createCustomer(['email' => 'google@example.com', 'is_active' => false]);
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect($existing->fresh()->google_id)->toBeNull();
});

it('returns safely to login when google callback state or provider fails', function () {
    configureGoogleOAuth();
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
