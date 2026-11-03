<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;

function guardSeatShowtime(): Showtime
{
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 2, 'cols' => 4]);
    $showtime = Showtime::factory()->for($room)->create();
    app(ShowtimeSeatGenerator::class)->generate($showtime);

    return $showtime;
}

function guardCancelledBooking(): Booking
{
    return Booking::create(['user_id' => createCustomer()->id, 'total_price' => 200000, 'status' => 'cancelled']);
}

beforeEach(function () {
    $this->admin = createAdmin();
});

test('a showtime whose seat booking was cancelled cannot be deleted', function () {
    $showtime = guardSeatShowtime();
    // Đơn đã hủy -> ghế đã trả về "available" nhưng booking_item vẫn trỏ tới ghế.
    guardCancelledBooking()->items()->create(['showtime_seat_id' => $showtime->showtimeSeats()->first()->id, 'price' => 200000]);

    $this->actingAs($this->admin)
        ->delete(route('admin.showtimes.destroy', $showtime))
        ->assertSessionHas('error');

    expect(Showtime::whereKey($showtime->id)->exists())->toBeTrue()
        ->and(BookingItem::count())->toBe(1);
});

test('a showtime whose standing-zone booking was cancelled cannot be deleted', function () {
    $room = Room::factory()->create(['layout_preset' => 'open_space']);
    app(RoomSeatGenerator::class)->apply($room, ['zones' => [[
        'code' => 'GA', 'name' => 'Khu đứng', 'kind' => 'standing', 'level' => 1, 'surcharge' => 0,
        'color' => '#99334D', 'gate' => 'Cổng chính', 'description' => '', 'perks' => [], 'capacity' => 5,
    ]], 'rows' => []]);
    $showtime = Showtime::factory()->for($room)->create();
    app(ShowtimeSeatGenerator::class)->generate($showtime);
    // Khu đứng = ghế ẩn của khu; đơn đã hủy vẫn trỏ tới ghế ẩn đó.
    guardCancelledBooking()->items()->create(['showtime_seat_id' => $showtime->showtimeSeats()->first()->id, 'price' => 150000]);

    $this->actingAs($this->admin)
        ->delete(route('admin.showtimes.destroy', $showtime))
        ->assertSessionHas('error');

    expect(Showtime::whereKey($showtime->id)->exists())->toBeTrue();
});

test('an event with only cancelled bookings cannot be deleted', function () {
    $showtime = guardSeatShowtime();
    guardCancelledBooking()->items()->create(['showtime_seat_id' => $showtime->showtimeSeats()->first()->id, 'price' => 200000]);

    $this->actingAs($this->admin)
        ->delete(route('admin.events.destroy', $showtime->event_id))
        ->assertSessionHas('error');

    expect(Event::whereKey($showtime->event_id)->exists())->toBeTrue()
        ->and(BookingItem::count())->toBe(1);
});

test('an event without bookings can still be deleted', function () {
    $showtime = guardSeatShowtime();

    $this->actingAs($this->admin)
        ->delete(route('admin.events.destroy', $showtime->event_id))
        ->assertSessionHas('success');

    expect(Event::whereKey($showtime->event_id)->exists())->toBeFalse();
});
