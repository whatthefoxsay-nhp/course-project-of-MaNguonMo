<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('admin can view user management page with correct layout', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
    $response->assertSee('Quản Lý Tài Khoản Khách Hàng & Quản Trị');
});

test('admin can filter users by search query, role and status', function () {
    $admin = User::factory()->create(['name' => 'Admin Boss', 'email' => 'boss@ticketbox.vn']);
    $admin->assignRole('admin');

    $activeUser = User::factory()->create(['name' => 'Nguyen Van An', 'email' => 'an@gmail.com', 'is_active' => true]);
    $activeUser->assignRole('user');

    $lockedUser = User::factory()->create(['name' => 'Tran Van Khoa', 'email' => 'khoa@gmail.com', 'is_active' => false]);
    $lockedUser->assignRole('user');

    // Search by query
    $response = $this->actingAs($admin)->get(route('admin.users.index', ['q' => 'Nguyen']));
    $response->assertOk();
    $response->assertSee('Nguyen Van An');
    $response->assertDontSee('Tran Van Khoa');

    // Filter by locked status
    $responseLocked = $this->actingAs($admin)->get(route('admin.users.index', ['status' => 'locked']));
    $responseLocked->assertOk();
    $responseLocked->assertSee('Tran Van Khoa');
});

test('admin can toggle user lock status via ajax', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create(['name' => 'Target User', 'is_active' => true]);
    $targetUser->assignRole('user');

    $response = $this->actingAs($admin)->patchJson(route('admin.users.toggle-status', $targetUser));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);

    expect($targetUser->fresh()->is_active)->toBeFalse();

    // Toggle back to active
    $responseUnlock = $this->actingAs($admin)->patchJson(route('admin.users.toggle-status', $targetUser));
    $responseUnlock->assertOk();
    expect($targetUser->fresh()->is_active)->toBeTrue();
});

test('admin cannot lock their own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->patchJson(route('admin.users.toggle-status', $admin));

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
    ]);
});

test('admin can toggle role between user and admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create();
    $targetUser->assignRole('user');

    $this->actingAs($admin)->patch(route('admin.users.toggle-role', $targetUser))
        ->assertRedirect();
    expect($targetUser->fresh()->hasRole('admin'))->toBeTrue();

    // Toggle back
    $this->actingAs($admin)->patch(route('admin.users.toggle-role', $targetUser))
        ->assertRedirect();
    expect($targetUser->fresh()->hasRole('user'))->toBeTrue();
});

test('admin cannot toggle their own role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->patch(route('admin.users.toggle-role', $admin))
        ->assertRedirect();
    expect($admin->fresh()->hasRole('admin'))->toBeTrue();
});

test('admin can reset password of a user to default temporary password', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create(['password' => 'old_secret_pwd']);

    $response = $this->actingAs($admin)->post(route('admin.users.reset-password', $targetUser));

    $response->assertRedirect();
    $tempPassword = 'TicketBox@' . date('Y');
    expect(Illuminate\Support\Facades\Hash::check($tempPassword, $targetUser->fresh()->password))->toBeTrue();
});

