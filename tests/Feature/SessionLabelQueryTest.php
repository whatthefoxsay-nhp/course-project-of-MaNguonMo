<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;
use Illuminate\Support\Facades\DB;

function labelEventWithShowtimes(int $count): Event
{
    $event = Event::factory()->for(Category::factory()->create(['slug' => 'concert-'.$count]))->create();
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 2, 'cols' => 4]);

    for ($i = 0; $i < $count; $i++) {
        $start = now()->addDays(3 + $i)->setTime(19, 0);
        $showtime = Showtime::factory()->for($event)->for($room)->create(['start_time' => $start, 'end_time' => $start->copy()->addHours(2)]);
        app(ShowtimeSeatGenerator::class)->generate($showtime);
    }

    return $event;
}

function showtimeQueryCount(callable $request): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $request();
    $count = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from "showtimes"') || str_contains($q['query'], 'from "events"'))->count();
    DB::disableQueryLog();

    return $count;
}

test('event page session labels do not query per showtime', function () {
    $few = labelEventWithShowtimes(2);
    $many = labelEventWithShowtimes(6);

    $fewQueries = showtimeQueryCount(fn () => $this->get(route('events.show', $few->slug))->assertOk()->assertSee('Suất Diễn 2'));
    $manyQueries = showtimeQueryCount(fn () => $this->get(route('events.show', $many->slug))->assertOk()->assertSee('Suất Diễn 6'));

    expect($manyQueries)->toBe($fewQueries);
});

test('seat page session labels do not query per showtime', function () {
    $few = labelEventWithShowtimes(2);
    $many = labelEventWithShowtimes(6);

    $fewQueries = showtimeQueryCount(fn () => $this->get(route('showtimes.seats', $few->showtimes()->first()))->assertOk());
    $manyQueries = showtimeQueryCount(fn () => $this->get(route('showtimes.seats', $many->showtimes()->first()))->assertOk());

    expect($manyQueries)->toBe($fewQueries);
});
