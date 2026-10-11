<?php

use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Services\ShowtimeSeatGenerator;
use App\Services\ZoneLayoutGenerator;

test('seated event renders physical seats alongside quantity based venue zones', function () {
    $room = Room::factory()->create(['layout_preset' => 'outdoor_concert']);
    app(ZoneLayoutGenerator::class)->generate($room, [
        ['code' => 'A', 'name' => 'Khán đài A', 'kind' => 'seated', 'seat_type' => 'cat1_stand', 'rows' => 2, 'cols' => 4],
        ['code' => 'T', 'name' => 'Khu bàn', 'kind' => 'table', 'seat_type' => 'cat1_stand', 'tables' => 1, 'seats_per_table' => 2],
        ['code' => 'GA', 'name' => 'Fanzone', 'kind' => 'standing', 'seat_type' => 'standing_pit', 'capacity' => 30],
    ]);
    $event = Event::factory()->create(['is_seated' => true]);
    $showtime = Showtime::factory()->for($room)->for($event)->create();
    app(ShowtimeSeatGenerator::class)->generate($showtime);

    $this->get(route('showtimes.seats', $showtime))
        ->assertOk()
        ->assertSee('Khán đài A')
        ->assertSee('Fanzone')
        ->assertSee('Chọn cả bàn')
        ->assertViewHas('seats', fn (array $seats) => count($seats) === 10 && $seats[0]->zone_id !== null)
        ->assertViewHas('venueZones', fn (array $zones) => count($zones) === 3 && $zones[2]['available'] === 30);
});

test('admission only venue renders quantity inventory without physical seats', function () {
    $room = Room::factory()->create(['layout_preset' => 'admission_only']);
    app(ZoneLayoutGenerator::class)->generate($room, [
        ['code' => 'IN', 'name' => 'Vé vào cửa', 'kind' => 'admission', 'seat_type' => 'cat1_stand', 'capacity' => 50],
    ]);
    $event = Event::factory()->create(['is_seated' => false]);
    $showtime = Showtime::factory()->for($room)->for($event)->create();

    $this->get(route('showtimes.seats', $showtime))
        ->assertOk()
        ->assertSee('Vé vào cửa')
        ->assertSee('Số vé')
        ->assertViewHas('seats', [])
        ->assertViewHas('venueZones', fn (array $zones) => $zones[0]['available'] === 50);
});
