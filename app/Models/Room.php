<?php

namespace App\Models;

use App\Support\LayoutTemplates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'capacity',
        'layout_preset',
        'seat_config',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'capacity' => 'integer',
        'seat_config' => 'array',
    ];

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    /** Khu vực của sơ đồ, theo thứ tự vẽ: tầng (level) rồi trái → phải (sort_order). */
    public function zones(): HasMany
    {
        return $this->hasMany(SeatZone::class)->orderBy('level')->orderBy('sort_order')->orderBy('id');
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    public function getPresetLabelAttribute(): string
    {
        return LayoutTemplates::label($this->layout_preset ?? 'custom_grid');
    }

    /** @return array{total: int, zones: list<array{code: string, name: string, color: string, kind: string, count: int}>} */
    public function getSeatsSummaryAttribute(): array
    {
        $counts = $this->seats->countBy('zone_id');

        return [
            'total' => $this->seats->count(),
            'zones' => $this->zones->map(fn (SeatZone $zone) => [
                'code' => $zone->code,
                'name' => $zone->name,
                'color' => $zone->color,
                'kind' => $zone->kind,
                'count' => (int) ($counts[$zone->id] ?? 0),
            ])->values()->all(),
        ];
    }
}
