<?php

namespace App\Support;

/**
 * Nguồn giá vé duy nhất của hệ thống.
 * Giá mỗi hạng = giá cơ bản của suất diễn + phụ thu hạng (xem `for()`).
 */
final class TicketTiers
{
    /** Loại ghế cũ trong DB -> hạng hiện hành. */
    private const LEGACY_SEAT_TYPES = ['normal' => 'cat2_wings', 'vip' => 'vip_gold'];

    /**
     * Hạng bán theo số lượng (khách không chọn ghế, server tự gán):
     * loại ghế được phép gán + số ghế chiếm cho mỗi 1 vé.
     */
    private const PASS_TIERS = [
        'standing_pit' => ['seat_types' => ['standing_pit'], 'seats_per_unit' => 1],
        'vip_pass' => ['seat_types' => ['svip_diamond', 'vip_gold', 'vip'], 'seats_per_unit' => 1],
        'standard_pass' => ['seat_types' => ['cat1_stand', 'cat2_wings', 'skybox_suite', 'normal'], 'seats_per_unit' => 1],
        'combo_pass' => ['seat_types' => ['cat1_stand', 'cat2_wings', 'skybox_suite', 'normal'], 'seats_per_unit' => 2],
    ];

    public static function for(int $basePrice, bool $isSeated): array
    {
        if ($isSeated) {
            return [
                'svip_diamond' => [
                    'name' => 'SVIP Sân Khấu Kim Cương',
                    'label' => 'SVIP B-Stage Diamond',
                    'zone_code' => 'ZONE-SVIP',
                    'gate' => 'Cổng VIP 1',
                    'surcharge' => 150000,
                    'price' => $basePrice + 150000,
                    'color' => '#D4AF37',
                    'badge' => 'badge-gold',
                    'description' => 'Khu vực VIP sát B-Stage & Runway Catwalk chữ T, tặng Soundcheck Access + Set Quà Độc Quyền.',
                    'perks' => ['Lối VIP Check-in riêng', 'Vé Soundcheck rehearsal', 'Welcome Drink & Canapé', 'Set quà độc quyền'],
                    'icon' => '💎',
                ],
                'vip_gold' => [
                    'name' => 'VIP Mặt Sàn Trực Diện',
                    'label' => 'VIP Floor A & B',
                    'zone_code' => 'ZONE-VIP',
                    'gate' => 'Cổng VIP 2',
                    'surcharge' => 80000,
                    'price' => $basePrice + 80000,
                    'color' => '#E5A93C',
                    'badge' => 'badge-gold',
                    'description' => 'Khu ghế VIP mặt sàn trung tâm, trực diện sân khấu với âm thanh Stereo Master đỉnh cao.',
                    'perks' => ['Ghế VIP mặt sàn chính', 'Âm thanh Studio Master', 'Lanyard VIP đeo cổ'],
                    'icon' => '⭐',
                ],
                'cat1_stand' => [
                    'name' => 'Khán Đài A - VIP Tầng 1',
                    'label' => 'West Stand Cat 1',
                    'zone_code' => 'STAND-A',
                    'gate' => 'Cổng Khán Đài A',
                    'surcharge' => 40000,
                    'price' => $basePrice + 40000,
                    'color' => '#3A5A40',
                    'badge' => 'badge-sage',
                    'description' => 'Khán đài tầng 1 trung tâm sân vận động, tầm nhìn bao quát toàn cảnh hiệu ứng laser & visual.',
                    'perks' => ['Góc nhìn toàn cảnh sân khấu', 'Ghế đệm êm có tựa tay'],
                    'icon' => '✨',
                ],
                'cat2_wings' => [
                    'name' => 'Khán Đài C & D - Cánh Khán Giả',
                    'label' => 'Wing Stands Cat 2',
                    'zone_code' => 'STAND-CD',
                    'gate' => 'Cổng Khán Đài C/D',
                    'surcharge' => 0,
                    'price' => $basePrice,
                    'color' => '#1F2937',
                    'badge' => 'badge-beige',
                    'description' => 'Khán đài cánh trái & cánh phải sân vận động, vị trí thoáng đãng với giá vé phổ thông.',
                    'perks' => ['Chỗ ngồi thoáng mát', 'Giá vé tối ưu'],
                    'icon' => '🏟️',
                ],
                'skybox_suite' => [
                    'name' => 'Skybox Suite / Buồng Đôi VIP',
                    'label' => 'Royal Skybox Lounge',
                    'zone_code' => 'SKYBOX',
                    'gate' => 'Thang Máy VIP 3',
                    'surcharge' => 200000,
                    'price' => ($basePrice * 2) + 200000,
                    'color' => '#C08497',
                    'badge' => 'badge-rose',
                    'description' => 'Phòng VIP riêng biệt tầng thượng có ban công kính, sofa đôi, phục vụ cocktail & snack không giới hạn.',
                    'perks' => ['Sofa đôi phòng riêng VIP', 'Bao gồm 2 vé + Buffet cocktail', 'Ban công kính riêng'],
                    'icon' => '🥂',
                ],
                'standing_pit' => [
                    'name' => 'GA Standing Fanzone (Pit Trái & Phải)',
                    'label' => 'GA Standing Pits',
                    'zone_code' => 'PIT-GA',
                    'gate' => 'Cổng Fanzone 1 & 2',
                    'surcharge' => -20000,
                    'price' => max($basePrice - 20000, 150000),
                    'color' => '#99334D',
                    'badge' => 'badge-rose',
                    'description' => 'Khu vực đứng sát hai bên sàn Catwalk chữ T, trải nghiệm cuồng nhiệt gần sát thần tượng nhất.',
                    'perks' => ['Vòng tay phát sáng LED', 'Lối vào Fanzone sát sân khấu'],
                    'icon' => '🔥',
                ],
            ];
        }

        // Non-concert events (Hội thảo, Triển lãm, Workshop, Festival...)
        return [
            'vip_pass' => [
                'name' => 'Vé VIP Toàn Diện (Executive Pass)',
                'label' => 'VIP All-Access',
                'zone_code' => 'VIP-PASS',
                'gate' => 'Cổng VIP',
                'surcharge' => 100000,
                'price' => $basePrice + 100000,
                'color' => '#D4AF37',
                'badge' => 'badge-gold',
                'description' => 'Toàn quyền tham dự tất cả các phiên, hàng ghế đầu ưu tiên, buffet trưa & kết nối trực tiếp cùng diễn giả.',
                'perks' => ['Hàng ghế ưu tiên VIP', 'Teabreak & Buffet trưa', 'Bộ tài liệu độc quyền', 'Chứng nhận Certificate'],
                'icon' => '👑',
            ],
            'standard_pass' => [
                'name' => 'Vé Tiêu Chuẩn (Standard Pass)',
                'label' => 'General Pass',
                'zone_code' => 'STD-PASS',
                'gate' => 'Cổng Chính',
                'surcharge' => 0,
                'price' => $basePrice,
                'color' => '#3A5A40',
                'badge' => 'badge-sage',
                'description' => 'Tham dự đầy đủ nội dung sự kiện, khu trải nghiệm triển lãm và nhận quà lưu niệm của ban tổ chức.',
                'perks' => ['Tham dự toàn bộ chương trình', 'Tài liệu sự kiện điện tử', 'Quà tặng check-in'],
                'icon' => '🎫',
            ],
            'combo_pass' => [
                'name' => 'Combo Nhóm 2 Người (Duo Pass)',
                'label' => 'Duo Group Pass',
                'zone_code' => 'COMBO-2',
                'gate' => 'Cổng Chính',
                'surcharge' => -($basePrice * 0.15),
                'price' => (int) round(($basePrice * 2) * 0.85),
                'color' => '#C08497',
                'badge' => 'badge-rose',
                'description' => 'Gói vé dành cho 2 người (đồng nghiệp, bạn bè) với ưu đãi giảm ngay 15% tổng hóa đơn.',
                'perks' => ['Bao gồm 2 vé tham dự', 'Giảm ngay 15%', 'Check-in cùng lúc'],
                'icon' => '👥',
            ],
        ];
    }

    public static function normalizeSeatType(string $seatType): string
    {
        return self::LEGACY_SEAT_TYPES[$seatType] ?? $seatType;
    }

    public static function seatPrice(string $seatType, int $basePrice): int
    {
        $tiers = self::for($basePrice, true);

        return (int) ($tiers[self::normalizeSeatType($seatType)]['price'] ?? $basePrice);
    }

    public static function isPassTier(string $tier, bool $isSeated): bool
    {
        return isset(self::PASS_TIERS[$tier])
            && array_key_exists($tier, self::for(0, $isSeated));
    }

    /** @return list<string> */
    public static function passSeatTypes(string $tier): array
    {
        return self::PASS_TIERS[$tier]['seat_types'] ?? [];
    }

    public static function seatsPerUnit(string $tier): int
    {
        return self::PASS_TIERS[$tier]['seats_per_unit'] ?? 1;
    }

    /** Giá ghi vào mỗi booking_item khi mua theo hạng (combo 2 người chia đôi giá gói). */
    public static function pricePerSeat(string $tier, int $basePrice, bool $isSeated): int
    {
        $unitPrice = (int) self::for($basePrice, $isSeated)[$tier]['price'];

        return intdiv($unitPrice, self::seatsPerUnit($tier));
    }
}
