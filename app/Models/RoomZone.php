<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomZone extends Model
{
    public const KIND_SEATED = 'seated';

    public const KIND_STANDING = 'standing';

    /** Hạng vé gán được cho khu ngồi (khớp TicketTiers::for(..., true)). */
    public const SEATED_TYPES = ['svip_diamond', 'vip_gold', 'cat1_stand', 'cat2_wings', 'skybox_suite'];

    public const STANDING_TYPE = 'standing_pit';

    protected $fillable = [
        'room_id',
        'code',
        'name',
        'kind',
        'seat_type',
        'capacity',
        'layout',
        'sort_order',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'layout' => 'array',
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

    public function isStanding(): bool
    {
        return $this->kind === self::KIND_STANDING;
    }
}
