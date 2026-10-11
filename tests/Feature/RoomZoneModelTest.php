<?php

use App\Models\Room;
use App\Models\RoomZone;
use App\Models\Seat;

test('a room has ordered zones and seats belong to a zone', function () {
    $room = Room::factory()->create(['capacity' => 70000]);
    $second = RoomZone::create(['room_id' => $room->id, 'code' => 'B', 'name' => 'Khán đài B', 'kind' => 'seated', 'seat_type' => 'cat1_stand', 'sort_order' => 2]);
    $first = RoomZone::create(['room_id' => $room->id, 'code' => 'A', 'name' => 'Khán đài A', 'kind' => 'seated', 'seat_type' => 'vip_gold', 'sort_order' => 1]);
    $seat = Seat::create(['room_id' => $room->id, 'zone_id' => $first->id, 'row_label' => 'A-A', 'seat_number' => 1, 'type' => 'vip_gold']);

    expect($room->zones->pluck('code')->all())->toBe(['A', 'B'])
        ->and($seat->zone->is($first))->toBeTrue()
        ->and($first->seats)->toHaveCount(1)
        ->and($room->fresh()->capacity)->toBe(1); // tổng ghế khu + ghế legacy
});

test('zone kinds determine inventory mode and effective capacity', function () {
    $room = Room::factory()->create();
    $seated = RoomZone::create(['room_id' => $room->id, 'code' => 'A', 'name' => 'Khu ngồi', 'kind' => 'seated', 'seat_type' => 'cat1_stand', 'capacity' => 999]);
    $standing = RoomZone::create(['room_id' => $room->id, 'code' => 'GA', 'name' => 'Khu đứng', 'kind' => 'standing', 'seat_type' => 'standing_pit', 'capacity' => 500]);
    $admission = RoomZone::create(['room_id' => $room->id, 'code' => 'IN', 'name' => 'Vào cửa', 'kind' => 'admission', 'seat_type' => 'cat1_stand', 'capacity' => 120]);
    $table = RoomZone::create(['room_id' => $room->id, 'code' => 'T', 'name' => 'Bàn', 'kind' => 'table', 'seat_type' => 'cat1_stand', 'capacity' => 999]);

    Seat::create(['room_id' => $room->id, 'zone_id' => $seated->id, 'row_label' => 'A', 'seat_number' => 1, 'type' => 'cat1_stand']);
    Seat::create(['room_id' => $room->id, 'zone_id' => $table->id, 'row_label' => 'T1', 'seat_number' => 1, 'type' => 'cat1_stand']);

    expect($seated->hasSeats())->toBeTrue()
        ->and($seated->sellsByQuantity())->toBeFalse()
        ->and($seated->effectiveCapacity())->toBe(1)
        ->and($table->effectiveCapacity())->toBe(1)
        ->and($standing->hasSeats())->toBeFalse()
        ->and($standing->sellsByQuantity())->toBeTrue()
        ->and($standing->effectiveCapacity())->toBe(500)
        ->and($admission->effectiveCapacity())->toBe(120);
});

test('room capacity recalculates from zone inventory and legacy seats', function () {
    $room = Room::factory()->create(['capacity' => 999]);
    $seated = RoomZone::create(['room_id' => $room->id, 'code' => 'A', 'name' => 'Khu ngồi', 'kind' => 'seated', 'seat_type' => 'cat1_stand', 'capacity' => 999]);
    $standing = RoomZone::create(['room_id' => $room->id, 'code' => 'GA', 'name' => 'Khu đứng', 'kind' => 'standing', 'seat_type' => 'standing_pit', 'capacity' => 500]);
    Seat::create(['room_id' => $room->id, 'zone_id' => $seated->id, 'row_label' => 'A', 'seat_number' => 1, 'type' => 'cat1_stand']);
    Seat::create(['room_id' => $room->id, 'zone_id' => null, 'row_label' => 'L', 'seat_number' => 1, 'type' => 'vip_gold']);

    expect($room->fresh()->recalculateCapacity())->toBe(502)
        ->and($room->fresh()->capacity)->toBe(502);

    $standing->update(['capacity' => 450]);
    expect($room->fresh()->capacity)->toBe(452);
});
