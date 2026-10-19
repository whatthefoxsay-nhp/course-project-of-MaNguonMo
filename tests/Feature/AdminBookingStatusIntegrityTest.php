<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\ZoneTicket;
use App\Services\CheckoutService;
use App\Services\RoomSeatGenerator;
use App\Services\SeatHoldService;
use App\Services\ShowtimeSeatGenerator;
use App\Services\ZoneLayoutGenerator;

function statusSeatShowtime(): Showtime
{
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 2, 'cols' => 4]);
    $showtime = Showtime::factory()->for($room)->for(Event::factory()->create(['is_seated' => true]))->create();
    app(ShowtimeSeatGenerator::class)->generate($showtime);

    return $showtime;
}

function statusZoneShowtime(int $capacity): array
{
    $room = Room::factory()->create(['layout_preset' => 'outdoor_concert']);
    app(ZoneLayoutGenerator::class)->generate($room, [
        ['code' => 'GA', 'name' => 'Khu đứng', 'kind' => 'standing', 'seat_type' => 'standing_pit', 'capacity' => $capacity],
    ]);
    $showtime = Showtime::factory()->for($room)->for(Event::factory()->create(['is_seated' => true]))->create();

    return [$showtime, $room->zones()->first()];
}

function statusBuySeats($user, Showtime $showtime, array $seatIds): Booking
{
    app(SeatHoldService::class)->hold($user, $showtime, $seatIds);

    return app(CheckoutService::class)->checkout($user, 'vietqr');
}

beforeEach(function () {
    $this->admin = createAdmin();
});

test('setting the same status is a no-op', function () {
    $showtime = statusSeatShowtime();
    $seat = $showtime->showtimeSeats()->first();
    $booking = statusBuySeats(createCustomer(), $showtime, [$seat->id]);

    $this->actingAs($this->admin)
        ->patch(route('admin.bookings.update-status', $booking), ['status' => 'confirmed'])
        ->assertSessionHas('warning');

    expect($booking->fresh()->status)->toBe('confirmed')
        ->and($seat->fresh()->status)->toBe('booked');
});

test('pending is no longer an accepted admin status', function () {
    $showtime = statusSeatShowtime();
    $booking = statusBuySeats(createCustomer(), $showtime, [$showtime->showtimeSeats()->first()->id]);

    $this->actingAs($this->admin)
        ->patch(route('admin.bookings.update-status', $booking), ['status' => 'pending'])
        ->assertSessionHasErrors('status');

    expect($booking->fresh()->status)->toBe('confirmed');
});

test('cancelling only releases seats still booked by this booking', function () {
    $showtime = statusSeatShowtime();
    [$mine, $foreign] = $showtime->showtimeSeats()->orderBy('id')->take(2)->get();
    $booking = statusBuySeats(createCustomer(), $showtime, [$mine->id]);
    // Dữ liệu lệch: đơn có thêm 1 ghế mà người khác đang giữ -> không được nhả ghế đó.
    $booking->items()->create(['showtime_seat_id' => $foreign->id, 'price' => 100000]);
    $other = createCustomer();
    $foreign->update(['status' => 'held', 'held_by_user_id' => $other->id, 'held_until' => now()->addMinutes(5)]);

    $this->actingAs($this->admin)
        ->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled'])
        ->assertSessionHas('success');

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($mine->fresh()->status)->toBe('available')
        ->and($foreign->fresh()->status)->toBe('held')
        ->and($foreign->fresh()->held_by_user_id)->toBe($other->id);
});

test('re-confirming a cancelled booking is refused when a seat was re-sold', function () {
    $showtime = statusSeatShowtime();
    $seat = $showtime->showtimeSeats()->first();
    $first = statusBuySeats(createCustomer(), $showtime, [$seat->id]);

    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $first), ['status' => 'cancelled']);
    $second = statusBuySeats(createCustomer(), $showtime, [$seat->id]);

    $this->actingAs($this->admin)
        ->patch(route('admin.bookings.update-status', $first), ['status' => 'confirmed'])
        ->assertSessionHas('error', fn ($message) => str_contains($message, 'Không thể xác nhận lại đơn'));

    expect($first->fresh()->status)->toBe('cancelled')
        ->and($second->fresh()->status)->toBe('confirmed')
        ->and($seat->fresh()->status)->toBe('booked');

    // Hủy đơn cũ thêm lần nữa không được nhả ghế của đơn mới (đơn đã hủy -> no-op).
    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $first), ['status' => 'cancelled']);
    expect($seat->fresh()->status)->toBe('booked');
});

test('re-confirming a cancelled booking is refused when a seat is held by someone else', function () {
    $showtime = statusSeatShowtime();
    $seat = $showtime->showtimeSeats()->first();
    $booking = statusBuySeats(createCustomer(), $showtime, [$seat->id]);
    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled']);

    $other = createCustomer();
    app(SeatHoldService::class)->hold($other, $showtime, [$seat->id]);

    $this->actingAs($this->admin)
        ->patch(route('admin.bookings.update-status', $booking), ['status' => 'confirmed'])
        ->assertSessionHas('error');

    $seat->refresh();
    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($seat->status)->toBe('held')
        ->and($seat->held_by_user_id)->toBe($other->id);
});

test('re-confirming a cancelled booking re-books seats that are still free', function () {
    $showtime = statusSeatShowtime();
    $seats = $showtime->showtimeSeats()->orderBy('id')->take(2)->pluck('id')->all();
    $booking = statusBuySeats(createCustomer(), $showtime, $seats);

    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled']);
    expect(ShowtimeSeat::whereIn('id', $seats)->where('status', 'available')->count())->toBe(2);

    $this->actingAs($this->admin)
        ->patch(route('admin.bookings.update-status', $booking), ['status' => 'confirmed'])
        ->assertSessionHas('success');

    expect($booking->fresh()->status)->toBe('confirmed')
        ->and(ShowtimeSeat::whereIn('id', $seats)->where('status', 'booked')->count())->toBe(2);
});

test('zone tickets are released on cancel, survive hold cleanup and respect capacity on re-confirm', function () {
    [$showtime, $zone] = statusZoneShowtime(2);
    $buyer = createCustomer();
    app(SeatHoldService::class)->hold($buyer, $showtime, [], [], [$zone->id => 2]);
    $booking = app(CheckoutService::class)->checkout($buyer, 'vietqr');

    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled']);
    app(SeatHoldService::class)->releaseExpired();

    // Vé của đơn đã hủy vẫn còn (gắn với đơn) nhưng không chiếm sức chứa.
    expect($booking->items()->whereNotNull('zone_ticket_id')->count())->toBe(2)
        ->and(ZoneTicket::where('status', 'booked')->count())->toBe(0);

    $other = createCustomer();
    app(SeatHoldService::class)->hold($other, $showtime, [], [], [$zone->id => 1]);

    $this->actingAs($this->admin)
        ->patch(route('admin.bookings.update-status', $booking), ['status' => 'confirmed'])
        ->assertSessionHas('error', fn ($message) => str_contains($message, 'không còn đủ vé'));
    expect($booking->fresh()->status)->toBe('cancelled');

    ZoneTicket::where('held_by_user_id', $other->id)->delete();

    $this->actingAs($this->admin)
        ->patch(route('admin.bookings.update-status', $booking), ['status' => 'confirmed'])
        ->assertSessionHas('success');
    expect($booking->fresh()->status)->toBe('confirmed')
        ->and(ZoneTicket::where('showtime_id', $showtime->id)->where('status', 'booked')->count())->toBe(2);
});
