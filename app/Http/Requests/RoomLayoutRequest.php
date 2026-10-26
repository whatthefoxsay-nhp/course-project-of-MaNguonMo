<?php

namespace App\Http\Requests;

use App\Models\SeatZone;
use App\Support\LayoutTemplates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Form tạo/sửa địa điểm: thông tin phòng + sơ đồ (layout JSON, xem LayoutTemplates).
 * Mọi lỗi của sơ đồ gắn vào key `layout`; sơ đồ cũ không bị đụng tới khi có lỗi.
 */
class RoomLayoutRequest extends FormRequest
{
    private const MAX_ZONES = 20;

    private const MAX_ROWS = 60;

    private const MAX_CELLS_PER_ROW = 40;

    private const MAX_TOTAL_SEATS = 5000;

    private ?array $layout = null;

    public function authorize(): bool
    {
        return true; // route đã có middleware role:admin
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'capacity' => 'required|integer|min:10|max:50000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'layout_preset' => 'required|string|in:'.implode(',', LayoutTemplates::PRESETS),
            'layout' => 'required|string|max:200000',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('layout')) {
                    return;
                }
                $error = $this->layoutError();
                if ($error !== null) {
                    $validator->errors()->add('layout', $error);
                }
            },
        ];
    }

    /** Layout đã kiểm tra (chỉ gọi sau khi validate thành công). */
    public function layout(): array
    {
        return $this->layout ?? [];
    }

    private function layoutError(): ?string
    {
        $data = json_decode((string) $this->input('layout'), true);
        if (! is_array($data) || ! isset($data['zones'], $data['rows']) || ! is_array($data['zones']) || ! is_array($data['rows'])) {
            return 'Dữ liệu sơ đồ không hợp lệ, vui lòng tải lại trang và thử lại.';
        }
        if ($data['zones'] === [] || count($data['zones']) > self::MAX_ZONES) {
            return 'Sơ đồ cần từ 1 đến '.self::MAX_ZONES.' khu vực.';
        }
        if (count($data['rows']) > self::MAX_ROWS) {
            return 'Sơ đồ tối đa '.self::MAX_ROWS.' hàng ghế.';
        }

        $zones = [];
        foreach (array_values($data['zones']) as $index => $zone) {
            $error = $this->zoneError($zone, $index + 1, $zones);
            if ($error !== null) {
                return $error;
            }
            $zones[$zone['code']] = $this->normalizeZone($zone);
        }

        $standingCodes = array_map('strtoupper', array_keys(array_filter($zones, fn ($zone) => $zone['kind'] === 'standing')));
        $labels = [];
        $rows = [];
        $seatCount = 0;
        foreach (array_values($data['rows']) as $index => $row) {
            $label = is_array($row) ? ($row['label'] ?? null) : null;
            if (! is_string($label) || ! preg_match('/^[A-Za-z0-9-]{1,12}$/', $label)) {
                return 'Hàng thứ '.($index + 1).': nhãn hàng chỉ gồm chữ không dấu, số, dấu gạch ngang (tối đa 12 ký tự).';
            }
            $key = strtoupper($label);
            if (isset($labels[$key]) || in_array($key, $standingCodes, true)) {
                return "Nhãn hàng \"{$label}\" bị trùng (với hàng khác hoặc mã khu đứng).";
            }
            $labels[$key] = true;

            $cells = $row['cells'] ?? null;
            if (! is_array($cells) || $cells === [] || count($cells) > self::MAX_CELLS_PER_ROW) {
                return "Hàng {$label}: cần từ 1 đến ".self::MAX_CELLS_PER_ROW.' ô.';
            }
            $normalized = [];
            foreach ($cells as $cell) {
                if (is_array($cell) && ! empty($cell['aisle'])) {
                    $normalized[] = ['aisle' => true];

                    continue;
                }
                $code = is_array($cell) ? ($cell['zone'] ?? null) : null;
                if (! is_string($code) || ! isset($zones[$code]) || $zones[$code]['kind'] === 'standing') {
                    return "Hàng {$label}: có ô thuộc khu không tồn tại hoặc là khu đứng.";
                }
                $normalized[] = ['zone' => $code];
                $seatCount++;
            }
            $rows[] = ['label' => $label, 'cells' => $normalized];
        }

        $seatCount += array_sum(array_map(fn ($zone) => (int) $zone['capacity'], $zones));
        if ($seatCount < 1 || $seatCount > self::MAX_TOTAL_SEATS) {
            return 'Tổng số chỗ phải từ 1 đến '.number_format(self::MAX_TOTAL_SEATS).' (hiện có '.number_format($seatCount).').';
        }

        $this->layout = ['zones' => array_values($zones), 'rows' => $rows];

        return null;
    }

    private function zoneError(mixed $zone, int $position, array $seen): ?string
    {
        if (! is_array($zone)) {
            return "Khu thứ {$position}: dữ liệu không hợp lệ.";
        }
        $code = $zone['code'] ?? null;
        if (! is_string($code) || ! preg_match('/^[A-Z0-9-]{1,12}$/', $code)) {
            return "Khu thứ {$position}: mã khu chỉ gồm chữ IN HOA không dấu, số, dấu gạch ngang (tối đa 12 ký tự).";
        }
        if (isset($seen[$code])) {
            return "Mã khu \"{$code}\" bị trùng.";
        }
        $name = $zone['name'] ?? null;
        if (! is_string($name) || trim($name) === '' || mb_strlen($name) > 120) {
            return "Khu {$code}: tên khu bắt buộc, tối đa 120 ký tự.";
        }
        if (! in_array($zone['kind'] ?? null, SeatZone::KINDS, true)) {
            return "Khu {$code}: loại khu không hợp lệ.";
        }
        if (! $this->intBetween($zone['level'] ?? 1, 1, 6)) {
            return "Khu {$code}: tầng (level) phải từ 1 đến 6.";
        }
        if (! $this->intBetween($zone['surcharge'] ?? 0, -1000000, 10000000)) {
            return "Khu {$code}: phụ thu phải là số nguyên từ -1.000.000 đến 10.000.000.";
        }
        if (! is_string($zone['color'] ?? null) || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $zone['color'])) {
            return "Khu {$code}: màu phải có dạng #RRGGBB.";
        }
        foreach (['gate' => 80, 'description' => 500] as $field => $max) {
            $value = $zone[$field] ?? null;
            if ($value !== null && (! is_string($value) || mb_strlen($value) > $max)) {
                return "Khu {$code}: {$field} tối đa {$max} ký tự.";
            }
        }
        $perks = $zone['perks'] ?? [];
        if (! is_array($perks) || count($perks) > 6
            || collect($perks)->contains(fn ($perk) => ! is_string($perk) || mb_strlen($perk) > 80)) {
            return "Khu {$code}: tối đa 6 quyền lợi, mỗi dòng tối đa 80 ký tự.";
        }
        if ($zone['kind'] === 'standing' && ! $this->intBetween($zone['capacity'] ?? null, 1, 2000)) {
            return "Khu đứng {$code}: sức chứa phải từ 1 đến 2.000.";
        }

        return null;
    }

    private function normalizeZone(array $zone): array
    {
        $blank = fn ($value) => is_string($value) && trim($value) !== '' ? trim($value) : null;

        return [
            'code' => $zone['code'],
            'name' => trim($zone['name']),
            'kind' => $zone['kind'],
            'level' => (int) ($zone['level'] ?? 1),
            'surcharge' => (int) ($zone['surcharge'] ?? 0),
            'color' => $zone['color'],
            'gate' => $blank($zone['gate'] ?? null),
            'description' => $blank($zone['description'] ?? null),
            'perks' => array_values(array_filter(array_map('trim', $zone['perks'] ?? []), fn ($perk) => $perk !== '')),
            'capacity' => $zone['kind'] === 'standing' ? (int) $zone['capacity'] : null,
        ];
    }

    private function intBetween(mixed $value, int $min, int $max): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value >= $min && (int) $value <= $max;
    }
}
