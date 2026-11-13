<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatZone;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\RoomSeatGenerator;
use App\Support\LayoutTemplates;

function customLayoutPayload(array $zones, array $rows, array $overrides = []): array
{
    return array_merge([
        'name' => 'Khán Phòng Đang Dựng Sơ Đồ',
        'address' => 'Số 15 Lê Duẩn, Bến Nghé, Quận 1',
        'capacity' => 1000,
        'layout_preset' => 'custom_grid',
        'layout' => json_encode([
            'zones' => $zones,
            'rows' => $rows,
        ]),
    ], $overrides);
}

// TC_UC005_001: Truy cập giao diện dựng sơ đồ tại /admin/rooms/{id}/builder thành công
test('TC_UC005_001: admin accesses room layout builder page successfully', function () {
    $room = Room::factory()->create(['name' => 'Nhà Hát Lớn Thành Phố']);
    app(RoomSeatGenerator::class)->generate($room, 'theater_hall');

    $this->actingAs(createAdmin())
        ->get(route('admin.rooms.builder', $room))
        ->assertOk()
        ->assertSee('Nhà Hát Lớn Thành Phố')
        ->assertSee('Chỉnh Sửa Sơ Đồ Địa Điểm')
        ->assertSee('Chọn mẫu bố trí')
        ->assertSee('Bản vẽ sơ đồ');
});

// TC_UC005_002: Áp dụng từng mẫu trong 6 Layout Presets
test('TC_UC005_002: all six layout presets load valid zones and template structures', function (string $preset) {
    $layout = LayoutTemplates::get($preset);

    expect($layout)->toHaveKey('zones')
        ->and($layout)->toHaveKey('rows')
        ->and($layout['zones'])->not->toBeEmpty();

    $room = Room::factory()->create(['layout_preset' => $preset]);
    $seatCount = app(RoomSeatGenerator::class)->generate($room, $preset);

    expect($seatCount)->toBeGreaterThan(0)
        ->and($room->seats()->count())->toBe($seatCount)
        ->and($room->zones()->count())->toBe(count($layout['zones']));
})->with([
    'mega_concert',
    'theater_hall',
    'convention_center',
    'banquet_hall',
    'open_space',
    'custom_grid',
]);

// TC_UC005_003: Mẫu Banquet (Bàn tiệc gala) sinh đúng cấu trúc bàn tiệc tròn
test('TC_UC005_003: banquet hall preset generates round table zones with table kind and seats per table', function () {
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'banquet_hall');

    $viptZone = $room->zones()->where('code', 'VIPT')->first();
    expect($viptZone)->not->toBeNull()
        ->and($viptZone->kind)->toBe('table');

    // Các bàn B01 đến B04 có 8 ghế mỗi bàn
    $table1Seats = $room->seats()->where('row_label', 'B01')->get();
    expect($table1Seats->count())->toBe(8)
        ->and($table1Seats->pluck('seat_number')->all())->toBe(range(1, 8))
        ->and($table1Seats->pluck('pos_col')->all())->toBe(range(1, 8));
});

// TC_UC005_004: Mẫu Open Space (Không gian mở) sinh đúng khu vực đứng tự do (GA) không gán tọa độ ghế
test('TC_UC005_004: open space preset generates free-standing zones without fixed row seat coordinates', function () {
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'open_space');

    $standingZones = $room->zones()->where('kind', 'standing')->get();
    expect($standingZones->count())->toBe(2);

    // Ghế khu đứng có pos_col = null và row_label mang mã khu vực
    $seats = $room->seats()->get();
    expect($seats->whereNull('pos_col')->count())->toBe($seats->count())
        ->and($seats->pluck('row_label')->unique()->values()->all())->toContain('VIPX', 'GEN');
});

// TC_UC005_005: Lưu sơ đồ lưới tùy chỉnh (Custom Grid) với ghế, bàn tiệc, lối đi
test('TC_UC005_005: admin saves custom grid with seat zones, table zones and aisles successfully', function () {
    $room = Room::factory()->create();

    $zones = [
        ['code' => 'VIP', 'name' => 'Ghế VIP', 'kind' => 'seated', 'surcharge' => 100000, 'color' => '#D4AF37'],
        ['code' => 'TBL', 'name' => 'Bàn Tiệc', 'kind' => 'table', 'surcharge' => 50000, 'color' => '#10B981'],
    ];

    $rows = [
        [
            'label' => 'A',
            'cells' => [
                ['zone' => 'VIP'],
                ['aisle' => true],
                ['zone' => 'VIP'],
            ],
        ],
        [
            'label' => 'T1',
            'cells' => [
                ['zone' => 'TBL'],
                ['zone' => 'TBL'],
                ['zone' => 'TBL'],
            ],
        ],
    ];

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), customLayoutPayload($zones, $rows))
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('success');

    expect($room->zones()->count())->toBe(2)
        ->and($room->seats()->count())->toBe(5); // 2 ghế hàng A + 3 ghế bàn T1
});

// TC_UC005_006: RoomSeatGenerator sinh đầy đủ bản ghi bảng seats với row_label, seat_number, pos_col, zone_id
test('TC_UC005_006: generator populates seats table with accurate row_label, seat_number, pos_col and zone_id', function () {
    $room = Room::factory()->create();
    $zones = [['code' => 'STD', 'name' => 'Ghế Tiêu Chuẩn', 'kind' => 'seated', 'surcharge' => 0, 'color' => '#3A5A40']];
    $rows = [
        [
            'label' => 'VIP-1',
            'cells' => [
                ['aisle' => true],
                ['zone' => 'STD'],
                ['zone' => 'STD'],
            ],
        ],
    ];

    app(RoomSeatGenerator::class)->apply($room, ['zones' => $zones, 'rows' => $rows]);

    $stdZone = $room->zones()->where('code', 'STD')->first();
    $seats = $room->seats()->where('row_label', 'VIP-1')->orderBy('seat_number')->get();

    expect($seats)->toHaveCount(2);

    // Ghế số 1 nằm ở cột thứ 2 (do cột 1 là lối đi)
    expect($seats[0]->seat_number)->toBe(1)
        ->and($seats[0]->pos_col)->toBe(2)
        ->and($seats[0]->zone_id)->toBe($stdZone->id);

    // Ghế số 2 nằm ở cột thứ 3
    expect($seats[1]->seat_number)->toBe(2)
        ->and($seats[1]->pos_col)->toBe(3)
        ->and($seats[1]->zone_id)->toBe($stdZone->id);
});

// TC_UC005_007: Xác nhận ô lối đi trống (Aisle) không sinh bản ghi trong bảng seats
test('TC_UC005_007: aisle cells do not create records in seats table', function () {
    $room = Room::factory()->create();
    $zones = [['code' => 'Z1', 'name' => 'Khu 1', 'kind' => 'seated', 'surcharge' => 0, 'color' => '#3A5A40']];
    $rows = [
        [
            'label' => 'A',
            'cells' => [
                ['aisle' => true],
                ['zone' => 'Z1'],
                ['aisle' => true],
                ['zone' => 'Z1'],
                ['aisle' => true],
            ],
        ],
    ];

    app(RoomSeatGenerator::class)->apply($room, ['zones' => $zones, 'rows' => $rows]);

    expect($room->seats()->count())->toBe(2)
        ->and($room->seats()->pluck('pos_col')->all())->toBe([2, 4]);
});

// TC_UC005_008: Cập nhật lại sơ đồ khi chưa có suất diễn bán vé
test('TC_UC005_008: updating room layout when no showtimes exist regenerates seats cleanly', function () {
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 2, 'cols' => 4]); // 8 ghế

    expect($room->seats()->count())->toBe(8);

    $zones = [['code' => 'NEW', 'name' => 'Khu Mới', 'kind' => 'seated', 'surcharge' => 0, 'color' => '#123D22']];
    $rows = [
        ['label' => 'R1', 'cells' => [['zone' => 'NEW'], ['zone' => 'NEW'], ['zone' => 'NEW']]],
        ['label' => 'R2', 'cells' => [['zone' => 'NEW'], ['zone' => 'NEW'], ['zone' => 'NEW']]],
    ]; // 6 ghế

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), customLayoutPayload($zones, $rows))
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('success');

    expect($room->fresh()->seats()->count())->toBe(6)
        ->and($room->fresh()->zones()->pluck('code')->all())->toBe(['NEW']);
});

// TC_UC005_009: Kiểm tra biên sức chứa: đúng bằng 5.000 chỗ thành công
test('TC_UC005_009: boundary test: saving layout with exactly 5000 seats succeeds', function () {
    $room = Room::factory()->create();
    $zones = [
        ['code' => 'STAND1', 'name' => 'Khu Đứng 1', 'kind' => 'standing', 'capacity' => 2000, 'surcharge' => 0, 'color' => '#123D22'],
        ['code' => 'STAND2', 'name' => 'Khu Đứng 2', 'kind' => 'standing', 'capacity' => 2000, 'surcharge' => 0, 'color' => '#123D22'],
        ['code' => 'STAND3', 'name' => 'Khu Đứng 3', 'kind' => 'standing', 'capacity' => 1000, 'surcharge' => 0, 'color' => '#123D22'],
    ];

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), customLayoutPayload($zones, [], ['capacity' => 5000]))
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('success');

    expect($room->fresh()->zones()->where('kind', 'standing')->sum('capacity'))->toBe(5000);
});

// TC_UC005_010: Kiểm tra vượt biên sức chứa: 5.001 chỗ bị validation chặn
test('TC_UC005_010: boundary test: saving layout with 5001 seats is rejected with error', function () {
    $room = Room::factory()->create();
    $zones = [
        ['code' => 'STAND1', 'name' => 'Khu Đứng 1', 'kind' => 'standing', 'capacity' => 2000, 'surcharge' => 0, 'color' => '#123D22'],
        ['code' => 'STAND2', 'name' => 'Khu Đứng 2', 'kind' => 'standing', 'capacity' => 2000, 'surcharge' => 0, 'color' => '#123D22'],
        ['code' => 'STAND3', 'name' => 'Khu Đứng 3', 'kind' => 'standing', 'capacity' => 1001, 'surcharge' => 0, 'color' => '#123D22'],
    ];

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), customLayoutPayload($zones, []))
        ->assertSessionHasErrors('layout');
});

// TC_UC005_011: Lưu sơ đồ thất bại khi ma trận không có bất kỳ ghế bán vé nào (0 chỗ)
test('TC_UC005_011: saving layout with zero sellable seats fails validation', function () {
    $room = Room::factory()->create();
    $zones = [
        ['code' => 'Z1', 'name' => 'Khu Rỗng', 'kind' => 'seated', 'surcharge' => 0, 'color' => '#3A5A40'],
    ];
    $rows = [
        ['label' => 'A', 'cells' => [['aisle' => true], ['aisle' => true]]],
    ];

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), customLayoutPayload($zones, $rows))
        ->assertSessionHasErrors('layout');
});

// TC_UC005_012: Payload cấu trúc lưới lỗi định dạng (hàng không có ô, ô gắn zone không tồn tại)
test('TC_UC005_012: malformed grid payload or invalid zone reference is rejected safely', function () {
    $room = Room::factory()->create();
    $zones = [
        ['code' => 'VIP', 'name' => 'Ghế VIP', 'kind' => 'seated', 'surcharge' => 0, 'color' => '#3A5A40'],
    ];
    $rows = [
        ['label' => 'A', 'cells' => [['zone' => 'ZONE_KHONG_TON_TAI']]],
    ];

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), customLayoutPayload($zones, $rows))
        ->assertSessionHasErrors('layout');

    // Trường hợp nhãn hàng trống
    $rowsMissingLabel = [
        ['label' => '', 'cells' => [['zone' => 'VIP']]],
    ];
    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), customLayoutPayload($zones, $rowsMissingLabel))
        ->assertSessionHasErrors('layout');
});

// TC_UC005_013: Chặn thay đổi vị trí ghế khi đã có suất diễn phát sinh vé bán thành công
test('TC_UC005_013: modifying seat structure of room with confirmed bookings keeps existing seats intact', function () {
    $room = Room::factory()->create(['layout_preset' => 'custom_grid']);
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 2, 'cols' => 2]);

    $showtime = Showtime::factory()->for($room)->create();
    $seat = $room->seats()->first();
    $showtimeSeat = ShowtimeSeat::create([
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'status' => 'booked',
    ]);
    $booking = Booking::create(['user_id' => createCustomer()->id, 'total_price' => 300000, 'status' => 'confirmed']);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $showtimeSeat->id, 'price' => 300000]);

    $originalSeatIds = $room->seats()->pluck('id')->sort()->values()->all();

    $newZones = [['code' => 'VIP', 'name' => 'Ghế VIP Đổi Giá', 'kind' => 'seated', 'surcharge' => 150000, 'color' => '#D4AF37']];
    $newRows = [['label' => 'A', 'cells' => [['zone' => 'VIP']]]]; // Cố đổi từ 4 ghế xuống 1 ghế

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), customLayoutPayload($newZones, $newRows))
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('warning');

    // Ghế cũ vẫn bảo toàn 100%, không bị xóa hay giảm
    expect($room->fresh()->seats()->pluck('id')->sort()->values()->all())->toBe($originalSeatIds)
        ->and(BookingItem::count())->toBe(1);
});

// TC_UC005_014: Chỉnh sửa sơ đồ khi phòng đã có suất diễn nhưng chưa bán vé nào
test('TC_UC005_014: room with showtime protects seats and updates zone metadata with warning notice', function () {
    $room = Room::factory()->create(['layout_preset' => 'custom_grid']);
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 2, 'cols' => 4]);

    $showtime = Showtime::factory()->for($room)->create();
    $originalSeatIds = $room->seats()->pluck('id')->sort()->values()->all();

    $layout = LayoutTemplates::get('custom_grid', ['rows' => 2, 'cols' => 4]);
    $layout['zones'][0]['surcharge'] = 50000;

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), [
            'name' => $room->name,
            'address' => $room->address ?? 'Địa chỉ hợp lệ',
            'capacity' => 100,
            'layout_preset' => 'custom_grid',
            'layout' => json_encode($layout),
        ])
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('warning');

    expect($room->fresh()->seats()->pluck('id')->sort()->values()->all())->toBe($originalSeatIds);
});

// TC_UC005_015: Chặn xóa/thay đổi ghế khi đang có ghế giữ chỗ (held)
test('TC_UC005_015: room with held seats protects seat layout and returns warning', function () {
    $room = Room::factory()->create(['layout_preset' => 'custom_grid']);
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 2, 'cols' => 4]);

    $showtime = Showtime::factory()->for($room)->create();
    $seat = $room->seats()->first();
    ShowtimeSeat::create([
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'status' => 'held',
    ]);

    $originalSeatIds = $room->seats()->pluck('id')->sort()->values()->all();

    $layout = LayoutTemplates::get('custom_grid', ['rows' => 2, 'cols' => 4]);
    $layout['zones'][0]['color'] = '#000000';

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), [
            'name' => $room->name,
            'address' => $room->address ?? 'Địa chỉ hợp lệ',
            'capacity' => 100,
            'layout_preset' => 'custom_grid',
            'layout' => json_encode($layout),
        ])
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('warning');

    expect($room->fresh()->seats()->pluck('id')->sort()->values()->all())->toBe($originalSeatIds);
});

// TC_UC005_016: Khách vãng lai (Guest) truy cập builder hoặc lưu sơ đồ bị chuyển hướng về /login
test('TC_UC005_016: guest accessing room builder routes is redirected to login', function () {
    $room = Room::factory()->create();

    $this->get(route('admin.rooms.builder', $room))->assertRedirect(route('login'));
    $this->put(route('admin.rooms.update', $room), [])->assertRedirect(route('login'));
});

// TC_UC005_017: Tài khoản vai trò user thường truy cập builder hoặc lưu sơ đồ bị chặn với mã 403
test('TC_UC005_017: regular user accessing room builder routes is forbidden 403', function () {
    $user = createCustomer();
    $room = Room::factory()->create();

    $this->actingAs($user)->get(route('admin.rooms.builder', $room))->assertForbidden();
    $this->actingAs($user)->put(route('admin.rooms.update', $room), [])->assertForbidden();
});
