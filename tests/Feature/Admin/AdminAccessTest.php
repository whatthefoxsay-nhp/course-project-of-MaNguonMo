<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('blocks a plain user from the admin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('allows an admin into the admin dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin')->assertOk();
});

it('redirects a guest to login', function () {
    $this->get('/admin')->assertRedirect('/login');
});
