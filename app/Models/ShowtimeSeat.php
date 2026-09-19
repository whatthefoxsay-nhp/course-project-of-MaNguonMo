<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShowtimeSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'showtime_id',
        'seat_id',
        'status',
        'held_by_user_id',
        'held_until',
        'price_override',
    ];

    protected $casts = [
        'held_until' => 'datetime',
        'price_override' => 'integer',
    ];

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function heldByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by_user_id');
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function getEffectivePriceAttribute(): int
    {
        if ($this->price_override !== null) {
            return $this->price_override;
        }

        $base = $this->showtime ? $this->showtime->base_price : 75000;
        $vipSurcharge = ($this->seat && $this->seat->isVip()) ? 25000 : 0;

        return $base + $vipSurcharge;
    }
}
