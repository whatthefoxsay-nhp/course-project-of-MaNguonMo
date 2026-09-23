<?php

namespace App\Models;

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

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    public function getPresetLabelAttribute(): string
    {
        return match ($this->layout_preset ?? 'mega_concert') {
            'theater_hall' => '🎭 Nhà Hát & Giao Hưởng',
            'convention_center' => '🏢 Trung Tâm Hội Nghị & Triển Lãm',
            'custom_grid' => '⚡ Ma Trận Tùy Chỉnh',
            default => '🏟️ Mega Concert Arena',
        };
    }

    public function getSeatsSummaryAttribute(): array
    {
        $seats = $this->seats;

        return [
            'total' => $seats->count(),
            'svip' => $seats->where('type', 'svip_diamond')->count(),
            'vip' => $seats->where('type', 'vip_gold')->count(),
            'cat1' => $seats->where('type', 'cat1_stand')->count(),
            'cat2' => $seats->where('type', 'cat2_wings')->count(),
            'skybox' => $seats->where('type', 'skybox_suite')->count(),
            'standing' => $seats->where('type', 'standing_pit')->count(),
        ];
    }
}
