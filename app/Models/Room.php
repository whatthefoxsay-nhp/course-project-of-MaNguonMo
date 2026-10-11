<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    public const ZONED_PRESET = 'zoned';

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

    public function zones(): HasMany
    {
        return $this->hasMany(RoomZone::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Phòng dựng theo mô hình khu vực (sức chứa = tổng các khu). */
    public function isZoned(): bool
    {
        return in_array($this->layout_preset, array_merge([self::ZONED_PRESET], \App\Support\VenuePresets::PRESETS), true)
            || $this->zones()->exists();
    }

    public function recalculateCapacity(): int
    {
        $zoneCapacity = $this->zones()->get()->sum(function (RoomZone $zone) {
            return $zone->hasSeats() ? $zone->seats()->count() : (int) $zone->capacity;
        });
        $legacySeats = $this->seats()->whereNull('zone_id')->count();
        $capacity = (int) $zoneCapacity + $legacySeats;

        $this->forceFill(['capacity' => $capacity])->saveQuietly();

        return $capacity;
    }

    public function getPresetLabelAttribute(): string
    {
        return match ($this->layout_preset ?? 'mega_concert') {
            self::ZONED_PRESET => '🗺️ Khán Phòng Theo Khu Vực',
            'outdoor_concert' => '🏟️ Sân khấu ngoài trời',
            'theater' => '🎭 Nhà hát',
            'fixed_hall' => '🏢 Hội trường ghế cố định',
            'conference_tables' => '🪑 Hội nghị theo bàn',
            'admission_only' => '🎫 Vé vào cửa',
            'sayhi_concert' => '🎤 Live Concert Anh Trai Say Hi',
            'sayhi_concert_d2' => '🎤 Live Concert Anh Trai Say Hi - Đêm 2 (Y-Stage Runway)',
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
