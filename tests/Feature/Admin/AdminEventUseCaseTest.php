<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\GoogleDriveService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    config(['services.google_drive.enabled' => false]);
});

function validEventData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Concert Mùa Thu 2026',
        'category_id' => Category::factory()->create()->id,
        'status' => 'draft',
        'is_seated' => '1',
        'duration_minutes' => 120,
        'release_date' => now()->addMonth()->toDateString(),
        'description' => 'Đêm nhạc acoustic mùa thu đầy lãng mạn.',
        'details' => [
            'venue_name' => 'Nhà Hát Thành Phố',
            'venue_address' => '07 Lam Sơn, Bến Nghé, Quận 1',
        ],
        'lineup_text' => "Vũ | Ca sĩ | Indie | Artist\nĐen Vâu | Rapper | Khách mời | VIP",
        'timeline_text' => "18:30 | Đón khách | Check-in\n19:30 | Bắt đầu diễn | Mở màn",
    ], $overrides);
}

// TC_UC004_001: Hiển thị danh sách sự kiện với đầy đủ thông tin, danh mục và trạng thái
test('TC_UC004_001: admin sees event list with titles, categories and badges for draft, published, archived', function () {
    $cat = Category::factory()->create(['name' => 'Âm Nhạc Live']);
    $eDraft = Event::factory()->create(['title' => 'Event Bản Nháp', 'category_id' => $cat->id, 'status' => 'draft']);
    $ePub = Event::factory()->create(['title' => 'Event Đang Mở Bán', 'category_id' => $cat->id, 'status' => 'published']);
    $eArch = Event::factory()->create(['title' => 'Event Đã Lưu Trữ', 'category_id' => $cat->id, 'status' => 'archived']);

    $this->actingAs(createAdmin())
        ->get(route('admin.events.index'))
        ->assertOk()
        ->assertSee('Event Bản Nháp')
        ->assertSee('Event Đang Mở Bán')
        ->assertSee('Event Đã Lưu Trữ')
        ->assertSee('Âm Nhạc Live');
});

// TC_UC004_002: Lọc danh sách sự kiện theo từng danh mục (category_id)
test('TC_UC004_002: admin filters events by category', function () {
    $catConcert = Category::factory()->create(['name' => 'Nhạc Hội']);
    $catWorkshop = Category::factory()->create(['name' => 'Hội Thảo']);

    $eConcert = Event::factory()->create(['title' => 'Liveshow Rock', 'category_id' => $catConcert->id]);
    $eWorkshop = Event::factory()->create(['title' => 'Kỹ Năng Lãnh Đạo', 'category_id' => $catWorkshop->id]);

    $this->actingAs(createAdmin())
        ->get(route('admin.events.index', ['category_id' => $catConcert->id]))
        ->assertOk()
        ->assertSee('Liveshow Rock')
        ->assertDontSee('Kỹ Năng Lãnh Đạo');
});

// TC_UC004_003: Lọc danh sách sự kiện theo từng trạng thái
test('TC_UC004_003: admin filters events by status', function () {
    $eDraft = Event::factory()->create(['title' => 'Sự Kiện Nháp Chỉ Định', 'status' => 'draft']);
    $ePub = Event::factory()->create(['title' => 'Sự Kiện Mở Bán Chỉ Định', 'status' => 'published']);

    $this->actingAs(createAdmin())
        ->get(route('admin.events.index', ['status' => 'published']))
        ->assertOk()
        ->assertSee('Sự Kiện Mở Bán Chỉ Định')
        ->assertDontSee('Sự Kiện Nháp Chỉ Định');
});

// TC_UC004_004: Kết hợp đồng thời cả bộ lọc Danh mục và Trạng thái
test('TC_UC004_004: admin filters events by both category and status simultaneously', function () {
    $catA = Category::factory()->create();
    $catB = Category::factory()->create();

    $target = Event::factory()->create(['title' => 'Mục Tiêu Trùng Khớp', 'category_id' => $catA->id, 'status' => 'published']);
    $diffStatus = Event::factory()->create(['title' => 'Khác Trạng Thái', 'category_id' => $catA->id, 'status' => 'draft']);
    $diffCategory = Event::factory()->create(['title' => 'Khác Danh Mục', 'category_id' => $catB->id, 'status' => 'published']);

    $this->actingAs(createAdmin())
        ->get(route('admin.events.index', ['category_id' => $catA->id, 'status' => 'published']))
        ->assertOk()
        ->assertSee('Mục Tiêu Trùng Khớp')
        ->assertDontSee('Khác Trạng Thái')
        ->assertDontSee('Khác Danh Mục');
});

// TC_UC004_005: Tạo mới sự kiện thành công có chọn chỗ ngồi (is_seated = true)
test('TC_UC004_005: admin creates a seated event successfully', function () {
    $payload = validEventData(['title' => 'Hòa Nhạc Giao Hưởng Seated', 'is_seated' => '1']);

    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), $payload)
        ->assertRedirect(route('admin.events.index'))
        ->assertSessionHas('success');

    $event = Event::firstWhere('title', 'Hòa Nhạc Giao Hưởng Seated');
    expect($event)->not->toBeNull()
        ->and($event->is_seated)->toBeTrue()
        ->and($event->duration_minutes)->toBe(120);
});

// TC_UC004_006: Tạo mới sự kiện không chọn chỗ ngồi (is_seated = false)
test('TC_UC004_006: admin creates a standing free-seating event with is_seated false', function () {
    $payload = validEventData(['title' => 'Festival Bia Ngoài Trời', 'is_seated' => '0']);

    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), $payload)
        ->assertRedirect(route('admin.events.index'))
        ->assertSessionHas('success');

    $event = Event::firstWhere('title', 'Festival Bia Ngoài Trời');
    expect($event)->not->toBeNull()
        ->and($event->is_seated)->toBeFalse();
});

// TC_UC004_007: Upload poster hợp lệ ở chế độ lưu trữ cục bộ (public storage)
test('TC_UC004_007: admin uploads valid poster storing locally when Google Drive is disabled', function () {
    $posterFile = UploadedFile::fake()->create('event_poster.jpg', 800, 'image/jpeg');

    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), validEventData(['poster' => $posterFile]))
        ->assertRedirect(route('admin.events.index'))
        ->assertSessionHas('success');

    $event = Event::firstWhere('title', 'Concert Mùa Thu 2026');
    expect($event->poster_path)->not->toBeNull()
        ->and($event->poster_path)->toStartWith('posters/');

    Storage::disk('public')->assertExists($event->poster_path);
});

// TC_UC004_008: Upload poster khi kích hoạt dịch vụ Google Drive
test('TC_UC004_008: admin uploads poster to Google Drive when service is enabled', function () {
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('isEnabled')->once()->andReturn(true);
    $drive->shouldReceive('upload')->once()->with(Mockery::type(UploadedFile::class))
        ->andReturn('https://drive.google.com/uc?export=view&id=drive_mock_999');
    $this->app->instance(GoogleDriveService::class, $drive);

    $posterFile = UploadedFile::fake()->create('drive_poster.png', 500, 'image/png');

    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), validEventData(['poster' => $posterFile]))
        ->assertRedirect(route('admin.events.index'))
        ->assertSessionHas('success');

    $event = Event::firstWhere('title', 'Concert Mùa Thu 2026');
    expect($event->poster_path)->toBe('https://drive.google.com/uc?export=view&id=drive_mock_999');
    Storage::disk('public')->assertDirectoryEmpty('posters');
});

// TC_UC004_009: Lưu trữ dữ liệu cấu trúc JSON (Địa chỉ, Lineup, Timeline)
test('TC_UC004_009: event details JSON accurately stores structured lineup and timeline', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), validEventData([
            'lineup_text' => "Sơn Tùng M-TP | Ca sĩ | Headliner | VVIP\nMONO | Ca sĩ | Khách mời | VIP",
            'timeline_text' => "18:00 | Check-in | Cổng mở\n20:00 | Bắt đầu | Tiết mục 1",
        ]))
        ->assertRedirect(route('admin.events.index'));

    $event = Event::firstWhere('title', 'Concert Mùa Thu 2026');
    expect($event->lineup)->toHaveCount(2)
        ->and($event->lineup[0])->toBe(['name' => 'Sơn Tùng M-TP', 'role' => 'Ca sĩ', 'tag' => 'Headliner', 'badge' => 'VVIP'])
        ->and($event->lineup[1])->toBe(['name' => 'MONO', 'role' => 'Ca sĩ', 'tag' => 'Khách mời', 'badge' => 'VIP'])
        ->and($event->timeline)->toHaveCount(2)
        ->and($event->timeline[0])->toBe(['time' => '18:00', 'title' => 'Check-in', 'desc' => 'Cổng mở'])
        ->and($event->venue_name)->toBe('Nhà Hát Thành Phố');
});

// TC_UC004_010: Cập nhật thông tin cơ bản và dữ liệu JSON chi tiết thành công
test('TC_UC004_010: admin updates event basic info and structured JSON details successfully', function () {
    $event = Event::factory()->create(['title' => 'Tên Cũ']);

    $this->actingAs(createAdmin())
        ->put(route('admin.events.update', $event), validEventData([
            'title' => 'Tên Mới Sau Cập Nhật',
            'duration_minutes' => 180,
            'lineup_text' => "Mỹ Tâm | Ca sĩ | Diva",
        ]))
        ->assertRedirect(route('admin.events.index'))
        ->assertSessionHas('success');

    $event->refresh();
    expect($event->title)->toBe('Tên Mới Sau Cập Nhật')
        ->and($event->duration_minutes)->toBe(180)
        ->and($event->lineup[0]['name'])->toBe('Mỹ Tâm');
});

// TC_UC004_011: Chuyển trạng thái từ draft sang published — hiển thị công khai trên catalog
test('TC_UC004_011: publishing an event makes it visible on the public catalog', function () {
    $event = Event::factory()->create([
        'title' => 'Đại Nhạc Hội Sắp Ra Mắt',
        'status' => 'draft',
    ]);

    // Trang public không thấy draft
    $this->get(route('events.index'))
        ->assertOk()
        ->assertDontSee('Đại Nhạc Hội Sắp Ra Mắt');

    // Chuyển sang published
    $this->actingAs(createAdmin())
        ->put(route('admin.events.update', $event), validEventData([
            'title' => 'Đại Nhạc Hội Sắp Ra Mắt',
            'status' => 'published',
        ]))
        ->assertRedirect(route('admin.events.index'));

    // Trang public thấy published
    $this->get(route('events.index'))
        ->assertOk()
        ->assertSee('Đại Nhạc Hội Sắp Ra Mắt');
});

// TC_UC004_012: Cập nhật thay thế poster mới và dọn dẹp file cũ
test('TC_UC004_012: replacing poster stores new file and deletes old file from storage', function () {
    Storage::disk('public')->put('posters/old_file.jpg', 'old_bytes');
    $event = Event::factory()->create(['poster_path' => 'posters/old_file.jpg']);

    $newFile = UploadedFile::fake()->create('brand_new.png', 400, 'image/png');

    $this->actingAs(createAdmin())
        ->put(route('admin.events.update', $event), validEventData(['poster' => $newFile]))
        ->assertRedirect(route('admin.events.index'));

    $event->refresh();
    expect($event->poster_path)->not->toBe('posters/old_file.jpg');
    Storage::disk('public')->assertMissing('posters/old_file.jpg');
    Storage::disk('public')->assertExists($event->poster_path);
});

// TC_UC004_013: Chuyển sang archived — ẩn khỏi mở bán mới nhưng giữ lịch sử đơn hàng
test('TC_UC004_013: archiving an event hides it from public shop while preserving bookings', function () {
    $event = Event::factory()->create(['title' => 'Sự Kiện Đã Qua', 'status' => 'published']);
    $room = Room::factory()->create();
    $showtime = Showtime::factory()->for($event)->for($room)->create();
    $seat = Seat::create(['room_id' => $room->id, 'row_label' => 'A', 'seat_number' => 1, 'type' => 'VIP']);
    $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'booked']);

    $customer = createCustomer();
    $booking = Booking::create(['user_id' => $customer->id, 'total_price' => 500000, 'status' => 'confirmed']);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $showtimeSeat->id, 'price' => 500000]);

    // Lưu trữ sự kiện
    $this->actingAs(createAdmin())
        ->put(route('admin.events.update', $event), validEventData([
            'title' => 'Sự Kiện Đã Qua',
            'status' => 'archived',
        ]))
        ->assertRedirect(route('admin.events.index'));

    expect($event->fresh()->status)->toBe('archived');

    // Không còn thấy ở trang khách hàng (xóa flash session của admin update trước đó)
    session()->forget('success');
    $publicResponse = $this->get(route('events.index'));
    $publicResponse->assertDontSee('Sự Kiện Đã Qua');
    expect($publicResponse->viewData('events')->pluck('id'))->not->toContain($event->id);

    // Đơn hàng và vé lịch sử vẫn nguyên vẹn
    expect(Booking::find($booking->id))->not->toBeNull()
        ->and(BookingItem::find($booking->id))->not->toBeNull();
});

// TC_UC004_014: Bỏ trống các trường bắt buộc (title, category_id, duration_minutes)
test('TC_UC004_014: creating event fails when required fields are missing', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), [
            'title' => '',
            'category_id' => '',
            'duration_minutes' => '',
            'status' => 'draft',
        ])
        ->assertSessionHasErrors(['title', 'category_id', 'duration_minutes']);
});

// TC_UC004_015: Validation category_id không tồn tại trong database
test('TC_UC004_015: creating event fails with invalid or non-existent category_id', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), validEventData([
            'category_id' => 999999,
        ]))
        ->assertSessionHasErrors('category_id');
});

// TC_UC004_016: Validation duration_minutes: số âm, 0, chuỗi không phải số
test('TC_UC004_016: creating event fails when duration is negative, zero or non-numeric', function () {
    $admin = createAdmin();

    $this->actingAs($admin)->post(route('admin.events.store'), validEventData(['duration_minutes' => -10]))
        ->assertSessionHasErrors('duration_minutes');

    $this->actingAs($admin)->post(route('admin.events.store'), validEventData(['duration_minutes' => 0]))
        ->assertSessionHasErrors('duration_minutes');

    $this->actingAs($admin)->post(route('admin.events.store'), validEventData(['duration_minutes' => 'khong-phai-so']))
        ->assertSessionHasErrors('duration_minutes');
});

// TC_UC004_017: File ảnh poster sai định dạng (pdf, exe, svg)
test('TC_UC004_017: uploading invalid poster formats is rejected by validation', function () {
    $admin = createAdmin();

    $this->actingAs($admin)->post(route('admin.events.store'), validEventData([
        'poster' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ]))->assertSessionHasErrors('poster');

    $this->actingAs($admin)->post(route('admin.events.store'), validEventData([
        'poster' => UploadedFile::fake()->create('danger.exe', 100, 'application/octet-stream'),
    ]))->assertSessionHasErrors('poster');

    $this->actingAs($admin)->post(route('admin.events.store'), validEventData([
        'poster' => UploadedFile::fake()->create('vector.svg', 100, 'image/svg+xml'),
    ]))->assertSessionHasErrors('poster');
});

// TC_UC004_018: Kiểm tra biên dung lượng file poster (2048 KB thành công, 2049 KB thất bại)
test('TC_UC004_018: boundary file size validation: 2048KB succeeds while 2049KB fails', function () {
    $admin = createAdmin();

    // 2048 KB: Vừa đúng giới hạn 2MB -> Thành công
    $this->actingAs($admin)->post(route('admin.events.store'), validEventData([
        'title' => 'Event Dung Lượng Chuẩn 2MB',
        'poster' => UploadedFile::fake()->create('valid_2mb.jpg', 2048, 'image/jpeg'),
    ]))->assertRedirect(route('admin.events.index'))->assertSessionHas('success');

    // 2049 KB: Vượt giới hạn -> Bị chặn
    $this->actingAs($admin)->post(route('admin.events.store'), validEventData([
        'title' => 'Event Dung Lượng Quá 2MB',
        'poster' => UploadedFile::fake()->create('invalid_over.jpg', 2049, 'image/jpeg'),
    ]))->assertSessionHasErrors('poster');
});

// TC_UC004_019: Chống tấn công XSS trong trường mô tả và thông tin lineup
test('TC_UC004_019: xss attacks in description and lineup are safely escaped on output', function () {
    $xssDesc = '<script>alert("XSS-Desc")</script>';
    $xssLineup = '<script>alert("XSS-Artist")</script> | Ca sĩ';

    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), validEventData([
            'title' => 'Sự Kiện Kiểm Thử XSS',
            'description' => $xssDesc,
            'lineup_text' => $xssLineup,
        ]))
        ->assertRedirect(route('admin.events.index'));

    $event = Event::firstWhere('title', 'Sự Kiện Kiểm Thử XSS');

    $this->actingAs(createAdmin())
        ->get(route('admin.events.edit', $event))
        ->assertOk()
        ->assertDontSee('<script>alert("XSS-Desc")</script>', false)
        ->assertDontSee('<script>alert("XSS-Artist")</script>', false)
        ->assertSee(e($xssDesc), false);
});

// TC_UC004_020: Xóa sự kiện thành công khi chưa có suất diễn / chưa bán vé
test('TC_UC004_020: admin deletes an event successfully when no tickets have been sold', function () {
    $event = Event::factory()->create(['title' => 'Sự Kiện Sẽ Bị Xóa']);

    $this->actingAs(createAdmin())
        ->delete(route('admin.events.destroy', $event))
        ->assertRedirect(route('admin.events.index'))
        ->assertSessionHas('success');

    expect(Event::find($event->id))->toBeNull();
});

// TC_UC004_021: Chặn xóa sự kiện đã phát sinh vé bán thành công (confirmed)
test('TC_UC004_021: deleting event is rejected when tickets are booked and suggests archiving', function () {
    $event = Event::factory()->create(['title' => 'Concert Đã Bán Hết Vé']);
    $room = Room::factory()->create();
    $showtime = Showtime::factory()->for($event)->for($room)->create();
    $seat = Seat::create(['room_id' => $room->id, 'row_label' => 'A', 'seat_number' => 1, 'type' => 'VIP']);
    $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'booked']);

    $this->actingAs(createAdmin())
        ->delete(route('admin.events.destroy', $event))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Event::find($event->id))->not->toBeNull();
});

// TC_UC004_022: Chặn xóa sự kiện khi đang có ghế giữ chỗ (held)
test('TC_UC004_022: deleting event is rejected when tickets are in held status during checkout', function () {
    $event = Event::factory()->create(['title' => 'Concert Đang Giữ Chỗ']);
    $room = Room::factory()->create();
    $showtime = Showtime::factory()->for($event)->for($room)->create();
    $seat = Seat::create(['room_id' => $room->id, 'row_label' => 'B', 'seat_number' => 2, 'type' => 'VIP']);
    ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'held']);

    $this->actingAs(createAdmin())
        ->delete(route('admin.events.destroy', $event))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Event::find($event->id))->not->toBeNull();
});

// TC_UC004_023: Khách vãng lai (Guest) truy cập các route quản lý sự kiện bị chuyển hướng về /login
test('TC_UC004_023: guest accessing admin event routes is redirected to login', function () {
    $event = Event::factory()->create();

    $this->get(route('admin.events.index'))->assertRedirect(route('login'));
    $this->get(route('admin.events.create'))->assertRedirect(route('login'));
    $this->post(route('admin.events.store'), [])->assertRedirect(route('login'));
    $this->get(route('admin.events.edit', $event))->assertRedirect(route('login'));
    $this->put(route('admin.events.update', $event), [])->assertRedirect(route('login'));
    $this->delete(route('admin.events.destroy', $event))->assertRedirect(route('login'));
});

// TC_UC004_024: Tài khoản vai trò User thường bị chặn với mã lỗi 403 Forbidden
test('TC_UC004_024: regular user accessing admin event routes is forbidden 403', function () {
    $user = createCustomer();
    $event = Event::factory()->create();

    $this->actingAs($user)->get(route('admin.events.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.events.create'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.events.store'), [])->assertForbidden();
    $this->actingAs($user)->get(route('admin.events.edit', $event))->assertForbidden();
    $this->actingAs($user)->put(route('admin.events.update', $event), [])->assertForbidden();
    $this->actingAs($user)->delete(route('admin.events.destroy', $event))->assertForbidden();
});

