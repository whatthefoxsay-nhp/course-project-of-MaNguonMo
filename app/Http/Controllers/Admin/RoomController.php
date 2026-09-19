<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Seat;
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

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create(): View
    {
        return view('admin.rooms.builder', [
            'room' => new Room([
                'name' => '',
                'address' => '',
                'capacity' => 200,
                'layout_preset' => 'mega_concert',
                'seat_config' => [
                    'base_price' => 250000,
                    'rows' => 10,
                    'cols' => 14,
                    'vip_ratio' => 30,
                    'svip_ratio' => 15,
                ],
            ]),
            'isEdit' => false,
        ]);
    }

    public function builder(Room $room): View
    {
        return view('admin.rooms.builder', [
            'room' => $room,
            'isEdit' => true,
        ]);
    }

    public function edit(Room $room): View
    {
        return $this->builder($room);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'capacity' => 'required|integer|min:10|max:50000',
            'layout_preset' => 'required|string|in:mega_concert,theater_hall,convention_center,custom_grid',
            'base_price' => 'nullable|numeric|min:10000',
            'rows' => 'nullable|integer|min:2|max:26',
            'cols' => 'nullable|integer|min:4|max:40',
            'vip_ratio' => 'nullable|integer|min:0|max:100',
            'svip_ratio' => 'nullable|integer|min:0|max:100',
        ]);

        $seatConfig = [
            'base_price' => (float) ($validated['base_price'] ?? 250000),
            'rows' => (int) ($validated['rows'] ?? 10),
            'cols' => (int) ($validated['cols'] ?? 14),
            'vip_ratio' => (int) ($validated['vip_ratio'] ?? 30),
            'svip_ratio' => (int) ($validated['svip_ratio'] ?? 15),
        ];

        $room = Room::create([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? 'Trung tâm Tổ chức Sự kiện',
            'capacity' => (int) $validated['capacity'],
            'layout_preset' => $validated['layout_preset'],
            'seat_config' => $seatConfig,
        ]);

        $this->generateSeatsForRoom($room, $validated['layout_preset'], $seatConfig);

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã tạo khán phòng [{$room->name}] và tự động sinh sơ đồ thành công!");
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'capacity' => 'required|integer|min:10|max:50000',
            'layout_preset' => 'required|string|in:mega_concert,theater_hall,convention_center,custom_grid',
            'base_price' => 'nullable|numeric|min:10000',
            'rows' => 'nullable|integer|min:2|max:26',
            'cols' => 'nullable|integer|min:4|max:40',
            'vip_ratio' => 'nullable|integer|min:0|max:100',
            'svip_ratio' => 'nullable|integer|min:0|max:100',
        ]);

        $seatConfig = [
            'base_price' => (float) ($validated['base_price'] ?? 250000),
            'rows' => (int) ($validated['rows'] ?? 10),
            'cols' => (int) ($validated['cols'] ?? 14),
            'vip_ratio' => (int) ($validated['vip_ratio'] ?? 30),
            'svip_ratio' => (int) ($validated['svip_ratio'] ?? 15),
        ];

        $room->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? $room->address,
            'capacity' => (int) $validated['capacity'],
            'layout_preset' => $validated['layout_preset'],
            'seat_config' => $seatConfig,
        ]);

        $this->generateSeatsForRoom($room, $validated['layout_preset'], $seatConfig);

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã cập nhật khán phòng [{$room->name}] và tái tạo sơ đồ ghế thành công!");
    }

    public function destroy(Room $room): RedirectResponse
    {
        $room->seats()->delete();
        $room->delete();

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Đã xóa khán phòng thành công!');
    }

    protected function generateSeatsForRoom(Room $room, string $preset, array $config): void
    {
        // Re-generate seats in DB for this room
        $room->seats()->delete();

        $rowsCount = max(2, min(26, $config['rows'] ?? 10));
        $colsCount = max(4, min(40, $config['cols'] ?? 14));
        $alphabet = range('A', 'Z');

        $seatInserts = [];
        $now = now();

        if ($preset === 'mega_concert') {
            // Sơ đồ concert lớn
            $sections = [
                ['rows' => ['SVIP-A', 'SVIP-B'], 'cols' => 12, 'type' => 'svip_diamond'],
                ['rows' => ['FL-1', 'FL-2', 'FL-3', 'FL-4'], 'cols' => 14, 'type' => 'vip_gold'],
                ['rows' => ['A1', 'A2', 'A3'], 'cols' => 16, 'type' => 'cat1_stand'],
                ['rows' => ['B1', 'B2'], 'cols' => 14, 'type' => 'cat1_stand'],
                ['rows' => ['C1', 'C2'], 'cols' => 12, 'type' => 'cat2_wings'],
                ['rows' => ['D1', 'D2'], 'cols' => 12, 'type' => 'cat2_wings'],
                ['rows' => ['SB'], 'cols' => 8, 'type' => 'skybox_suite'],
            ];

            foreach ($sections as $sec) {
                foreach ($sec['rows'] as $r) {
                    for ($c = 1; $c <= $sec['cols']; $c++) {
                        $seatInserts[] = [
                            'room_id' => $room->id,
                            'row_label' => $r,
                            'seat_number' => $c,
                            'type' => $sec['type'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
        } elseif ($preset === 'theater_hall') {
            // Nhà hát giao hưởng
            $rows = ['ST-1', 'ST-2', 'ST-3', 'DC-1', 'DC-2', 'GL-1', 'GL-2'];
            foreach ($rows as $idx => $r) {
                $type = $idx < 2 ? 'vip_gold' : ($idx < 5 ? 'cat1_stand' : 'cat2_wings');
                for ($c = 1; $c <= 16; $c++) {
                    $seatInserts[] = [
                        'room_id' => $room->id,
                        'row_label' => $r,
                        'seat_number' => $c,
                        'type' => $type,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        } elseif ($preset === 'convention_center') {
            // Trung tâm hội nghị
            $rows = ['KN-A', 'KN-B', 'STD-1', 'STD-2', 'STD-3', 'STD-4'];
            foreach ($rows as $idx => $r) {
                $type = $idx < 2 ? 'svip_diamond' : 'cat1_stand';
                for ($c = 1; $c <= 18; $c++) {
                    $seatInserts[] = [
                        'room_id' => $room->id,
                        'row_label' => $r,
                        'seat_number' => $c,
                        'type' => $type,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        } else {
            // Custom grid
            for ($r = 0; $r < $rowsCount; $r++) {
                $rowLabel = $alphabet[$r] ?? ('R'.($r + 1));
                for ($c = 1; $c <= $colsCount; $c++) {
                    $type = 'normal';
                    if ($r < 2) {
                        $type = 'svip_diamond';
                    } elseif ($r < 5) {
                        $type = 'vip_gold';
                    } else {
                        $type = 'cat1_stand';
                    }

                    $seatInserts[] = [
                        'room_id' => $room->id,
                        'row_label' => $rowLabel,
                        'seat_number' => $c,
                        'type' => $type,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($seatInserts, 100) as $chunk) {
            Seat::insert($chunk);
        }
    }
}
