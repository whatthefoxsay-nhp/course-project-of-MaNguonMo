<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;

beforeEach(function () {
    $this->admin = createAdmin();
    $customer = createCustomer();

    $this->concert = Category::factory()->create(['name' => 'Âm nhạc']);
    $this->theater = Category::factory()->create(['name' => 'Kịch']);
    $this->seatEvent = Event::factory()->for($this->concert)->create(['title' => 'Đêm nhạc ghế ngồi']);
    $this->idleEvent = Event::factory()->for($this->concert)->create(['title' => 'Sự kiện chưa bán']);
    $this->zoneEvent = Event::factory()->for($this->theater)->create(['title' => 'Vở kịch vé khu']);

    // Sự kiện bán theo ghế: đơn confirmed 300k + 200k, đơn cancelled 999k (không tính).
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 2, 'cols' => 4]);
    $seatShowtime = Showtime::factory()->for($room)->for($this->seatEvent)->create();
    app(ShowtimeSeatGenerator::class)->generate($seatShowtime);
    [$s1, $s2, $s3] = $seatShowtime->showtimeSeats()->orderBy('id')->take(3)->get();
    $s1->update(['status' => 'booked']);
    $s2->update(['status' => 'booked']);

    $confirmed = Booking::create(['user_id' => $customer->id, 'total_price' => 500000, 'status' => 'confirmed']);
    $confirmed->items()->create(['showtime_seat_id' => $s1->id, 'price' => 300000]);
    $confirmed->items()->create(['showtime_seat_id' => $s2->id, 'price' => 200000]);
    Booking::create(['user_id' => $customer->id, 'total_price' => 999000, 'status' => 'cancelled'])
        ->items()->create(['showtime_seat_id' => $s3->id, 'price' => 999000]);

    // Sự kiện bán theo khu đứng (ghế ẩn của khu): 2 vé confirmed x 150k, 1 vé cancelled 777k (không tính).
    $zoneRoom = Room::factory()->create(['layout_preset' => 'open_space']);
    app(RoomSeatGenerator::class)->apply($zoneRoom, ['zones' => [[
        'code' => 'GA', 'name' => 'Khu đứng', 'kind' => 'standing', 'level' => 1, 'surcharge' => 0,
        'color' => '#99334D', 'gate' => 'Cổng chính', 'description' => '', 'perks' => [], 'capacity' => 10,
    ]], 'rows' => []]);
    $zoneShowtime = Showtime::factory()->for($zoneRoom)->for($this->zoneEvent)->create();
    app(ShowtimeSeatGenerator::class)->generate($zoneShowtime);
    [$g1, $g2, $g3] = $zoneShowtime->showtimeSeats()->orderBy('id')->take(3)->get();
    $g1->update(['status' => 'booked']);
    $g2->update(['status' => 'booked']);

    $zoneBooking = Booking::create(['user_id' => $customer->id, 'total_price' => 300000, 'status' => 'confirmed']);
    $zoneBooking->items()->create(['showtime_seat_id' => $g1->id, 'price' => 150000]);
    $zoneBooking->items()->create(['showtime_seat_id' => $g2->id, 'price' => 150000]);
    Booking::create(['user_id' => $customer->id, 'total_price' => 777000, 'status' => 'cancelled'])
        ->items()->create(['showtime_seat_id' => $g3->id, 'price' => 777000]);
});

function revenueRows($rows, string $key): array
{
    return collect($rows)->mapWithKeys(fn ($row) => [$row[$key] => [$row['revenue'], $row['tickets']]])->all();
}

test('report revenue per event and category counts only confirmed seat and zone items', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();

    expect($response->viewData('totalRevenue'))->toEqual(800000)
        ->and($response->viewData('totalTicketsSold'))->toBe(4)
        ->and(revenueRows($response->viewData('eventsRevenue'), 'title'))->toBe([
            'Đêm nhạc ghế ngồi' => [500000, 2],
            'Vở kịch vé khu' => [300000, 2],
            'Sự kiện chưa bán' => [0, 0],
        ])
        ->and(revenueRows($response->viewData('categoriesRevenue'), 'name'))->toBe([
            'Âm nhạc' => [500000, 2],
            'Kịch' => [300000, 2],
        ]);
});

test('the pdf report shows the same numbers as the report page', function () {
    $index = $this->actingAs($this->admin)->get(route('admin.reports.index'));
    $pdf = $this->actingAs($this->admin)->get(route('admin.reports.pdf'))->assertOk();

    expect($pdf->viewData('eventsRevenue')->all())->toBe($index->viewData('eventsRevenue')->all())
        ->and($pdf->viewData('categoriesRevenue')->all())->toBe($index->viewData('categoriesRevenue')->all());
    $pdf->assertSee('500.000');
});

test('dashboard category revenue includes zone tickets and occupancy uses aggregates', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

    expect(revenueRows($response->viewData('categories'), 'name'))->toBe([
        'Âm nhạc' => [500000, 2],
        'Kịch' => [300000, 2],
    ]);

    $top = collect($response->viewData('topEvents'))->keyBy('title');
    expect($top['Đêm nhạc ghế ngồi']['booked_seats'])->toBe(2)
        ->and($top['Đêm nhạc ghế ngồi']['total_seats'])->toBe(8)
        ->and($top['Đêm nhạc ghế ngồi']['fill_rate'])->toEqual(25.0);

    $rooms = collect($response->viewData('rooms'));
    expect($rooms->firstWhere('booked', 2)['total'])->toBe(8);
});
