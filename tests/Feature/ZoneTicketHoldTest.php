<?php

use App\Exceptions\SeatHoldException;
use App\Models\Event;
use App\Models\Room;
use App\Models\RoomZone;
use App\Models\Showtime;
use App\Models\ZoneTicket;
use App\Services\SeatHoldService;
use App\Services\ZoneLayoutGenerator;
use App\Services\CheckoutService;
use App\Models\BookingItem;

function zoneTicketShowtime(int $capacity = 2): array
{
    $room = Room::factory()->create(['layout_preset' => 'outdoor_concert']);
    app(ZoneLayoutGenerator::class)->generate($room, [
        ['code' => 'GA', 'name' => 'Khu đứng', 'kind' => 'standing', 'seat_type' => 'standing_pit', 'capacity' => $capacity],
    ]);
    $event = Event::factory()->create(['is_seated' => true]);
    $showtime = Showtime::factory()->for($room)->for($event)->create();

    return [$showtime, $room->zones()->first()];
}

test('quantity based zone tickets enforce capacity and expire into available inventory', function () {
    [$showtime, $zone] = zoneTicketShowtime(2);
    $user = createCustomer();

    app(SeatHoldService::class)->hold($user, $showtime, [], [], [$zone->id => 2]);
    expect(ZoneTicket::where('showtime_id', $showtime->id)->where('status', 'held')->count())->toBe(2);

    expect(fn () => app(SeatHoldService::class)->hold(createCustomer(), $showtime, [], [], [$zone->id => 1]))
        ->toThrow(SeatHoldException::class, 'Khu Khu đứng chỉ còn 0 vé.');

    ZoneTicket::where('showtime_id', $showtime->id)->update(['held_until' => now()->subMinute()]);
    app(SeatHoldService::class)->releaseExpired();

    expect(ZoneTicket::where('showtime_id', $showtime->id)->count())->toBe(0);
    app(SeatHoldService::class)->hold(createCustomer(), $showtime, [], [], [$zone->id => 2]);
    expect(ZoneTicket::where('showtime_id', $showtime->id)->where('status', 'held')->count())->toBe(2);
});

test('legacy standing tier maps into the first configured standing zone', function () {
    [$showtime, $zone] = zoneTicketShowtime(5);
    app(SeatHoldService::class)->hold(createCustomer(), $showtime, [], ['standing_pit' => 2]);

    expect(ZoneTicket::where('showtime_id', $showtime->id)->where('zone_id', $zone->id)->count())->toBe(2)
        ->and($showtime->showtimeSeats()->whereHas('seat', fn ($query) => $query->where('type', 'standing_pit'))->count())->toBe(0);
});

test('the eight ticket limit counts zone tickets across repeated holds', function () {
    [$showtime, $zone] = zoneTicketShowtime(10);
    $user = createCustomer();
    app(SeatHoldService::class)->hold($user, $showtime, [], [], [$zone->id => 7]);

    expect(fn () => app(SeatHoldService::class)->hold($user, $showtime, [], [], [$zone->id => 2]))
        ->toThrow(SeatHoldException::class, 'Mỗi lượt đặt tối đa 8 chỗ');
});

test('checkout creates booking items for quantity tickets and marks them sold', function () {
    [$showtime, $zone] = zoneTicketShowtime(3);
    $user = createCustomer();
    app(SeatHoldService::class)->hold($user, $showtime, [], [], [$zone->id => 2]);

    $booking = app(CheckoutService::class)->checkout($user, 'vietqr');
    $items = BookingItem::where('booking_id', $booking->id)->get();

    expect($booking->total_price)->toBe(360000)
        ->and($items)->toHaveCount(2)
        ->and($items->every(fn ($item) => $item->showtime_seat_id === null && $item->zone_ticket_id !== null))->toBeTrue()
        ->and(ZoneTicket::where('showtime_id', $showtime->id)->where('status', 'booked')->count())->toBe(2);
});

test('cart and booking history show zone ticket labels', function () {
    [$showtime, $zone] = zoneTicketShowtime(3);
    $user = createCustomer();
    app(SeatHoldService::class)->hold($user, $showtime, [], [], [$zone->id => 1]);

    $this->actingAs($user)->get(route('cart.index'))->assertOk()->assertSee('Khu đứng — vé số');
    app(CheckoutService::class)->checkout($user, 'vietqr');
    $this->actingAs($user)->get(route('bookings.history'))->assertOk()->assertSee('Khu đứng — vé số');
});

test('admin cancellation releases zone ticket inventory and retains booking label', function () {
    [$showtime, $zone] = zoneTicketShowtime(3);
    $user = createCustomer();
    app(SeatHoldService::class)->hold($user, $showtime, [], [], [$zone->id => 1]);
    $booking = app(CheckoutService::class)->checkout($user, 'vietqr');

    $this->actingAs(createAdmin())->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled'])
        ->assertRedirect();

    $ticket = ZoneTicket::firstOrFail();
    expect($ticket->status)->toBe('held')
        ->and($ticket->held_until->isPast())->toBeTrue()
        ->and($booking->items()->first()->fresh()->zone_ticket_id)->toBe($ticket->id);
});
