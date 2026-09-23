<?php

use App\Models\Booking;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('dashboard renders 0 VND when database has no bookings and does not show fake numbers', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('0₫');
    $response->assertDontSee('128.500.000');
    $response->assertDontSee('128500000');
    $response->assertSee('Chưa có lượt đặt vé nào trên hệ thống');
});

test('dashboard renders exact confirmed revenue when bookings exist', function () {
    $customer = User::factory()->create();
    $customer->assignRole('user');

    Booking::create([
        'user_id' => $customer->id,
        'booking_code' => 'TBX-REAL01',
        'total_price' => 750000,
        'status' => 'confirmed',
    ]);

    Booking::create([
        'user_id' => $customer->id,
        'booking_code' => 'TBX-CANCEL01',
        'total_price' => 500000,
        'status' => 'cancelled',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('750.000₫');
    $response->assertSee('TBX-REAL01');
});

test('reports index and pdf views render real metrics without fake overrides', function () {
    $responseIndex = $this->actingAs($this->admin)->get(route('admin.reports.index'));
    $responseIndex->assertOk();
    $responseIndex->assertSee('0₫');
    $responseIndex->assertDontSee('128.500.000');

    $responsePdf = $this->actingAs($this->admin)->get(route('admin.reports.pdf'));
    $responsePdf->assertOk();
    $responsePdf->assertSee('0₫');
    $responsePdf->assertDontSee('128.500.000');
});
