<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->category = Category::create([
        'name' => 'Concert',
        'slug' => 'concert',
        'description' => 'Live concert',
    ]);

    $this->event = Event::create([
        'category_id' => $this->category->id,
        'title' => 'Rock Arena 2026',
        'slug' => 'rock-arena-2026',
        'description' => 'Test event',
        'duration_minutes' => 120,
        'release_date' => now()->addDays(5)->toDateString(),
        'status' => 'published',
        'is_seated' => true,
    ]);

    $this->room = Room::create([
        'name' => 'Grand Arena',
        'address' => 'District 1, HCMC',
        'capacity' => 100,
        'layout_preset' => 'mega_concert',
    ]);

    $this->seat1 = Seat::create([
        'room_id' => $this->room->id,
        'row_label' => 'A',
        'seat_number' => 1,
        'type' => 'vip',
    ]);

    $this->seat2 = Seat::create([
        'room_id' => $this->room->id,
        'row_label' => 'A',
        'seat_number' => 2,
        'type' => 'vip',
    ]);

    $this->showtime = Showtime::create([
        'event_id' => $this->event->id,
        'room_id' => $this->room->id,
        'start_time' => now()->addDays(3)->setTime(19, 0),
        'end_time' => now()->addDays(3)->setTime(21, 0),
        'base_price' => 300000,
    ]);

    $this->stSeat1 = ShowtimeSeat::create([
        'showtime_id' => $this->showtime->id,
        'seat_id' => $this->seat1->id,
        'price' => 300000,
        'status' => 'booked',
    ]);

    $this->stSeat2 = ShowtimeSeat::create([
        'showtime_id' => $this->showtime->id,
        'seat_id' => $this->seat2->id,
        'price' => 300000,
        'status' => 'booked',
    ]);

    $this->customer = User::factory()->create();
    $this->customer->assignRole('user');

    $this->booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-TEST99',
        'total_price' => 600000,
        'status' => 'confirmed',
    ]);

    BookingItem::create([
        'booking_id' => $this->booking->id,
        'showtime_seat_id' => $this->stSeat1->id,
        'price' => 300000,
    ]);

    BookingItem::create([
        'booking_id' => $this->booking->id,
        'showtime_seat_id' => $this->stSeat2->id,
        'price' => 300000,
    ]);
});

test('admin can view bookings index list', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.bookings.index'));

    $response->assertStatus(200);
    $response->assertSee('TBX-TEST99');
    $response->assertSee('Rock Arena 2026');
});

test('admin can view booking details', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $this->booking));

    $response->assertStatus(200);
    $response->assertSee('TBX-TEST99');
    $response->assertSee('Hàng A - Ghế 1');
    $response->assertSee('Hàng A - Ghế 2');
    $response->assertSee('600.000');
});

test('cancelling a booking marks it cancelled and safely releases seats back to available', function () {
    expect($this->stSeat1->fresh()->status)->toBe('booked');
    expect($this->stSeat2->fresh()->status)->toBe('booked');

    $response = $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $this->booking), [
        'status' => 'cancelled',
    ]);

    $response->assertRedirect();
    expect($this->booking->fresh()->status)->toBe('cancelled');
    expect($this->stSeat1->fresh()->status)->toBe('available');
    expect($this->stSeat2->fresh()->status)->toBe('available');
});

test('admin can confirm a pending booking', function () {
    $this->booking->update(['status' => 'pending']);
    $this->stSeat1->update(['status' => 'held']);

    $response = $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $this->booking), [
        'status' => 'confirmed',
    ]);

    $response->assertRedirect();
    expect($this->booking->fresh()->status)->toBe('confirmed');
    expect($this->stSeat1->fresh()->status)->toBe('booked');
});

test('regular user cannot access admin booking show or update status', function () {
    $this->actingAs($this->customer)->get(route('admin.bookings.show', $this->booking))
        ->assertStatus(403);

    $this->actingAs($this->customer)->patch(route('admin.bookings.update-status', $this->booking), [
        'status' => 'cancelled',
    ])->assertStatus(403);
});
