<?php

use App\Models\Room;
use App\Models\Showtime;

function roomCoordinatesForm(array $overrides = []): array
{
    return array_merge([
        'name' => 'Nhà hát Bến Thành',
        'address' => '6 Mạc Đĩnh Chi, Quận 1',
        'capacity' => 500,
        'layout_preset' => 'theater_hall',
        'latitude' => '10.7837',
        'longitude' => '106.7005',
    ], $overrides);
}

test('admin saves venue coordinates', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.rooms.store'), roomCoordinatesForm())
        ->assertRedirect(route('admin.rooms.index'));

    $room = Room::firstWhere('name', 'Nhà hát Bến Thành');
    expect($room->latitude)->toBe(10.7837)->and($room->longitude)->toBe(106.7005);
});

test('coordinates must be valid', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.rooms.store'), roomCoordinatesForm(['latitude' => '123', 'longitude' => 'abc']))
        ->assertSessionHasErrors(['latitude', 'longitude']);
});

test('coordinates can be updated on a room that already has showtimes', function () {
    $room = Room::factory()->create();
    Showtime::factory()->for($room)->create();

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), roomCoordinatesForm(['name' => $room->name, 'latitude' => '10.5', 'longitude' => '106.5']));

    expect($room->fresh()->latitude)->toBe(10.5);
});

test('zoned venues accept coordinates too', function () {
    $this->actingAs(createAdmin())->post(route('admin.rooms.store'), [
        'name' => 'Sân khấu ngoài trời Mỹ Đình',
        'address' => 'Lê Đức Thọ, Hà Nội',
        'diagram_mode' => Room::MODE_ZONES,
        'layout_preset' => 'admission_only',
        'zones' => json_encode([['code' => 'GA', 'name' => 'Vé vào cửa', 'kind' => 'admission', 'seat_type' => 'cat1_stand', 'capacity' => 100]]),
        'latitude' => '21.0205',
        'longitude' => '105.7642',
    ])->assertRedirect(route('admin.rooms.index'));

    $room = Room::firstWhere('name', 'Sân khấu ngoài trời Mỹ Đình');
    expect($room->latitude)->toBe(21.0205)->and($room->longitude)->toBe(105.7642);
});

test('the new room form starts empty instead of a demo venue', function () {
    $this->actingAs(createAdmin())
        ->get(route('admin.rooms.create', ['mode' => Room::MODE_SEATS]))
        ->assertOk()
        ->assertSee('name="latitude"', false)
        ->assertSee('name="longitude"', false)
        ->assertDontSee('capacity: 252', false);
});
