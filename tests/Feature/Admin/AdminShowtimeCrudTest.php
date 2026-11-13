<?php

use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;

function roomWithSeats(): Room
{
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 3, 'cols' => 4]);

    return $room;
}

function showtimePayload(Room $room, array $overrides = []): array
{
    $start = now()->addDays(5)->setTime(19, 0);

    return array_merge([
        'event_id' => Event::factory()->create()->id,
        'room_id' => $room->id,
        'start_time' => $start->format('Y-m-d\TH:i'),
        'end_time' => $start->copy()->addHours(2)->format('Y-m-d\TH:i'),
        'base_price' => 250000,
    ], $overrides);
}

test('creating a showtime generates one available seat per room seat', function () {
    $room = roomWithSeats();

    $this->actingAs(createAdmin())
        ->post(route('admin.showtimes.store'), showtimePayload($room))
        ->assertRedirect(route('admin.showtimes.index'))
        ->assertSessionHas('success');

    $showtime = Showtime::first();
    expect($showtime->showtimeSeats()->count())->toBe(12)
        ->and($showtime->showtimeSeats()->where('status', 'available')->count())->toBe(12);
});

test('a showtime cannot overlap another one in the same room', function () {
    $room = roomWithSeats();
    $start = now()->addDays(5)->setTime(19, 0);
    Showtime::factory()->for($room)->create(['start_time' => $start, 'end_time' => $start->copy()->addHours(3)]);

    $this->actingAs(createAdmin())
        ->post(route('admin.showtimes.store'), showtimePayload($room, [
            'start_time' => $start->copy()->addHour()->format('Y-m-d\TH:i'),
            'end_time' => $start->copy()->addHours(4)->format('Y-m-d\TH:i'),
        ]))
        ->assertSessionHasErrors('start_time');

    expect(Showtime::count())->toBe(1);
});

test('end time must be after start time and start must be in the future', function () {
    $room = roomWithSeats();

    $this->actingAs(createAdmin())
        ->post(route('admin.showtimes.store'), showtimePayload($room, [
            'start_time' => now()->subDay()->format('Y-m-d\TH:i'),
            'end_time' => now()->subDays(2)->format('Y-m-d\TH:i'),
        ]))
        ->assertSessionHasErrors(['start_time', 'end_time']);
});

test('the room of a showtime with sold seats cannot be changed', function () {
    $showtime = Showtime::factory()->for(roomWithSeats())->create();
    app(ShowtimeSeatGenerator::class)->generate($showtime);
    $showtime->showtimeSeats()->first()->update(['status' => 'booked']);
    $otherRoom = roomWithSeats();

    $this->actingAs(createAdmin())
        ->put(route('admin.showtimes.update', $showtime), showtimePayload($otherRoom, ['event_id' => $showtime->event_id]))
        ->assertSessionHas('error');

    expect($showtime->fresh()->room_id)->not->toBe($otherRoom->id);
});

test('a showtime without sold seats can be deleted', function () {
    $showtime = Showtime::factory()->for(roomWithSeats())->create();
    app(ShowtimeSeatGenerator::class)->generate($showtime);

    $this->actingAs(createAdmin())
        ->delete(route('admin.showtimes.destroy', $showtime))
        ->assertSessionHas('success');

    expect(Showtime::count())->toBe(0)->and(ShowtimeSeat::count())->toBe(0);
});

test('a showtime with sold seats cannot be deleted', function () {
    $showtime = Showtime::factory()->for(roomWithSeats())->create();
    app(ShowtimeSeatGenerator::class)->generate($showtime);
    $showtime->showtimeSeats()->first()->update(['status' => 'booked']);

    $this->actingAs(createAdmin())
        ->delete(route('admin.showtimes.destroy', $showtime))
        ->assertSessionHas('error');

    expect(Showtime::count())->toBe(1);
});

test('the showtime list filters by event', function () {
    $a = Showtime::factory()->create();
    $b = Showtime::factory()->create();

    // Không dùng assertDontSee: dropdown lọc trên trang liệt kê mọi sự kiện.
    $this->actingAs(createAdmin())
        ->get(route('admin.showtimes.index', ['movie_id' => $a->event_id]))
        ->assertOk()
        ->assertViewHas('showtimes', fn ($page) => $page->pluck('id')->all() === [$a->id]);
});
