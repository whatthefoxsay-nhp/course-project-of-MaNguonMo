<?php

use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use App\Models\Discount;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Services\RoomSeatGenerator;
use App\Services\SeatHoldService;
use App\Services\ShowtimeSeatGenerator;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->withoutMiddleware(ThrottleRequests::class);
    Mail::fake();
});

function createCheckoutCustomer(array $attributes = []): User
{
    $user = User::factory()->create(array_merge([
        'name' => 'Nguyễn Minh Quân',
        'email' => 'customer_'.uniqid().'@ticketbox.test',
        'phone' => '0912345678',
        'is_active' => true,
    ], $attributes));

    $user->assignRole('user');

    return $user;
}

function setupCheckoutHold(User $user, int $count = 2, int $basePrice = 250000): array
{
    $room = Room::factory()->create(['layout_preset' => 'custom_grid']);
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid');

    $event = Event::factory()->create([
        'is_seated' => true,
        'status' => 'published',
    ]);

    $showtime = Showtime::factory()->create([
        'event_id' => $event->id,
        'room_id' => $room->id,
        'base_price' => $basePrice,
        'start_time' => now()->addDays(5)->setTime(20, 0),
        'end_time' => now()->addDays(5)->setTime(23, 0),
    ]);

    app(ShowtimeSeatGenerator::class)->generate($showtime);

    $seats = $showtime->showtimeSeats()->take($count)->get();
    $seatIds = $seats->pluck('id')->all();

    app(SeatHoldService::class)->hold($user, $showtime, $seatIds);

    return [$showtime, $seats->fresh()];
}

test('TC_UC019_001: successful payment with VietQR generates confirmed booking and redirects to ticket', function () {
    $user = createCheckoutCustomer();
    setupCheckoutHold($user, 2);

    $payload = [
        'payment_method' => 'vietqr',
        'delivery_name' => 'Nguyễn Minh Quân',
        'delivery_email' => 'quan.nguyen@ticketbox.test',
        'delivery_phone' => '0912345678',
    ];

    $response = $this->actingAs($user)
        ->postJson(route('checkout.store'), $payload)
        ->assertOk()
        ->assertJsonStructure([
            'message',
            'booking_code',
            'total_price',
            'qr_data_uri',
            'redirect',
        ]);

    $booking = Booking::firstWhere('booking_code', $response->json('booking_code'));

    expect($booking)->not->toBeNull()
        ->and($booking->user_id)->toBe($user->id)
        ->and($booking->status)->toBe('confirmed')
        ->and($booking->payment_method)->toBe('vietqr')
        ->and($booking->buyer_name)->toBe('Nguyễn Minh Quân')
        ->and($booking->buyer_email)->toBe('quan.nguyen@ticketbox.test')
        ->and($booking->buyer_phone)->toBe('0912345678')
        ->and($response->json('redirect'))->toBe(route('bookings.ticket', $booking->id));
});

test('TC_UC019_002: successful payment with MoMo e-wallet stores correct payment method and confirms booking', function () {
    $user = createCheckoutCustomer();
    setupCheckoutHold($user, 1);

    $response = $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'momo',
            'delivery_name' => $user->name,
            'delivery_email' => $user->email,
            'delivery_phone' => '0987654321',
        ])
        ->assertOk();

    $booking = Booking::firstWhere('booking_code', $response->json('booking_code'));

    expect($booking)->not->toBeNull()
        ->and($booking->payment_method)->toBe('momo')
        ->and($booking->status)->toBe('confirmed');
});

test('TC_UC019_003: successful payment with ZaloPay e-wallet stores correct payment method and confirms booking', function () {
    $user = createCheckoutCustomer();
    setupCheckoutHold($user, 2);

    $response = $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'zalopay',
            'delivery_name' => $user->name,
            'delivery_email' => $user->email,
            'delivery_phone' => '0933112233',
        ])
        ->assertOk();

    $booking = Booking::firstWhere('booking_code', $response->json('booking_code'));

    expect($booking)->not->toBeNull()
        ->and($booking->payment_method)->toBe('zalopay')
        ->and($booking->status)->toBe('confirmed');
});

test('TC_UC019_004: successful payment with card stores correct payment method and confirms booking', function () {
    $user = createCheckoutCustomer();
    setupCheckoutHold($user, 1);

    $response = $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'card',
            'delivery_name' => $user->name,
            'delivery_email' => $user->email,
            'delivery_phone' => '0909123456',
        ])
        ->assertOk();

    $booking = Booking::firstWhere('booking_code', $response->json('booking_code'));

    expect($booking)->not->toBeNull()
        ->and($booking->payment_method)->toBe('card')
        ->and($booking->status)->toBe('confirmed');
});

test('TC_UC019_005: seat status updates to booked and releases temporary hold attributes upon checkout', function () {
    $user = createCheckoutCustomer();
    [$showtime, $seats] = setupCheckoutHold($user, 2);

    $response = $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'vietqr',
            'delivery_name' => $user->name,
            'delivery_email' => $user->email,
            'delivery_phone' => '0912345678',
        ])
        ->assertOk();

    $booking = Booking::with('items')->firstWhere('booking_code', $response->json('booking_code'));

    expect($booking->items)->toHaveCount(2);

    foreach ($seats as $seat) {
        $seat->refresh();
        expect($seat->status)->toBe('booked')
            ->and($seat->held_by_user_id)->toBeNull()
            ->and($seat->held_until)->toBeNull();

        $item = $booking->items->firstWhere('showtime_seat_id', $seat->id);
        expect($item)->not->toBeNull()
            ->and($item->price)->toBe($seat->price_override ?? $seat->effective_price);
    }
});

test('TC_UC019_006: sends confirmation email with e-ticket to the designated delivery email address', function () {
    $user = createCheckoutCustomer();
    setupCheckoutHold($user, 1);

    $recipientEmail = 'vip_guest_recipient@ticketbox.test';

    $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'vietqr',
            'delivery_name' => 'Khách VIP',
            'delivery_email' => $recipientEmail,
            'delivery_phone' => '0912345678',
        ])
        ->assertOk();

    Mail::assertQueued(BookingConfirmationMail::class, function (BookingConfirmationMail $mail) use ($recipientEmail) {
        return $mail->hasTo($recipientEmail);
    });
});

test('TC_UC019_007: checkout without discount code totals the exact sum of all held seats', function () {
    $user = createCheckoutCustomer();
    [$showtime, $seats] = setupCheckoutHold($user, 2, 350000);

    // Ghế thứ 2 có giá override
    $seats[1]->update(['price_override' => 500000]);

    $expectedTotal = (int) ($seats[0]->effective_price + 500000);

    $response = $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'momo',
            'delivery_name' => $user->name,
            'delivery_email' => $user->email,
            'delivery_phone' => '0912345678',
        ])
        ->assertOk();

    $booking = Booking::firstWhere('booking_code', $response->json('booking_code'));

    expect($booking->total_price)->toBe($expectedTotal)
        ->and($booking->discount_id)->toBeNull()
        ->and($booking->discount_amount)->toBe(0);
});

test('TC_UC019_008: checkout with voucher discount applies deduction, stores discount link and increments used count', function () {
    $user = createCheckoutCustomer();
    [$showtime, $seats] = setupCheckoutHold($user, 2, 200000); // Subtotal = 400.000₫

    $discount = Discount::create([
        'code' => 'SUPERVIP20',
        'title' => 'Giảm 20% tối đa 50K',
        'discount_type' => 'percentage',
        'discount_value' => 20, // 20% của 400k = 80k -> trần 50k
        'min_order_value' => 100000,
        'max_discount_amount' => 50000,
        'max_uses' => 100,
        'used_count' => 0,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'vietqr',
            'delivery_name' => $user->name,
            'delivery_email' => $user->email,
            'delivery_phone' => '0912345678',
            'discount_code' => 'SUPERVIP20',
        ])
        ->assertOk();

    $booking = Booking::firstWhere('booking_code', $response->json('booking_code'));

    $subtotal = (int) $seats->sum(fn ($s) => $s->price_override ?? $s->effective_price);

    expect($booking->discount_id)->toBe($discount->id)
        ->and($booking->discount_amount)->toBe(50000)
        ->and($booking->total_price)->toBe($subtotal - 50000)
        ->and($discount->fresh()->used_count)->toBe(1);
});

test('TC_UC019_009: payment rejected when 10-minute seat hold time expires', function () {
    $user = createCheckoutCustomer();
    setupCheckoutHold($user, 2);

    // Tua thời gian 11 phút (vượt quá 10 phút giữ chỗ)
    $this->travel(11)->minutes();

    $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'vietqr',
            'delivery_name' => $user->name,
            'delivery_email' => $user->email,
            'delivery_phone' => '0912345678',
        ])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Một hoặc nhiều ghế trong giỏ vé đã hết thời gian giữ chỗ. Vui lòng chọn lại.']);

    expect(Booking::where('user_id', $user->id)->exists())->toBeFalse();
});

test('TC_UC019_010: transaction rollbacks completely when partial seat hold in cart is expired or released', function () {
    $user = createCheckoutCustomer();
    [$showtime, $seats] = setupCheckoutHold($user, 2);

    // Ghế 2 bị hết hạn sớm
    $seats[1]->update(['held_until' => now()->subMinute()]);

    $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'vietqr',
            'delivery_name' => $user->name,
            'delivery_email' => $user->email,
            'delivery_phone' => '0912345678',
        ])
        ->assertStatus(422);

    // Toàn bộ transaction bị rollback, không phát sinh đơn hàng thiếu vé
    expect(Booking::where('user_id', $user->id)->count())->toBe(0);

    // Ghế 1 không bị chuyển sang booked một cách dở dang
    expect($seats[0]->fresh()->status)->toBe('held');
});

test('TC_UC019_011: idempotency prevents double submit and generates only one booking for duplicate requests', function () {
    $user = createCheckoutCustomer();
    setupCheckoutHold($user, 2);

    $payload = [
        'payment_method' => 'vietqr',
        'delivery_name' => $user->name,
        'delivery_email' => $user->email,
        'delivery_phone' => '0912345678',
    ];

    // Request 1 thành công
    $this->actingAs($user)
        ->postJson(route('checkout.store'), $payload)
        ->assertOk();

    // Request 2 gửi trùng lặp ngay sau đó
    $this->actingAs($user)
        ->postJson(route('checkout.store'), $payload)
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Giỏ vé trống hoặc đã hết thời gian giữ chỗ. Vui lòng chọn lại ghế.']);

    // Xác nhận chỉ có duy nhất 1 booking được tạo ra
    expect(Booking::where('user_id', $user->id)->count())->toBe(1);
});

test('TC_UC019_012: checkout with an empty cart is blocked with 422 error', function () {
    $user = createCheckoutCustomer();

    $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'vietqr',
            'delivery_name' => $user->name,
            'delivery_email' => $user->email,
            'delivery_phone' => '0912345678',
        ])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Giỏ vé trống hoặc đã hết thời gian giữ chỗ. Vui lòng chọn lại ghế.']);
});

test('TC_UC019_013: checkout validation rejects empty delivery recipient fields', function () {
    $user = createCheckoutCustomer();
    setupCheckoutHold($user, 1);

    $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'vietqr',
            'delivery_name' => '',
            'delivery_email' => '',
            'delivery_phone' => '',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['delivery_name', 'delivery_email', 'delivery_phone']);
});

test('TC_UC019_014: checkout validation rejects invalid email and invalid Vietnamese phone format', function () {
    $user = createCheckoutCustomer();
    setupCheckoutHold($user, 1);

    $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'vietqr',
            'delivery_name' => 'Nguyễn Minh Quân',
            'delivery_email' => 'invalid-email-address',
            'delivery_phone' => '12345',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['delivery_email', 'delivery_phone']);
});

test('TC_UC019_015: checkout validation rejects empty or unsupported payment methods', function () {
    $user = createCheckoutCustomer();
    setupCheckoutHold($user, 1);

    $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => '',
            'delivery_name' => $user->name,
            'delivery_email' => $user->email,
            'delivery_phone' => '0912345678',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['payment_method']);

    $this->actingAs($user)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'crypto_bitcoin',
            'delivery_name' => $user->name,
            'delivery_email' => $user->email,
            'delivery_phone' => '0912345678',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['payment_method']);
});

test('TC_UC019_016: unauthenticated guest is blocked by auth middleware', function () {
    $this->postJson(route('checkout.store'), [
        'payment_method' => 'vietqr',
        'delivery_name' => 'Khách Vãng Lai',
        'delivery_email' => 'guest@example.com',
        'delivery_phone' => '0912345678',
    ])
    ->assertStatus(401);
});

test('TC_UC019_017: inactive user is blocked by EnsureUserIsActive middleware and logged out', function () {
    $lockedUser = createCheckoutCustomer(['is_active' => false]);
    setupCheckoutHold($lockedUser, 1);

    $this->actingAs($lockedUser)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'vietqr',
            'delivery_name' => $lockedUser->name,
            'delivery_email' => $lockedUser->email,
            'delivery_phone' => '0912345678',
        ])
        ->assertStatus(403);

    expect(auth()->check())->toBeFalse();
});

test('TC_UC019_018: cart isolation ensures User A only checks out their own held seats and cannot touch User B seats', function () {
    $userA = createCheckoutCustomer();
    $userB = createCheckoutCustomer();

    [$showtime, $seatsA] = setupCheckoutHold($userA, 1);
    [$showtimeB, $seatsB] = setupCheckoutHold($userB, 2);

    $response = $this->actingAs($userA)
        ->postJson(route('checkout.store'), [
            'payment_method' => 'vietqr',
            'delivery_name' => $userA->name,
            'delivery_email' => $userA->email,
            'delivery_phone' => '0912345678',
        ])
        ->assertOk();

    $bookingA = Booking::with('items')->firstWhere('booking_code', $response->json('booking_code'));

    // Đơn hàng của User A chỉ có 1 ghế
    expect($bookingA->items)->toHaveCount(1)
        ->and($bookingA->items[0]->showtime_seat_id)->toBe($seatsA[0]->id);

    // Ghế của User B vẫn đang được giữ an toàn bởi User B
    foreach ($seatsB as $seatB) {
        $seatB->refresh();
        expect($seatB->status)->toBe('held')
            ->and($seatB->held_by_user_id)->toBe($userB->id);
    }
});
