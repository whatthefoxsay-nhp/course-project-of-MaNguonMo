<?php

namespace App\Models;

use App\Support\TicketTiers;
use Illuminate\Database\Eloquent\Builder;
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
        // Giá chốt tại lúc khách giữ ghế (xóa khi nhả); booking_items.price lấy từ đây.
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

        return TicketTiers::seatPrice($this->seat?->type ?? 'normal', $this->showtime?->base_price ?? 0);
    }

    /** Trạng thái hiển thị cho khách: ghế giữ quá hạn coi như còn trống. */
    public function publicStatus(): string
    {
        if ($this->status === 'held' && $this->held_until !== null && $this->held_until->isPast()) {
            return 'available';
        }

        return $this->status;
    }

    /** Ghế có thể giữ: đang trống, hoặc đang giữ nhưng đã quá hạn. */
    public function scopeHoldable(Builder $query): Builder
    {
        return $query->where(function (Builder $where) {
            $where->where('showtime_seats.status', 'available')
                ->orWhere(fn (Builder $expired) => $expired
                    ->where('showtime_seats.status', 'held')
                    ->where('showtime_seats.held_until', '<', now()));
        });
    }

    /** Ghế đang nằm trong giỏ (còn hạn giữ) của user. */
    public function scopeHeldBy(Builder $query, User $user): Builder
    {
        return $query->where('showtime_seats.status', 'held')
            ->where('showtime_seats.held_by_user_id', $user->id)
            ->where('showtime_seats.held_until', '>', now());
    }

    public function isHoldable(): bool
    {
        return $this->publicStatus() === 'available';
    }
}
