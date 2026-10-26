<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomLayoutRequest;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatZone;
use App\Services\RoomSeatGenerator;
use App\Support\LayoutTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        $rooms = Room::withCount(['seats', 'showtimes'])
            ->with(['zones', 'seats:id,room_id,zone_id,row_label,seat_number,pos_col'])
            ->latest('id')
            ->paginate(10);

        $rooms->getCollection()->transform(function (Room $room) {
            $zones = $room->zones->keyBy('id');
            $zoneOf = fn (Seat $seat) => $zones[$seat->zone_id] ?? null;

            $rows = $room->seats
                ->whereNotNull('pos_col')
                ->groupBy('row_label')
                ->map(fn ($rowSeats, $rowLabel) => [
                    'row' => (string) $rowLabel,
                    'seats' => $rowSeats->sortBy('pos_col')->map(fn (Seat $seat) => [
                        'id' => $seat->id,
                        'number' => $seat->seat_number,
                        'type_name' => $zoneOf($seat)?->name ?? 'Ghế ngồi',
                        'color' => $zoneOf($seat)?->color ?? '#4B5563',
                    ])->values()->all(),
                ])->values()->all();

            $summary = $room->seats_summary;

            $room->blueprint_data = [
                'id' => $room->id,
                'name' => $room->name,
                'address' => $room->address ?? 'Trung tâm tổ chức sự kiện',
                'capacity' => $room->capacity,
                'preset' => $room->layout_preset,
                'preset_label' => $room->preset_label,
                'showtimes_count' => $room->showtimes_count ?? 0,
                'summary' => $summary,
                'rows' => $rows,
                'standing' => collect($summary['zones'])->where('kind', 'standing')->values()->all(),
                'builder_url' => route('admin.rooms.builder', $room),
            ];

            return $room;
        });

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create(): View
    {
        return view('admin.rooms.builder', [
            'room' => new Room(['name' => '', 'address' => '', 'capacity' => 20000, 'layout_preset' => 'mega_concert']),
            'templates' => LayoutTemplates::all(),
            'initialLayout' => LayoutTemplates::get('mega_concert'),
            'isEdit' => false,
            'hasShowtimes' => false,
        ]);
    }

    public function builder(Room $room): View
    {
        return view('admin.rooms.builder', [
            'room' => $room,
            'templates' => LayoutTemplates::all(),
            'initialLayout' => LayoutTemplates::fromRoom($room),
            'isEdit' => true,
            'hasShowtimes' => $room->showtimes()->exists(),
        ]);
    }

    public function edit(Room $room): View
    {
        return $this->builder($room);
    }

    public function store(RoomLayoutRequest $request, RoomSeatGenerator $generator): RedirectResponse
    {
        $room = DB::transaction(function () use ($request, $generator) {
            $room = Room::create($this->roomAttributes($request));
            $generator->apply($room, $request->layout());

            return $room;
        });

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã tạo địa điểm [{$room->name}] với ".$room->seats()->count().' chỗ.');
    }

    public function update(RoomLayoutRequest $request, Room $room, RoomSeatGenerator $generator): RedirectResponse
    {
        if ($room->showtimes()->exists()) {
            // Đã có suất diễn: KHÔNG sinh lại ghế (sẽ cascade xóa vé đã bán) — chỉ sửa thông tin khu.
            DB::transaction(function () use ($request, $room) {
                $room->update(collect($this->roomAttributes($request))->except('layout_preset')->all());
                $this->updateZoneDetails($room, $request->layout()['zones']);
            });

            return redirect()->route('admin.rooms.index')
                ->with('warning', "Đã cập nhật thông tin và giá các khu của [{$room->name}]. Địa điểm đã có suất diễn nên vị trí ghế được giữ nguyên — dùng nút Nhân Bản nếu muốn vẽ sơ đồ mới.");
        }

        DB::transaction(function () use ($request, $room, $generator) {
            $room->update($this->roomAttributes($request));
            $generator->apply($room, $request->layout());
        });

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã cập nhật địa điểm [{$room->name}] và dựng lại sơ đồ (".$room->seats()->count().' chỗ).');
    }

    public function duplicate(Room $room, RoomSeatGenerator $generator): RedirectResponse
    {
        $newRoom = DB::transaction(function () use ($room, $generator) {
            $newRoom = $room->replicate(['id', 'created_at', 'updated_at']);
            $newRoom->name = $room->name.' (Bản Sao '.now()->format('d/m H:i').')';
            $newRoom->save();
            $generator->apply($newRoom, LayoutTemplates::fromRoom($room));

            return $newRoom;
        });

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã nhân bản [{$room->name}] thành [{$newRoom->name}]. Bạn có thể sửa sơ đồ mới mà không ảnh hưởng địa điểm gốc.");
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

    private function roomAttributes(RoomLayoutRequest $request): array
    {
        return [
            'name' => $request->validated('name'),
            'address' => $request->validated('address') ?: 'Trung tâm Tổ chức Sự kiện',
            'capacity' => (int) $request->validated('capacity'),
            'latitude' => $request->validated('latitude'),
            'longitude' => $request->validated('longitude'),
            'layout_preset' => $request->validated('layout_preset'),
        ];
    }

    /** Chỉ đổi thông tin hiển thị/giá của các khu đang có (khớp theo mã); không thêm, xóa khu hay ghế. */
    private function updateZoneDetails(Room $room, array $zones): void
    {
        $existing = $room->zones()->get()->keyBy('code');
        foreach ($zones as $zone) {
            $existing->get($zone['code'])?->update(collect($zone)
                ->only(['name', 'surcharge', 'color', 'gate', 'description', 'perks'])
                ->all());
        }
    }
}
