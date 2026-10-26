<?php

namespace App\Support;

/**
 * Bảng giá theo loại ghế CŨ (svip_diamond, vip_gold, ...).
 * Chỉ dùng cho ghế chưa thuộc khu nào; ghế mới lấy giá từ khu (SeatZone::priceFor).
 */
final class TicketTiers
{
    /** Loại ghế cũ trong DB -> hạng hiện hành. */
    private const LEGACY_SEAT_TYPES = ['normal' => 'cat2_wings', 'vip' => 'vip_gold'];

    /** [loại ghế => [tên, phụ thu]] */
    private const TIERS = [
        'svip_diamond' => ['SVIP Sân Khấu Kim Cương', 150000],
        'vip_gold' => ['VIP Mặt Sàn Trực Diện', 80000],
        'cat1_stand' => ['Khán Đài A - Tầng 1', 40000],
        'cat2_wings' => ['Khán Đài Cánh C & D', 0],
        'skybox_suite' => ['Skybox VIP Suite', 200000],
        'standing_pit' => ['Khu Đứng GA Fanzone', -20000],
    ];

    /** @return array<string, array{name: string, surcharge: int, price: int}> */
    public static function for(int $basePrice): array
    {
        $tiers = [];
        foreach (self::TIERS as $type => [$name, $surcharge]) {
            $price = match ($type) {
                'skybox_suite' => ($basePrice * 2) + $surcharge,
                'standing_pit' => max($basePrice + $surcharge, 150000),
                default => $basePrice + $surcharge,
            };
            $tiers[$type] = ['name' => $name, 'surcharge' => $surcharge, 'price' => $price];
        }

        return $tiers;
    }

    public static function normalizeSeatType(string $seatType): string
    {
        return self::LEGACY_SEAT_TYPES[$seatType] ?? $seatType;
    }

    public static function seatPrice(string $seatType, int $basePrice): int
    {
        return (int) (self::for($basePrice)[self::normalizeSeatType($seatType)]['price'] ?? $basePrice);
    }

    public static function seatName(string $seatType): string
    {
        return self::TIERS[self::normalizeSeatType($seatType)][0] ?? 'Ghế ngồi';
    }
}
