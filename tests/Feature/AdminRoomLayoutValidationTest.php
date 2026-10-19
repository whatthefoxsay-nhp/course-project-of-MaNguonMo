<?php

use App\Models\Room;
use App\Models\Seat;

function layoutRoomPayload(array $matrix, array $overrides = []): array
{
    return array_merge([
        'name' => 'Phòng kiểm thử',
        'address' => 'HCM',
        'capacity' => 100,
        'layout_preset' => 'custom_grid',
        'custom_layout_matrix' => json_encode($matrix),
    ], $overrides);
}

beforeEach(function () {
    $this->admin = createAdmin();
});

test('a valid custom matrix creates the room with its seats', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.rooms.store'), layoutRoomPayload([
            ['row' => 'A', 'seats' => [['number' => 1, 'type' => 'vip_gold'], ['number' => 2, 'type' => 'aisle'], ['number' => 3, 'type' => 'cat1_stand']]],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.rooms.index'));

    expect(Room::first()->seats()->count())->toBe(2);
});

test('duplicate seats in the custom matrix are a validation error, not a 500', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.rooms.store'), layoutRoomPayload([
            ['row' => 'A', 'seats' => [['number' => 1], ['number' => 2]]],
            ['row' => 'A', 'seats' => [['number' => 2]]],
        ]))
        ->assertSessionHasErrors('custom_layout_matrix');

    expect(Room::count())->toBe(0)->and(Seat::count())->toBe(0);
});

test('invalid row labels, seat numbers or json are rejected', function (array|string $matrix) {
    $payload = layoutRoomPayload([]);
    $payload['custom_layout_matrix'] = is_string($matrix) ? $matrix : json_encode($matrix);

    $this->actingAs($this->admin)
        ->postJson(route('admin.rooms.store'), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('custom_layout_matrix');

    expect(Room::count())->toBe(0);
})->with([
    'row label too long' => [[['row' => str_repeat('X', 17), 'seats' => [['number' => 1]]]]],
    'zero seat number' => [[['row' => 'A', 'seats' => [['number' => 0]]]]],
    'non numeric seat number' => [[['row' => 'A', 'seats' => [['number' => 'abc']]]]],
    'only aisles' => [[['row' => 'A', 'seats' => [['number' => 1, 'type' => 'aisle']]]]],
    'broken json' => ['{not json'],
]);

test('a bad matrix on update keeps the existing seats', function () {
    $this->actingAs($this->admin)->post(route('admin.rooms.store'), layoutRoomPayload([
        ['row' => 'A', 'seats' => [['number' => 1], ['number' => 2]]],
    ]));
    $room = Room::first();

    $this->actingAs($this->admin)
        ->put(route('admin.rooms.update', $room), layoutRoomPayload([
            ['row' => 'B', 'seats' => [['number' => 1], ['number' => 1]]],
        ]))
        ->assertSessionHasErrors('custom_layout_matrix');

    expect($room->seats()->pluck('row_label')->unique()->all())->toBe(['A'])
        ->and($room->seats()->count())->toBe(2);
});

test('standing zone codes cannot collide with seat rows', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.rooms.store'), layoutRoomPayload(
            [['row' => 'GA', 'seats' => [['number' => 1]]]],
            ['standing_zones' => json_encode([['code' => 'GA', 'capacity' => 5]])],
        ))
        ->assertSessionHasErrors('standing_zones');

    expect(Room::count())->toBe(0);
});

test('zoned rooms reject duplicate zone codes and duplicate seats inside a zone', function (array $zones) {
    $this->actingAs($this->admin)
        ->post(route('admin.rooms.store'), [
            'name' => 'Sân khu',
            'layout_preset' => 'outdoor_concert',
            'diagram_mode' => Room::MODE_ZONES,
            'zones' => json_encode($zones),
        ])
        ->assertSessionHasErrors('zones');

    expect(Room::count())->toBe(0);
})->with([
    'duplicate codes' => [[
        ['code' => 'A', 'name' => 'Khu A', 'kind' => 'standing', 'seat_type' => 'standing_pit', 'capacity' => 5],
        ['code' => 'a', 'name' => 'Khu A2', 'kind' => 'standing', 'seat_type' => 'standing_pit', 'capacity' => 5],
    ]],
    'duplicate seats' => [[
        ['code' => 'S', 'name' => 'Khu ngồi', 'kind' => 'seated', 'seat_type' => 'vip_gold', 'layout' => [
            ['row' => 'A', 'seats' => [['number' => 1], ['number' => 1]]],
        ]],
    ]],
]);
