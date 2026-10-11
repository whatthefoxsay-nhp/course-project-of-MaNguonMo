<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Models\RoomZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateRoomsToZones extends Command
{
    protected $signature = 'rooms:migrate-zones {--dry-run : Show the migration report and roll back all changes}';

    protected $description = 'Create venue zones from legacy room seats and remove unused virtual GA seats';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $rows = [];

        DB::beginTransaction();
        try {
            foreach (Room::with('zones')->orderBy('id')->get() as $room) {
                if ($room->zones->isNotEmpty()) {
                    continue;
                }

                $oldCapacity = (int) $room->capacity;
                $allSeats = $room->seats()->orderBy('id')->get();
                $standingSeats = $allSeats->where('type', RoomZone::STANDING_TYPE);
                $deleteIds = [];
                $keepIds = [];

                foreach ($standingSeats as $seat) {
                    $hasBookingItem = $seat->showtimeSeats()->whereHas('bookingItems')->exists();
                    $hasSoldState = $seat->showtimeSeats()->where('status', 'booked')->exists();
                    $hasActiveHold = $seat->showtimeSeats()->where('status', 'held')->where('held_until', '>', now())->exists();
                    if ($hasBookingItem || $hasSoldState || $hasActiveHold) {
                        $keepIds[] = $seat->id;
                    } else {
                        $deleteIds[] = $seat->id;
                    }
                }

                $seatedSeats = $allSeats->reject(fn ($seat) => $seat->type === RoomZone::STANDING_TYPE);
                $groups = $seatedSeats->groupBy(fn ($seat) => $this->rowPrefix($seat->row_label).'|'.$seat->type);
                $usedCodes = [];
                foreach ($groups as $groupKey => $seats) {
                    [$prefix, $type] = explode('|', $groupKey, 2);
                    $code = $this->uniqueCode($prefix, $type, $usedCodes);
                    $zone = RoomZone::create([
                        'room_id' => $room->id,
                        'code' => $code,
                        'name' => $this->zoneName($prefix, $type),
                        'kind' => RoomZone::KIND_SEATED,
                        'seat_type' => $type,
                        'capacity' => 0,
                    ]);
                    $room->seats()->whereIn('id', $seats->pluck('id'))->update(['zone_id' => $zone->id]);
                }

                if ($standingSeats->isNotEmpty()) {
                    $gaZone = RoomZone::create([
                        'room_id' => $room->id,
                        'code' => $this->uniqueCode('GA', RoomZone::STANDING_TYPE, $usedCodes),
                        'name' => 'Khu đứng GA',
                        'kind' => RoomZone::KIND_STANDING,
                        'seat_type' => RoomZone::STANDING_TYPE,
                        'capacity' => $standingSeats->count(),
                    ]);
                    if ($keepIds !== []) {
                        $room->seats()->whereIn('id', $keepIds)->update(['zone_id' => $gaZone->id]);
                    }
                    if ($deleteIds !== []) {
                        $room->seats()->whereIn('id', $deleteIds)->delete();
                    }
                }

                if ($allSeats->isEmpty() && $oldCapacity > 0) {
                    RoomZone::create([
                        'room_id' => $room->id,
                        'code' => 'ADMIT',
                        'name' => 'Vé vào cửa',
                        'kind' => RoomZone::KIND_ADMISSION,
                        'seat_type' => 'cat1_stand',
                        'capacity' => $oldCapacity,
                    ]);
                }

                $newCapacity = $room->recalculateCapacity();
                $rows[] = [
                    $room->name,
                    $oldCapacity,
                    $newCapacity,
                    $newCapacity - $oldCapacity,
                    count($deleteIds),
                    count($keepIds),
                ];
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }

        $this->table(['Khán phòng', 'Sức chứa cũ', 'Tổng khu', 'Chênh lệch', 'GA xóa', 'GA giữ lại'], $rows);
        $this->info($dryRun
            ? 'Chế độ xem trước: đã hoàn tác toàn bộ thay đổi. Chạy lại không có --dry-run để áp dụng.'
            : 'Đã chuyển dữ liệu khán phòng sang mô hình khu vực.');

        return self::SUCCESS;
    }

    private function rowPrefix(string $row): string
    {
        $parts = preg_split('/[-_]/', trim($row));
        return strtoupper((string) ($parts[0] ?? $row));
    }

    private function uniqueCode(string $prefix, string $type, array &$used): string
    {
        $suffix = match ($type) {
            'svip_diamond' => 'S', 'vip_gold' => 'V', 'cat1_stand' => 'C1',
            'cat2_wings' => 'C2', 'skybox_suite' => 'SB', RoomZone::STANDING_TYPE => 'GA',
            default => 'Z',
        };
        $base = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $prefix), 0, 3).$suffix);
        $base = substr($base ?: 'ZONE', 0, 6);
        $code = $base;
        $index = 1;
        while (isset($used[$code])) {
            $code = substr($base, 0, 5).$index;
            $index++;
        }
        $used[$code] = true;

        return $code;
    }

    private function zoneName(string $prefix, string $type): string
    {
        $typeName = match ($type) {
            'svip_diamond' => 'SVIP', 'vip_gold' => 'VIP', 'cat1_stand' => 'Cat 1',
            'cat2_wings' => 'Cat 2', 'skybox_suite' => 'Skybox', default => 'Ghế ngồi',
        };

        return trim($typeName.' '.$prefix);
    }
}
