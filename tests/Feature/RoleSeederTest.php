<?php

use Spatie\Permission\Models\Role;

it('seeds exactly the admin and user roles', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())->toBe(['admin', 'user']);
});
