<?php

namespace App\Support;

use App\Models\Showtime;
use App\Models\ShowtimeSeat;

/**
 * Chuyển showtime_seats thành object đúng dạng mà <x-seat-map> đang render.
 */
final class SeatMapPresenter
{
    /** @return list<object> */
    public static function forShowtime(Showtime $showtime): array
    {
        $showtime->loadMissing('room');
        $tiers = TicketTiers::for($showtime->base_price, true);
        $isStadium = $showtime->room->layout_preset === 'mega_concert';

        return $showtime->showtimeSeats()
            ->with('seat')
            ->orderBy('id')
            ->get()
            ->reject(fn (ShowtimeSeat $showtimeSeat) => $showtimeSeat->seat->type === 'standing_pit')
            ->map(function (ShowtimeSeat $showtimeSeat) use ($tiers, $isStadium, $showtime) {
                $seat = $showtimeSeat->seat;
                $type = TicketTiers::normalizeSeatType($seat->type);
                $tier = $tiers[$type] ?? $tiers['cat2_wings'];
                [$sector, $sectorLabel] = self::sector($seat->row_label, $isStadium);

                return (object) [
                    'id' => $showtimeSeat->id,
                    'showtime_id' => $showtime->id,
                    'block' => $seat->row_label,
                    'sector' => $sector,
                    'sector_label' => $sectorLabel,
                    'row_label' => $seat->row_label,
                    'seat_number' => $seat->seat_number,
                    'code' => $seat->row_label.'-'.str_pad((string) $seat->seat_number, 2, '0', STR_PAD_LEFT),
                    'type' => $type,
                    'type_name' => $tier['name'],
                    'type_badge' => $tier['badge'],
                    'type_icon' => $tier['icon'],
                    'gate' => $tier['gate'],
                    'price' => (int) $tier['price'],
                    'status' => $showtimeSeat->publicStatus(),
                    'perks' => $tier['perks'],
                ];
            })
            ->values()
            ->all();
    }

    /** @return array{0: string, 1: string} [sector, nhãn hiển thị] */
    private static function sector(string $row, bool $isStadium): array
    {
        if (! $isStadium) {
            return ['general', self::formatGeneralSectorLabel($row)];
        }

        return match (true) {
            str_starts_with($row, 'SVIP') => ['floor', 'SVIP B-Stage Hàng '.substr($row, -1)],
            str_starts_with($row, 'FL') => ['floor', 'VIP Floor Hàng '.substr($row, 3)],
            str_starts_with($row, 'SB') => ['skybox', 'Skybox VIP Suites (Tầng Thượng)'],
            str_starts_with($row, 'A') => ['center_stand', 'Khán Đài A Hàng '.substr($row, 1).' (Trung Tâm Tầng 1)'],
            str_starts_with($row, 'B') => ['upper_stand', 'Khán Đài B Hàng '.substr($row, 1).' (Trung Tâm Tầng 2)'],
            str_starts_with($row, 'C') => ['left_stand', 'Khán Đài C Hàng '.substr($row, 1).' (Cánh Trái)'],
            str_starts_with($row, 'D') => ['right_stand', 'Khán Đài D Hàng '.substr($row, 1).' (Cánh Phải)'],
            default => ['general', "Hàng {$row}"],
        };
    }

    private static function formatGeneralSectorLabel(string $row): string
    {
        return match (true) {
            str_starts_with($row, 'ST') => 'Tầng Trệt VIP Stalls Hàng '.substr($row, 3),
            str_starts_with($row, 'DC') => 'Khán Đài Dress Circle Hàng '.substr($row, 3).' (Tầng 1)',
            str_starts_with($row, 'GL') => 'Ban Công Upper Gallery Hàng '.substr($row, 3).' (Tầng 2)',
            str_starts_with($row, 'KN') => 'Khu Keynote VIP Hàng '.substr($row, 3),
            str_starts_with($row, 'STD') => 'Khu Tiêu Chuẩn Hàng '.substr($row, 4),
            default => "Hàng {$row}",
        };
    }
}
