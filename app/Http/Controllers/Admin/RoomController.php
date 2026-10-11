<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\RoomSeatGenerator;
use App\Services\ZoneLayoutGenerator;
use App\Support\ZoneCanvas;
use App\Support\VenuePresets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $rooms = Room::withCount(['seats', 'showtimes', 'zones'])
            ->with(['seats', 'zones'])
            ->latest('id')
            ->paginate(10);

        $rooms->getCollection()->transform(function ($room) {
            $groupedSeats = $room->seats
                ->reject(fn ($s) => $s->type === 'standing_pit')
                ->groupBy('row_label')
                ->map(fn ($rowSeats, $rowLabel) => [
                    'row' => $rowLabel,
                    'seats' => $rowSeats->map(fn ($s) => [
                        'id' => $s->id,
                        'number' => $s->seat_number,
                        'type' => $s->type,
                        'type_name' => match ($s->type) {
                            'svip_diamond' => 'SVIP Diamond',
                            'vip_gold' => 'VIP Gold',
                            'cat1_stand' => 'Khán Đài Cat 1',
                            'cat2_wings' => 'Khán Đài Cánh Cat 2',
                            'skybox_suite' => 'Skybox VIP Suite',
                            default => 'Tiêu Chuẩn',
                        },
                        'color' => match ($s->type) {
                            'svip_diamond' => '#D4AF37',
                            'vip_gold' => '#C08497',
                            'cat1_stand' => '#3A5A40',
                            'cat2_wings' => '#1E293B',
                            'skybox_suite' => '#E11D48',
                            default => '#4B5563',
                        },
                    ])->values()->all(),
                ])->values()->all();

            $room->blueprint_data = [
                'id' => $room->id,
                'name' => $room->name,
                'address' => $room->address ?? 'Trung tâm tổ chức sự kiện',
                'capacity' => $room->capacity,
                'diagram_mode' => $room->diagram_mode,
                'preset' => $room->layout_preset ?? 'mega_concert',
                'preset_label' => $room->preset_label,
                'showtimes_count' => $room->showtimes_count ?? 0,
                'summary' => $room->seats_summary,
                'zones' => $room->isZoned() ? $room->zones->values()->map(fn ($zone, $index) => [
                    'id' => $zone->id,
                    'code' => $zone->code,
                    'name' => $zone->name,
                    'kind' => $zone->kind,
                    'capacity' => $zone->effectiveCapacity(),
                    'canvas' => ZoneCanvas::normalize($zone->meta, $index, $room->zones->count()),
                    'color' => match ($zone->sort_order % 6) {
                        0 => '#d4af37', 1 => '#c08497', 2 => '#3a5a40',
                        3 => '#2563eb', 4 => '#e11d48', default => '#7c3aed',
                    },
                ])->values()->all() : [],
                'rows' => $groupedSeats,
                'has_standing' => $room->seats->contains('type', 'standing_pit'),
                'standing_count' => $room->seats->where('type', 'standing_pit')->count(),
                'builder_url' => route('admin.rooms.builder', $room),
            ];

            return $room;
        });

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create(Request $request): View
    {
        $mode = $request->query('mode');
        if (! in_array($mode, [Room::MODE_SEATS, Room::MODE_ZONES], true)) {
            return view('admin.rooms.choose-layout');
        }

        $room = new Room([
            'name' => '',
            'address' => '',
            'capacity' => 0,
            'layout_preset' => $mode === Room::MODE_ZONES ? 'outdoor_concert' : 'mega_concert',
            'diagram_mode' => $mode,
        ]);

        if ($mode === Room::MODE_SEATS) {
            return view('admin.rooms.builder', [
                'room' => $room,
                'initialRows' => [],
                'isEdit' => false,
            ]);
        }

        return view('admin.rooms.zoned-builder', [
            'room' => $room,
            'initialZones' => VenuePresets::zones('outdoor_concert'),
            'presetOptions' => VenuePresets::PRESETS,
            'isEdit' => false,
        ]);
    }

    public function builder(Room $room): View
    {
        if ($room->isZoned()) {
            return view('admin.rooms.zoned-builder', [
                'room' => $room->load('zones.seats'),
                'initialZones' => $room->zones->map(fn ($zone) => [
                    'code' => $zone->code,
                    'name' => $zone->name,
                    'kind' => $zone->kind,
                    'seat_type' => $zone->seat_type,
                    'ticket_tier' => $zone->ticket_tier,
                    'capacity' => $zone->capacity,
                    'meta' => $zone->meta,
                    'rows' => max(1, $zone->seats->groupBy('row_label')->count()),
                    'cols' => max(1, (int) $zone->seats->groupBy('row_label')->map->count()->max()),
                    'tables' => max(1, $zone->seats->groupBy('row_label')->count()),
                    'seats_per_table' => max(1, (int) $zone->seats->groupBy('row_label')->map->count()->max()),
                    'layout' => $zone->seats->groupBy('row_label')->map(function ($rowSeats, $rowLabel) use ($zone) {
                        $prefix = $zone->code.'-';

                        return [
                            'row' => str_starts_with($rowLabel, $prefix) ? substr($rowLabel, strlen($prefix)) : $rowLabel,
                            'seats' => $rowSeats->map(fn ($seat) => ['number' => $seat->seat_number, 'type' => $seat->type])->values()->all(),
                        ];
                    })->values()->all(),
                    'sort_order' => $zone->sort_order,
                ])->values()->all(),
                'presetOptions' => VenuePresets::PRESETS,
                'isEdit' => true,
            ]);
        }

        $initialRows = $room->seats()
            ->where('type', '!=', 'standing_pit')
            ->orderBy('id')
            ->get()
            ->groupBy('row_label')
            ->map(fn ($rowSeats, $rowLabel) => [
                'row' => $rowLabel,
                'seats' => $rowSeats->map(fn ($s) => [
                    'id' => $s->id,
                    'number' => $s->seat_number,
                    'type' => $s->type,
                    'is_aisle' => false,
                    'is_blocked' => false,
                ])->values()->all(),
            ])->values()->all();

        return view('admin.rooms.builder', [
            'room' => $room,
            'initialRows' => $initialRows,
            'isEdit' => true,
        ]);
    }

    public function edit(Room $room): View
    {
        return $this->builder($room);
    }

    public function store(Request $request, RoomSeatGenerator $generator): RedirectResponse
    {
        if ($request->input('diagram_mode') === Room::MODE_ZONES || ($request->exists('zones') && $request->input('diagram_mode') !== Room::MODE_SEATS)) {
            return $this->storeZoned($request, $generator, null);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'capacity' => 'required|integer|min:10|max:50000',
            'layout_preset' => 'required|string|in:'.implode(',', RoomSeatGenerator::PRESETS),
            'base_price' => 'nullable|numeric|min:10000',
            'rows' => 'nullable|integer|min:2|max:26',
            'cols' => 'nullable|integer|min:4|max:40',
            'vip_ratio' => 'nullable|integer|min:0|max:100',
            'svip_ratio' => 'nullable|integer|min:0|max:100',
            'custom_layout_matrix' => 'nullable|string',
        ]);

        $customMatrix = null;
        if (! empty($validated['custom_layout_matrix'])) {
            $decoded = json_decode($validated['custom_layout_matrix'], true);
            if (is_array($decoded) && ! empty($decoded)) {
                $customMatrix = $decoded;
            }
        }

        $seatConfig = [
            'base_price' => (float) ($validated['base_price'] ?? 250000),
            'rows' => (int) ($validated['rows'] ?? 10),
            'cols' => (int) ($validated['cols'] ?? 14),
            'vip_ratio' => (int) ($validated['vip_ratio'] ?? 30),
            'svip_ratio' => (int) ($validated['svip_ratio'] ?? 15),
        ];

        if ($customMatrix) {
            $seatConfig['matrix'] = $customMatrix;
        }

        if ($request->filled('stage_type')) {
            $seatConfig['stage_type'] = $request->input('stage_type');
        }

        if ($request->filled('standing_zones')) {
            $sz = json_decode($request->input('standing_zones'), true);
            if (is_array($sz)) {
                $seatConfig['standing_zones'] = $sz;
            }
        }

        $room = Room::create([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? 'Trung tâm Tổ chức Sự kiện',
            'capacity' => (int) $validated['capacity'],
            'layout_preset' => $validated['layout_preset'],
            'diagram_mode' => Room::MODE_SEATS,
            'seat_config' => $seatConfig,
        ]);

        if ($customMatrix) {
            $seatCount = $generator->generateFromMatrix($room, $customMatrix, $validated['layout_preset'], $seatConfig);
        } else {
            $seatCount = $generator->generate($room, $validated['layout_preset'], $seatConfig);
        }

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã tạo khán phòng [{$room->name}] và lưu sơ đồ tùy chỉnh thành công ({$room->capacity} chỗ)!");
    }

    public function update(Request $request, Room $room, RoomSeatGenerator $generator): RedirectResponse
    {
        if ($request->input('diagram_mode') === Room::MODE_ZONES || ($request->exists('zones') && $request->input('diagram_mode') !== Room::MODE_SEATS)) {
            return $this->storeZoned($request, $generator, $room);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'capacity' => 'required|integer|min:10|max:50000',
            'layout_preset' => 'required|string|in:'.implode(',', RoomSeatGenerator::PRESETS),
            'base_price' => 'nullable|numeric|min:10000',
            'rows' => 'nullable|integer|min:2|max:26',
            'cols' => 'nullable|integer|min:4|max:40',
            'vip_ratio' => 'nullable|integer|min:0|max:100',
            'svip_ratio' => 'nullable|integer|min:0|max:100',
            'custom_layout_matrix' => 'nullable|string',
        ]);

        $customMatrix = null;
        if (! empty($validated['custom_layout_matrix'])) {
            $decoded = json_decode($validated['custom_layout_matrix'], true);
            if (is_array($decoded) && ! empty($decoded)) {
                $customMatrix = $decoded;
            }
        }

        $seatConfig = [
            'base_price' => (float) ($validated['base_price'] ?? 250000),
            'rows' => (int) ($validated['rows'] ?? 10),
            'cols' => (int) ($validated['cols'] ?? 14),
            'vip_ratio' => (int) ($validated['vip_ratio'] ?? 30),
            'svip_ratio' => (int) ($validated['svip_ratio'] ?? 15),
        ];

        if ($customMatrix) {
            $seatConfig['matrix'] = $customMatrix;
        }

        if ($request->filled('stage_type')) {
            $seatConfig['stage_type'] = $request->input('stage_type');
        }

        if ($request->filled('standing_zones')) {
            $sz = json_decode($request->input('standing_zones'), true);
            if (is_array($sz)) {
                $seatConfig['standing_zones'] = $sz;
            }
        }

        if ($room->showtimes()->exists()) {
            // Đã có suất diễn: KHÔNG sinh lại ghế (sẽ cascade xóa vé đã bán).
            $room->update([
                'name' => $validated['name'],
                'address' => $validated['address'] ?? $room->address,
                'capacity' => (int) $validated['capacity'],
            ]);

            return redirect()->route('admin.rooms.index')
                ->with('warning', "Đã cập nhật thông tin [{$room->name}]. Khán phòng đã có suất diễn nên sơ đồ ghế được giữ nguyên. Hãy dùng nút Nhân Bản nếu muốn tạo phiên bản sơ đồ mới!");
        }

        $room->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? $room->address,
            'capacity' => (int) $validated['capacity'],
            'layout_preset' => $validated['layout_preset'],
            'diagram_mode' => Room::MODE_SEATS,
            'seat_config' => $seatConfig,
        ]);

        if ($customMatrix) {
            $seatCount = $generator->generateFromMatrix($room, $customMatrix, $validated['layout_preset'], $seatConfig);
        } else {
            $seatCount = $generator->generate($room, $validated['layout_preset'], $seatConfig);
        }

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã cập nhật khán phòng [{$room->name}] và tái tạo sơ đồ ghế thành công ({$room->capacity} chỗ)!");
    }

    private function storeZoned(Request $request, RoomSeatGenerator $generator, ?Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'layout_preset' => 'required|string|in:'.implode(',', array_merge(VenuePresets::PRESETS, [Room::ZONED_PRESET])),
            'zones' => 'required|json',
        ]);
        $zones = json_decode($validated['zones'], true);
        if (! is_array($zones) || count($zones) < 1) {
            return back()->withInput()->withErrors(['zones' => 'Khán phòng cần ít nhất một khu vực.']);
        }

        $allowedKinds = ['seated', 'standing', 'table', 'admission'];
        foreach ($zones as $index => $zone) {
            if (! is_array($zone) || empty($zone['code']) || strlen((string) $zone['code']) > 6 || empty($zone['name']) || mb_strlen((string) $zone['name']) > 100 || ! in_array($zone['kind'] ?? '', $allowedKinds, true)) {
                return back()->withInput()->withErrors(['zones' => 'Thông tin mã, tên hoặc loại khu vực chưa hợp lệ.']);
            }
            if (! in_array($zone['seat_type'] ?? '', ['svip_diamond', 'vip_gold', 'cat1_stand', 'cat2_wings', 'skybox_suite', 'standing_pit'], true)) {
                return back()->withInput()->withErrors(['zones' => 'Hạng vé của khu vực chưa hợp lệ.']);
            }
            if (in_array($zone['kind'], ['standing', 'admission'], true) && (int) ($zone['capacity'] ?? 0) < 1) {
                return back()->withInput()->withErrors(['zones' => "Khu {$zone['name']} cần sức chứa lớn hơn 0."]);
            }
        }

        if ($room?->showtimes()->exists()) {
            return redirect()->route('admin.rooms.index')->with('warning', "Khán phòng [{$room->name}] đã có suất diễn nên không thể sửa khu vực. Hãy nhân bản phòng để tạo sơ đồ mới.");
        }

        $room ??= new Room;
        $room->fill([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? 'Trung tâm Tổ chức Sự kiện',
            'layout_preset' => $validated['layout_preset'] === Room::ZONED_PRESET ? 'outdoor_concert' : $validated['layout_preset'],
            'diagram_mode' => Room::MODE_ZONES,
            'seat_config' => ['base_price' => 250000],
        ]);
        $room->save();

        try {
            app(ZoneLayoutGenerator::class)->generate($room, $zones);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        }

        return redirect()->route('admin.rooms.index')->with('success', "Đã lưu khán phòng [{$room->name}] với sức chứa {$room->fresh()->capacity} chỗ.");
    }

    public function duplicate(Room $room): RedirectResponse
    {
        $newRoom = $room->replicate(['id', 'created_at', 'updated_at']);
        $newRoom->name = $room->name.' (Bản Sao '.now()->format('d/m H:i').')';
        $newRoom->capacity = $room->capacity;
        $newRoom->save();

        $zoneIdMap = [];
        foreach ($room->isZoned() ? $room->zones : [] as $zone) {
            $copy = $zone->replicate(['id', 'created_at', 'updated_at']);
            $copy->room_id = $newRoom->id;
            $copy->save();
            $zoneIdMap[$zone->id] = $copy->id;
        }

        // Sao chép toàn bộ ghế sang khán phòng mới
        $now = now();
        $seatInserts = [];
        foreach ($room->seats as $seat) {
            $seatInserts[] = [
                'room_id' => $newRoom->id,
                'zone_id' => $room->isZoned() ? ($zoneIdMap[$seat->zone_id] ?? null) : null,
                'row_label' => $seat->row_label,
                'seat_number' => $seat->seat_number,
                'type' => $seat->type,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($seatInserts, 200) as $chunk) {
            \App\Models\Seat::insert($chunk);
        }
        // Zone model hooks run while zones are copied, before their seats are inserted.
        $copiedCapacity = ! $room->isZoned()
            ? (int) $room->capacity
            : $newRoom->zones()->get()->sum(fn ($zone) => $zone->hasSeats() ? $zone->seats()->count() : (int) $zone->capacity)
                + $newRoom->seats()->whereNull('zone_id')->count();
        Room::whereKey($newRoom->id)->update(['capacity' => $copiedCapacity]);

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã nhân bản khán phòng [{$room->name}] thành [{$newRoom->name}] thành công ({$copiedCapacity} chỗ)! Bạn có thể chỉnh sửa sơ đồ mới mà không ảnh hưởng phòng gốc.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        if ($room->showtimes()->exists()) {
            return back()->with('error', 'Không thể xóa khán phòng đã có suất diễn. Hãy xóa các suất diễn trước.');
        }

        $room->delete();

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Đã xóa khán phòng thành công!');
    }
}
