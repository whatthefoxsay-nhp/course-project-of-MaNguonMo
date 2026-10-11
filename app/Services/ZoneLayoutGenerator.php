<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomZone;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ZoneLayoutGenerator
{
    public function generate(Room $room, array $zoneDefinitions): int
    {
        if ($room->showtimes()->exists()) {
            throw ValidationException::withMessages([
                'zones' => 'Không thể thay đổi khu vực khi khán phòng đã có suất diễn. Hãy nhân bản khán phòng để tạo sơ đồ mới.',
            ]);
        }

        return DB::transaction(function () use ($room, $zoneDefinitions) {
            $room->seats()->delete();
            $room->zones()->delete();
            $seatCount = 0;

            foreach (array_values($zoneDefinitions) as $index => $definition) {
                $kind = $definition['kind'] ?? RoomZone::KIND_SEATED;
                $zone = RoomZone::create([
                    'room_id' => $room->id,
                    'code' => strtoupper(trim((string) ($definition['code'] ?? ''))),
                    'name' => trim((string) ($definition['name'] ?? '')),
                    'kind' => $kind,
                    'seat_type' => $definition['seat_type'] ?? 'cat1_stand',
                    'ticket_tier' => $definition['ticket_tier'] ?? null,
                    'capacity' => in_array($kind, [RoomZone::KIND_STANDING, RoomZone::KIND_ADMISSION], true)
                        ? max(0, (int) ($definition['capacity'] ?? 0)) : 0,
                    'layout' => $definition['layout'] ?? null,
                    'meta' => $definition['meta'] ?? null,
                    'sort_order' => $definition['sort_order'] ?? $index,
                ]);

                if (! $zone->hasSeats()) {
                    continue;
                }

                $rows = $definition['layout'] ?? $this->makeGrid($definition, $kind);
                $inserts = [];
                foreach ($rows as $rowIndex => $row) {
                    $label = trim((string) ($row['row'] ?? ''));
                    if ($label === '') {
                        continue;
                    }
                    foreach ((array) ($row['seats'] ?? []) as $seat) {
                        if (! empty($seat['is_aisle']) || ($seat['type'] ?? '') === 'aisle') {
                            continue;
                        }
                        $number = (int) ($seat['number'] ?? 0);
                        if ($number < 1) {
                            continue;
                        }
                        $type = $seat['type'] ?? $zone->seat_type;
                        if (! in_array($type, RoomZone::SEATED_TYPES, true)) {
                            $type = $zone->seat_type;
                        }
                        $inserts[] = [
                            'room_id' => $room->id,
                            'zone_id' => $zone->id,
                            'row_label' => $zone->code.'-'.$label,
                            'seat_number' => $number,
                            'type' => $type,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                foreach (array_chunk($inserts, 200) as $chunk) {
                    Seat::insert($chunk);
                }
                $seatCount += count($inserts);
            }

            $room->recalculateCapacity();

            return $seatCount;
        });
    }

    private function makeGrid(array $definition, string $kind): array
    {
        if ($kind === RoomZone::KIND_TABLE) {
            $rowCount = max(1, min(100, (int) ($definition['tables'] ?? 1)));
            $cols = max(1, min(20, (int) ($definition['seats_per_table'] ?? 8)));
            $prefix = 'T';
        } else {
            $rowCount = max(1, min(26, (int) ($definition['rows'] ?? 6)));
            $cols = max(1, min(40, (int) ($definition['cols'] ?? 10)));
            $prefix = '';
        }

        $rows = [];
        for ($r = 0; $r < $rowCount; $r++) {
            $label = $kind === RoomZone::KIND_TABLE
                ? $prefix.str_pad((string) ($r + 1), 2, '0', STR_PAD_LEFT)
                : chr(65 + $r);
            $seats = [];
            for ($number = 1; $number <= $cols; $number++) {
                $seats[] = ['number' => $number, 'type' => $definition['seat_type'] ?? 'cat1_stand'];
            }
            $rows[] = ['row' => $label, 'seats' => $seats];
        }

        return $rows;
    }
}
