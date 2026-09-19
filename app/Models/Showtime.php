<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Showtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'movie_id',
        'room_id',
        'start_time',
        'end_time',
        'base_price',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'base_price' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function movie(): BelongsTo
    {
        return $this->event();
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function showtimeSeats(): HasMany
    {
        return $this->hasMany(ShowtimeSeat::class);
    }

    public function availableSeatsCount(): int
    {
        return $this->showtimeSeats()->where('status', 'available')->count();
    }
}
