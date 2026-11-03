<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'discount_type',
        'discount_value',
        'min_order_value',
        'max_discount_amount',
        'max_uses',
        'used_count',
        'start_date',
        'end_date',
        'applicable_to',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'min_order_value' => 'integer',
        'max_discount_amount' => 'integer',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            });
    }

    /** Lý do mã không dùng được cho đơn trị giá $orderAmount (tiếng Việt), null nếu dùng được. */
    public function unusableReason(int $orderAmount): ?string
    {
        if (! $this->is_active) {
            return 'Mã giảm giá không tồn tại hoặc đã ngừng áp dụng.';
        }

        $today = now()->toDateString();
        if ($this->start_date && $this->start_date->toDateString() > $today) {
            return 'Mã giảm giá chưa đến thời gian áp dụng.';
        }

        // end_date là ngày: mã còn hiệu lực đến hết ngày cuối cùng.
        if ($this->end_date && $this->end_date->toDateString() < $today) {
            return 'Mã giảm giá đã hết hạn.';
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return 'Mã giảm giá đã hết lượt sử dụng.';
        }

        if ($orderAmount < (int) $this->min_order_value) {
            return 'Đơn hàng cần tối thiểu '.number_format((int) $this->min_order_value, 0, ',', '.').'₫ để dùng mã này.';
        }

        return null;
    }

    public function isValidForOrder(int $orderAmount): bool
    {
        return $this->unusableReason($orderAmount) === null;
    }

    /** Số tiền được giảm (không vượt quá giá trị đơn); 0 nếu mã không dùng được. */
    public function calculateDiscount(int $orderAmount): int
    {
        if (! $this->isValidForOrder($orderAmount)) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            $amount = (int) round($orderAmount * $this->discount_value / 100);
            if ($this->max_discount_amount) {
                $amount = min($amount, (int) $this->max_discount_amount);
            }
        } else {
            $amount = (int) $this->discount_value;
        }

        return max(0, min($amount, $orderAmount));
    }
}
