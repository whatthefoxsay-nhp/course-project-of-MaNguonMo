<?php

use App\Models\Discount;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

test('admin can view discounts index page with database records', function () {
    $discount = Discount::create([
        'code' => 'TESTCODE20',
        'title' => 'Giảm giá 20%',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'min_order_value' => 100000,
        'max_uses' => 50,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.discounts.index'));

    $response->assertStatus(200);
    $response->assertSee('TESTCODE20');
    $response->assertSee('Giảm giá 20%');
});

test('admin can create a new discount voucher in database', function () {
    $data = [
        'code' => 'NEWYEAR2026',
        'title' => 'Ưu đãi năm mới',
        'discount_type' => 'fixed',
        'discount_value' => 50000,
        'min_order_value' => 200000,
        'max_uses' => 100,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'applicable_to' => 'Tất cả sự kiện',
    ];

    $response = $this->actingAs($this->admin)->post(route('admin.discounts.store'), $data);

    $response->assertRedirect(route('admin.discounts.index'));
    $this->assertDatabaseHas('discounts', [
        'code' => 'NEWYEAR2026',
        'discount_value' => 50000,
        'is_active' => true,
    ]);
});

test('admin can toggle active status of a discount', function () {
    $discount = Discount::create([
        'code' => 'TOGGLE10',
        'title' => 'Toggle Voucher',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'max_uses' => 10,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->patch(route('admin.discounts.toggle-status', $discount));

    $response->assertRedirect();
    expect($discount->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)->patch(route('admin.discounts.toggle-status', $discount));
    expect($discount->fresh()->is_active)->toBeTrue();
});

test('admin can delete a discount voucher', function () {
    $discount = Discount::create([
        'code' => 'DELCODE',
        'title' => 'To be deleted',
        'discount_type' => 'fixed',
        'discount_value' => 10000,
        'max_uses' => 10,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->delete(route('admin.discounts.destroy', $discount));

    $response->assertRedirect(route('admin.discounts.index'));
    $this->assertDatabaseMissing('discounts', [
        'code' => 'DELCODE',
    ]);
});

test('regular user cannot manage discounts', function () {
    $this->actingAs($this->user)->get(route('admin.discounts.index'))
        ->assertStatus(403);

    $this->actingAs($this->user)->post(route('admin.discounts.store'), [
        'code' => 'HACK100',
        'title' => 'Hack',
        'discount_type' => 'percentage',
        'discount_value' => 100,
        'max_uses' => 10,
    ])->assertStatus(403);
});
