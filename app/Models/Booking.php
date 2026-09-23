<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_code',
        'total_price',
        'status',
        'payment_method',
    ];

    protected $casts = [
        'total_price' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Booking $booking) {
            if (empty($booking->booking_code)) {
                do {
                    $code = 'TBX-'.strtoupper(Str::random(8));
                } while (static::where('booking_code', $code)->exists());

                $booking->booking_code = $code;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }
}
