<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\Showtime;
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
