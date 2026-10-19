<?php

use App\Models\Booking;
use App\Models\Discount;
use App\Models\Event;
use App\Models\Showtime;
use App\Services\CheckoutService;
use App\Services\SeatHoldService;

beforeEach(fn () => $this->seed());

function completionShowtime(): Showtime
{
    return Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026')
        ->showtimes()->orderBy('start_time')->first();
}

function holdCompletionSeats($user, int $count = 2, ?Showtime $showtime = null): Showtime
{
    $showtime ??= completionShowtime();
    $ids = $showtime->showtimeSeats()->where('status', 'available')->orderBy('id')->take($count)->pluck('id')->all();
    app(SeatHoldService::class)->hold($user, $showtime, $ids);

    return $showtime;
}

function checkoutPayload(array $overrides = []): array
{
    return array_merge([
        'payment_method' => 'vietqr',
        'buyer_name' => 'Trần Thị Người Nhận',
        'buyer_phone' => '0912 345 678',
        'buyer_email' => 'nguoinhan@example.com',
    ], $overrides);
}

test('checkout stores the buyer information sent from the cart', function () {
    $user = createCustomer(['name' => 'Chủ Tài Khoản']);
    holdCompletionSeats($user);

    $code = $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload())
        ->assertOk()->json('booking_code');

    $booking = Booking::firstWhere('booking_code', $code);
    expect($booking->buyer_name)->toBe('Trần Thị Người Nhận')
        ->and($booking->buyer_phone)->toBe('0912 345 678')
        ->and($booking->buyer_email)->toBe('nguoinhan@example.com');
});

test('checkout requires buyer name and a valid phone number', function () {
    $user = createCustomer();
    holdCompletionSeats($user);

    $this->actingAs($user)
        ->postJson(route('checkout.store'), checkoutPayload(['buyer_name' => '', 'buyer_phone' => 'abc', 'buyer_email' => 'khong-phai-email']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['buyer_name', 'buyer_phone', 'buyer_email']);

    expect(Booking::where('user_id', $user->id)->exists())->toBeFalse();
});

test('buyer email is optional', function () {
    $user = createCustomer();
    holdCompletionSeats($user);

    $code = $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload(['buyer_email' => '']))
        ->assertOk()->json('booking_code');

    expect(Booking::firstWhere('booking_code', $code)->buyer_email)->toBeNull();
});

test('booking history and admin detail show the buyer information', function () {
    $user = createCustomer();
    holdCompletionSeats($user);
    $code = $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload())->json('booking_code');
    $booking = Booking::firstWhere('booking_code', $code);

    $this->actingAs($user)->get(route('bookings.history'))
        ->assertOk()
        ->assertSee('Trần Thị Người Nhận')
        ->assertSee('0912 345 678');

    $this->actingAs(createAdmin())->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Người Nhận Vé')
        ->assertSee('Trần Thị Người Nhận')
        ->assertSee('nguoinhan@example.com');
});

test('the cart buyer form no longer asks for national id or address', function () {
    $user = createCustomer(['name' => 'Lê Văn Điền Sẵn']);
    holdCompletionSeats($user, 1);

    $this->actingAs($user)->get(route('cart.index'))
        ->assertOk()
        ->assertSee('for="buyer-name"', false)
        ->assertSee('id="buyer-phone"', false)
        ->assertSee('Lê Văn Điền Sẵn')
        ->assertDontSee('CCCD')
        ->assertDontSee('Địa chỉ liên hệ');
});

test('checkout is refused once the showtime has started', function () {
    $user = createCustomer();
    $showtime = holdCompletionSeats($user, 1);
    $showtime->update(['start_time' => now()->subMinute()]);

    $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload())
        ->assertStatus(422)
        ->assertJsonPath('message', 'Suất diễn đã bắt đầu, không thể thanh toán.');

    expect(Booking::where('user_id', $user->id)->exists())->toBeFalse();
});

test('checkout is refused when the event is no longer published', function () {
    $user = createCustomer();
    $showtime = holdCompletionSeats($user, 1);
    $showtime->event->update(['status' => 'draft']);

    $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload())
        ->assertStatus(422)
        ->assertJsonPath('message', 'Sự kiện hiện không mở bán, không thể thanh toán.');

    expect(Booking::where('user_id', $user->id)->exists())->toBeFalse();
});

function makeDiscount(array $overrides = []): Discount
{
    return Discount::create(array_merge([
        'code' => 'GIAM10',
        'title' => 'Giảm 10%',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'min_order_value' => 0,
        'max_discount_amount' => null,
        'max_uses' => 5,
        'used_count' => 0,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'is_active' => true,
    ], $overrides));
}

test('the cart discount endpoint previews the discounted total from server-side prices', function () {
    $user = createCustomer();
    holdCompletionSeats($user, 2);
    $subtotal = app(CheckoutService::class)->heldSubtotal($user);
    makeDiscount();

    $this->actingAs($user)->postJson(route('cart.discount'), ['code' => 'giam10'])
        ->assertOk()
        ->assertJsonPath('code', 'GIAM10')
        ->assertJsonPath('subtotal', $subtotal)
        ->assertJsonPath('discount_amount', (int) round($subtotal * 0.1))
        ->assertJsonPath('total', $subtotal - (int) round($subtotal * 0.1));
});

test('a discount is usable for the whole of its end date', function () {
    $this->travelTo(now()->setTime(23, 30));
    $user = createCustomer();
    holdCompletionSeats($user, 1);
    makeDiscount(['end_date' => now()->toDateString()]);

    $this->actingAs($user)->postJson(route('cart.discount'), ['code' => 'GIAM10'])->assertOk();
});

test('invalid discounts are rejected with a vietnamese message', function (array $overrides, string $message) {
    $user = createCustomer();
    holdCompletionSeats($user, 1);
    makeDiscount($overrides);

    $this->actingAs($user)->postJson(route('cart.discount'), ['code' => 'GIAM10'])
        ->assertStatus(422)
        ->assertJsonPath('message', $message);
})->with([
    'inactive' => [['is_active' => false], 'Mã giảm giá không tồn tại hoặc đã ngừng áp dụng.'],
    'not started' => [['start_date' => now()->addDays(2)->toDateString()], 'Mã giảm giá chưa đến thời gian áp dụng.'],
    'expired' => [['end_date' => now()->subDay()->toDateString(), 'start_date' => now()->subDays(5)->toDateString()], 'Mã giảm giá đã hết hạn.'],
    'used up' => [['used_count' => 5], 'Mã giảm giá đã hết lượt sử dụng.'],
    'min order' => [['min_order_value' => 999999999], 'Đơn hàng cần tối thiểu 999.999.999₫ để dùng mã này.'],
]);

test('unknown discount codes are rejected', function () {
    $user = createCustomer();
    holdCompletionSeats($user, 1);

    $this->actingAs($user)->postJson(route('cart.discount'), ['code' => 'KHONGCO'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');
});

test('checkout applies the discount server-side and records it on the booking', function () {
    $user = createCustomer();
    holdCompletionSeats($user, 2);
    $subtotal = app(CheckoutService::class)->heldSubtotal($user);
    $discount = makeDiscount(['discount_type' => 'fixed', 'discount_value' => 50000, 'used_count' => 2]);

    $code = $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload(['discount_code' => 'GIAM10']))
        ->assertOk()
        ->assertJsonPath('total_price', $subtotal - 50000)
        ->json('booking_code');

    $booking = Booking::firstWhere('booking_code', $code);
    expect($booking->discount_id)->toBe($discount->id)
        ->and($booking->discount_amount)->toBe(50000)
        ->and($booking->total_price)->toBe($subtotal - 50000)
        ->and($booking->items->sum('price'))->toBe($subtotal)
        ->and($discount->fresh()->used_count)->toBe(3);

    $this->actingAs($user)->get(route('bookings.history'))->assertSee('Đã giảm 50.000₫ (mã GIAM10)');
    $this->actingAs(createAdmin())->get(route('admin.bookings.show', $booking))->assertSee('Mã giảm giá: GIAM10');
});

test('percentage discounts respect the maximum discount amount', function () {
    $user = createCustomer();
    holdCompletionSeats($user, 2);
    $subtotal = app(CheckoutService::class)->heldSubtotal($user);
    makeDiscount(['discount_value' => 50, 'max_discount_amount' => 1000]);

    $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload(['discount_code' => 'GIAM10']))
        ->assertOk()
        ->assertJsonPath('total_price', $subtotal - 1000);
});

test('checkout refuses a discount that ran out and does not book', function () {
    $user = createCustomer();
    holdCompletionSeats($user, 1);
    $discount = makeDiscount(['used_count' => 5]);

    $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload(['discount_code' => 'GIAM10']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('discount_code');

    expect(Booking::where('user_id', $user->id)->exists())->toBeFalse()
        ->and($discount->fresh()->used_count)->toBe(5);
});

test('deleting a discount keeps the booking but clears the link', function () {
    $user = createCustomer();
    holdCompletionSeats($user, 1);
    $discount = makeDiscount(['discount_type' => 'fixed', 'discount_value' => 1000]);
    $code = $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload(['discount_code' => 'GIAM10']))->json('booking_code');

    $discount->delete();

    $booking = Booking::firstWhere('booking_code', $code);
    expect($booking->discount_id)->toBeNull()->and($booking->discount_amount)->toBe(1000);
});

test('holding another seat extends the earlier holds so checkout books the whole cart', function () {
    $user = createCustomer();
    $showtime = completionShowtime();
    $ids = $showtime->showtimeSeats()->where('status', 'available')->orderBy('id')->take(2)->pluck('id')->all();

    app(SeatHoldService::class)->hold($user, $showtime, [$ids[0]]);
    $this->travel(8)->minutes();
    app(SeatHoldService::class)->hold($user, $showtime, [$ids[1]]);
    $this->travel(5)->minutes(); // 13 phút sau lần giữ đầu tiên

    $code = $this->actingAs($user)->postJson(route('checkout.store'), checkoutPayload())
        ->assertOk()->json('booking_code');

    expect(Booking::firstWhere('booking_code', $code)->items()->count())->toBe(2);
});
