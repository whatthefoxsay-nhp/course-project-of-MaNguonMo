<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomZone extends Model
{
    protected static function booted(): void
    {
        static::saved(fn (self $zone) => $zone->room?->recalculateCapacity());
        static::deleted(fn (self $zone) => $zone->room?->recalculateCapacity());
    }

    public const KIND_SEATED = 'seated';

    public const KIND_STANDING = 'standing';

    public const KIND_TABLE = 'table';

    public const KIND_ADMISSION = 'admission';

    /** Hạng vé gán được cho khu ngồi (khớp TicketTiers::for(..., true)). */
    public const SEATED_TYPES = ['svip_diamond', 'vip_gold', 'cat1_stand', 'cat2_wings', 'skybox_suite'];

    public const STANDING_TYPE = 'standing_pit';

    protected $fillable = [
        'room_id',
        'code',
        'name',
        'kind',
        'seat_type',
        'ticket_tier',
        'capacity',
        'layout',
        'meta',
        'sort_order',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'layout' => 'array',
        'meta' => 'array',
        'sort_order' => 'integer',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'zone_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(ZoneTicket::class, 'zone_id');
    }

    public function isStanding(): bool
    {
        return $this->kind === self::KIND_STANDING;
    }

    public function sellsByQuantity(): bool
    {
        return in_array($this->kind, [self::KIND_STANDING, self::KIND_ADMISSION], true);
    }

    public function hasSeats(): bool
    {
        return in_array($this->kind, [self::KIND_SEATED, self::KIND_TABLE], true);
    }

    public function effectiveCapacity(): int
    {
        return $this->hasSeats() ? $this->seats()->count() : (int) $this->capacity;
    }
}
