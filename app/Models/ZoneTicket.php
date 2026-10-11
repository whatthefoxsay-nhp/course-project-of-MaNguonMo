<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneTicket extends Model
{
    protected $fillable = [
        'showtime_id', 'zone_id', 'status', 'held_by_user_id', 'held_until', 'price',
    ];

    protected $casts = [
        'held_until' => 'datetime',
        'price' => 'integer',
    ];

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(RoomZone::class, 'zone_id');
    }

    public function heldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by_user_id');
    }

    public function publicStatus(): string
    {
        return $this->status === 'held' && $this->held_until?->isPast() ? 'available' : $this->status;
    }

    public function isActiveHold(): bool
    {
        return $this->status === 'held' && $this->held_until?->isFuture();
    }
}
