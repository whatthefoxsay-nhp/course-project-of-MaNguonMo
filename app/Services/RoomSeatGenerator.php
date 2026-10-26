<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatZone;
use App\Support\LayoutTemplates;

class RoomSeatGenerator
{
    /**
     * Sinh sơ đồ theo mẫu có sẵn (xem LayoutTemplates).
     * CHỈ gọi khi phòng chưa có suất diễn (xóa ghế sẽ cascade xóa showtime_seats).
     */
    public function generate(Room $room, string $preset, array $options = []): int
    {
        return $this->apply($room, LayoutTemplates::get($preset, $options));
    }

    /**
     * Xóa khu + ghế cũ của phòng rồi dựng lại từ layout (đã được kiểm tra hợp lệ).
     * Ghế trong hàng đánh số 1..n bỏ qua lối đi; pos_col là vị trí ô (tính từ 1).
     * Khu đứng có `capacity` ghế ẩn, row_label = mã khu, pos_col = null.
     *
     * @return int tổng số ghế đã tạo
     */
    public function apply(Room $room, array $layout): int
    {
        Seat::where('room_id', $room->id)->delete();
        SeatZone::where('room_id', $room->id)->delete();

        $zones = [];
        foreach (array_values($layout['zones']) as $index => $zone) {
            $zones[$zone['code']] = SeatZone::create([
                'room_id' => $room->id,
                'code' => $zone['code'],
                'name' => $zone['name'],
                'kind' => $zone['kind'],
                'level' => $zone['level'] ?? 1,
                'sort_order' => $index,
                'surcharge' => $zone['surcharge'] ?? 0,
                'color' => $zone['color'] ?? '#3A5A40',
                'gate' => $zone['gate'] ?? null,
                'description' => $zone['description'] ?? null,
                'perks' => array_values($zone['perks'] ?? []),
                'capacity' => $zone['kind'] === 'standing' ? (int) ($zone['capacity'] ?? 0) : null,
            ]);
        }

        $now = now();
        $inserts = [];
        $seat = fn (SeatZone $zone, string $row, int $number, ?int $col) => [
            'room_id' => $room->id,
            'zone_id' => $zone->id,
            'row_label' => $row,
            'seat_number' => $number,
            'pos_col' => $col,
            'type' => $zone->code,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        foreach ($layout['rows'] ?? [] as $row) {
            $number = 0;
            foreach (array_values($row['cells']) as $index => $cell) {
                if (! empty($cell['aisle'])) {
                    continue;
                }
                $inserts[] = $seat($zones[$cell['zone']], $row['label'], ++$number, $index + 1);
            }
        }

        foreach ($zones as $zone) {
            if ($zone->isStanding()) {
                for ($number = 1; $number <= $zone->capacity; $number++) {
                    $inserts[] = $seat($zone, $zone->code, $number, null);
                }
            }
        }

        foreach (array_chunk($inserts, 200) as $chunk) {
            Seat::insert($chunk);
        }

        return count($inserts);
    }
}
