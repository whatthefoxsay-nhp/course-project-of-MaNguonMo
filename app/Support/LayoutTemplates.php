<?php

namespace App\Support;

use App\Models\Room;
use App\Models\SeatZone;
use InvalidArgumentException;

/**
 * Sáu mẫu bố trí chỗ ngồi, mỗi loại sự kiện một kiểu.
 *
 * Dạng "layout" dùng chung cho seeder, trình dựng admin và bộ sinh ghế:
 *   ['zones' => [['code','name','kind','level','surcharge','color','gate','description','perks','capacity'], ...],
 *    'rows'  => [['label' => 'FL-1', 'cells' => [['zone' => 'VIP'], ['aisle' => true], ...]], ...]]
 * Thứ tự trong `zones` là thứ tự trái → phải (sort_order) trong cùng một tầng (level).
 */
final class LayoutTemplates
{
    public const PRESETS = ['mega_concert', 'theater_hall', 'convention_center', 'banquet_hall', 'open_space', 'custom_grid'];

    private const META = [
        'mega_concert' => ['🏟️', 'Sân vận động Concert', 'Khu đứng Fanzone hai bên sân khấu, ghế mặt sàn, khán đài 3 phía và Skybox'],
        'theater_hall' => ['🎭', 'Nhà hát & Giao hưởng', 'Tầng trệt, tầng lửng và ban công, có lối đi giữa'],
        'convention_center' => ['🏢', 'Hội trường Hội nghị', 'Hàng ghế VIP Keynote phía trước, 3 khối ghế trái – giữa – phải'],
        'banquet_hall' => ['🍽️', 'Bàn tiệc & Workshop', 'Bàn tròn 6–8 ghế, bàn VIP gần sân khấu'],
        'open_space' => ['🎪', 'Không gian mở', 'Triển lãm, festival ngoài trời: vé vào cửa theo sức chứa, không chọn ghế'],
        'custom_grid' => ['⚡', 'Lưới tùy chỉnh', 'Tự chọn số hàng, số ghế mỗi hàng rồi tô khu và lối đi'],
    ];

    public static function get(string $preset, array $options = []): array
    {
        return match ($preset) {
            'mega_concert' => self::megaConcert(),
            'theater_hall' => self::theaterHall(),
            'convention_center' => self::conventionCenter(),
            'banquet_hall' => self::banquetHall(),
            'open_space' => self::openSpace(),
            'custom_grid' => self::customGrid((int) ($options['rows'] ?? 10), (int) ($options['cols'] ?? 14)),
            default => throw new InvalidArgumentException("Mẫu bố trí không tồn tại: {$preset}"),
        };
    }

    /** @return array<string, array{icon: string, label: string, subtitle: string, layout: array}> */
    public static function all(): array
    {
        $templates = [];
        foreach (self::PRESETS as $preset) {
            [$icon, $label, $subtitle] = self::META[$preset];
            $templates[$preset] = ['icon' => $icon, 'label' => $label, 'subtitle' => $subtitle, 'layout' => self::get($preset)];
        }

        return $templates;
    }

    public static function label(string $preset): string
    {
        [$icon, $label] = self::META[$preset] ?? self::META['custom_grid'];

        return "{$icon} {$label}";
    }

    /** Đọc ngược khu + ghế đang có của phòng thành layout (ô trống giữa các cột thành lối đi). */
    public static function fromRoom(Room $room): array
    {
        $zones = $room->zones()->get();
        $seats = $room->seats()->orderBy('id')->get(['id', 'zone_id', 'row_label', 'pos_col']);
        $codeOf = $zones->pluck('code', 'id');

        $rows = $seats->whereNotNull('pos_col')
            ->groupBy('row_label')
            ->map(function ($rowSeats, $label) use ($codeOf) {
                $byCol = $rowSeats->keyBy('pos_col');
                $cells = [];
                for ($col = 1; $col <= $rowSeats->max('pos_col'); $col++) {
                    $cells[] = $byCol->has($col) ? ['zone' => $codeOf[$byCol[$col]->zone_id]] : ['aisle' => true];
                }

                return ['label' => (string) $label, 'cells' => $cells];
            })
            ->values()
            ->all();

        $standingCounts = $seats->whereNull('pos_col')->countBy('zone_id');

        return [
            'zones' => $zones->map(fn (SeatZone $zone) => [
                'code' => $zone->code,
                'name' => $zone->name,
                'kind' => $zone->kind,
                'level' => $zone->level,
                'surcharge' => $zone->surcharge,
                'color' => $zone->color,
                'gate' => $zone->gate,
                'description' => $zone->description,
                'perks' => $zone->perks ?? [],
                'capacity' => $zone->isStanding() ? (int) ($standingCounts[$zone->id] ?? $zone->capacity ?? 0) : null,
            ])->values()->all(),
            'rows' => $rows,
        ];
    }

    private static function megaConcert(): array
    {
        $rows = [
            self::row('SVIP-A', ['SVIP', 12]), self::row('SVIP-B', ['SVIP', 12]),
        ];
        foreach (['FL-1', 'FL-2', 'FL-3', 'FL-4'] as $label) {
            $rows[] = self::row($label, ['VIP', 7], 'aisle', ['VIP', 7]);
        }
        $rows[] = self::row('C1', ['KD-C', 10]);
        $rows[] = self::row('C2', ['KD-C', 10]);
        foreach (['A1', 'A2', 'A3'] as $label) {
            $rows[] = self::row($label, ['KD-A', 4], 'aisle', ['KD-A', 8], 'aisle', ['KD-A', 4]);
        }
        $rows[] = self::row('D1', ['KD-D', 10]);
        $rows[] = self::row('D2', ['KD-D', 10]);
        $rows[] = self::row('SB', ['SKY', 8]);

        return ['zones' => [
            self::zone('GA-L', 'Fanzone đứng trái', 'standing', 1, -20000, '#99334D', 'Cổng Fanzone 1',
                'Khu đứng sát cánh trái sân khấu, gần nghệ sĩ nhất.', ['Vòng tay LED'], 40),
            self::zone('SVIP', 'SVIP sát sân khấu', 'seated', 1, 150000, '#D4AF37', 'Cổng VIP 1',
                'Hai hàng ghế đầu ngay trước sân khấu.', ['Lối check-in riêng', 'Quà tặng độc quyền']),
            self::zone('GA-R', 'Fanzone đứng phải', 'standing', 1, -20000, '#99334D', 'Cổng Fanzone 2',
                'Khu đứng sát cánh phải sân khấu.', ['Vòng tay LED'], 40),
            self::zone('VIP', 'VIP mặt sàn', 'seated', 2, 80000, '#E07A5F', 'Cổng VIP 2',
                'Ghế mặt sàn trung tâm, nhìn thẳng sân khấu.', ['Lanyard VIP']),
            self::zone('KD-C', 'Khán đài C (cánh trái)', 'seated', 3, 0, '#1F2937', 'Cổng C',
                'Khán đài cánh trái, giá phổ thông.'),
            self::zone('KD-A', 'Khán đài A (trung tâm)', 'seated', 3, 40000, '#3A5A40', 'Cổng A',
                'Khán đài chính đối diện sân khấu, thấy toàn cảnh hiệu ứng.'),
            self::zone('KD-D', 'Khán đài D (cánh phải)', 'seated', 3, 0, '#334155', 'Cổng D',
                'Khán đài cánh phải, giá phổ thông.'),
            self::zone('SKY', 'Skybox VIP', 'seated', 4, 300000, '#C08497', 'Thang máy VIP',
                'Phòng kính tầng thượng, sofa riêng, đồ uống miễn phí.', ['Phòng riêng', 'Đồ uống & snack']),
        ], 'rows' => $rows];
    }

    private static function theaterHall(): array
    {
        $rows = [];
        foreach (['ST-A', 'ST-B', 'ST-C', 'ST-D', 'ST-E'] as $label) {
            $rows[] = self::row($label, ['STALLS', 8], 'aisle', ['STALLS', 8]);
        }
        foreach (['DC-A', 'DC-B', 'DC-C'] as $label) {
            $rows[] = self::row($label, ['CIRCLE', 7], 'aisle', ['CIRCLE', 7]);
        }
        $rows[] = self::row('GL-A', ['GALLERY', 18]);
        $rows[] = self::row('GL-B', ['GALLERY', 18]);

        return ['zones' => [
            self::zone('STALLS', 'Tầng trệt (Stalls)', 'seated', 1, 80000, '#D4AF37', 'Cửa trệt A',
                'Gần sân khấu nhất, âm thanh trực diện.', ['Brochure chương trình']),
            self::zone('CIRCLE', 'Tầng lửng (Dress Circle)', 'seated', 2, 40000, '#C08497', 'Cửa tầng 1',
                'Góc nhìn bao quát toàn bộ sân khấu và dàn nhạc.'),
            self::zone('GALLERY', 'Ban công (Gallery)', 'seated', 3, 0, '#3A5A40', 'Cửa tầng 2',
                'Tầng trên cùng, giá dễ chịu.'),
        ], 'rows' => $rows];
    }

    private static function conventionCenter(): array
    {
        $rows = [self::row('KN-A', ['KEYNOTE', 12]), self::row('KN-B', ['KEYNOTE', 12])];
        foreach ([1, 2, 3, 4, 5] as $n) {
            $rows[] = self::row("L{$n}", ['BL-L', 6]);
        }
        foreach ([1, 2, 3, 4, 5] as $n) {
            $rows[] = self::row("C{$n}", ['BL-C', 10]);
        }
        foreach ([1, 2, 3, 4, 5] as $n) {
            $rows[] = self::row("R{$n}", ['BL-R', 6]);
        }

        return ['zones' => [
            self::zone('KEYNOTE', 'Hàng ghế VIP Keynote', 'seated', 1, 100000, '#D4AF37', 'Cổng VIP',
                'Hai hàng đầu, giao lưu trực tiếp với diễn giả.', ['Buffet trưa', 'Gặp gỡ diễn giả']),
            self::zone('BL-L', 'Khối trái', 'seated', 2, 0, '#3A5A40', 'Cổng chính',
                'Khối ghế bên trái hội trường.', ['Tài liệu điện tử']),
            self::zone('BL-C', 'Khối giữa', 'seated', 2, 20000, '#2563EB', 'Cổng chính',
                'Khối ghế chính giữa, nhìn thẳng màn chiếu.', ['Tài liệu điện tử']),
            self::zone('BL-R', 'Khối phải', 'seated', 2, 0, '#3A5A40', 'Cổng chính',
                'Khối ghế bên phải hội trường.', ['Tài liệu điện tử']),
        ], 'rows' => $rows];
    }

    private static function banquetHall(): array
    {
        $rows = [];
        foreach (range(1, 12) as $n) {
            $rows[] = $n <= 4
                ? self::row(sprintf('B%02d', $n), ['VIPT', 8])
                : self::row(sprintf('B%02d', $n), ['STDT', 6]);
        }

        return ['zones' => [
            self::zone('VIPT', 'Bàn VIP gần sân khấu', 'table', 1, 120000, '#D4AF37', 'Lối VIP',
                'Bàn 8 người ngay trước sân khấu, phục vụ tại bàn.', ['Teabreak tại bàn', 'Bộ dụng cụ thực hành']),
            self::zone('STDT', 'Bàn tiêu chuẩn', 'table', 2, 0, '#3A5A40', 'Cửa chính',
                'Bàn 6 người, phù hợp nhóm làm bài thực hành.', ['Bộ dụng cụ thực hành']),
        ], 'rows' => $rows];
    }

    private static function openSpace(): array
    {
        return ['zones' => [
            self::zone('VIPX', 'Vé VIP / Fast-track', 'standing', 1, 150000, '#D4AF37', 'Cổng ưu tiên',
                'Vào cổng ưu tiên, không xếp hàng, có khu nghỉ riêng.', ['Không xếp hàng', 'Quà lưu niệm'], 50),
            self::zone('GEN', 'Vé vào cửa phổ thông', 'standing', 1, 0, '#3A5A40', 'Cổng chính',
                'Tự do tham quan toàn bộ khu vực trong khung giờ của suất.', [], 300),
        ], 'rows' => []];
    }

    private static function customGrid(int $rowCount, int $colCount): array
    {
        $rowCount = max(2, min(26, $rowCount));
        $colCount = max(4, min(40, $colCount));
        $rows = [];
        foreach (array_slice(range('A', 'Z'), 0, $rowCount) as $index => $label) {
            $rows[] = self::row($label, [$index < 2 ? 'VIP' : 'STD', $colCount]);
        }

        return ['zones' => [
            self::zone('VIP', 'Ghế VIP', 'seated', 1, 80000, '#D4AF37', 'Cổng VIP', 'Hai hàng ghế đầu.'),
            self::zone('STD', 'Ghế tiêu chuẩn', 'seated', 2, 0, '#3A5A40', 'Cổng chính', 'Các hàng ghế còn lại.'),
        ], 'rows' => $rows];
    }

    private static function zone(
        string $code, string $name, string $kind, int $level, int $surcharge, string $color,
        string $gate, string $description, array $perks = [], ?int $capacity = null,
    ): array {
        return compact('code', 'name', 'kind', 'level', 'surcharge', 'color', 'gate', 'description', 'perks', 'capacity');
    }

    /** Một hàng: mỗi đoạn là [mã khu, số ghế] hoặc 'aisle' (1 ô lối đi). */
    private static function row(string $label, array|string ...$segments): array
    {
        $cells = [];
        foreach ($segments as $segment) {
            if ($segment === 'aisle') {
                $cells[] = ['aisle' => true];

                continue;
            }
            [$zone, $count] = $segment;
            array_push($cells, ...array_fill(0, $count, ['zone' => $zone]));
        }

        return ['label' => $label, 'cells' => $cells];
    }
}
