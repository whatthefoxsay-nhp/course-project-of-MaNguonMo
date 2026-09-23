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

        $generator->generate($room, $validated['layout_preset'], $seatConfig);

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã tạo khán phòng [{$room->name}] và tự động sinh sơ đồ thành công!");
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
        ]);

        $seatConfig = [
            'base_price' => (float) ($validated['base_price'] ?? 250000),
            'rows' => (int) ($validated['rows'] ?? 10),
            'cols' => (int) ($validated['cols'] ?? 14),
            'vip_ratio' => (int) ($validated['vip_ratio'] ?? 30),
            'svip_ratio' => (int) ($validated['svip_ratio'] ?? 15),
        ];

        if ($room->showtimes()->exists()) {
            // Đã có suất diễn: KHÔNG sinh lại ghế (sẽ cascade xóa vé đã bán).
            $room->update([
                'name' => $validated['name'],
                'address' => $validated['address'] ?? $room->address,
                'capacity' => (int) $validated['capacity'],
            ]);

            return redirect()->route('admin.rooms.index')
                ->with('warning', "Đã cập nhật thông tin [{$room->name}]. Khán phòng đã có suất diễn nên sơ đồ ghế được giữ nguyên.");
        }

        $room->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? $room->address,
            'capacity' => (int) $validated['capacity'],
            'layout_preset' => $validated['layout_preset'],
            'seat_config' => $seatConfig,
        ]);

        $generator->generate($room, $validated['layout_preset'], $seatConfig);

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã cập nhật khán phòng [{$room->name}] và tái tạo sơ đồ ghế thành công!");
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
