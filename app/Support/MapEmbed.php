<?php

namespace App\Support;

use App\Models\Room;

/** URL iframe bản đồ địa điểm: Maps Embed API nếu có key, không có key thì dùng link nhúng công khai. */
final class MapEmbed
{
    public static function url(Room $room): ?string
    {
        $query = self::query($room);
        if ($query === null) {
            return null;
        }

        $key = config('services.google_maps.embed_key');

        return $key
            ? "https://www.google.com/maps/embed/v1/place?key={$key}&q={$query}&zoom=16"
            : "https://maps.google.com/maps?q={$query}&z=16&output=embed";
    }

    private static function query(Room $room): ?string
    {
        if ($room->latitude !== null && $room->longitude !== null) {
            return "{$room->latitude},{$room->longitude}";
        }

        return $room->address ? rawurlencode($room->address) : null;
    }
}
