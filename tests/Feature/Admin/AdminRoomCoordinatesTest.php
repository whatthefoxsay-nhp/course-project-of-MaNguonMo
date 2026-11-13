<?php

use App\Models\Room;
use App\Models\Showtime;
use App\Services\RoomSeatGenerator;
use App\Support\LayoutTemplates;

function roomForm(array $overrides = []): array
{
    return array_merge([
        'name' => 'Nhà hát Bến Thành',
        'address' => '6 Mạc Đĩnh Chi, Quận 1',
        'capacity' => 500,
        'layout_preset' => 'theater_hall',
        'layout' => json_encode(LayoutTemplates::get('theater_hall')),
        'latitude' => '10.7837',
        'longitude' => '106.7005',
    ], $overrides);
}

test('admin saves venue coordinates', function () {
    $this->actingAs(createAdmin())->post(route('admin.rooms.store'), roomForm())->assertRedirect(route('admin.rooms.index'));

    $room = Room::firstWhere('name', 'Nhà hát Bến Thành');
    expect($room->latitude)->toBe(10.7837)->and($room->longitude)->toBe(106.7005);
});

test('coordinates must be valid', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.rooms.store'), roomForm(['latitude' => '123', 'longitude' => 'abc']))
        ->assertSessionHasErrors(['latitude', 'longitude']);
});

test('coordinates can be updated on a room that already has showtimes', function () {
    $room = Room::factory()->create(['layout_preset' => 'theater_hall']);
    app(RoomSeatGenerator::class)->generate($room, 'theater_hall');
    Showtime::factory()->for($room)->create();

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), roomForm([
            'name' => $room->name,
            'layout' => json_encode(LayoutTemplates::fromRoom($room)),
            'latitude' => '10.5',
            'longitude' => '106.5',
        ]));

    expect($room->fresh()->latitude)->toBe(10.5);
});

test('the builder shows the coordinate fields', function () {
    $this->actingAs(createAdmin())->get(route('admin.rooms.create'))
        ->assertOk()
        ->assertSee('name="latitude"', false)
        ->assertSee('name="longitude"', false);
});
