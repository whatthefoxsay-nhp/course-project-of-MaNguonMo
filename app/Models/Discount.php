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

    public function isValidForOrder(int $orderAmount): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        if ($this->used_count >= $this->max_uses) {
            return false;
        }

        if ($orderAmount < $this->min_order_value) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(int $orderAmount): int
    {
        if (! $this->isValidForOrder($orderAmount)) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            $discount = (int) round(($orderAmount * $this->discount_value) / 100);
            if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
                return $this->max_discount_amount;
            }
            return $discount;
        }

        return min($this->discount_value, $orderAmount);
    }
}
