<?php

use App\Models\Event;
use App\Models\Showtime;
use App\Models\User;

beforeEach(fn () => $this->seed());

test('live concert anh trai say hi is published with complete lineup and metadata', function () {
    $event = Event::firstWhere('slug', 'live-concert-anh-trai-say-hi-2026');

    expect($event)->not->toBeNull()
        ->and($event->title)->toContain('Anh Trai "Say Hi"')
        ->and($event->status)->toBe('published')
        ->and($event->is_seated)->toBeTrue()
        ->and($event->venue_name)->toContain('Quân Khu 7')
        ->and($event->lineup)->not->toBeEmpty();

    $lineupNames = collect($event->lineup)->pluck('name');
    expect($lineupNames)->toContain('HIEUTHUHAI')
        ->and($lineupNames)->toContain('Rhyder')
        ->and($lineupNames)->toContain('Quang Hùng MasterD')
        ->and($lineupNames)->toContain('Anh Tú Atus');
});

test('anh trai say hi concert has showtimes at stadium room', function () {
    $event = Event::firstWhere('slug', 'live-concert-anh-trai-say-hi-2026');
    $showtimes = $event->showtimes()->with('room')->get();

    expect($showtimes)->not->toBeEmpty()
        ->and($showtimes->first()->room->layout_preset)->toBe('sayhi_concert')
        ->and($showtimes->first()->showtimeSeats()->count())->toBe(252);
});

test('anh trai say hi seat selection page renders stadium concert zone map', function () {
    $event = Event::firstWhere('slug', 'live-concert-anh-trai-say-hi-2026');
    $showtime = $event->showtimes()->orderBy('start_time')->first();

    $this->get(route('showtimes.seats', $showtime))
        ->assertOk()
        ->assertSee('SÂN KHẤU CHÍNH')
        ->assertSee('CATWALK RUNWAY')
        ->assertSee('B-STAGE')
        ->assertSee('GA PIT A')
        ->assertSee('GA PIT B')
        ->assertSee('Khán Đài A')
        ->assertSee('Skybox VIP Suites')
        ->assertSee('HIEUTHUHAI', false);
});
