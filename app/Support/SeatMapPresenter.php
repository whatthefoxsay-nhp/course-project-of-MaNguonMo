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
        $isStadium = in_array($showtime->room->layout_preset, ['mega_concert', 'sayhi_concert', 'sayhi_concert_d2', 'outdoor_concert'], true);

        return $showtime->showtimeSeats()
            ->with('seat.zone')
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
                    'zone_id' => $seat->zone_id,
                    'zone_code' => $seat->zone?->code,
                    'zone_kind' => $seat->zone?->kind,
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

    /** @return list<array<string, mixed>> */
    public static function zonesForShowtime(Showtime $showtime): array
    {
        $showtime->loadMissing('room.zones.seats');
        $tiers = TicketTiers::for($showtime->base_price, (bool) $showtime->event?->is_seated);

        return $showtime->room->zones->map(function ($zone) use ($showtime, $tiers) {
            $seatTypes = $zone->seats->pluck('type')->unique();
            $seatCount = $zone->seats->count();
            $availableSeats = $showtime->showtimeSeats()->whereHas('seat', fn ($query) => $query->where('zone_id', $zone->id))
                ->where(fn ($query) => $query->where('status', 'available')->orWhere(fn ($expired) => $expired->where('status', 'held')->where('held_until', '<', now())))
                ->count();
            $occupiedTickets = $zone->tickets()->where('showtime_id', $showtime->id)
                ->where(fn ($query) => $query->where('status', 'booked')->orWhere(fn ($held) => $held->where('status', 'held')->where('held_until', '>', now())))
                ->count();
            $tierKey = $zone->ticket_tier ?: ($zone->isStanding() && $showtime->event?->is_seated ? 'standing_pit' : 'standard_pass');
            $tier = $tiers[$tierKey] ?? null;
            $price = $zone->hasSeats()
                ? TicketTiers::seatPrice($zone->seat_type, $showtime->base_price)
                : (int) ($tier['price'] ?? $showtime->base_price);

            return [
                'id' => $zone->id,
                'code' => $zone->code,
                'name' => $zone->name,
                'kind' => $zone->kind,
                'capacity' => $zone->effectiveCapacity(),
                'available' => $zone->hasSeats() ? $availableSeats : max(0, $zone->capacity - $occupiedTickets),
                'price' => $price,
                'ticket_tier' => $tierKey,
                'seat_type' => $zone->seat_type,
                'type_names' => $seatTypes->values()->all(),
            ];
        })->values()->all();
    }

    /** @return array{0: string, 1: string} [sector, nhãn hiển thị] */
    private static function sector(string $row, bool $isStadium): array
    {
        if (! $isStadium) {
            return ['general', self::formatGeneralSectorLabel($row)];
        }

        return match (true) {
            str_starts_with($row, 'SVIP') => ['floor', 'SVIP Diamond Hàng '.substr($row, -1)],
            str_starts_with($row, 'VIP') || str_starts_with($row, 'FL') => ['floor', 'VIP Gold Hàng '.substr($row, -1)],
            str_starts_with($row, 'SB') || str_starts_with($row, 'SKYLNG') => ['skybox', 'Sky Lounge VIP Suites (Tầng Thượng)'],
            str_starts_with($row, 'CAT1') || str_starts_with($row, 'A') => ['center_stand', 'Khán Đài CAT 1 Hàng '.substr($row, -1).' (Trung Tâm Tầng 1)'],
            str_starts_with($row, 'CAT2') || str_starts_with($row, 'B') => ['upper_stand', 'Khán Đài CAT 2 Hàng '.substr($row, -1).' (Trung Tâm Tầng 2)'],
            str_starts_with($row, 'CAT3-C') || str_starts_with($row, 'C') => ['left_stand', 'Khán Đài CAT 3 Hàng '.substr($row, -1).' (Cánh Trái)'],
            str_starts_with($row, 'CAT3-D') || str_starts_with($row, 'D') => ['right_stand', 'Khán Đài CAT 3 Hàng '.substr($row, -1).' (Cánh Phải)'],
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
