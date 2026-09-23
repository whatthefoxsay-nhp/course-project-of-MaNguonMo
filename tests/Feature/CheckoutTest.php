<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\SeatHoldService;

beforeEach(fn () => $this->seed());

function heldCart($user, int $count = 2): Showtime
{
    $showtime = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026')
        ->showtimes()->orderBy('start_time')->first();
    $ids = $showtime->showtimeSeats()->where('status', 'available')->orderBy('id')->take($count)->pluck('id')->all();
    app(SeatHoldService::class)->hold($user, $showtime, $ids);

    return $showtime;
}

test('checkout turns held seats into a confirmed booking', function () {
    $user = createCustomer();
    heldCart($user, 2);

    $response = $this->actingAs($user)
        ->postJson(route('checkout.store'), ['payment_method' => 'momo'])
        ->assertOk()
        ->assertJsonPath('redirect', route('bookings.history'));

    $booking = Booking::with('items.showtimeSeat')->firstWhere('booking_code', $response->json('booking_code'));

    expect($booking->user_id)->toBe($user->id)
        ->and($booking->status)->toBe('confirmed')
        ->and($booking->payment_method)->toBe('momo')
        ->and($booking->booking_code)->toMatch('/^TBX-[A-Z0-9]{8}$/')
        ->and($booking->items)->toHaveCount(2)
        ->and($booking->total_price)->toBe($booking->items->sum('price'))
        ->and($booking->items->every(fn ($item) => $item->showtimeSeat->status === 'booked' && $item->showtimeSeat->held_by_user_id === null))->toBeTrue();
});

test('checkout with an empty cart is rejected', function () {
    $this->actingAs(createCustomer())
        ->postJson(route('checkout.store'), ['payment_method' => 'vietqr'])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Giỏ vé trống hoặc đã hết thời gian giữ chỗ. Vui lòng chọn lại ghế.']);
});

test('expired holds cannot be checked out', function () {
    $user = createCustomer();
    heldCart($user, 1);
    ShowtimeSeat::where('held_by_user_id', $user->id)->update(['held_until' => now()->subMinute()]);

    $this->actingAs($user)
        ->postJson(route('checkout.store'), ['payment_method' => 'vietqr'])
        ->assertStatus(422);

    expect(Booking::where('user_id', $user->id)->exists())->toBeFalse();
});

test('payment method must be one of the supported ones', function () {
    $user = createCustomer();
    heldCart($user, 1);

    $this->actingAs($user)
        ->postJson(route('checkout.store'), ['payment_method' => 'bitcoin'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('payment_method');
});

test('a user cannot check out somebody else\'s held seats', function () {
    heldCart(createCustomer(), 2);

    $this->actingAs(createCustomer())
        ->postJson(route('checkout.store'), ['payment_method' => 'card'])
        ->assertStatus(422);
});
