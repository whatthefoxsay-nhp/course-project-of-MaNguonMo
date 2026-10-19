<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ZoneTicket;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;
use App\Services\ZoneLayoutGenerator;

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

test('a showtime whose zone ticket booking was cancelled cannot be deleted', function () {
    $room = Room::factory()->create(['layout_preset' => 'outdoor_concert']);
    app(ZoneLayoutGenerator::class)->generate($room, [
        ['code' => 'GA', 'name' => 'Khu đứng', 'kind' => 'standing', 'seat_type' => 'standing_pit', 'capacity' => 5],
    ]);
    $showtime = Showtime::factory()->for($room)->create();
    $ticket = ZoneTicket::create([
        'showtime_id' => $showtime->id, 'zone_id' => $room->zones()->first()->id,
        'status' => 'held', 'held_until' => now()->subMinute(), 'price' => 150000,
    ]);
    guardCancelledBooking()->items()->create(['zone_ticket_id' => $ticket->id, 'price' => 150000]);

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
