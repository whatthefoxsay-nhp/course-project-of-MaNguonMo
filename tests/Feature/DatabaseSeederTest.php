<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
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

    expect(Showtime::count())->toBeGreaterThanOrEqual(11);
});

test('the concert runs in the stadium room with two standing fanzones', function () {
    $room = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026')->showtimes()->first()->room;

    expect($room->layout_preset)->toBe('mega_concert')
        ->and($room->latitude)->not->toBeNull()
        ->and($room->zones()->where('kind', 'standing')->pluck('code')->all())->toBe(['GA-L', 'GA-R'])
        ->and($room->seats()->whereNull('pos_col')->count())->toBe(80);
});

test('each demo event runs in a venue with a fitting layout', function () {
    $preset = fn (string $slug) => Event::where('slug', $slug)->first()->showtimes()->first()->room->layout_preset;

    expect($preset('live-concert-anh-trai-vuot-ngan-chong-gai-2026'))->toBe('mega_concert')
        ->and($preset('hoa-nhac-giao-huong-saigon-philharmonic'))->toBe('theater_hall')
        ->and($preset('nhac-kich-les-miserables-vn'))->toBe('theater_hall')
        ->and($preset('fan-meeting-running-man-vn-2026'))->toBe('theater_hall')
        ->and($preset('vietnam-tech-summit-ai-expo-2026'))->toBe('convention_center')
        ->and($preset('masterclass-sang-tao-phim-ai-2026'))->toBe('banquet_hall')
        ->and($preset('trien-lam-nghe-thuat-van-gogh-impressionism'))->toBe('open_space')
        ->and($preset('saigon-autumn-music-light-festival-2026'))->toBe('open_space')
        ->and(Event::where('slug', 'fan-meeting-running-man-vn-2026')->first()->is_seated)->toBeTrue()
        ->and(Event::where('slug', 'masterclass-sang-tao-phim-ai-2026')->first()->is_seated)->toBeTrue();
});

test('demo rooms of new events sit at the venue named in the event details', function () {
    foreach (['trien-lam-nghe-thuat-van-gogh-impressionism', 'saigon-autumn-music-light-festival-2026',
        'masterclass-sang-tao-phim-ai-2026', 'fan-meeting-running-man-vn-2026', 'nhac-kich-les-miserables-vn'] as $slug) {
        $event = Event::firstWhere('slug', $slug);
        expect($event->showtimes()->first()->room->name)->toBe($event->venue_name);
    }
});

test('every seat of every demo room belongs to a zone of that room', function () {
    expect(Seat::whereNull('zone_id')->count())->toBe(0)
        ->and(Seat::join('seat_zones', 'seat_zones.id', '=', 'seats.zone_id')
            ->whereColumn('seat_zones.room_id', '!=', 'seats.room_id')->count())->toBe(0);
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
        ->and(Room::count())->toBe(8)
        ->and(Booking::where('booking_code', 'TBX-89214')->count())->toBe(1);
});
