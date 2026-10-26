<?php

namespace App\Support;

use App\Models\SeatZone;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Illuminate\Support\Collection;

/**
 * Dữ liệu sơ đồ của một suất diễn, gom theo khu và theo tầng (level) để trang chọn chỗ vẽ.
 */
final class SeatMap
{
    /**
     * @return array{preset: string, is_seated: bool, available_total: int, levels: array<int, list<array>>}
     *   Mỗi khu: id, code, name, kind, color, gate, description, perks, price, available, total, max_col,
     *   rows = [['label', 'seats' => [['id', 'number', 'col', 'code', 'status', 'price']]]] (rỗng với khu đứng).
     */
    public static function forShowtime(Showtime $showtime): array
    {
        $showtime->loadMissing(['room', 'event']);
        $basePrice = (int) $showtime->base_price;

        $seats = $showtime->showtimeSeats()->with('seat')->orderBy('id')->get();
        $zones = $showtime->room->zones()->get();
        $byZone = $seats->groupBy(fn (ShowtimeSeat $seat) => $seat->seat->zone_id ?? 0);

        $zoneArrays = $zones->map(fn (SeatZone $zone) => self::zoneArray($zone, $byZone->get($zone->id, collect()), $basePrice));

        // Ghế cũ chưa thuộc khu nào (dữ liệu tạo tay): gom vào một khu ảo để vẫn chọn được.
        if ($byZone->has(0)) {
            $legacy = new SeatZone(['code' => 'STD', 'name' => 'Ghế ngồi', 'kind' => 'seated', 'level' => 1, 'color' => '#3A5A40']);
            $zoneArrays->prepend(self::zoneArray($legacy, $byZone->get(0), $basePrice));
        }

        $levels = $zoneArrays->groupBy('level')->sortKeys()
            ->map(fn (Collection $group) => $group->map(fn (array $zone) => collect($zone)->except('level')->all())->values()->all())
            ->all();

        return [
            'preset' => (string) ($showtime->room->layout_preset ?? 'custom_grid'),
            'is_seated' => (bool) $showtime->event->is_seated,
            'available_total' => $zoneArrays->sum('available'),
            'levels' => $levels,
        ];
    }

    /** Thẻ giá từng khu (không kèm ghế) — dùng ở trang chi tiết sự kiện và chế độ mua theo số lượng. */
    public static function zoneCards(Showtime $showtime): array
    {
        return collect(self::forShowtime($showtime)['levels'])
            ->flatten(1)
            ->map(fn (array $zone) => collect($zone)->except(['rows', 'max_col'])->all())
            ->values()
            ->all();
    }

    private static function zoneArray(SeatZone $zone, Collection $seats, int $basePrice): array
    {
        $priceOf = fn (ShowtimeSeat $seat) => $zone->exists
            ? $zone->priceFor($basePrice)
            : TicketTiers::seatPrice($seat->seat->type, $basePrice);

        $rows = $zone->isStanding() ? [] : $seats
            ->groupBy(fn (ShowtimeSeat $seat) => $seat->seat->row_label)
            ->map(fn (Collection $rowSeats, $label) => [
                'label' => (string) $label,
                'seats' => $rowSeats->map(fn (ShowtimeSeat $seat) => [
                    'id' => $seat->id,
                    'number' => $seat->seat->seat_number,
                    'col' => $seat->seat->pos_col ?? $seat->seat->seat_number,
                    'code' => $label.'-'.str_pad((string) $seat->seat->seat_number, 2, '0', STR_PAD_LEFT),
                    'status' => $seat->publicStatus(),
                    'price' => $priceOf($seat),
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return [
            'id' => $zone->id,
            'code' => $zone->code,
            'name' => $zone->name,
            'kind' => $zone->kind,
            'level' => $zone->level ?? 1,
            'color' => $zone->color,
            'gate' => $zone->gate,
            'description' => $zone->description,
            'perks' => $zone->perks ?? [],
            'price' => $zone->exists ? $zone->priceFor($basePrice) : (int) ($seats->map($priceOf)->min() ?? $basePrice),
            'available' => $seats->filter(fn (ShowtimeSeat $seat) => $seat->isHoldable())->count(),
            'total' => $seats->count(),
            'max_col' => (int) $seats->max(fn (ShowtimeSeat $seat) => $seat->seat->pos_col ?? $seat->seat->seat_number),
            'rows' => $rows,
        ];
    }
}
