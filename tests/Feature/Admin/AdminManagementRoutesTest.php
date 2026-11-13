<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

test('admin can access all dedicated admin management routes', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $routes = [
        route('admin.dashboard'),
        route('admin.reports.index'),
        route('admin.reports.pdf'),
        route('admin.events.index'),
        route('admin.showtimes.index'),
        route('admin.rooms.index'),
        route('admin.bookings.index'),
        route('admin.discounts.index'),
        route('admin.users.index'),
    ];

    foreach ($routes as $url) {
        $response = $this->actingAs($admin)->get($url);
        $response->assertStatus(200);
    }
});

test('regular user cannot access dedicated admin routes', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $routes = [
        route('admin.dashboard'),
        route('admin.reports.index'),
        route('admin.reports.pdf'),
        route('admin.events.index'),
        route('admin.showtimes.index'),
        route('admin.rooms.index'),
        route('admin.bookings.index'),
        route('admin.discounts.index'),
        route('admin.users.index'),
    ];

    foreach ($routes as $url) {
        $response = $this->actingAs($user)->get($url);
        $response->assertStatus(403);
    }
});
