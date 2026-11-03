<?php

use App\Models\Booking;
use App\Models\Discount;
use App\Models\Event;
use App\Services\SalesReportService;

beforeEach(fn () => $this->seed());

function lifecycleDiscount(array $overrides = []): Discount
{
    return Discount::create(array_merge([
        'code' => 'GIAM100K',
        'title' => 'Giảm 100.000₫',
        'discount_type' => 'fixed',
        'discount_value' => 100000,
        'min_order_value' => 0,
        'max_uses' => 1,
        'used_count' => 0,
        'is_active' => true,
    ], $overrides));
}

function checkoutWithDiscount($test, $user, string $code): Booking
{
    holdCompletionSeats($user);
    $bookingCode = $test->actingAs($user)
        ->postJson(route('checkout.store'), checkoutPayload(['discount_code' => $code]))
        ->assertOk()->json('booking_code');

    return Booking::firstWhere('booking_code', $bookingCode);
}

test('per-event revenue is net of discounts and matches the headline total', function () {
    lifecycleDiscount();
    $booking = checkoutWithDiscount($this, createCustomer(), 'GIAM100K');
    expect($booking->discount_amount)->toBe(100000);

    $eventId = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026')->id;
    $reports = app(SalesReportService::class);

    expect($reports->revenueByEventId()[$eventId]['revenue'])->toBe($booking->total_price)
        ->and($reports->eventsRevenue()->sum('revenue'))->toBe((int) Booking::where('status', 'confirmed')->sum('total_price'));
});

test('admin cancelling a discounted booking gives the discount use back', function () {
    $discount = lifecycleDiscount();
    $booking = checkoutWithDiscount($this, createCustomer(), 'GIAM100K');
    expect($discount->fresh()->used_count)->toBe(1);

    $this->actingAs(createAdmin())
        ->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled'])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($discount->fresh()->used_count)->toBe(0);
});

test('re-confirming is refused when the booking discount has no uses left', function () {
    $discount = lifecycleDiscount();
    $booking = checkoutWithDiscount($this, createCustomer(), 'GIAM100K');
    $admin = createAdmin();

    $this->actingAs($admin)->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled']);
    $discount->update(['used_count' => 1]); // lượt duy nhất đã bị đơn khác dùng

    $this->actingAs($admin)
        ->patch(route('admin.bookings.update-status', $booking), ['status' => 'confirmed'])
        ->assertSessionHas('error');

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($discount->fresh()->used_count)->toBe(1);
});

test('a comment payload that is not a string is a validation error, not a crash', function () {
    $event = Event::published()->first();

    $this->actingAs(createCustomer())
        ->postJson(route('comments.store', $event), ['content' => ['x']])
        ->assertStatus(422)
        ->assertJsonValidationErrors('content');
});
