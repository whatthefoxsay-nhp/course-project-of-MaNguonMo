<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'zone_id',
        'row_label',
        'seat_number',
        'pos_col',
        'type',
    ];

    protected $casts = [
        'seat_number' => 'integer',
        'pos_col' => 'integer',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(SeatZone::class, 'zone_id');
    }

    public function showtimeSeats(): HasMany
    {
        return $this->hasMany(ShowtimeSeat::class);
    }

    public function isVip(): bool
    {
        if ($this->zone_id !== null && $this->zone !== null) {
            return $this->zone->surcharge > 0;
        }

        return in_array($this->type, ['vip', 'vip_gold', 'svip_diamond', 'skybox_suite'], true);
    }

    public function getFullCodeAttribute(): string
    {
        return "{$this->row_label}{$this->seat_number}";
    }
}
