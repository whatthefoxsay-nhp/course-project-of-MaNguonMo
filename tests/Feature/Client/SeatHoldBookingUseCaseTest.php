<?php

use App\Models\Discount;
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
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

function setupShowtime(string $preset = 'custom_grid', bool $isSeated = true, int $basePrice = 200000, string $status = 'published', $startTime = null): Showtime
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

test('TC_UC016_001: hold 1 to 8 available seats successfully and receive valid redirect to cart', function () {
    $user = createCustomer();
    $showtime = setupShowtime('custom_grid');

    $seats = $showtime->showtimeSeats()->take(3)->get();
    $seatIds = $seats->pluck('id')->all();

    $response = $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => $seatIds,
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Đã giữ 3 chỗ trong 10 phút.',
            'redirect' => route('cart.index'),
        ])
        ->assertJsonStructure(['held_until']);
});

test('TC_UC016_002: verifies DB updates to held status, held_by_user_id, and held_until set to 10 minutes ahead', function () {
    $user = createCustomer();
    $showtime = setupShowtime('custom_grid');

    $seats = $showtime->showtimeSeats()->take(2)->get();
    $seatIds = $seats->pluck('id')->all();

    $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => $seatIds,
    ])->assertOk();

    foreach ($seatIds as $id) {
        $seat = ShowtimeSeat::find($id);
        expect($seat->status)->toBe('held')
            ->and($seat->held_by_user_id)->toBe($user->id)
            ->and($seat->held_until)->not->toBeNull()
            ->and($seat->held_until->greaterThan(now()->addMinutes(9)))
            ->and($seat->held_until->lessThanOrEqualTo(now()->addMinutes(10)->addSeconds(5)));
    }
});

test('TC_UC016_003: hold standing tickets for non-seated event and reduces available capacity accordingly', function () {
    $user = createCustomer();
    $showtime = setupShowtime('open_space', false);

    $genZone = SeatZone::where('room_id', $showtime->room_id)->where('code', 'GEN')->first();
    $initialMap = SeatMap::forShowtime($showtime);
    $initialAvailable = collect($initialMap['levels'])->flatten(1)->firstWhere('code', 'GEN')['available']; // 300

    $response = $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'zones' => [$genZone->id => 4],
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Đã giữ 4 chỗ trong 10 phút.',
            'redirect' => route('cart.index'),
        ]);

    $newMap = SeatMap::forShowtime($showtime);
    $newAvailable = collect($newMap['levels'])->flatten(1)->firstWhere('code', 'GEN')['available'];

    expect($newAvailable)->toBe($initialAvailable - 4);
});

test('TC_UC016_004: re-hold an expired held seat by previous user transfers ownership to current user', function () {
    $userA = createCustomer();
    $userB = createCustomer();
    $showtime = setupShowtime('custom_grid');

    $seat = $showtime->showtimeSeats()->first();
    // User A từng giữ nhưng đã quá hạn 2 phút
    $seat->update([
        'status' => 'held',
        'held_by_user_id' => $userA->id,
        'held_until' => now()->subMinutes(2),
    ]);

    // User B vào đặt ghế đó
    $response = $this->actingAs($userB)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => [$seat->id],
    ]);

    $response->assertOk();

    $seat->refresh();
    expect($seat->status)->toBe('held')
        ->and($seat->held_by_user_id)->toBe($userB->id)
        ->and($seat->held_until->isFuture())->toBeTrue();
});

test('TC_UC016_005: accesses /cart successfully displaying held seats, unit prices, subtotal, and countdown', function () {
    $user = createCustomer();
    $showtime = setupShowtime('custom_grid', true, 200000);

    // Giữ 2 ghế
    $seats = $showtime->showtimeSeats()->take(2)->get();
    $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => $seats->pluck('id')->all(),
    ]);

    $response = $this->actingAs($user)->get(route('cart.index'));

    $response->assertOk();
    $items = $response->viewData('items');
    $total = $response->viewData('total');
    $expiresInSeconds = $response->viewData('expiresInSeconds');

    expect(count($items))->toBe(2)
        ->and($total)->toBeGreaterThan(0)
        ->and($expiresInSeconds)->toBeGreaterThan(500) // ~10 phút = 600s
        ->and($response->assertSee($showtime->event->title));
});

test('TC_UC016_006: remove a seat from cart restores it immediately to available and recalculates cart total', function () {
    $user = createCustomer();
    $showtime = setupShowtime('custom_grid');

    $seats = $showtime->showtimeSeats()->take(2)->get();
    $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => $seats->pluck('id')->all(),
    ]);

    $seatToRemove = $seats[0];
    $seatToKeep = $seats[1];

    $deleteResponse = $this->actingAs($user)->deleteJson(route('cart.seats.destroy', $seatToRemove->id));

    $deleteResponse->assertOk()
        ->assertJson(['message' => 'Đã bỏ ghế khỏi giỏ vé.']);

    $seatToRemove->refresh();
    expect($seatToRemove->status)->toBe('available')
        ->and($seatToRemove->held_by_user_id)->toBeNull()
        ->and($seatToRemove->held_until)->toBeNull();

    // Ghế còn lại vẫn trong giỏ của user
    $seatToKeep->refresh();
    expect($seatToKeep->status)->toBe('held')
        ->and($seatToKeep->held_by_user_id)->toBe($user->id);

    // Kiểm tra trang giỏ hàng chỉ còn 1 ghế
    $cartResponse = $this->actingAs($user)->get(route('cart.index'));
    expect(count($cartResponse->viewData('items')))->toBe(1);
});

test('TC_UC016_007: apply valid percentage discount voucher with max discount cap calculates correctly', function () {
    $user = createCustomer();
    $showtime = setupShowtime('custom_grid', true, 300000);

    // Giữ 2 ghế STD (2 x 300k = 600k)
    $stdZone = SeatZone::where('room_id', $showtime->room_id)->where('code', 'STD')->first();
    $seats = $showtime->showtimeSeats()
        ->whereHas('seat', fn ($q) => $q->where('zone_id', $stdZone->id))
        ->take(2)
        ->get();

    $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => $seats->pluck('id')->all(),
    ]);

    // Tạo voucher giảm 20%, tối đa 100.000đ, đơn tối thiểu 200.000đ
    // 600.000đ x 20% = 120.000đ => capped at 100.000đ => còn lại 500.000đ
    Discount::create([
        'code' => 'SALE20MAX100',
        'title' => 'Giảm 20% tối đa 100k',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'max_discount_amount' => 100000,
        'min_order_value' => 200000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->postJson(route('cart.discount'), [
        'code' => 'sale20max100', // Test tự động hoa
    ]);

    $response->assertOk()
        ->assertJson([
            'discount_code' => 'SALE20MAX100',
            'discount_amount' => 100000,
            'subtotal' => 600000,
            'final_total' => 500000,
        ]);
});

test('TC_UC016_008: apply valid fixed amount discount voucher subtracts exact promo value', function () {
    $user = createCustomer();
    $showtime = setupShowtime('custom_grid', true, 200000);

    $seats = $showtime->showtimeSeats()->take(2)->get();
    $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => $seats->pluck('id')->all(),
    ]);

    // Lấy subtotal giỏ vé thực tế
    $subtotal = $seats->sum(fn ($s) => $s->effective_price);

    Discount::create([
        'code' => 'GIAM50K',
        'title' => 'Giảm cố định 50k',
        'discount_type' => 'fixed',
        'discount_value' => 50000,
        'min_order_value' => 100000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->postJson(route('cart.discount'), [
        'code' => 'GIAM50K',
    ]);

    $response->assertOk()
        ->assertJson([
            'discount_code' => 'GIAM50K',
            'discount_amount' => 50000,
            'subtotal' => $subtotal,
            'final_total' => $subtotal - 50000,
        ]);
});

test('TC_UC016_009: rejects invalid discount vouchers across all business validation constraints', function () {
    $user = createCustomer();
    $showtime = setupShowtime('custom_grid', true, 100000);

    $seats = $showtime->showtimeSeats()->take(2)->get(); // subtotal ~ 200k - 280k
    $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => $seats->pluck('id')->all(),
    ]);

    // 1. Mã không tồn tại
    $this->actingAs($user)->postJson(route('cart.discount'), ['code' => 'NONEXISTENT'])
        ->assertStatus(422);

    // 2. Mã đã tạm tắt (is_active = false)
    Discount::create([
        'code' => 'DISABLED_VOUCHER',
        'title' => 'Mã tạm tắt',
        'discount_type' => 'fixed',
        'discount_value' => 20000,
        'is_active' => false,
    ]);
    $this->actingAs($user)->postJson(route('cart.discount'), ['code' => 'DISABLED_VOUCHER'])
        ->assertStatus(422);

    // 3. Mã đã hết hạn sử dụng (end_date in past)
    Discount::create([
        'code' => 'EXPIRED_VOUCHER',
        'title' => 'Mã hết hạn',
        'discount_type' => 'fixed',
        'discount_value' => 20000,
        'end_date' => now()->subDay(),
        'is_active' => true,
    ]);
    $this->actingAs($user)->postJson(route('cart.discount'), ['code' => 'EXPIRED_VOUCHER'])
        ->assertStatus(422);

    // 4. Mã đã hết số lượt dùng tối đa (used_count >= max_uses)
    Discount::create([
        'code' => 'MAXED_VOUCHER',
        'title' => 'Mã hết lượt',
        'discount_type' => 'fixed',
        'discount_value' => 20000,
        'max_uses' => 5,
        'used_count' => 5,
        'is_active' => true,
    ]);
    $this->actingAs($user)->postJson(route('cart.discount'), ['code' => 'MAXED_VOUCHER'])
        ->assertStatus(422);

    // 5. Đơn hàng chưa đạt mức tối thiểu (min_order_value = 5.000.000đ)
    Discount::create([
        'code' => 'HIGH_MIN_VOUCHER',
        'title' => 'Mã đơn cao',
        'discount_type' => 'fixed',
        'discount_value' => 200000,
        'min_order_value' => 5000000,
        'is_active' => true,
    ]);
    $this->actingAs($user)->postJson(route('cart.discount'), ['code' => 'HIGH_MIN_VOUCHER'])
        ->assertStatus(422);
});

test('TC_UC016_010: race condition conflict with active held seat by another user cancels entire operation', function () {
    $userA = createCustomer();
    $userB = createCustomer();
    $showtime = setupShowtime('custom_grid');

    $seats = $showtime->showtimeSeats()->take(3)->get();
    $seat1 = $seats[0];
    $seat2 = $seats[1];
    $seat3 = $seats[2];

    // User A đang giữ seat2 còn hiệu lực 7 phút
    $seat2->update([
        'status' => 'held',
        'held_by_user_id' => $userA->id,
        'held_until' => now()->addMinutes(7),
    ]);

    // User B cố gắng đặt cả [seat1, seat2, seat3]
    $response = $this->actingAs($userB)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => [$seat1->id, $seat2->id, $seat3->id],
    ]);

    $response->assertStatus(422);

    // Xác nhận giao dịch bị rollback: seat1 và seat3 vẫn available, không bị User B giữ dở dang
    expect($seat1->fresh()->status)->toBe('available')
        ->and($seat3->fresh()->status)->toBe('available')
        ->and($seat2->fresh()->held_by_user_id)->toBe($userA->id);
});

test('TC_UC016_011: conflict with booked seat triggers rollback and prevents partial holds', function () {
    $user = createCustomer();
    $showtime = setupShowtime('custom_grid');

    $seats = $showtime->showtimeSeats()->take(3)->get();
    $seat1 = $seats[0];
    $seat2 = $seats[1];
    $seat3 = $seats[2];

    // seat2 đã được bán chính thức (booked)
    $seat2->update(['status' => 'booked']);

    $response = $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => [$seat1->id, $seat2->id, $seat3->id],
    ]);

    $response->assertStatus(422);

    // Không có ghế nào bị giữ
    expect($seat1->fresh()->status)->toBe('available')
        ->and($seat3->fresh()->status)->toBe('available');
});

test('TC_UC016_012: artisan seats:release-holds command releases expired holds after 11 minutes while retaining active holds', function () {
    $userA = createCustomer();
    $userB = createCustomer();
    $showtime = setupShowtime('custom_grid');

    $seats = $showtime->showtimeSeats()->take(2)->get();
    $expiredSeat = $seats[0];
    $activeSeat = $seats[1];

    // Ghế 1: Giữ từ lúc này
    $expiredSeat->update([
        'status' => 'held',
        'held_by_user_id' => $userA->id,
        'held_until' => now()->addMinutes(10),
    ]);

    // Giả lập thời gian trôi qua 11 phút
    $this->travel(11)->minutes();

    // Ghế 2: Mới được giữ lúc này (sau khi travel 11 phút) -> còn hạn
    $activeSeat->update([
        'status' => 'held',
        'held_by_user_id' => $userB->id,
        'held_until' => now()->addMinutes(10),
    ]);

    // Chạy lệnh Artisan nhả ghế hết hạn
    Artisan::call('seats:release-holds');

    $expiredSeat->refresh();
    $activeSeat->refresh();

    // Ghế 1 hết hạn tự động chuyển về available
    expect($expiredSeat->status)->toBe('available')
        ->and($expiredSeat->held_by_user_id)->toBeNull()
        ->and($expiredSeat->held_until)->toBeNull();

    // Ghế 2 còn hạn vẫn giữ nguyên
    expect($activeSeat->status)->toBe('held')
        ->and($activeSeat->held_by_user_id)->toBe($userB->id);
});

test('TC_UC016_013: empty payload or exceeding boundary of 8 seats is rejected with validation error', function () {
    $user = createCustomer();
    $showtime = setupShowtime('custom_grid');

    // 1. Danh sách ghế rỗng
    $emptyResponse = $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => [],
    ]);
    $emptyResponse->assertStatus(422);

    // 2. Vượt quá 8 ghế (gửi 9 ghế)
    $nineSeatIds = $showtime->showtimeSeats()->take(9)->pluck('id')->all();
    $exceedResponse = $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime->id), [
        'seat_ids' => $nineSeatIds,
    ]);
    $exceedResponse->assertStatus(422);
});

test('TC_UC016_014: seat IDs belonging to another showtime are rejected preventing IDOR payload tampering', function () {
    $user = createCustomer();
    $showtime1 = setupShowtime('custom_grid');
    $showtime2 = setupShowtime('custom_grid');

    // Lấy ghế thuộc showtime 2
    $foreignSeatId = $showtime2->showtimeSeats()->first()->id;

    // Gửi request giữ ghế của showtime 2 vào endpoint của showtime 1
    $response = $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime1->id), [
        'seat_ids' => [$foreignSeatId],
    ]);

    $response->assertStatus(422)
        ->assertJson(['message' => 'Có ghế không thuộc suất diễn này.']);
});

test('TC_UC016_015: holding seats for past showtimes or unpublished events is rejected', function () {
    $user = createCustomer();

    // 1. Suất diễn trong quá khứ
    $pastShowtime = setupShowtime('custom_grid', true, 200000, 'published', now()->subHours(1));
    $pastSeatId = $pastShowtime->showtimeSeats()->first()->id;

    $this->actingAs($user)->postJson(route('showtimes.holds.store', $pastShowtime->id), [
        'seat_ids' => [$pastSeatId],
    ])->assertStatus(422)
      ->assertJson(['message' => 'Suất diễn đã bắt đầu, không thể đặt vé.']);

    // 2. Sự kiện bản nháp (draft)
    $draftShowtime = setupShowtime('custom_grid', true, 200000, 'draft');
    $draftSeatId = $draftShowtime->showtimeSeats()->first()->id;

    $this->actingAs($user)->postJson(route('showtimes.holds.store', $draftShowtime->id), [
        'seat_ids' => [$draftSeatId],
    ])->assertStatus(422)
      ->assertJson(['message' => 'Sự kiện hiện không mở bán.']);
});

test('TC_UC016_016: unauthenticated guest is redirected to login for holding, cart viewing, and discounting', function () {
    $showtime = setupShowtime('custom_grid');
    $seatId = $showtime->showtimeSeats()->first()->id;

    $this->assertGuest();

    // 1. Giữ chỗ không đăng nhập
    $this->postJson(route('showtimes.holds.store', $showtime->id), ['seat_ids' => [$seatId]])
        ->assertUnauthorized();

    // 2. Vào giỏ hàng không đăng nhập
    $this->get(route('cart.index'))
        ->assertRedirect(route('login'));

    // 3. Áp dụng mã giảm giá không đăng nhập
    $this->postJson(route('cart.discount'), ['code' => 'TEST'])
        ->assertUnauthorized();
});

test('TC_UC016_017: strict cart isolation between users: User A cannot view or delete seats held by User B', function () {
    $userA = createCustomer();
    $userB = createCustomer();
    $showtime = setupShowtime('custom_grid');

    $seats = $showtime->showtimeSeats()->take(2)->get();
    $seatA = $seats[0];
    $seatB = $seats[1];

    // User A giữ seatA
    $this->actingAs($userA)->postJson(route('showtimes.holds.store', $showtime->id), ['seat_ids' => [$seatA->id]]);

    // User B giữ seatB
    $this->actingAs($userB)->postJson(route('showtimes.holds.store', $showtime->id), ['seat_ids' => [$seatB->id]]);

    // 1. User A vào giỏ chỉ thấy seatA, không thấy seatB
    $responseA = $this->actingAs($userA)->get(route('cart.index'));
    $itemsA = $responseA->viewData('items');
    expect(count($itemsA))->toBe(1)
        ->and($itemsA[0]->id)->toBe($seatA->id);

    // 2. User A cố xóa seatB của User B -> bị chặn 422
    $deleteAttempt = $this->actingAs($userA)->deleteJson(route('cart.seats.destroy', $seatB->id));
    $deleteAttempt->assertStatus(422);

    // Ghế của User B vẫn an toàn
    expect($seatB->fresh()->status)->toBe('held')
        ->and($seatB->fresh()->held_by_user_id)->toBe($userB->id);
});

