<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Seat;

class RoomSeatGenerator
{
    public const PRESETS = ['mega_concert', 'sayhi_concert', 'sayhi_concert_d2', 'theater_hall', 'convention_center', 'custom_grid', 'zoned', 'outdoor_concert', 'theater', 'fixed_hall', 'conference_tables', 'admission_only'];

    /** Hàng ghế ẩn dùng cho vé đứng (GA) — không vẽ trên sơ đồ, server tự gán. */
    public const STANDING_ROW = 'GA';

    public const STANDING_CAPACITY = 40;

    /**
     * Xóa toàn bộ ghế cũ của phòng và sinh lại theo preset.
     * CHỈ gọi khi phòng chưa có suất diễn (xóa ghế sẽ cascade xóa showtime_seats).
     */
    public function generate(Room $room, string $preset, array $config): int
    {
        $room->zones()->delete();
        $room->forceFill(['diagram_mode' => Room::MODE_SEATS])->saveQuietly();
        $room->seats()->delete();

        $now = now();
        $inserts = [];

        foreach ($this->rows($preset, $config) as [$rowLabel, $count, $type]) {
            for ($number = 1; $number <= $count; $number++) {
                $inserts[] = [
                    'room_id' => $room->id,
                    'row_label' => $rowLabel,
                    'seat_number' => $number,
                    'type' => $type,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($inserts, 200) as $chunk) {
            Seat::insert($chunk);
        }

        return count($inserts);
    }

    /** Rows used to bootstrap a legacy preset as explicit venue zones. */
    public function rowsForPreset(string $preset, array $config = []): array
    {
        return $this->rows($preset, $config);
    }

    /**
     * Sinh ghế từ ma trận tùy chỉnh do Admin thiết kế trực tiếp.
     */
    public function generateFromMatrix(Room $room, array $matrixRows, string $preset = 'mega_concert', array $seatConfig = []): int
    {
        $room->zones()->delete();
        $room->forceFill(['diagram_mode' => Room::MODE_SEATS])->saveQuietly();
        $room->seats()->delete();

        $now = now();
        $inserts = [];
        $validTypes = ['svip_diamond', 'vip_gold', 'cat1_stand', 'cat2_wings', 'skybox_suite', 'standing_pit'];

        foreach ($matrixRows as $r) {
            $rowLabel = trim((string) ($r['row'] ?? ''));
            if ($rowLabel === '') {
                continue;
            }

            $seats = (array) ($r['seats'] ?? []);
            foreach ($seats as $s) {
                $isAisle = ! empty($s['is_aisle']) || ($s['type'] ?? '') === 'aisle';
                if ($isAisle) {
                    continue; // Bỏ qua ô lối đi
                }

                $type = (string) ($s['type'] ?? 'cat1_stand');
                if (! in_array($type, $validTypes, true)) {
                    $type = 'cat1_stand';
                }

                $inserts[] = [
                    'room_id' => $room->id,
                    'row_label' => $rowLabel,
                    'seat_number' => (int) ($s['number'] ?? 1),
                    'type' => $type,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Tùy chỉnh các khu đứng Fanzone theo cấu hình Admin (nếu có)
        $standingZones = $seatConfig['standing_zones'] ?? null;
        if (is_array($standingZones) && ! empty($standingZones)) {
            foreach ($standingZones as $zone) {
                $count = max(0, (int) ($zone['capacity'] ?? 20));
                $code = trim((string) ($zone['code'] ?? 'GA'));
                if ($code === '') {
                    $code = 'GA';
                }
                for ($num = 1; $num <= $count; $num++) {
                    $inserts[] = [
                        'room_id' => $room->id,
                        'row_label' => $code,
                        'seat_number' => $num,
                        'type' => 'standing_pit',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        } else {
            // Mặc định cho concert nếu chưa có khu đứng riêng
            $hasGa = collect($inserts)->contains('row_label', self::STANDING_ROW);
            if (in_array($preset, ['mega_concert', 'sayhi_concert', 'sayhi_concert_d2'], true) && ! $hasGa) {
                $standingCapacity = (int) ($seatConfig['standing_capacity'] ?? ($preset === 'sayhi_concert_d2' ? 60 : self::STANDING_CAPACITY));
                for ($num = 1; $num <= $standingCapacity; $num++) {
                    $inserts[] = [
                        'room_id' => $room->id,
                        'row_label' => self::STANDING_ROW,
                        'seat_number' => $num,
                        'type' => 'standing_pit',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($inserts, 200) as $chunk) {
            Seat::insert($chunk);
        }

        return count($inserts);
    }

    /**
     * @return list<array{0: string, 1: int, 2: string}> [row_label, số ghế, loại ghế]
     */
    private function rows(string $preset, array $config): array
    {
        return match ($preset) {
            'mega_concert' => [
                ['SVIP-A', 12, 'svip_diamond'], ['SVIP-B', 12, 'svip_diamond'],
                ['FL-1', 14, 'vip_gold'], ['FL-2', 14, 'vip_gold'], ['FL-3', 14, 'vip_gold'], ['FL-4', 14, 'vip_gold'],
                ['A1', 16, 'cat1_stand'], ['A2', 16, 'cat1_stand'], ['A3', 16, 'cat1_stand'],
                ['B1', 14, 'cat1_stand'], ['B2', 14, 'cat1_stand'],
                ['C1', 12, 'cat2_wings'], ['C2', 12, 'cat2_wings'],
                ['D1', 12, 'cat2_wings'], ['D2', 12, 'cat2_wings'],
                ['SB', 8, 'skybox_suite'],
                [self::STANDING_ROW, self::STANDING_CAPACITY, 'standing_pit'],
            ],
            'sayhi_concert' => [
                ['SVIP-SH1', 12, 'svip_diamond'], ['SVIP-SH2', 12, 'svip_diamond'],
                ['VIP-SH1', 14, 'vip_gold'], ['VIP-SH2', 14, 'vip_gold'], ['VIP-SH3', 14, 'vip_gold'], ['VIP-SH4', 14, 'vip_gold'],
                ['CAT1-A1', 16, 'cat1_stand'], ['CAT1-A2', 16, 'cat1_stand'], ['CAT1-A3', 16, 'cat1_stand'],
                ['CAT2-B1', 14, 'cat1_stand'], ['CAT2-B2', 14, 'cat1_stand'],
                ['CAT3-C1', 12, 'cat2_wings'], ['CAT3-C2', 12, 'cat2_wings'],
                ['CAT3-D1', 12, 'cat2_wings'], ['CAT3-D2', 12, 'cat2_wings'],
                ['SKYLNG', 8, 'skybox_suite'],
                [self::STANDING_ROW, self::STANDING_CAPACITY, 'standing_pit'],
            ],
            'sayhi_concert_d2' => [
                ['SVIP-D2-1', 14, 'svip_diamond'], ['SVIP-D2-2', 14, 'svip_diamond'],
                ['VIP-D2-1', 16, 'vip_gold'], ['VIP-D2-2', 16, 'vip_gold'], ['VIP-D2-3', 16, 'vip_gold'], ['VIP-D2-4', 16, 'vip_gold'],
                ['CAT1-D2-1', 18, 'cat1_stand'], ['CAT1-D2-2', 18, 'cat1_stand'], ['CAT1-D2-3', 18, 'cat1_stand'],
                ['CAT2-D2-1', 16, 'cat1_stand'], ['CAT2-D2-2', 16, 'cat1_stand'],
                ['CAT3-C1', 14, 'cat2_wings'], ['CAT3-C2', 14, 'cat2_wings'],
                ['CAT3-D1', 14, 'cat2_wings'], ['CAT3-D2', 14, 'cat2_wings'],
                ['SKYLNG-1', 8, 'skybox_suite'], ['SKYLNG-2', 8, 'skybox_suite'],
                [self::STANDING_ROW, 60, 'standing_pit'],
            ],
            'theater_hall' => [
                ['ST-1', 16, 'vip_gold'], ['ST-2', 16, 'vip_gold'],
                ['ST-3', 16, 'cat1_stand'], ['DC-1', 16, 'cat1_stand'], ['DC-2', 16, 'cat1_stand'],
                ['GL-1', 16, 'cat2_wings'], ['GL-2', 16, 'cat2_wings'],
            ],
            'convention_center' => [
                ['KN-A', 18, 'svip_diamond'], ['KN-B', 18, 'svip_diamond'],
                ['STD-1', 18, 'cat1_stand'], ['STD-2', 18, 'cat1_stand'],
                ['STD-3', 18, 'cat1_stand'], ['STD-4', 18, 'cat1_stand'],
            ],
            default => $this->customGridRows($config),
        };
    }

    private function customGridRows(array $config): array
    {
        $rowsCount = max(2, min(26, (int) ($config['rows'] ?? 10)));
        $colsCount = max(4, min(40, (int) ($config['cols'] ?? 14)));
        $alphabet = range('A', 'Z');
        $rows = [];

        for ($r = 0; $r < $rowsCount; $r++) {
            $type = $r < 2 ? 'svip_diamond' : ($r < 5 ? 'vip_gold' : 'cat1_stand');
            $rows[] = [$alphabet[$r], $colsCount, $type];
        }

        return $rows;
    }
}
