<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\RoomSeatGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $rooms = Room::withCount(['seats', 'showtimes'])
            ->with(['seats'])
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
                'preset' => $room->layout_preset ?? 'mega_concert',
                'preset_label' => $room->preset_label,
                'showtimes_count' => $room->showtimes_count ?? 0,
                'summary' => $room->seats_summary,
                'rows' => $groupedSeats,
                'has_standing' => $room->seats->contains('type', 'standing_pit'),
                'standing_count' => $room->seats->where('type', 'standing_pit')->count(),
                'builder_url' => route('admin.rooms.builder', $room),
            ];

            return $room;
        });

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create(): View
    {
        return view('admin.rooms.builder', [
            'room' => new Room([
                'name' => '',
                'address' => '',
                'capacity' => 20000,
                'layout_preset' => 'mega_concert',
                'seat_config' => [
                    'base_price' => 250000,
                    'rows' => 10,
                    'cols' => 14,
                    'vip_ratio' => 30,
                    'svip_ratio' => 15,
                ],
            ]),
            'initialRows' => [],
            'isEdit' => false,
        ]);
    }

    public function builder(Room $room): View
    {
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

        $room = Room::create([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? 'Trung tâm Tổ chức Sự kiện',
            'capacity' => (int) $validated['capacity'],
            'layout_preset' => $validated['layout_preset'],
            'seat_config' => $seatConfig,
        ]);

        if ($customMatrix) {
            $generator->generateFromMatrix($room, $customMatrix, $validated['layout_preset']);
        } else {
            $generator->generate($room, $validated['layout_preset'], $seatConfig);
        }

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã tạo khán phòng [{$room->name}] và lưu sơ đồ tùy chỉnh thành công!");
    }

    public function update(Request $request, Room $room, RoomSeatGenerator $generator): RedirectResponse
    {
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
            'seat_config' => $seatConfig,
        ]);

        if ($customMatrix) {
            $generator->generateFromMatrix($room, $customMatrix, $validated['layout_preset']);
        } else {
            $generator->generate($room, $validated['layout_preset'], $seatConfig);
        }

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã cập nhật khán phòng [{$room->name}] và tái tạo sơ đồ ghế thành công!");
    }

    public function duplicate(Room $room): RedirectResponse
    {
        $newRoom = $room->replicate(['id', 'created_at', 'updated_at']);
        $newRoom->name = $room->name.' (Bản Sao '.now()->format('d/m H:i').')';
        $newRoom->save();

        // Sao chép toàn bộ ghế sang khán phòng mới
        $now = now();
        $seatInserts = [];
        foreach ($room->seats as $seat) {
            $seatInserts[] = [
                'room_id' => $newRoom->id,
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

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã nhân bản khán phòng [{$room->name}] thành [{$newRoom->name}] thành công! Bạn có thể chỉnh sửa sơ đồ mới mà không ảnh hưởng phòng gốc.");
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
