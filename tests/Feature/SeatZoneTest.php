<?php

use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatZone;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;

test('zone price is base price plus surcharge and never negative', function () {
    $zone = new SeatZone(['surcharge' => 80000]);

    expect($zone->priceFor(200000))->toBe(280000)
        ->and((new SeatZone(['surcharge' => -300000]))->priceFor(200000))->toBe(0);
});

test('a seat in a zone is priced from its zone', function () {
    $showtime = Showtime::factory()->create(['base_price' => 200000]);
    $zone = SeatZone::create(['room_id' => $showtime->room_id, 'code' => 'VIP', 'name' => 'VIP', 'kind' => 'seated', 'surcharge' => 50000]);
    $seat = Seat::create(['room_id' => $showtime->room_id, 'zone_id' => $zone->id, 'row_label' => 'A', 'seat_number' => 1, 'pos_col' => 1, 'type' => 'VIP']);
    $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'available']);

    expect($showtimeSeat->effective_price)->toBe(250000)
        ->and($seat->isVip())->toBeTrue();
});

test('room zones are ordered by level then sort order', function () {
    $room = Room::factory()->create();
    SeatZone::create(['room_id' => $room->id, 'code' => 'B', 'name' => 'B', 'kind' => 'seated', 'level' => 2]);
    SeatZone::create(['room_id' => $room->id, 'code' => 'A2', 'name' => 'A2', 'kind' => 'seated', 'level' => 1, 'sort_order' => 1]);
    SeatZone::create(['room_id' => $room->id, 'code' => 'A1', 'name' => 'A1', 'kind' => 'seated', 'level' => 1, 'sort_order' => 0]);

    expect($room->zones()->pluck('code')->all())->toBe(['A1', 'A2', 'B']);
});

test('standing and pickable kinds are recognised', function () {
    expect((new SeatZone(['kind' => 'standing']))->isStanding())->toBeTrue()
        ->and((new SeatZone(['kind' => 'standing']))->isPickable())->toBeFalse()
        ->and((new SeatZone(['kind' => 'table']))->isPickable())->toBeTrue()
        ->and((new SeatZone(['kind' => 'seated']))->isPickable())->toBeTrue();
});
