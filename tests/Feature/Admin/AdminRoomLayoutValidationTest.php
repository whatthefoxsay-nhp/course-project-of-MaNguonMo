<?php

use App\Models\Room;
use App\Models\Showtime;
use App\Services\RoomSeatGenerator;
use App\Support\LayoutTemplates;

function roomPayload(array $layout, array $overrides = []): array
{
    return array_merge([
        'name' => 'Khán phòng thử',
        'address' => 'Quận 1, TP.HCM',
        'capacity' => 500,
        'layout_preset' => 'theater_hall',
        'layout' => json_encode($layout),
    ], $overrides);
}

function generatedRoom(string $preset = 'theater_hall'): Room
{
    $room = Room::factory()->create(['layout_preset' => $preset]);
    app(RoomSeatGenerator::class)->generate($room, $preset);

    return $room;
}

test('duplicate row labels are rejected and the old seats survive', function () {
    $room = generatedRoom();
    $before = $room->seats()->count();
    $layout = LayoutTemplates::get('theater_hall');
    $layout['rows'][1]['label'] = $layout['rows'][0]['label'];

    $this->actingAs(createAdmin())->put(route('admin.rooms.update', $room), roomPayload($layout))
        ->assertSessionHasErrors('layout');

    expect($room->seats()->count())->toBe($before);
});

test('row labels that differ only by case count as duplicates', function () {
    $layout = LayoutTemplates::get('custom_grid', ['rows' => 2, 'cols' => 4]);
    $layout['rows'][1]['label'] = 'a';

    $this->actingAs(createAdmin())->post(route('admin.rooms.store'), roomPayload($layout, ['layout_preset' => 'custom_grid']))
        ->assertSessionHasErrors('layout');

    expect(Room::count())->toBe(0);
});

test('broken json is rejected without a server error', function () {
    $this->actingAs(createAdmin())->post(route('admin.rooms.store'), array_merge(roomPayload([]), ['layout' => '{oops']))
        ->assertSessionHasErrors('layout');

    expect(Room::count())->toBe(0);
});

test('a cell pointing to an unknown or standing zone is rejected', function (string $zoneCode) {
    $layout = LayoutTemplates::get('mega_concert');
    $layout['rows'][0]['cells'][0] = ['zone' => $zoneCode];

    $this->actingAs(createAdmin())->post(route('admin.rooms.store'), roomPayload($layout, ['layout_preset' => 'mega_concert']))
        ->assertSessionHasErrors('layout');

    expect(Room::count())->toBe(0);
})->with(['KHONG-CO', 'GA-L']);

test('zone fields are checked', function (string $field, mixed $value) {
    $layout = LayoutTemplates::get('theater_hall');
    $layout['zones'][0][$field] = $value;

    $this->actingAs(createAdmin())->post(route('admin.rooms.store'), roomPayload($layout))
        ->assertSessionHasErrors('layout');
})->with([
    'bad code' => ['code', 'có dấu'],
    'empty name' => ['name', ''],
    'unknown kind' => ['kind', 'balcony'],
    'bad color' => ['color', 'red'],
    'huge surcharge' => ['surcharge', 99999999],
]);

test('a standing zone needs a capacity between 1 and 2000', function () {
    $layout = LayoutTemplates::get('open_space');
    $layout['zones'][1]['capacity'] = 0;

    $this->actingAs(createAdmin())->post(route('admin.rooms.store'), roomPayload($layout, ['layout_preset' => 'open_space']))
        ->assertSessionHasErrors('layout');
});

test('standing zone capacity becomes hidden seats', function () {
    $layout = ['zones' => [[
        'code' => 'GA', 'name' => 'Vé đứng', 'kind' => 'standing', 'level' => 1, 'surcharge' => 0,
        'color' => '#3A5A40', 'gate' => null, 'description' => null, 'perks' => [], 'capacity' => 120,
    ]], 'rows' => []];

    $this->actingAs(createAdmin())->post(route('admin.rooms.store'), roomPayload($layout, ['layout_preset' => 'open_space']))
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHasNoErrors();

    $room = Room::firstWhere('name', 'Khán phòng thử');
    expect($room->seats()->count())->toBe(120)
        ->and($room->seats()->whereNotNull('pos_col')->count())->toBe(0);
});

test('editing zones of a room with showtimes changes prices but not seats', function () {
    $room = generatedRoom();
    Showtime::factory()->for($room)->create();
    $before = $room->seats()->pluck('id')->all();
    $layout = LayoutTemplates::fromRoom($room);
    $layout['zones'][0]['surcharge'] = 50000;
    $layout['zones'][0]['name'] = 'Tầng trệt mới';
    $layout['rows'][] = ['label' => 'NEW', 'cells' => [['zone' => 'STALLS']]];

    $this->actingAs(createAdmin())->put(route('admin.rooms.update', $room), roomPayload($layout))
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('warning');

    $stalls = $room->zones()->where('code', 'STALLS')->first();
    expect($room->seats()->pluck('id')->all())->toBe($before)
        ->and($stalls->surcharge)->toBe(50000)
        ->and($stalls->name)->toBe('Tầng trệt mới');
});

test('duplicating a room copies zones and seats', function () {
    $room = generatedRoom('banquet_hall');

    $this->actingAs(createAdmin())->post(route('admin.rooms.duplicate', $room))
        ->assertRedirect(route('admin.rooms.index'));

    $copy = Room::where('id', '!=', $room->id)->first();
    expect($copy->zones()->pluck('code')->all())->toBe($room->zones()->pluck('code')->all())
        ->and($copy->seats()->count())->toBe($room->seats()->count())
        ->and($copy->seats()->whereNull('zone_id')->count())->toBe(0);
});

test('the builder page lists all six layout templates', function () {
    $this->actingAs(createAdmin())->get(route('admin.rooms.create'))
        ->assertOk()
        ->assertSee(['Sân vận động Concert', 'Nhà hát &amp; Giao hưởng', 'Hội trường Hội nghị', 'Bàn tiệc &amp; Workshop', 'Không gian mở', 'Lưới tùy chỉnh'], false);
});
