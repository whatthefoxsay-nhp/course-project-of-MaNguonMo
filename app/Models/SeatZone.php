<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Một khu vực trong sơ đồ địa điểm: ghế đánh số, bàn tiệc hoặc khu đứng/vào cửa tự do.
 * Giá 1 vé = giá cơ bản của suất diễn + phụ thu của khu.
 */
class SeatZone extends Model
{
    public const KINDS = ['seated', 'table', 'standing'];

    protected $fillable = [
        'room_id',
        'code',
        'name',
        'kind',
        'level',
        'sort_order',
        'surcharge',
        'color',
        'gate',
        'description',
        'perks',
        'capacity',
    ];

    protected $casts = [
        'level' => 'integer',
        'sort_order' => 'integer',
        'surcharge' => 'integer',
        'capacity' => 'integer',
        'perks' => 'array',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'zone_id');
    }

    public function priceFor(int $basePrice): int
    {
        return max(0, $basePrice + (int) $this->surcharge);
    }

    public function isStanding(): bool
    {
        return $this->kind === 'standing';
    }

    /** Khách chọn từng ghế trên sơ đồ (ghế đánh số hoặc ghế quanh bàn). */
    public function isPickable(): bool
    {
        return in_array($this->kind, ['seated', 'table'], true);
    }
}
