<?php

namespace App\Support;

use App\Models\RoomZone;

final class VenuePresets
{
    public const PRESETS = ['outdoor_concert', 'theater', 'fixed_hall', 'conference_tables', 'admission_only'];

    public static function normalize(string $preset): string
    {
        return match ($preset) {
            'mega_concert', 'sayhi_concert', 'sayhi_concert_d2' => 'outdoor_concert',
            'theater_hall' => 'theater',
            'convention_center' => 'fixed_hall',
            default => $preset,
        };
    }

    public static function zones(string $preset): array
    {
        return match (self::normalize($preset)) {
            'outdoor_concert' => [
                ['code' => 'SVIP', 'name' => 'SVIP', 'kind' => RoomZone::KIND_SEATED, 'seat_type' => 'svip_diamond', 'rows' => 2, 'cols' => 12],
                ['code' => 'VIP', 'name' => 'VIP', 'kind' => RoomZone::KIND_SEATED, 'seat_type' => 'vip_gold', 'rows' => 4, 'cols' => 14],
                ['code' => 'GA', 'name' => 'Khu đứng GA', 'kind' => RoomZone::KIND_STANDING, 'seat_type' => RoomZone::STANDING_TYPE, 'capacity' => 1000],
            ],
            'theater' => [
                ['code' => 'STALL', 'name' => 'Tầng trệt', 'kind' => RoomZone::KIND_SEATED, 'seat_type' => 'vip_gold', 'rows' => 8, 'cols' => 12],
                ['code' => 'BALC', 'name' => 'Ban công', 'kind' => RoomZone::KIND_SEATED, 'seat_type' => 'cat1_stand', 'rows' => 5, 'cols' => 12],
            ],
            'fixed_hall' => [
                ['code' => 'HALL', 'name' => 'Hội trường', 'kind' => RoomZone::KIND_SEATED, 'seat_type' => 'cat1_stand', 'rows' => 8, 'cols' => 12],
            ],
            'conference_tables' => [
                ['code' => 'TABLE', 'name' => 'Bàn hội nghị', 'kind' => RoomZone::KIND_TABLE, 'seat_type' => 'cat1_stand', 'tables' => 12, 'seats_per_table' => 8],
            ],
            'admission_only' => [
                ['code' => 'GA', 'name' => 'Vé vào cửa', 'kind' => RoomZone::KIND_ADMISSION, 'seat_type' => 'cat1_stand', 'capacity' => 500],
            ],
            default => [],
        };
    }
}
