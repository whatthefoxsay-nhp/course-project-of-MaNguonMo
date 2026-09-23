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
        'row_label',
        'seat_number',
        'type',
    ];

    protected $casts = [
        'seat_number' => 'integer',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function showtimeSeats(): HasMany
    {
        return $this->hasMany(ShowtimeSeat::class);
    }

    public function isVip(): bool
    {
        return in_array($this->type, ['vip', 'vip_gold', 'svip_diamond', 'skybox_suite'], true);
    }

    public function getFullCodeAttribute(): string
    {
        return "{$this->row_label}{$this->seat_number}";
    }
}
