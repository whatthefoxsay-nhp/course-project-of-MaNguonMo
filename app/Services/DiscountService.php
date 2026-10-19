<?php

namespace App\Services;

use App\Exceptions\DiscountException;
use App\Models\Discount;

/**
 * Tra mã giảm giá theo code (không phân biệt hoa thường); quy tắc hợp lệ và cách tính nằm ở model Discount.
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

        if (! $discount) {
            throw new DiscountException('Mã giảm giá không tồn tại hoặc đã ngừng áp dụng.');
        }

        if ($reason = $discount->unusableReason($subtotal)) {
            throw new DiscountException($reason);
        }

        return [$discount, $discount->calculateDiscount($subtotal)];
    }
}
