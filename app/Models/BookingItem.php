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

    /**
     * Vé thuộc các suất diễn đã cho — kể cả đơn đã hủy. (Khu đứng cũng là ghế ẩn trong showtime_seats.)
     *
     * @param  \Illuminate\Contracts\Database\Query\Builder|array<int>  $showtimeIds
     */
    public function scopeForShowtimes(Builder $query, $showtimeIds): Builder
    {
        return $query->whereIn('showtime_seat_id', ShowtimeSeat::select('id')->whereIn('showtime_id', $showtimeIds));
    }
}
