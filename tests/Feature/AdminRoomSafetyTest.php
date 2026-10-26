<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\RoomSeatGenerator;
use App\Support\LayoutTemplates;

function roomWithSoldTicket(): array
{
    $room = Room::factory()->create(['layout_preset' => 'custom_grid']);
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 3, 'cols' => 4]);
    $showtime = Showtime::factory()->for($room)->create();
    $seat = $room->seats()->first();
    $showtimeSeat = ShowtimeSeat::create([
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'status' => 'booked',
    ]);
    $customer = createCustomer();
    $booking = Booking::create(['user_id' => $customer->id, 'total_price' => 200000, 'status' => 'confirmed']);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $showtimeSeat->id, 'price' => 200000]);

    return [$room, $showtime];
}

test('generator builds a custom grid with the requested size', function () {
    $room = Room::factory()->create();

    $count = app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 3, 'cols' => 4]);

    expect($count)->toBe(12)
        ->and($room->seats()->count())->toBe(12)
        ->and($room->seats()->where('row_label', 'A')->value('type'))->toBe('VIP');
});

test('mega concert preset puts hidden standing places in two fanzones', function () {
    $room = Room::factory()->create();

    app(RoomSeatGenerator::class)->generate($room, 'mega_concert', []);

    foreach (['GA-L', 'GA-R'] as $code) {
        $zone = $room->zones()->where('code', $code)->first();
        expect($zone->kind)->toBe('standing')
            ->and($zone->capacity)->toBe(40)
            ->and($zone->seats()->whereNull('pos_col')->count())->toBe(40);
    }
});

test('updating a room that already has showtimes keeps seats and sold tickets', function () {
    [$room] = roomWithSoldTicket();
    $seatIdsBefore = $room->seats()->pluck('id')->all();

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), [
            'name' => 'Tên mới',
            'address' => 'Địa chỉ mới',
            'capacity' => 500,
            'layout_preset' => 'custom_grid',
            'layout' => json_encode(LayoutTemplates::get('custom_grid', ['rows' => 10, 'cols' => 10])),
        ])
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('warning');

    expect($room->fresh()->name)->toBe('Tên mới')
        ->and($room->seats()->pluck('id')->all())->toBe($seatIdsBefore)
        ->and(BookingItem::count())->toBe(1);
});

test('deleting a room that has showtimes is refused', function () {
    [$room] = roomWithSoldTicket();

    $this->actingAs(createAdmin())
        ->delete(route('admin.rooms.destroy', $room))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Room::find($room->id))->not->toBeNull()
        ->and(BookingItem::count())->toBe(1);
});

test('a room without showtimes can be deleted together with its zones and seats', function () {
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'mega_concert');

    $this->actingAs(createAdmin())->delete(route('admin.rooms.destroy', $room))
        ->assertRedirect(route('admin.rooms.index'));

    expect(Room::find($room->id))->toBeNull()
        ->and(\App\Models\SeatZone::where('room_id', $room->id)->count())->toBe(0)
        ->and(\App\Models\Seat::where('room_id', $room->id)->count())->toBe(0);
});
