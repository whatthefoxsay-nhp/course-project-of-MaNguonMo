<?php

use App\Models\Event;
use App\Models\Room;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;
use App\Models\Showtime;

beforeEach(fn () => $this->seed());

function firstShowtimeOf(string $slug): Showtime
{
    return Event::firstWhere('slug', $slug)->showtimes()->orderBy('start_time')->first();
}

function presetShowtime(string $preset, bool $isSeated = true): Showtime
{
    $room = Room::factory()->create(['layout_preset' => $preset]);
    app(RoomSeatGenerator::class)->generate($room, $preset);
    $showtime = Showtime::factory()->for($room)->for(Event::factory()->create(['is_seated' => $isSeated]))->create();
    app(ShowtimeSeatGenerator::class)->generate($showtime);

    return $showtime;
}

/** @return \Illuminate\Support\Collection<int, array> mọi ghế (đã phẳng) trên sơ đồ của trang */
function pageSeats($response)
{
    return collect($response->viewData('seatMap')['levels'])->flatten(1)
        ->flatMap(fn (array $zone) => collect($zone['rows'])->flatMap(fn (array $row) => $row['seats']));
}

test('stadium page shows standing pits beside the numbered zones', function () {
    $this->get(route('showtimes.seats', presetShowtime('mega_concert')))->assertOk()
        ->assertSeeInOrder(['Fanzone đứng trái', 'SVIP sát sân khấu', 'Fanzone đứng phải'])
        ->assertSee('còn 40 chỗ');
});

test('theater page shows stalls circle and gallery', function () {
    $this->get(route('showtimes.seats', presetShowtime('theater_hall')))->assertOk()
        ->assertSeeInOrder(['Tầng trệt (Stalls)', 'Tầng lửng (Dress Circle)', 'Ban công (Gallery)']);
});

test('banquet page draws tables with a whole table button', function () {
    $this->get(route('showtimes.seats', presetShowtime('banquet_hall')))->assertOk()
        ->assertSee('B01')->assertSee('Chọn cả bàn');
});

test('non seated events show zone cards with quantity steppers', function () {
    $this->get(route('showtimes.seats', presetShowtime('open_space', false)))->assertOk()
        ->assertSee('Vé vào cửa phổ thông')->assertSee('còn 300 chỗ')->assertDontSee('Chọn cả bàn');
});

test('sold seats are reported as booked', function () {
    $showtime = firstShowtimeOf('hoa-nhac-giao-huong-saigon-philharmonic'); // có đơn TBX-89214

    $response = $this->get(route('showtimes.seats', $showtime))->assertOk();

    expect(pageSeats($response)->where('status', 'booked')->count())->toBe(2);
});

test('an expired hold is offered as available', function () {
    $showtime = firstShowtimeOf('nhac-kich-les-miserables-vn');
    $seat = $showtime->showtimeSeats()->where('status', 'available')->first();
    $seat->update(['status' => 'held', 'held_until' => now()->subMinute()]);

    $response = $this->get(route('showtimes.seats', $showtime))->assertOk();

    expect(pageSeats($response)->firstWhere('id', $seat->id)['status'])->toBe('available');
});

test('non seated events show the ticket tier selector', function () {
    $this->get(route('showtimes.seats', firstShowtimeOf('vietnam-tech-summit-ai-expo-2026')))
        ->assertOk()
        ->assertSee('Chọn Khu Vực & Số Lượng')
        ->assertSee('Hàng ghế VIP Keynote');
});

test('showtimes of unpublished events are hidden', function () {
    $showtime = firstShowtimeOf('fan-meeting-running-man-vn-2026');
    $showtime->event->update(['status' => 'draft']);

    $this->get(route('showtimes.seats', $showtime))->assertNotFound();
});
