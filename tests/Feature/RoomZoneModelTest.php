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
        ->and($room->fresh()->capacity)->toBe(70000); // vượt giới hạn smallint cũ
});
