<?php

use App\Models\Event;
use App\Models\Showtime;

beforeEach(fn () => $this->seed());

function firstShowtimeOf(string $slug): Showtime
{
    return Event::firstWhere('slug', $slug)->showtimes()->orderBy('start_time')->first();
}

test('stadium showtime renders the stadium blueprint with real seats', function () {
    $showtime = firstShowtimeOf('live-concert-anh-trai-vuot-ngan-chong-gai-2026');

    $this->get(route('showtimes.seats', $showtime))
        ->assertOk()
        ->assertSee('Sơ đồ khán phòng')
        ->assertSee('Chọn khu vé trên sơ đồ')
        ->assertSee('Khán Đài Cat 1')
        ->assertSee('Skybox VIP')
        ->assertViewHas('useZoneMap', true)
        ->assertViewHas('seats', fn (array $seats) => count($seats) === 212
            && $seats[0]->id === $showtime->showtimeSeats()->orderBy('id')->value('id')
            && collect($seats)->doesntContain('type', 'standing_pit'));
});

test('theater showtime renders the plain seat grid instead of the stadium', function () {
    $showtime = firstShowtimeOf('hoa-nhac-giao-huong-saigon-philharmonic');

    $this->get(route('showtimes.seats', $showtime))
        ->assertOk()
        ->assertSee('ST-1')
        ->assertDontSee('Chọn khu vé trên sơ đồ')
        ->assertDontSee('CATWALK RUNWAY')
        ->assertViewHas('useZoneMap', false)
        ->assertViewHas('seats', fn (array $seats) => collect($seats)->every(fn ($seat) => $seat->sector === 'general'));
});

test('sold seats are reported as booked', function () {
    $showtime = firstShowtimeOf('hoa-nhac-giao-huong-saigon-philharmonic'); // có đơn TBX-89214

    $this->get(route('showtimes.seats', $showtime))
        ->assertViewHas('seats', fn (array $seats) => collect($seats)->where('status', 'booked')->count() === 2);
});

test('an expired hold is offered as available', function () {
    $showtime = firstShowtimeOf('nhac-kich-les-miserables-vn');
    $seat = $showtime->showtimeSeats()->where('status', 'available')->first();
    $seat->update(['status' => 'held', 'held_until' => now()->subMinute()]);

    $this->get(route('showtimes.seats', $showtime))
        ->assertViewHas('seats', fn (array $seats) => collect($seats)->firstWhere('id', $seat->id)->status === 'available');
});

test('non seated events show the ticket tier selector', function () {
    $this->get(route('showtimes.seats', firstShowtimeOf('vietnam-tech-summit-ai-expo-2026')))
        ->assertOk()
        ->assertSee('Vé vào khu triển lãm')
        ->assertSee('Số vé')
        ->assertSee('Chọn khu vé trên sơ đồ')
        ->assertViewHas('seats', [])
        ->assertViewHas('useZoneMap', true);
});

test('showtimes of unpublished events are hidden', function () {
    $showtime = firstShowtimeOf('fan-meeting-running-man-vn-2026');
    $showtime->event->update(['status' => 'draft']);

    $this->get(route('showtimes.seats', $showtime))->assertNotFound();
});
