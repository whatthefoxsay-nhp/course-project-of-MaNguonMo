<?php

use App\Models\Room;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

test('admin can access room builder creation page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.rooms.create'));
    $response->assertStatus(200);
    $response->assertSee('Thiết Kế & Sinh Sơ Đồ Khán Đài Mới');
});

test('admin can create room with mega concert preset and auto-generate seats', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.rooms.store'), [
        'name' => 'Sân Vận Động Quốc Tế Mỹ Đình',
        'address' => 'Đường Lê Đức Thọ, Mỹ Đình 1, Nam Từ Liêm, Hà Nội',
        'capacity' => 40000,
        'layout_preset' => 'mega_concert',
        'base_price' => 300000,
    ]);

    $response->assertRedirect(route('admin.rooms.index'));
    $response->assertSessionHas('success');

    $room = Room::where('name', 'Sân Vận Động Quốc Tế Mỹ Đình')->first();
    expect($room)->not->toBeNull()
        ->and($room->layout_preset)->toBe('mega_concert')
        ->and($room->capacity)->toBe(40000);

    // Verify seats were auto-generated
    expect($room->seats()->count())->toBeGreaterThan(50);
    expect($room->seats()->where('type', 'svip_diamond')->count())->toBeGreaterThan(0);
    expect($room->seats()->where('type', 'vip_gold')->count())->toBeGreaterThan(0);
    expect($room->seats()->where('type', 'skybox_suite')->count())->toBeGreaterThan(0);
});

test('admin can create room with custom grid preset and auto-generate seats', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.rooms.store'), [
        'name' => 'Phòng Hội Thảo Matrix VIP',
        'address' => 'Quận 1, TP.HCM',
        'capacity' => 120,
        'layout_preset' => 'custom_grid',
        'base_price' => 200000,
        'rows' => 6,
        'cols' => 10,
    ]);

    $response->assertRedirect(route('admin.rooms.index'));

    $room = Room::where('name', 'Phòng Hội Thảo Matrix VIP')->first();
    expect($room)->not->toBeNull()
        ->and($room->seats()->count())->toBe(60); // 6 rows * 10 cols
});

test('admin can update room and regenerate seat map', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $room = Room::create([
        'name' => 'Khán Phòng Opera Cũ',
        'capacity' => 500,
        'layout_preset' => 'theater_hall',
    ]);

    $response = $this->actingAs($admin)->put(route('admin.rooms.update', $room), [
        'name' => 'Khán Phòng Opera Mới Cải Tạo',
        'capacity' => 800,
        'layout_preset' => 'theater_hall',
        'base_price' => 350000,
    ]);

    $response->assertRedirect(route('admin.rooms.index'));

    $room->refresh();
    expect($room->name)->toBe('Khán Phòng Opera Mới Cải Tạo')
        ->and($room->capacity)->toBe(800)
        ->and($room->seats()->count())->toBeGreaterThan(0);
});

test('regular user cannot access room builder or create rooms', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)->get(route('admin.rooms.create'));
    $response->assertStatus(403);

    $responseStore = $this->actingAs($user)->post(route('admin.rooms.store'), [
        'name' => 'Hacker Arena',
        'capacity' => 100,
        'layout_preset' => 'custom_grid',
    ]);
    $responseStore->assertStatus(403);
});
