<?php

namespace App\Support;

use App\Models\Room;

/**
 * Dựng URL bản đồ Google Maps cho một khán phòng.
 * Ưu tiên tọa độ; không có tọa độ thì dùng địa chỉ; không có cả hai thì trả null.
 */
final class MapEmbed
{
    /** URL cho iframe: Maps Embed API khi có key, không có key thì dùng link nhúng công khai. */
    public static function url(Room $room): ?string
    {
        $query = self::query($room);
        if ($query === null) {
            return null;
        }

        $key = config('services.google_maps.embed_key');

        return $key
            ? 'https://www.google.com/maps/embed/v1/place?key='.rawurlencode($key)."&q={$query}&zoom=16"
            : "https://maps.google.com/maps?q={$query}&z=16&output=embed";
    }

    /** Link "Chỉ đường" mở Google Maps (không cần key). */
    public static function directionsUrl(Room $room): ?string
    {
        $query = self::query($room);

        return $query === null ? null : "https://www.google.com/maps/dir/?api=1&destination={$query}";
    }

    private static function query(Room $room): ?string
    {
        if ($room->latitude !== null && $room->longitude !== null) {
            return "{$room->latitude},{$room->longitude}";
        }

        $address = trim((string) $room->address);

        return $address !== '' ? rawurlencode($address) : null;
    }
}
