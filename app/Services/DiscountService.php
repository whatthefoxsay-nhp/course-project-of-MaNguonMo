<?php

namespace App\Services;

use App\Exceptions\DiscountException;
use App\Models\Discount;

/**
 * Kiểm tra & tính tiền giảm của mã giảm giá. Tự kiểm tra ngày theo kiểu "bao gồm"
 * (mã còn dùng được trong cả ngày end_date) thay vì dựa vào Discount::isValidForOrder().
 */
class DiscountService
{
    public static function normalize(string $code): string
    {
        return mb_strtoupper(trim($code));
    }

    /**
     * @return array{0: Discount, 1: int} [mã giảm giá, số tiền được giảm]
     *
     * @throws DiscountException
     */
    public function resolve(string $code, int $subtotal, bool $lockForUpdate = false): array
    {
        $query = Discount::whereRaw('UPPER(code) = ?', [self::normalize($code)]);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $discount = $query->first();

        if (! $discount || ! $discount->is_active) {
            throw new DiscountException('Mã giảm giá không tồn tại hoặc đã ngừng áp dụng.');
        }

        $today = now()->toDateString();
        if ($discount->start_date && $discount->start_date->toDateString() > $today) {
            throw new DiscountException('Mã giảm giá chưa đến thời gian áp dụng.');
        }
        if ($discount->end_date && $discount->end_date->toDateString() < $today) {
            throw new DiscountException('Mã giảm giá đã hết hạn.');
        }
        if ($discount->max_uses !== null && $discount->used_count >= $discount->max_uses) {
            throw new DiscountException('Mã giảm giá đã hết lượt sử dụng.');
        }
        if ($subtotal < (int) $discount->min_order_value) {
            throw new DiscountException('Đơn hàng cần tối thiểu '.number_format((int) $discount->min_order_value, 0, ',', '.').'₫ để dùng mã này.');
        }

        return [$discount, $this->amountFor($discount, $subtotal)];
    }

    public function amountFor(Discount $discount, int $subtotal): int
    {
        if ($discount->discount_type === 'percentage') {
            $amount = (int) round($subtotal * $discount->discount_value / 100);
            if ($discount->max_discount_amount) {
                $amount = min($amount, (int) $discount->max_discount_amount);
            }
        } else {
            $amount = (int) $discount->discount_value;
        }

        return max(0, min($amount, $subtotal));
    }
}
