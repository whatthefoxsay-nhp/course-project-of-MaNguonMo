<?php

use App\Models\Room;
use App\Services\RoomSeatGenerator;
use App\Support\LayoutTemplates;

test('every preset produces zones and only references its own zones', function (string $preset) {
    $layout = LayoutTemplates::get($preset);
    $codes = collect($layout['zones'])->pluck('code');

    expect($codes)->not->toBeEmpty()->and($codes->duplicates())->toBeEmpty();
    foreach ($layout['rows'] as $row) {
        foreach ($row['cells'] as $cell) {
            expect(isset($cell['aisle']) || $codes->contains($cell['zone']))->toBeTrue();
        }
    }
})->with(LayoutTemplates::PRESETS);

test('theater rows leave a centre aisle gap', function () {
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'theater_hall');

    $row = $room->seats()->where('row_label', 'ST-A')->orderBy('pos_col')->get();
    expect($row)->toHaveCount(16)
        ->and($row->pluck('pos_col')->contains(9))->toBeFalse()
        ->and($row->last()->seat_number)->toBe(16)
        ->and($row->last()->pos_col)->toBe(17);
});

test('banquet tables become rows of chairs in table zones', function () {
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'banquet_hall');

    expect($room->zones()->pluck('kind')->unique()->values()->all())->toBe(['table'])
        ->and($room->seats()->where('row_label', 'B01')->count())->toBe(8)
        ->and($room->seats()->where('row_label', 'B12')->count())->toBe(6);
});

test('open space has only standing zones with hidden seats', function () {
    $room = Room::factory()->create();
    $total = app(RoomSeatGenerator::class)->generate($room, 'open_space');

    expect($total)->toBe(350)
        ->and($room->seats()->whereNotNull('pos_col')->count())->toBe(0)
        ->and($room->zones()->where('code', 'GEN')->first()->seats()->count())->toBe(300);
});

test('a room layout can be read back and regenerated identically', function () {
    $room = Room::factory()->create();
    $generator = app(RoomSeatGenerator::class);
    $generator->generate($room, 'mega_concert');
    $before = $room->seats()->orderBy('id')->get(['row_label', 'seat_number', 'pos_col', 'type'])->toArray();

    $copy = Room::factory()->create();
    $generator->apply($copy, LayoutTemplates::fromRoom($room));

    expect($copy->seats()->orderBy('id')->get(['row_label', 'seat_number', 'pos_col', 'type'])->toArray())->toBe($before)
        ->and($copy->zones()->pluck('code')->all())->toBe($room->zones()->pluck('code')->all());
});

test('custom grid follows the requested size and gives the first two rows to VIP', function () {
    $room = Room::factory()->create();
    $total = app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 6, 'cols' => 10]);

    expect($total)->toBe(60)
        ->and($room->zones()->where('code', 'VIP')->first()->seats()->count())->toBe(20);
});

test('regenerating replaces the old zones and seats', function () {
    $room = Room::factory()->create();
    $generator = app(RoomSeatGenerator::class);
    $generator->generate($room, 'mega_concert');
    $generator->generate($room, 'theater_hall');

    expect($room->zones()->pluck('code')->all())->toBe(['STALLS', 'CIRCLE', 'GALLERY'])
        ->and($room->seats()->whereNull('zone_id')->count())->toBe(0);
});
