<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;

beforeEach(fn () => $this->seed());

test('the seeder publishes the eight demo events with their details', function () {
    $concert = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026');

    expect(Event::published()->count())->toBe(8)
        ->and($concert->is_seated)->toBeTrue()
        ->and($concert->venue_name)->toContain('Quân Khu 7')
        ->and($concert->lineup)->not->toBeEmpty()
        ->and($concert->entry_policy)->toHaveKey('prohibited')
        ->and(Event::firstWhere('slug', 'vietnam-tech-summit-ai-expo-2026')->is_seated)->toBeFalse();
});

test('every showtime has one showtime seat per room seat', function () {
    Showtime::with('room')->get()->each(function (Showtime $showtime) {
        expect($showtime->showtimeSeats()->count())->toBe($showtime->room->seats()->count());
    });

    expect(Showtime::count())->toBeGreaterThanOrEqual(12);
});

test('the concert runs in the stadium room with a standing row', function () {
    $concert = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026');
    $room = $concert->showtimes()->first()->room;

    expect($room->layout_preset)->toBe('mega_concert')
        ->and($room->latitude)->not->toBeNull()
        ->and($room->seats()->where('type', 'standing_pit')->count())->toBe(40);
});

test('the demo booking is confirmed and its seats are sold', function () {
    $booking = Booking::with('items.showtimeSeat')->firstWhere('booking_code', 'TBX-89214');

    expect($booking->status)->toBe('confirmed')
        ->and($booking->items)->toHaveCount(2)
        ->and($booking->total_price)->toBe($booking->items->sum('price'))
        ->and($booking->items->every(fn ($item) => $item->showtimeSeat->status === 'booked'))->toBeTrue()
        ->and(Booking::firstWhere('booking_code', 'TBX-77102')->status)->toBe('cancelled');
});

test('the event base price is the cheapest showtime price', function () {
    $concert = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026');

    expect($concert->base_price)->toBe(250000);
});

test('seeding twice does not duplicate data', function () {
    $this->seed();

    expect(Event::count())->toBe(8)
        ->and(Room::count())->toBe(4)
        ->and(Booking::where('booking_code', 'TBX-89214')->count())->toBe(1);
});
