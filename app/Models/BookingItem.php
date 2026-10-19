<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'showtime_seat_id',
        'zone_ticket_id',
        'price',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function showtimeSeat(): BelongsTo
    {
        return $this->belongsTo(ShowtimeSeat::class);
    }

    public function zoneTicket(): BelongsTo
    {
        return $this->belongsTo(ZoneTicket::class);
    }

    /**
     * Vé (theo ghế hoặc theo khu) thuộc các suất diễn đã cho — kể cả đơn đã hủy.
     *
     * @param  \Illuminate\Contracts\Database\Query\Builder|array<int>  $showtimeIds
     */
    public function scopeForShowtimes(Builder $query, $showtimeIds): Builder
    {
        return $query->where(fn (Builder $where) => $where
            ->whereIn('showtime_seat_id', ShowtimeSeat::select('id')->whereIn('showtime_id', $showtimeIds))
            ->orWhereIn('zone_ticket_id', ZoneTicket::select('id')->whereIn('showtime_id', $showtimeIds)));
    }
}
