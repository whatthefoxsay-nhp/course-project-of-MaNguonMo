<?php

use App\Exceptions\SeatHoldException;
use App\Models\Event;
use App\Models\Room;
use App\Models\SeatZone;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Services\RoomSeatGenerator;
use App\Services\SeatHoldService;
use App\Services\ShowtimeSeatGenerator;
use App\Support\SeatMap;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

function createZonedShowtime(string $preset, bool $isSeated = true, int $basePrice = 250000, string $status = 'published', $startTime = null): Showtime
{
    $room = Room::factory()->create(['layout_preset' => $preset]);
    app(RoomSeatGenerator::class)->generate($room, $preset);

    $event = Event::factory()->create([
        'is_seated' => $isSeated,
        'status' => $status,
    ]);

    $showtime = Showtime::factory()->create([
        'event_id' => $event->id,
        'room_id' => $room->id,
        'base_price' => $basePrice,
        'start_time' => $startTime ?? now()->addDays(3)->setTime(19, 0),
        'end_time' => ($startTime ?? now()->addDays(3)->setTime(19, 0))->copy()->addHours(3),
    ]);

    app(ShowtimeSeatGenerator::class)->generate($showtime);

    return $showtime;
}

test('TC_UC015_001: authenticated user accesses seats selection page successfully with HTTP 200', function () {
    $user = createCustomer();
    $showtime = createZonedShowtime('theater_hall');

    $response = $this->actingAs($user)->get(route('showtimes.seats', $showtime->id));

    $response->assertOk();
    $response->assertSee($showtime->event->title);
    $response->assertSee($showtime->room->name);
    $response->assertViewHas('showtime');
    $response->assertViewHas('seatMap');
});

test('TC_UC015_002: seated event seat map data structure contains seats, coordinates, zone color and surcharge price', function () {
    $user = createCustomer();
    $showtime = createZonedShowtime('custom_grid', true, 200000);

    $response = $this->actingAs($user)->get(route('showtimes.seats', $showtime->id));

    $response->assertOk();
    $seatMap = $response->viewData('seatMap');

    expect($seatMap['is_seated'])->toBeTrue()
        ->and($seatMap['levels'])->not->toBeEmpty();

    $firstLevelZones = collect($seatMap['levels'])->flatten(1);
    $vipZone = $firstLevelZones->firstWhere('code', 'VIP');
    $stdZone = $firstLevelZones->firstWhere('code', 'STD');

    expect($vipZone)->not->toBeNull()
        ->and($vipZone['color'])->toBe('#D4AF37')
        ->and($vipZone['price'])->toBe(280000) // base 200k + surcharge 80k
        ->and($stdZone['price'])->toBe(200000); // base 200k + surcharge 0k

    $firstRow = $vipZone['rows'][0];
    expect($firstRow['label'])->toBe('A');

    $firstSeat = $firstRow['seats'][0];
    expect($firstSeat)->toHaveKeys(['id', 'number', 'col', 'code', 'status', 'price'])
        ->and($firstSeat['code'])->toBe('A-01')
        ->and($firstSeat['status'])->toBe('available')
        ->and($firstSeat['price'])->toBe(280000);
});

test('TC_UC015_003: non-seated event returns standing seat zones with accurate available capacity', function () {
    $user = createCustomer();
    $showtime = createZonedShowtime('open_space', false, 150000);

    $response = $this->actingAs($user)->get(route('showtimes.seats', $showtime->id));

    $response->assertOk();
    $seatMap = $response->viewData('seatMap');

    expect($seatMap['is_seated'])->toBeFalse();

    $zones = collect($seatMap['levels'])->flatten(1);
    $vipZone = $zones->firstWhere('code', 'VIPX');
    $genZone = $zones->firstWhere('code', 'GEN');

    expect($vipZone['kind'])->toBe('standing')
        ->and($vipZone['available'])->toBe(50)
        ->and($vipZone['price'])->toBe(300000) // base 150k + surcharge 150k
        ->and($genZone['kind'])->toBe('standing')
        ->and($genZone['available'])->toBe(300)
        ->and($genZone['price'])->toBe(150000); // base 150k + surcharge 0k
});

test('TC_UC015_004: seatStatus API reports mixed seat statuses accurately: available, held, and booked', function () {
    $showtime = createZonedShowtime('theater_hall');
    $seats = $showtime->showtimeSeats()->take(3)->get();

    $availableSeat = $seats[0];
    $heldSeat = $seats[1];
    $bookedSeat = $seats[2];

    $heldUser = createCustomer();
    $heldSeat->update([
        'status' => 'held',
        'held_by_user_id' => $heldUser->id,
        'held_until' => now()->addMinutes(8),
    ]);

    $bookedSeat->update([
        'status' => 'booked',
    ]);

    $response = $this->getJson(route('showtimes.seat-status', $showtime->id));

    $response->assertOk();
    $unavailableIds = $response->json('unavailable');

    expect($unavailableIds)->toContain($heldSeat->id)
        ->and($unavailableIds)->toContain($bookedSeat->id)
        ->and($unavailableIds)->not->toContain($availableSeat->id);
});

test('TC_UC015_005: expired seat hold is treated as available by seatStatus API and seat map', function () {
    $showtime = createZonedShowtime('theater_hall');
    $expiredSeat = $showtime->showtimeSeats()->first();

    $heldUser = createCustomer();
    $expiredSeat->update([
        'status' => 'held',
        'held_by_user_id' => $heldUser->id,
        'held_until' => now()->subMinutes(2), // Đã hết hạn giữ chỗ
    ]);

    $response = $this->getJson(route('showtimes.seat-status', $showtime->id));

    $response->assertOk();
    $unavailableIds = $response->json('unavailable');

    // Ghế hết hạn không được xem là unavailable
    expect($unavailableIds)->not->toContain($expiredSeat->id)
        ->and($expiredSeat->fresh()->publicStatus())->toBe('available')
        ->and($expiredSeat->fresh()->isHoldable())->toBeTrue();
});

test('TC_UC015_006: standing zone capacity accurately subtracts booked seats and active held seats', function () {
    $showtime = createZonedShowtime('open_space', false);
    $genZone = SeatZone::where('room_id', $showtime->room_id)->where('code', 'GEN')->first();

    $genSeats = $showtime->showtimeSeats()
        ->whereHas('seat', fn ($q) => $q->where('zone_id', $genZone->id))
        ->get();

    $totalSeats = $genSeats->count(); // 300 seats

    // 1. Giả lập 10 vé đã bán (booked)
    $bookedSeats = $genSeats->slice(0, 10);
    foreach ($bookedSeats as $seat) {
        $seat->update(['status' => 'booked']);
    }

    // 2. Giả lập 5 vé đang được người khác giữ còn hiệu lực (held)
    $heldSeats = $genSeats->slice(10, 5);
    $heldUser = createCustomer();
    foreach ($heldSeats as $seat) {
        $seat->update([
            'status' => 'held',
            'held_by_user_id' => $heldUser->id,
            'held_until' => now()->addMinutes(5),
        ]);
    }

    // 3. Giả lập 3 vé giữ nhưng đã hết hạn (expired held -> vẫn tính là available)
    $expiredSeats = $genSeats->slice(15, 3);
    foreach ($expiredSeats as $seat) {
        $seat->update([
            'status' => 'held',
            'held_by_user_id' => $heldUser->id,
            'held_until' => now()->subMinute(),
        ]);
    }

    $seatMap = SeatMap::forShowtime($showtime);
    $genCard = collect($seatMap['levels'])->flatten(1)->firstWhere('code', 'GEN');

    // Số vé còn lại: 300 - 10 (booked) - 5 (active held) = 285
    expect($genCard['available'])->toBe($totalSeats - 10 - 5);
});

test('TC_UC015_007: payload enforces maximum 8 seats limit per booking and rejects 9 or more', function () {
    $user = createCustomer();
    $showtime = createZonedShowtime('custom_grid');

    $allSeats = $showtime->showtimeSeats()->take(10)->get();

    // 1. Chọn 8 ghế hợp lệ (<= 8) -> thành công
    $validIds = $allSeats->take(8)->pluck('id')->all();
    $validResponse = $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => $validIds,
    ]);
    $validResponse->assertOk()
        ->assertJsonStructure(['message', 'held_until', 'redirect']);

    // 2. Thử gửi 9 ghế (> 8) -> Validation error 422
    $invalidIds = $allSeats->take(9)->pluck('id')->all();
    $invalidResponse = $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => $invalidIds,
    ]);
    $invalidResponse->assertStatus(422)
        ->assertJsonValidationErrors(['seat_ids']);
});

test('TC_UC015_008: calculates subtotal accurately when selecting multiple seats across different tiers', function () {
    $showtime = createZonedShowtime('custom_grid', true, 200000);

    // Lấy 2 ghế VIP (surcharge 80k => 280k/ghế, 2 ghế = 560k)
    $vipZone = SeatZone::where('room_id', $showtime->room_id)->where('code', 'VIP')->first();
    $vipSeats = $showtime->showtimeSeats()
        ->whereHas('seat', fn ($q) => $q->where('zone_id', $vipZone->id))
        ->take(2)
        ->get();

    // Lấy 1 ghế STD (surcharge 0k => 200k/ghế)
    $stdZone = SeatZone::where('room_id', $showtime->room_id)->where('code', 'STD')->first();
    $stdSeat = $showtime->showtimeSeats()
        ->whereHas('seat', fn ($q) => $q->where('zone_id', $stdZone->id))
        ->first();

    $expectedSubtotal = ($vipZone->priceFor(200000) * 2) + $stdZone->priceFor(200000);
    // (280.000 * 2) + 200.000 = 760.000đ
    expect($expectedSubtotal)->toBe(760000);

    // Kiểm tra tính toán giá từng ghế theo model attribute
    expect($vipSeats[0]->effective_price)->toBe(280000)
        ->and($vipSeats[1]->effective_price)->toBe(280000)
        ->and($stdSeat->effective_price)->toBe(200000);
});

test('TC_UC015_009: standing tickets selection validates non-negative quantity, capacity ceiling, and max 8 limit', function () {
    $user = createCustomer();
    $showtime = createZonedShowtime('open_space', false);
    $vipZone = SeatZone::where('room_id', $showtime->room_id)->where('code', 'VIPX')->first();

    // 1. Không cho phép số vé âm (< 0) -> Validation error
    $negResponse = $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'zones' => [$vipZone->id => -1],
    ]);
    $negResponse->assertStatus(422);

    // 2. Không cho phép vượt quá tổng giới hạn 8 vé mỗi lần đặt
    $overResponse = $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'zones' => [$vipZone->id => 9],
    ]);
    $overResponse->assertStatus(422);

    // 3. Đặt số vé hợp lệ (ví dụ: 4 vé VIP) -> Thành công
    $okResponse = $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'zones' => [$vipZone->id => 4],
    ]);
    $okResponse->assertOk()
        ->assertJsonStructure(['message', 'held_until', 'redirect']);
});

test('TC_UC015_010: unauthenticated guest accessing seats page is intercepted by auth middleware and redirected to login', function () {
    $showtime = createZonedShowtime('theater_hall');

    $this->assertGuest();
    $response = $this->get(route('showtimes.seats', $showtime->id));

    $response->assertRedirect(route('login'));
});

test('TC_UC015_011: accessing seats selection page with non-existent showtime id returns 404', function () {
    $user = createCustomer();

    $response = $this->actingAs($user)->get('/showtimes/99999999/seats');

    $response->assertNotFound();
});

test('TC_UC015_012: showtimes belonging to draft or archived events are inaccessible and return 404', function () {
    $user = createCustomer();

    // 1. Suất diễn của sự kiện bản nháp (draft)
    $draftShowtime = createZonedShowtime('theater_hall', true, 200000, 'draft');
    $this->actingAs($user)->get(route('showtimes.seats', $draftShowtime->id))->assertNotFound();

    // 2. Suất diễn của sự kiện lưu trữ (archived)
    $archivedShowtime = createZonedShowtime('theater_hall', true, 200000, 'archived');
    $this->actingAs($user)->get(route('showtimes.seats', $archivedShowtime->id))->assertNotFound();
});

test('TC_UC015_013: showtime past its start time is blocked from ticket selection with expired notice', function () {
    $user = createCustomer();

    // Suất diễn đã bắt đầu trong quá khứ (cách đây 2 tiếng)
    $pastStartTime = now()->subHours(2);
    $pastShowtime = createZonedShowtime('theater_hall', true, 200000, 'published', $pastStartTime);

    $response = $this->actingAs($user)->get(route('showtimes.seats', $pastShowtime->id));

    // Hệ thống chặn và điều hướng về trang sự kiện kèm thông báo lỗi
    $response->assertRedirect(route('events.show', $pastShowtime->event->slug));
    $response->assertSessionHas('error', 'Suất diễn đã kết thúc hoặc hết hạn mở bán.');
});

