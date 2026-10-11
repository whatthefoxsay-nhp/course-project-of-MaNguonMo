<?php

use App\Models\Room;
use App\Models\Showtime;
use App\Models\RoomZone;
use App\Services\ZoneLayoutGenerator;

test('zone generator creates physical seats only for seated zones and recalculates capacity', function () {
    $room = Room::factory()->create();

    $seatCount = app(ZoneLayoutGenerator::class)->generate($room, [
        ['code' => 'A', 'name' => 'Khán đài A', 'kind' => 'seated', 'seat_type' => 'cat1_stand', 'rows' => 2, 'cols' => 10],
        ['code' => 'GA', 'name' => 'Khu đứng', 'kind' => 'standing', 'seat_type' => 'standing_pit', 'capacity' => 500],
    ]);

    expect($seatCount)->toBe(20)
        ->and($room->seats()->count())->toBe(20)
        ->and($room->seats()->whereNull('zone_id')->count())->toBe(0)
        ->and($room->zones()->where('kind', 'standing')->first()->seats()->count())->toBe(0)
        ->and($room->fresh()->capacity)->toBe(520);
});

test('zone generator preserves custom per-row seat counts', function () {
    $room = Room::factory()->create();

    $seatCount = app(ZoneLayoutGenerator::class)->generate($room, [
        [
            'code' => 'VIP',
            'name' => 'VIP cánh trái',
            'kind' => 'seated',
            'seat_type' => 'vip_gold',
            'layout' => [
                ['row' => 'A', 'seats' => [['number' => 1], ['number' => 2], ['number' => 3]]],
                ['row' => 'C', 'seats' => [['number' => 1], ['number' => 2]]],
            ],
        ],
    ]);

    expect($seatCount)->toBe(5)
        ->and($room->seats()->where('zone_id', $room->zones()->first()->id)->count())->toBe(5)
        ->and($room->seats()->pluck('row_label')->unique()->sort()->values()->all())->toBe(['VIP-A', 'VIP-C'])
        ->and($room->fresh()->capacity)->toBe(5);
});

test('zone generator refuses to replace a room that has showtimes', function () {
    $room = Room::factory()->create();
    Showtime::factory()->for($room)->create();

    expect(fn () => app(ZoneLayoutGenerator::class)->generate($room, [
        ['code' => 'GA', 'name' => 'Khu đứng', 'kind' => 'standing', 'seat_type' => 'standing_pit', 'capacity' => 500],
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});
