<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

test('user list uses multibyte initials, route() toggle URL and JS-escaped names', function () {
    $admin = createAdmin();
    $user = User::factory()->create(['name' => "Đặng O'Brien"]);
    $user->assignRole('user');

    $html = $this->actingAs($admin)->get(route('admin.users.index'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toMatch('/>\s*Đ\s*</u')
        ->not->toContain("\xC4<")
        ->toContain(str_replace('/', '\/', route('admin.users.toggle-status', ['user' => '__USER__'])))
        ->not->toContain('`/admin/users/${')
        ->not->toContain("O\\'Brien");
});
