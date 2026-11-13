<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatZone;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\RoomSeatGenerator;
use App\Support\LayoutTemplates;

// TC_UC003_001: Hiển thị danh sách địa điểm tại /admin/rooms với đầy đủ cột
test('TC_UC003_001: admin sees room list with full columns including name, address, capacity, preset label and showtimes count', function () {
    $room = Room::factory()->create([
        'name' => 'Khán Phòng Hòa Nhạc Quốc Gia',
        'address' => 'Số 1 Tràng Tiền, Hoàn Kiếm, Hà Nội',
        'capacity' => 1200,
        'layout_preset' => 'theater_hall',
    ]);
    app(RoomSeatGenerator::class)->generate($room, 'theater_hall');

    $event = Event::factory()->create();
    Showtime::factory()->create([
        'room_id' => $room->id,
        'event_id' => $event->id,
    ]);

    $this->actingAs(createAdmin())
        ->get(route('admin.rooms.index'))
        ->assertOk()
        ->assertSee('Khán Phòng Hòa Nhạc Quốc Gia')
        ->assertSee('Số 1 Tràng Tiền, Hoàn Kiếm, Hà Nội')
        ->assertSee('1,200 Khán giả')
        ->assertSee('Nhà hát &amp; Giao hưởng', false)
        ->assertSee('1 Suất diễn');
});

// TC_UC003_002: Tạo mới địa điểm thành công với đầy đủ trường hợp lệ
test('TC_UC003_002: admin creates a room with valid attributes and layout preset successfully', function () {
    $payload = [
        'name' => 'Trung Tâm Hội Nghị Triển Lãm SECC',
        'address' => '799 Nguyễn Văn Linh, Quận 7, TP.HCM',
        'capacity' => 5000,
        'layout_preset' => 'convention_center',
        'layout' => json_encode(LayoutTemplates::get('convention_center')),
    ];

    $this->actingAs(createAdmin())
        ->post(route('admin.rooms.store'), $payload)
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('success');

    $room = Room::where('name', 'Trung Tâm Hội Nghị Triển Lãm SECC')->first();
    expect($room)->not->toBeNull()
        ->and($room->address)->toBe('799 Nguyễn Văn Linh, Quận 7, TP.HCM')
        ->and($room->capacity)->toBe(5000)
        ->and($room->layout_preset)->toBe('convention_center')
        ->and($room->zones()->count())->toBeGreaterThan(0)
        ->and($room->seats()->count())->toBeGreaterThan(0);
});

// TC_UC003_003: Tạo mới địa điểm với từng mẫu sơ đồ khởi tạo khác nhau
test('TC_UC003_003: admin creates rooms with every available layout preset', function (string $preset) {
    $payload = [
        'name' => 'Địa Điểm Mẫu ' . ucfirst($preset),
        'address' => 'Khu Đô Thị Mới Thủ Thiêm, TP.HCM',
        'capacity' => 1500,
        'layout_preset' => $preset,
        'layout' => json_encode(LayoutTemplates::get($preset)),
    ];

    $this->actingAs(createAdmin())
        ->post(route('admin.rooms.store'), $payload)
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('success');

    $room = Room::where('name', 'Địa Điểm Mẫu ' . ucfirst($preset))->first();
    expect($room)->not->toBeNull()
        ->and($room->layout_preset)->toBe($preset)
        ->and($room->seats()->count())->toBeGreaterThan(0);
})->with([
    'mega_concert',
    'theater_hall',
    'convention_center',
    'banquet_hall',
    'open_space',
    'custom_grid',
]);

// TC_UC003_004: Cập nhật thông tin địa điểm khi chưa phát sinh vé bán
test('TC_UC003_004: admin updates room details when no tickets have been sold', function () {
    $room = Room::factory()->create([
        'name' => 'Hội Trường A - Cũ',
        'address' => 'Địa chỉ Cũ',
        'capacity' => 300,
        'layout_preset' => 'theater_hall',
    ]);
    app(RoomSeatGenerator::class)->generate($room, 'theater_hall');

    $updatePayload = [
        'name' => 'Hội Trường A - Đã Nâng Cấp VIP',
        'address' => 'Số 10 Lê Duẩn, Bến Nghé, Quận 1',
        'capacity' => 600,
        'layout_preset' => 'theater_hall',
        'layout' => json_encode(LayoutTemplates::get('theater_hall')),
    ];

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), $updatePayload)
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('success');

    $fresh = $room->fresh();
    expect($fresh->name)->toBe('Hội Trường A - Đã Nâng Cấp VIP')
        ->and($fresh->address)->toBe('Số 10 Lê Duẩn, Bến Nghé, Quận 1')
        ->and($fresh->capacity)->toBe(600);
});

// TC_UC003_005: Xóa địa điểm thành công khi chưa có suất diễn nào
test('TC_UC003_005: admin deletes an empty room with no showtimes', function () {
    $room = Room::factory()->create(['name' => 'Khán Phòng Tạm']);
    app(RoomSeatGenerator::class)->generate($room, 'banquet_hall');

    $this->actingAs(createAdmin())
        ->delete(route('admin.rooms.destroy', $room))
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('success');

    expect(Room::find($room->id))->toBeNull()
        ->and(SeatZone::where('room_id', $room->id)->count())->toBe(0)
        ->and(Seat::where('room_id', $room->id)->count())->toBe(0);
});

// TC_UC003_006: Xóa địa điểm thành công khi có suất diễn nhưng chưa bán vé nào
test('TC_UC003_006: admin deletes a room that has showtimes with no sold or held tickets', function () {
    $room = Room::factory()->create(['name' => 'Khán Phòng Show Chưa Bán Vé']);
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 2, 'cols' => 3]);

    $showtime = Showtime::factory()->for($room)->create();
    // Tạo ghế suất diễn ở trạng thái available (chưa bán, chưa giữ)
    foreach ($room->seats as $seat) {
        ShowtimeSeat::create([
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
            'status' => 'available',
        ]);
    }

    $this->actingAs(createAdmin())
        ->delete(route('admin.rooms.destroy', $room))
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('success');

    expect(Room::find($room->id))->toBeNull()
        ->and(Showtime::find($showtime->id))->toBeNull();
});

// TC_UC003_007: Nhân bản địa điểm thành công — kiểm tra hậu tố (Bản sao)
test('TC_UC003_007: duplicating room creates a new record with copy suffix in name', function () {
    $room = Room::factory()->create(['name' => 'Nhà Hát Tuổi Trẻ']);
    app(RoomSeatGenerator::class)->generate($room, 'theater_hall');

    $this->actingAs(createAdmin())
        ->post(route('admin.rooms.duplicate', $room))
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('success');

    $copy = Room::where('name', 'like', 'Nhà Hát Tuổi Trẻ (Bản Sao%')->first();
    expect($copy)->not->toBeNull()
        ->and($copy->id)->not->toBe($room->id);
});

// TC_UC003_008: Nhân bản địa điểm — sao chép đầy đủ zones, seats và tọa độ
test('TC_UC003_008: duplicating room copies seat_zones and seats without altering original room', function () {
    $room = Room::factory()->create(['name' => 'Sân Khấu Lan Anh']);
    app(RoomSeatGenerator::class)->generate($room, 'banquet_hall');

    $origZoneCodes = $room->zones()->pluck('code')->sort()->values()->all();
    $origSeatsCount = $room->seats()->count();

    $this->actingAs(createAdmin())
        ->post(route('admin.rooms.duplicate', $room))
        ->assertRedirect(route('admin.rooms.index'));

    $copy = Room::where('id', '!=', $room->id)->latest('id')->first();
    expect($copy->zones()->pluck('code')->sort()->values()->all())->toBe($origZoneCodes)
        ->and($copy->seats()->count())->toBe($origSeatsCount)
        ->and($copy->seats()->where('room_id', $copy->id)->count())->toBe($origSeatsCount)
        ->and($room->fresh()->seats()->count())->toBe($origSeatsCount);
});

// TC_UC003_009: Nhân bản nhiều lần liên tiếp không bị xung đột tên/unique
test('TC_UC003_009: duplicating same room multiple times generates distinct names without error', function () {
    $room = Room::factory()->create(['name' => 'Trung Tâm Hội Nghị Riverside']);
    app(RoomSeatGenerator::class)->generate($room, 'theater_hall');

    $admin = createAdmin();

    // Nhân bản lần 1
    $this->actingAs($admin)
        ->post(route('admin.rooms.duplicate', $room))
        ->assertRedirect(route('admin.rooms.index'));

    // Nhân bản lần 2
    $this->actingAs($admin)
        ->post(route('admin.rooms.duplicate', $room))
        ->assertRedirect(route('admin.rooms.index'));

    // Nhân bản lần 3
    $this->actingAs($admin)
        ->post(route('admin.rooms.duplicate', $room))
        ->assertRedirect(route('admin.rooms.index'));

    $copies = Room::where('name', 'like', 'Trung Tâm Hội Nghị Riverside (Bản Sao%')->get();
    expect($copies->count())->toBe(3)
        ->and($copies->pluck('name')->unique()->count())->toBe(3);
});

// TC_UC003_010: Bỏ trống các trường bắt buộc bị validation chặn
test('TC_UC003_010: creating room fails when required fields are missing', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.rooms.store'), [
            'name' => '',
            'address' => '',
            'capacity' => null,
            'layout_preset' => '',
            'layout' => '',
        ])
        ->assertSessionHasErrors(['name', 'address', 'capacity', 'layout_preset', 'layout']);
});

// TC_UC003_011: Tên khán phòng vượt quá 255 ký tự hoặc chỉ chứa khoảng trắng
test('TC_UC003_011: creating room fails when name exceeds 255 characters or is only whitespace', function () {
    $layout = json_encode(LayoutTemplates::get('theater_hall'));

    // Test vượt 255 ký tự
    $this->actingAs(createAdmin())
        ->post(route('admin.rooms.store'), [
            'name' => str_repeat('A', 256),
            'address' => 'Địa chỉ hợp lệ',
            'capacity' => 100,
            'layout_preset' => 'theater_hall',
            'layout' => $layout,
        ])
        ->assertSessionHasErrors('name');

    // Test chỉ chứa khoảng trắng
    $this->actingAs(createAdmin())
        ->post(route('admin.rooms.store'), [
            'name' => '     ',
            'address' => 'Địa chỉ hợp lệ',
            'capacity' => 100,
            'layout_preset' => 'theater_hall',
            'layout' => $layout,
        ])
        ->assertSessionHasErrors('name');
});

// TC_UC003_012: Giá trị Preset Layout không hợp lệ
test('TC_UC003_012: creating room fails when layout preset does not exist in system presets', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.rooms.store'), [
            'name' => 'Khán phòng test preset sai',
            'address' => 'Địa chỉ test',
            'capacity' => 100,
            'layout_preset' => 'non_existent_preset_xyz',
            'layout' => json_encode(LayoutTemplates::get('theater_hall')),
        ])
        ->assertSessionHasErrors('layout_preset');
});

// TC_UC003_013: Chống tấn công XSS trong tên khán phòng và vị trí
test('TC_UC003_013: xss scripts in room name and address are safely escaped on output', function () {
    $xssName = '<script>alert("XSS-Room")</script>';
    $xssAddress = '<script>alert("XSS-Address")</script>';

    $room = Room::factory()->create([
        'name' => $xssName,
        'address' => $xssAddress,
        'layout_preset' => 'theater_hall',
    ]);
    app(RoomSeatGenerator::class)->generate($room, 'theater_hall');

    $this->actingAs(createAdmin())
        ->get(route('admin.rooms.index'))
        ->assertOk()
        ->assertDontSee('<script>alert("XSS-Room")</script>', false)
        ->assertDontSee('<script>alert("XSS-Address")</script>', false)
        ->assertSee(e($xssName), false)
        ->assertSee(e($xssAddress), false);
});

// TC_UC003_014: Chặn xóa địa điểm khi đã có suất diễn phát sinh vé bán thành công (confirmed)
test('TC_UC003_014: deleting room is blocked when showtimes have confirmed booked tickets', function () {
    $room = Room::factory()->create(['name' => 'Khán Phòng Đã Bán Vé']);
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 2, 'cols' => 2]);

    $showtime = Showtime::factory()->for($room)->create();
    $seat = $room->seats()->first();
    $showtimeSeat = ShowtimeSeat::create([
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'status' => 'booked',
    ]);

    $customer = createCustomer();
    $booking = Booking::create([
        'user_id' => $customer->id,
        'total_price' => 250000,
        'status' => 'confirmed',
    ]);
    BookingItem::create([
        'booking_id' => $booking->id,
        'showtime_seat_id' => $showtimeSeat->id,
        'price' => 250000,
    ]);

    $this->actingAs(createAdmin())
        ->delete(route('admin.rooms.destroy', $room))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Room::find($room->id))->not->toBeNull()
        ->and(BookingItem::count())->toBe(1);
});

// TC_UC003_015: Chặn xóa địa điểm khi có vé ở trạng thái giữ chỗ (held)
test('TC_UC003_015: deleting room is blocked when showtime has held seats in pending checkout', function () {
    $room = Room::factory()->create(['name' => 'Khán Phòng Đang Giữ Chỗ']);
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 2, 'cols' => 2]);

    $showtime = Showtime::factory()->for($room)->create();
    $seat = $room->seats()->first();
    ShowtimeSeat::create([
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'status' => 'held',
    ]);

    $this->actingAs(createAdmin())
        ->delete(route('admin.rooms.destroy', $room))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Room::find($room->id))->not->toBeNull();
});

// TC_UC003_016: Khách vãng lai (Guest) truy cập các endpoint của rooms bị chuyển hướng về /login
test('TC_UC003_016: guest accessing room management routes is redirected to login', function () {
    $room = Room::factory()->create();

    $this->get(route('admin.rooms.index'))->assertRedirect(route('login'));
    $this->get(route('admin.rooms.create'))->assertRedirect(route('login'));
    $this->post(route('admin.rooms.store'), [])->assertRedirect(route('login'));
    $this->get(route('admin.rooms.builder', $room))->assertRedirect(route('login'));
    $this->put(route('admin.rooms.update', $room), [])->assertRedirect(route('login'));
    $this->post(route('admin.rooms.duplicate', $room))->assertRedirect(route('login'));
    $this->delete(route('admin.rooms.destroy', $room))->assertRedirect(route('login'));
});

// TC_UC003_017: Tài khoản vai trò User thường bị chặn với mã lỗi 403 Forbidden
test('TC_UC003_017: regular user accessing room management routes is forbidden 403', function () {
    $user = createCustomer();
    $room = Room::factory()->create();

    $this->actingAs($user)->get(route('admin.rooms.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.rooms.create'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.rooms.store'), [])->assertForbidden();
    $this->actingAs($user)->get(route('admin.rooms.builder', $room))->assertForbidden();
    $this->actingAs($user)->put(route('admin.rooms.update', $room), [])->assertForbidden();
    $this->actingAs($user)->post(route('admin.rooms.duplicate', $room))->assertForbidden();
    $this->actingAs($user)->delete(route('admin.rooms.destroy', $room))->assertForbidden();
});

