<?php

use App\Models\Discount;

function validityDiscount(array $overrides = []): Discount
{
    return Discount::create(array_merge([
        'code' => 'SALE10',
        'title' => 'Giảm 10%',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'min_order_value' => 0,
        'max_uses' => 100,
        'used_count' => 0,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-20',
        'is_active' => true,
    ], $overrides))->fresh();
}

test('a discount is valid through the whole of its last day', function () {
    $discount = validityDiscount();

    $this->travelTo(now()->setDate(2026, 10, 20)->setTime(23, 59, 0));
    expect($discount->isValidForOrder(500000))->toBeTrue()
        ->and($discount->calculateDiscount(500000))->toBe(50000)
        ->and(Discount::active()->whereKey($discount->id)->exists())->toBeTrue();

    $this->travelTo(now()->setDate(2026, 10, 21)->setTime(0, 0, 1));
    expect($discount->isValidForOrder(500000))->toBeFalse()
        ->and(Discount::active()->whereKey($discount->id)->exists())->toBeFalse();
});

test('a discount becomes valid at the start of its first day', function () {
    $discount = validityDiscount();

    $this->travelTo(now()->setDate(2026, 9, 30)->setTime(23, 59, 0));
    expect($discount->isValidForOrder(500000))->toBeFalse();

    $this->travelTo(now()->setDate(2026, 10, 1)->setTime(0, 0, 1));
    expect($discount->isValidForOrder(500000))->toBeTrue();
});
