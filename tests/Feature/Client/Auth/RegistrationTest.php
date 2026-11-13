<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
    $response->assertSee('Tạo Tài Khoản');
    $response->assertSee('Số Điện Thoại');
});

test('new users can register and automatically receive user role and active status', function () {
    $response = $this->post('/register', [
        'name' => 'Nguyen Van A',
        'email' => 'nguyenvana@example.com',
        'phone' => '0912345678',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));

    $user = User::where('email', 'nguyenvana@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Nguyen Van A')
        ->and($user->phone)->toBe('0912345678')
        ->and($user->is_active)->toBeTrue()
        ->and($user->hasRole('user'))->toBeTrue()
        ->and($user->hasRole('admin'))->toBeFalse();
});

test('registration fails with invalid phone format', function () {
    $response = $this->post('/register', [
        'name' => 'Nguyen Van B',
        'email' => 'nguyenvanb@example.com',
        'phone' => 'invalid-phone-abc!@#',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['phone']);
    $this->assertGuest();
});

test('registration fails when email is already registered', function () {
    User::factory()->create(['email' => 'duplicate@example.com']);

    $response = $this->post('/register', [
        'name' => 'Nguyen Duplicate',
        'email' => 'duplicate@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});
