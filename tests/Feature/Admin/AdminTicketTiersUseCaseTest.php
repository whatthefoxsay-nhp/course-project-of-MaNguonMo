<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatZone;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\RoomSeatGenerator;

function zonePayload(array $zones, array $overrides = []): array
{
    $rows = [
        ['label' => 'A', 'cells' => [['zone' => $zones[0]['code']]]],
    ];

    return array_merge([
        'name' => 'Khán Phòng Đang Thử Loại Vé',
        'address' => 'Số 10 Hai Bà Trưng, Quận 1',
        'capacity' => 500,
        'layout_preset' => 'custom_grid',
        'layout' => json_encode([
            'zones' => $zones,
            'rows' => $rows,
        ]),
    ], $overrides);
}

// TC_UC006_001: Tạo mới khu vực ghế ngồi (kind = seated) thành công
test('TC_UC006_001: admin creates seated zone with code, hex color, gate and surcharge successfully', function () {
    $room = Room::factory()->create();
    $zones = [
        [
            'code' => 'SVIP',
            'name' => 'Ghế Siêu VIP Kim Cương',
            'kind' => 'seated',
            'color' => '#D4AF37',
            'gate' => 'Cổng 1 VIP',
            'surcharge' => 150000,
            'description' => 'Trải nghiệm đẳng cấp nhất sát sân khấu',
            'perks' => ['Welcome drink', 'Bộ quà tặng VIP'],
        ],
    ];

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), zonePayload($zones))
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('success');

    $zone = SeatZone::where('room_id', $room->id)->where('code', 'SVIP')->first();
    expect($zone)->not->toBeNull()
        ->and($zone->name)->toBe('Ghế Siêu VIP Kim Cương')
        ->and($zone->kind)->toBe('seated')
        ->and($zone->color)->toBe('#D4AF37')
        ->and($zone->gate)->toBe('Cổng 1 VIP')
        ->and($zone->surcharge)->toBe(150000)
        ->and($zone->perks)->toBe(['Welcome drink', 'Bộ quà tặng VIP']);
});

// TC_UC006_002: Tạo mới khu vực bàn tiệc tròn (kind = table) thành công
test('TC_UC006_002: admin creates round table zone with table kind in database', function () {
    $room = Room::factory()->create();
    $zones = [
        [
            'code' => 'TBL',
            'name' => 'Bàn Tròn Gala Dinner',
            'kind' => 'table',
            'color' => '#10B981',
            'gate' => 'Sảnh B',
            'surcharge' => 80000,
        ],
    ];

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), zonePayload($zones))
        ->assertRedirect(route('admin.rooms.index'));

    $zone = SeatZone::where('room_id', $room->id)->where('code', 'TBL')->first();
    expect($zone)->not->toBeNull()
        ->and($zone->kind)->toBe('table')
        ->and($zone->isPickable())->toBeTrue();
});

// TC_UC006_003: Tạo mới khu vực đứng tự do (kind = standing) thành công
test('TC_UC006_003: admin creates free standing zone with standing kind and capacity', function () {
    $room = Room::factory()->create();
    $zones = [
        [
            'code' => 'GA',
            'name' => 'Khu Đứng GA Fanzone',
            'kind' => 'standing',
            'capacity' => 500,
            'color' => '#EC4899',
            'gate' => 'Cổng Fanzone',
            'surcharge' => 50000,
        ],
    ];

    $payload = [
        'name' => $room->name,
        'address' => 'Địa chỉ test',
        'capacity' => 500,
        'layout_preset' => 'open_space',
        'layout' => json_encode(['zones' => $zones, 'rows' => []]),
    ];

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), $payload)
        ->assertRedirect(route('admin.rooms.index'));

    $zone = SeatZone::where('room_id', $room->id)->where('code', 'GA')->first();
    expect($zone)->not->toBeNull()
        ->and($zone->kind)->toBe('standing')
        ->and($zone->capacity)->toBe(500)
        ->and($zone->isStanding())->toBeTrue();
});

// TC_UC006_004: Tạo loại vé tiêu chuẩn có mức phụ thu bằng 0 (surcharge = 0)
test('TC_UC006_004: system accepts zero surcharge for standard ticket zone', function () {
    $room = Room::factory()->create();
    $zones = [
        [
            'code' => 'STD',
            'name' => 'Ghế Tiêu Chuẩn',
            'kind' => 'seated',
            'color' => '#3A5A40',
            'surcharge' => 0,
        ],
    ];

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), zonePayload($zones))
        ->assertRedirect(route('admin.rooms.index'));

    $zone = SeatZone::where('room_id', $room->id)->where('code', 'STD')->first();
    expect($zone->surcharge)->toBe(0);
});

// TC_UC006_005: Cập nhật thông tin khu vực vé (đổi tên, mã màu, cổng, phụ thu)
test('TC_UC006_005: admin updates zone details and changes are synced', function () {
    $room = Room::factory()->create();
    SeatZone::create([
        'room_id' => $room->id,
        'code' => 'VIP',
        'name' => 'VIP Cũ',
        'kind' => 'seated',
        'color' => '#3A5A40',
        'gate' => 'Cổng Cũ',
        'surcharge' => 50000,
    ]);

    $updatedZones = [
        [
            'code' => 'VIP',
            'name' => 'VIP Siêu Cấp Mới',
            'kind' => 'seated',
            'color' => '#D4AF37',
            'gate' => 'Cổng Gold 1',
            'surcharge' => 120000,
        ],
    ];

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), zonePayload($updatedZones))
        ->assertRedirect(route('admin.rooms.index'));

    $zone = SeatZone::where('room_id', $room->id)->where('code', 'VIP')->first();
    expect($zone)->not->toBeNull()
        ->and($zone->name)->toBe('VIP Siêu Cấp Mới')
        ->and($zone->color)->toBe('#D4AF37')
        ->and($zone->gate)->toBe('Cổng Gold 1')
        ->and($zone->surcharge)->toBe(120000);
});

// TC_UC006_006: Xóa khu vực vé (seat_zone) thành công khi chưa có ghế nào gán
test('TC_UC006_006: deleting unused seat zone record succeeds', function () {
    $room = Room::factory()->create();
    $zone = SeatZone::create([
        'room_id' => $room->id,
        'code' => 'UNUSED',
        'name' => 'Khu Chưa Dùng',
        'kind' => 'seated',
        'surcharge' => 0,
    ]);

    expect(SeatZone::find($zone->id))->not->toBeNull();

    $zone->delete();

    expect(SeatZone::find($zone->id))->toBeNull();
});

// TC_UC006_007: Tính giá vé tự động với khu vực có phụ thu dương (300.000 + 200.000 = 500.000)
test('TC_UC006_007: effective price calculates base price plus positive zone surcharge', function () {
    $room = Room::factory()->create();
    $showtime = Showtime::factory()->for($room)->create(['base_price' => 300000]);

    $zone = SeatZone::create([
        'room_id' => $room->id,
        'code' => 'VIP',
        'name' => 'Khu VIP',
        'kind' => 'seated',
        'surcharge' => 200000,
    ]);
    $seat = Seat::create(['room_id' => $room->id, 'zone_id' => $zone->id, 'row_label' => 'A', 'seat_number' => 1, 'type' => 'VIP']);
    $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'available']);

    expect($zone->priceFor(300000))->toBe(500000)
        ->and($showtimeSeat->effective_price)->toBe(500000);
});

// TC_UC006_008: Tính giá vé tự động với khu vực không phụ thu (surcharge = 0 -> 300.000)
test('TC_UC006_008: effective price calculates exact base price when zone surcharge is zero', function () {
    $room = Room::factory()->create();
    $showtime = Showtime::factory()->for($room)->create(['base_price' => 300000]);

    $zone = SeatZone::create([
        'room_id' => $room->id,
        'code' => 'STD',
        'name' => 'Khu Thường',
        'kind' => 'seated',
        'surcharge' => 0,
    ]);
    $seat = Seat::create(['room_id' => $room->id, 'zone_id' => $zone->id, 'row_label' => 'B', 'seat_number' => 1, 'type' => 'STD']);
    $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'available']);

    expect($zone->priceFor(300000))->toBe(300000)
        ->and($showtimeSeat->effective_price)->toBe(300000);
});

// TC_UC006_009: Kiểm tra giá vé của nhiều khu vực khác nhau (SVIP, VIP, CAT1, GA) trong cùng suất diễn
test('TC_UC006_009: showtime seats accurately calculate respective prices across multiple tiers in same showtime', function () {
    $room = Room::factory()->create();
    $showtime = Showtime::factory()->for($room)->create(['base_price' => 300000]);

    $tiers = [
        'SVIP' => 300000, // 300k + 300k = 600k
        'VIP' => 150000,  // 300k + 150k = 450k
        'CAT1' => 50000,  // 300k + 50k = 350k
        'GA' => 0,        // 300k + 0k = 300k
    ];

    foreach ($tiers as $code => $surcharge) {
        $zone = SeatZone::create([
            'room_id' => $room->id,
            'code' => $code,
            'name' => "Khu {$code}",
            'kind' => $code === 'GA' ? 'standing' : 'seated',
            'surcharge' => $surcharge,
        ]);
        $seat = Seat::create(['room_id' => $room->id, 'zone_id' => $zone->id, 'row_label' => $code, 'seat_number' => 1, 'type' => $code]);
        $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'available']);

        expect($showtimeSeat->effective_price)->toBe(300000 + $surcharge);
    }
});

// TC_UC006_010: Bỏ trống các trường bắt buộc (code, name, kind)
test('TC_UC006_010: zone validation fails when required fields are missing', function () {
    $room = Room::factory()->create();

    // Thiếu code
    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), zonePayload([
            ['code' => '', 'name' => 'Tên Khu', 'kind' => 'seated', 'color' => '#123D22'],
        ]))->assertSessionHasErrors('layout');

    // Thiếu name
    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), zonePayload([
            ['code' => 'Z1', 'name' => '', 'kind' => 'seated', 'color' => '#123D22'],
        ]))->assertSessionHasErrors('layout');

    // Thiếu kind
    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), zonePayload([
            ['code' => 'Z1', 'name' => 'Tên Khu', 'kind' => '', 'color' => '#123D22'],
        ]))->assertSessionHasErrors('layout');
});

// TC_UC006_011: Trùng lặp code trong cùng room bị từ chối; khác room thì hợp lệ
test('TC_UC006_011: duplicate zone code is rejected in same room but allowed across different rooms', function () {
    $room = Room::factory()->create();

    // 2 khu cùng code 'VIP' trong 1 room -> Bị từ chối
    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), zonePayload([
            ['code' => 'VIP', 'name' => 'VIP 1', 'kind' => 'seated', 'color' => '#123D22'],
            ['code' => 'VIP', 'name' => 'VIP 2', 'kind' => 'seated', 'color' => '#123D22'],
        ]))->assertSessionHasErrors('layout');

    // Khác room: Room 1 có VIP, Room 2 tạo VIP -> Hoàn toàn hợp lệ
    $room1 = Room::factory()->create();
    $room2 = Room::factory()->create();

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room1), zonePayload([
            ['code' => 'VIP', 'name' => 'VIP Room 1', 'kind' => 'seated', 'color' => '#123D22'],
        ]))->assertRedirect(route('admin.rooms.index'));

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room2), zonePayload([
            ['code' => 'VIP', 'name' => 'VIP Room 2', 'kind' => 'seated', 'color' => '#123D22'],
        ]))->assertRedirect(route('admin.rooms.index'));

    expect(SeatZone::where('code', 'VIP')->count())->toBe(2);
});

// TC_UC006_012: Giá trị kind không hợp lệ bị từ chối
test('TC_UC006_012: invalid zone kind outside seated, table, standing is rejected', function () {
    $room = Room::factory()->create();

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), zonePayload([
            ['code' => 'BAD', 'name' => 'Loại Lạ', 'kind' => 'flying_seat', 'color' => '#123D22'],
        ]))->assertSessionHasErrors('layout');
});

// TC_UC006_013: Định dạng mã màu HEX: chấp nhận 6 ký tự hex, từ chối định dạng sai và XSS
test('TC_UC006_013: color validation accepts valid 6-char hex and rejects invalid formats or xss', function () {
    $room = Room::factory()->create();
    $admin = createAdmin();

    // Hợp lệ: #D4AF37
    $this->actingAs($admin)->put(route('admin.rooms.update', $room), zonePayload([
        ['code' => 'HEX1', 'name' => 'Vàng Gold', 'kind' => 'seated', 'color' => '#D4AF37'],
    ]))->assertRedirect(route('admin.rooms.index'));

    // Không hợp lệ: 'red'
    $this->actingAs($admin)->put(route('admin.rooms.update', $room), zonePayload([
        ['code' => 'BAD1', 'name' => 'Màu Chữ', 'kind' => 'seated', 'color' => 'red'],
    ]))->assertSessionHasErrors('layout');

    // Không hợp lệ: '#GGGGGG'
    $this->actingAs($admin)->put(route('admin.rooms.update', $room), zonePayload([
        ['code' => 'BAD2', 'name' => 'Mã Hex Sai', 'kind' => 'seated', 'color' => '#GGGGGG'],
    ]))->assertSessionHasErrors('layout');

    // Không hợp lệ: '<script>'
    $this->actingAs($admin)->put(route('admin.rooms.update', $room), zonePayload([
        ['code' => 'XSS', 'name' => 'Mã XSS', 'kind' => 'seated', 'color' => '<script>alert(1)</script>'],
    ]))->assertSessionHasErrors('layout');
});

// TC_UC006_014: Validation mức phụ thu: từ chối số âm dưới giới hạn, không phải số hoặc vượt ngưỡng
test('TC_UC006_014: surcharge validation rejects negative numbers, non-numeric strings or excessive amounts', function () {
    $room = Room::factory()->create();
    $admin = createAdmin();

    // Vượt ngưỡng âm cho phép (< -1.000.000đ)
    $this->actingAs($admin)->put(route('admin.rooms.update', $room), zonePayload([
        ['code' => 'NEG', 'name' => 'Phụ Thu Âm Quá Mức', 'kind' => 'seated', 'color' => '#123D22', 'surcharge' => -2000000],
    ]))->assertSessionHasErrors('layout');

    // Không phải số ('nam-chuc-nghin')
    $this->actingAs($admin)->put(route('admin.rooms.update', $room), zonePayload([
        ['code' => 'STR', 'name' => 'Phụ Thu Chữ', 'kind' => 'seated', 'color' => '#123D22', 'surcharge' => 'nam-chuc-nghin'],
    ]))->assertSessionHasErrors('layout');

    // Vượt ngưỡng > 10.000.000đ (15.000.000đ)
    $this->actingAs($admin)->put(route('admin.rooms.update', $room), zonePayload([
        ['code' => 'HUGE', 'name' => 'Phụ Thu Quá Lớn', 'kind' => 'seated', 'color' => '#123D22', 'surcharge' => 15000000],
    ]))->assertSessionHasErrors('layout');
});

// TC_UC006_015: Cổng soát vé (gate) là nullable, cho phép để trống
test('TC_UC006_015: gate field is nullable and saving succeeds when omitted or null', function () {
    $room = Room::factory()->create();

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), zonePayload([
            ['code' => 'NOGATE', 'name' => 'Không Cổng', 'kind' => 'seated', 'color' => '#123D22', 'gate' => null],
        ]))->assertRedirect(route('admin.rooms.index'));

    $zone = SeatZone::where('room_id', $room->id)->where('code', 'NOGATE')->first();
    expect($zone->gate)->toBeNull();
});

// TC_UC006_016: Bảo toàn kind và cấu trúc ghế của seat_zone khi địa điểm đã có suất diễn bán vé
test('TC_UC006_016: seat zone kind and linked seats are protected when room has showtimes with confirmed tickets', function () {
    $room = Room::factory()->create(['layout_preset' => 'custom_grid']);
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 2, 'cols' => 4]);

    $zone = $room->zones()->first();
    $seat = $zone->seats()->first();
    $showtime = Showtime::factory()->for($room)->create(['base_price' => 200000]);
    $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'booked']);

    $booking = Booking::create(['user_id' => createCustomer()->id, 'total_price' => 300000, 'status' => 'confirmed']);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $showtimeSeat->id, 'price' => 300000]);

    $originalSeatCount = $zone->seats()->count();
    $originalKind = $zone->kind;

    // Gửi cập nhật thông tin khu (đổi tên và phụ thu)
    $layout = \App\Support\LayoutTemplates::get('custom_grid', ['rows' => 2, 'cols' => 4]);
    $layout['zones'][0]['name'] = 'VIP Đã Bán Vé - Đổi Tên';
    $layout['zones'][0]['surcharge'] = 180000;

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

    $zone->refresh();
    // Kind và số lượng ghế liên kết vẫn bảo toàn nguyên vẹn
    expect($zone->kind)->toBe($originalKind)
        ->and($zone->name)->toBe('VIP Đã Bán Vé - Đổi Tên')
        ->and($zone->surcharge)->toBe(180000)
        ->and($zone->seats()->count())->toBe($originalSeatCount);
});

// TC_UC006_017: Thay đổi surcharge của zone không làm thay đổi giá tiền trên booking_items cũ
test('TC_UC006_017: updating zone surcharge does not alter prices of previously paid booking items', function () {
    $room = Room::factory()->create();
    $zone = SeatZone::create([
        'room_id' => $room->id,
        'code' => 'VIP',
        'name' => 'Khu VIP',
        'kind' => 'seated',
        'surcharge' => 200000,
        'color' => '#D4AF37',
    ]);
    $seat = Seat::create(['room_id' => $room->id, 'zone_id' => $zone->id, 'row_label' => 'A', 'seat_number' => 1, 'type' => 'VIP']);
    $showtime = Showtime::factory()->for($room)->create(['base_price' => 300000]);
    $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'booked']);

    // Đơn hàng đã chốt thanh toán ở mức 500.000đ (300k base + 200k surcharge)
    $booking = Booking::create(['user_id' => createCustomer()->id, 'total_price' => 500000, 'status' => 'confirmed']);
    $item = BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $showtimeSeat->id, 'price' => 500000]);

    // Admin điều chỉnh phụ thu lên 450.000đ
    $updatedZones = [
        ['code' => 'VIP', 'name' => 'Khu VIP Tăng Giá', 'kind' => 'seated', 'surcharge' => 450000, 'color' => '#D4AF37'],
    ];

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), [
            'name' => $room->name,
            'address' => $room->address ?? 'Địa chỉ',
            'capacity' => 100,
            'layout_preset' => 'custom_grid',
            'layout' => json_encode(['zones' => $updatedZones, 'rows' => [['label' => 'A', 'cells' => [['zone' => 'VIP']]]]]),
        ]);

    $zone->refresh();
    expect($zone->surcharge)->toBe(450000);

    // Vé mới tính theo phụ thu mới (300k + 450k = 750k)
    expect($showtimeSeat->fresh()->effective_price)->toBe(750000);

    // Dữ liệu đơn hàng cũ trên booking_items vẫn giữ nguyên 500.000đ, không bị đổi
    expect($item->fresh()->price)->toBe(500000);
});

// TC_UC006_018: Khách vãng lai (Guest) thao tác seat_zones bị chuyển hướng về /login
test('TC_UC006_018: guest attempting to update seat zones is redirected to login', function () {
    $room = Room::factory()->create();

    $this->put(route('admin.rooms.update', $room), [])
        ->assertRedirect(route('login'));
});

// TC_UC006_019: Tài khoản vai trò user thao tác seat_zones bị chặn 403 Forbidden
test('TC_UC006_019: regular user attempting to update seat zones is forbidden 403', function () {
    $user = createCustomer();
    $room = Room::factory()->create();

    $this->actingAs($user)
        ->put(route('admin.rooms.update', $room), [])
        ->assertForbidden();
});
