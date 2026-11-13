<?php

use App\Exceptions\SeatHoldException;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Discount;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatZone;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Services\SeatHoldService;
use App\Support\LayoutTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Storage::fake('public');
    config(['services.google_drive.enabled' => false]);
});

/*
|--------------------------------------------------------------------------
| NHÓM 1: RÀNG BUỘC THỜI GIAN & LOGIC CHÉO (CROSS-FIELD & TEMPORAL)
|--------------------------------------------------------------------------
*/
describe('Nhóm 1: Ràng buộc thời gian & logic chéo giữa các trường', function () {
    test('TC_UC006_001: validation chặn tạo suất diễn với start_time trong quá khứ hoặc end_time nhỏ hơn start_time', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create(['status' => 'published']);
        $room = Room::factory()->create();

        // 1. start_time trong quá khứ
        $responsePast = $this->actingAs($admin)
            ->post(route('admin.showtimes.store'), [
                'event_id' => $event->id,
                'room_id' => $room->id,
                'start_time' => now()->subHour()->format('Y-m-d H:i:s'),
                'end_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
                'base_price' => 200000,
            ]);
        $responsePast->assertSessionHasErrors(['start_time']);

        // 2. end_time trước start_time
        $responseInvalidEnd = $this->actingAs($admin)
            ->post(route('admin.showtimes.store'), [
                'event_id' => $event->id,
                'room_id' => $room->id,
                'start_time' => now()->addHours(2)->format('Y-m-d H:i:s'),
                'end_time' => now()->addHour()->format('Y-m-d H:i:s'),
                'base_price' => 200000,
            ]);
        $responseInvalidEnd->assertSessionHasErrors(['end_time']);

        $this->assertDatabaseMissing('showtimes', [
            'event_id' => $event->id,
            'room_id' => $room->id,
        ]);
    });

    test('TC_UC006_002: validation chặn tạo suất diễn trùng khung giờ trong cùng một khán phòng', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create(['status' => 'published']);
        $room = Room::factory()->create();

        // Đã có 1 suất diễn từ 14:00 đến 16:00
        Showtime::create([
            'event_id' => $event->id,
            'room_id' => $room->id,
            'start_time' => now()->addDays(2)->setTime(14, 0),
            'end_time' => now()->addDays(2)->setTime(16, 0),
            'base_price' => 150000,
        ]);

        // Tạo suất thứ hai từ 15:00 đến 17:00 (chồng giờ)
        $response = $this->actingAs($admin)
            ->post(route('admin.showtimes.store'), [
                'event_id' => $event->id,
                'room_id' => $room->id,
                'start_time' => now()->addDays(2)->setTime(15, 0)->format('Y-m-d H:i:s'),
                'end_time' => now()->addDays(2)->setTime(17, 0)->format('Y-m-d H:i:s'),
                'base_price' => 180000,
            ]);

        $response->assertSessionHasErrors(['start_time']);
        expect(Showtime::where('room_id', $room->id)->count())->toBe(1);
    });

    test('TC_UC010_001: validation chặn tạo mã giảm giá với ngày kết thúc (end_date) trong quá khứ', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->post(route('admin.discounts.store'), [
                'code' => 'EXPIRED_VOUCHER',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'start_date' => now()->subDays(10)->toDateString(),
                'end_date' => now()->subDay()->toDateString(),
            ]);

        $response->assertSessionHasErrors(['end_date']);
        $this->assertDatabaseMissing('discounts', ['code' => 'EXPIRED_VOUCHER']);
    });

    test('TC_UC016_001: chặn giữ chỗ và đặt vé cho suất diễn đã qua giờ bắt đầu (start_time < now)', function () {
        $user = User::factory()->create();
        $user->assignRole('user');
        $event = Event::factory()->create(['status' => 'published', 'is_seated' => true]);
        $room = Room::factory()->create();
        $showtime = Showtime::create([
            'event_id' => $event->id,
            'room_id' => $room->id,
            'start_time' => now()->subMinute(), // Đã qua giờ bắt đầu
            'end_time' => now()->addHours(2),
            'base_price' => 100000,
        ]);

        $seat = Seat::create(['room_id' => $room->id, 'row_label' => 'A', 'seat_number' => 1, 'type' => 'normal']);
        $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'available']);

        $service = app(SeatHoldService::class);

        expect(fn () => $service->hold($user, $showtime, [$showtimeSeat->id]))
            ->toThrow(SeatHoldException::class, 'Suất diễn đã bắt đầu, không thể đặt vé.');

        expect($showtimeSeat->fresh()->status)->toBe('available');
    });

    test('TC_UC016_002: thời gian giữ chỗ (held_until) khóa chính xác 10 phút kể từ thời điểm request', function () {
        $user = User::factory()->create();
        $user->assignRole('user');
        $event = Event::factory()->create(['status' => 'published', 'is_seated' => true]);
        $room = Room::factory()->create();
        $showtime = Showtime::create([
            'event_id' => $event->id,
            'room_id' => $room->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(2),
            'base_price' => 100000,
        ]);

        $seat = Seat::create(['room_id' => $room->id, 'row_label' => 'A', 'seat_number' => 2, 'type' => 'normal']);
        $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'available']);

        $service = app(SeatHoldService::class);
        $beforeHold = now();
        $service->hold($user, $showtime, [$showtimeSeat->id]);

        $freshSeat = $showtimeSeat->fresh();
        expect($freshSeat->status)->toBe('held')
            ->and($freshSeat->held_by_user_id)->toBe($user->id)
            ->and((int) abs(round($freshSeat->held_until->diffInSeconds($beforeHold) / 60)))->toBe(10);
    });
})->group('validation');

/*
|--------------------------------------------------------------------------
| NHÓM 2: RÀNG BUỘC BIÊN DỮ LIỆU & ĐỊNH DẠNG (BOUNDARY & FORMAT)
|--------------------------------------------------------------------------
*/
describe('Nhóm 2: Ràng buộc biên dữ liệu & định dạng', function () {
    test('TC_UC005_001: sơ đồ khán phòng chặn vượt biên sức chứa tối đa 5.000 chỗ/phòng', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Layout mẫu có khu đứng sức chứa 5.001 chỗ (> 5000)
        $invalidLayout = [
            'zones' => [
                ['code' => 'VIP', 'name' => 'Khu VIP', 'kind' => 'seated', 'surcharge' => 0, 'capacity' => 0],
                ['code' => 'GA', 'name' => 'Khu Đứng', 'kind' => 'standing', 'surcharge' => 0, 'capacity' => 5001],
            ],
            'rows' => [
                [
                    'label' => 'A',
                    'cells' => [['zone' => 'VIP']],
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.rooms.store'), [
                'name' => 'Khán phòng Siêu Sức Chứa',
                'address' => 'Số 1 Đại Lộ Thăng Long',
                'capacity' => 6000,
                'layout_preset' => LayoutTemplates::PRESETS[0],
                'layout' => json_encode($invalidLayout),
            ]);

        $response->assertSessionHasErrors(['layout']);
        $this->assertDatabaseMissing('rooms', ['name' => 'Khán phòng Siêu Sức Chứa']);
    });

    test('TC_UC015_001: khách hàng bị chặn khi giữ vượt quá 8 ghế trong một lượt đặt', function () {
        $user = User::factory()->create();
        $user->assignRole('user');
        $event = Event::factory()->create(['status' => 'published', 'is_seated' => true]);
        $room = Room::factory()->create();
        $showtime = Showtime::create([
            'event_id' => $event->id,
            'room_id' => $room->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(2),
            'base_price' => 100000,
        ]);

        $seatIds = [];
        for ($i = 1; $i <= 9; $i++) {
            $seat = Seat::create(['room_id' => $room->id, 'row_label' => 'B', 'seat_number' => $i, 'type' => 'normal']);
            $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'available']);
            $seatIds[] = $showtimeSeat->id;
        }

        $service = app(SeatHoldService::class);

        // Giữ 9 ghế (vượt quá 8)
        expect(fn () => $service->hold($user, $showtime, $seatIds))
            ->toThrow(SeatHoldException::class, 'Mỗi lượt đặt tối đa 8 chỗ');
    });

    test('TC_UC004_001: validation dung lượng file poster sự kiện tối đa 2MB (2048 KB)', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $category = Category::factory()->create();

        // 1. File 2049 KB (> 2MB) -> Thất bại
        $fileOver = UploadedFile::fake()->create('poster_heavy.jpg', 2049, 'image/jpeg');
        $responseOver = $this->actingAs($admin)
            ->post(route('admin.events.store'), [
                'title' => 'Sự kiện Poster Quá Dung Lượng',
                'category_id' => $category->id,
                'status' => 'draft',
                'duration_minutes' => 120,
                'poster' => $fileOver,
            ]);
        $responseOver->assertSessionHasErrors(['poster']);

        // 2. File 2048 KB (Đúng biên 2MB) -> Thành công
        $fileValid = UploadedFile::fake()->create('poster_valid.jpg', 2048, 'image/jpeg');
        $responseValid = $this->actingAs($admin)
            ->post(route('admin.events.store'), [
                'title' => 'Sự kiện Poster Đúng Biên 2MB',
                'category_id' => $category->id,
                'status' => 'draft',
                'duration_minutes' => 120,
                'poster' => $fileValid,
            ]);
        $responseValid->assertSessionHasNoErrors();
        $this->assertDatabaseHas('events', ['title' => 'Sự kiện Poster Đúng Biên 2MB']);
    });

    test('TC_UC020_001: validation dung lượng file avatar tài khoản tối đa 1MB (1024 KB)', function () {
        $user = User::factory()->create();
        $user->assignRole('user');

        // File 1025 KB (> 1MB) -> Thất bại
        $fileOver = UploadedFile::fake()->create('avatar_heavy.png', 1025, 'image/png');
        $response = $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $fileOver,
            ]);

        $response->assertSessionHasErrors(['avatar']);
        expect($user->fresh()->avatar)->toBeNull();
    });

    test('TC_UC010_002: validation mã giảm giá phần trăm phải từ 1 đến 100 và tự động viết hoa mã code', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // 1. Phần trăm 101% -> Thất bại
        $responseOver = $this->actingAs($admin)
            ->post(route('admin.discounts.store'), [
                'code' => 'sale101',
                'discount_type' => 'percentage',
                'discount_value' => 101,
            ]);
        $responseOver->assertSessionHasErrors(['discount_value']);

        // 2. Phần trăm hợp lệ 50% kèm mã code chữ thường -> Tự động chuyển thành chữ hoa SALE50
        $responseValid = $this->actingAs($admin)
            ->post(route('admin.discounts.store'), [
                'code' => 'sale50',
                'discount_type' => 'percentage',
                'discount_value' => 50,
            ]);
        $responseValid->assertSessionHasNoErrors();
        $this->assertDatabaseHas('discounts', [
            'code' => 'SALE50',
            'discount_value' => 50,
        ]);
    });

    test('TC_UC021_001: validation đánh giá bình luận số sao từ 1 đến 5 và độ dài nội dung từ 3 đến 1000 ký tự', function () {
        $user = User::factory()->create();
        $user->assignRole('user');
        $event = Event::factory()->create(['status' => 'published']);

        // 1. Sao = 6 và nội dung = 2 ký tự ("OK") -> Lỗi cả 2
        $responseInvalid = $this->actingAs($user)
            ->postJson(route('comments.store', $event->slug), [
                'rating' => 6,
                'content' => 'OK',
            ]);
        $responseInvalid->assertStatus(422)
            ->assertJsonValidationErrors(['rating', 'content']);

        // 2. Nội dung 3 ký tự và sao = 1 -> Thành công
        $responseValidMin = $this->actingAs($user)
            ->postJson(route('comments.store', $event->slug), [
                'rating' => 1,
                'content' => 'Tốt',
            ]);
        $responseValidMin->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'rating' => 1,
            'content' => 'Tốt',
        ]);
    });

    test('TC_UC013_001: validation đăng ký tài khoản yêu cầu số điện thoại chuẩn di động Việt Nam 10 chữ số', function () {
        // SĐT thiếu số hoặc sai đầu số nhà mạng VN
        $response = $this->post(route('register'), [
            'name' => 'Nguyễn Văn Test',
            'email' => 'phone_test@example.com',
            'phone' => '012345678', // 9 chữ số và đầu 01 cũ
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['phone']);
        $this->assertDatabaseMissing('users', ['email' => 'phone_test@example.com']);
    });
})->group('validation');

/*
|--------------------------------------------------------------------------
| NHÓM 3: RÀNG BUỘC TOÀN VẸN DỮ LIỆU & TRẠNG THÁI (INTEGRITY & STATE MACHINE)
|--------------------------------------------------------------------------
*/
describe('Nhóm 3: Ràng buộc toàn vẹn dữ liệu & trạng thái', function () {
    test('TC_UC002_001: chặn xóa danh mục đang có sự kiện liên kết', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $category = Category::factory()->create(['name' => 'Âm nhạc']);
        Event::factory()->create(['category_id' => $category->id, 'title' => 'Concert Sống']);

        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    });

    test('TC_UC003_001: chặn xóa khán phòng đang có suất diễn phát sinh vé đã bán (booked)', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create(['status' => 'published']);
        $room = Room::factory()->create(['name' => 'Nhà Hát Thành Phố']);
        $showtime = Showtime::create([
            'event_id' => $event->id,
            'room_id' => $room->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(2),
            'base_price' => 100000,
        ]);
        $seat = Seat::create(['room_id' => $room->id, 'row_label' => 'A', 'seat_number' => 1, 'type' => 'normal']);
        ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'booked']);

        $response = $this->actingAs($admin)->delete(route('admin.rooms.destroy', $room));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    });

    test('TC_UC007_001: chặn chuyển đơn hàng cancelled sang confirmed nếu ghế đã thuộc đơn khác hoặc đang bị người khác giữ', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $event = Event::factory()->create();
        $room = Room::factory()->create();
        $showtime = Showtime::create([
            'event_id' => $event->id,
            'room_id' => $room->id,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
            'base_price' => 100000,
        ]);
        $seat = Seat::create(['room_id' => $room->id, 'row_label' => 'A', 'seat_number' => 10, 'type' => 'normal']);
        $showtimeSeat = ShowtimeSeat::create([
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
            'status' => 'held',
            'held_by_user_id' => $user2->id,
            'held_until' => now()->addMinutes(10),
        ]);

        // Đơn hàng cũ của user1 đã bị cancelled
        $cancelledBooking = Booking::create([
            'user_id' => $user1->id,
            'booking_code' => 'TBX-CANCEL1',
            'total_price' => 100000,
            'status' => 'cancelled',
            'payment_method' => 'card',
        ]);
        BookingItem::create([
            'booking_id' => $cancelledBooking->id,
            'showtime_seat_id' => $showtimeSeat->id,
            'price' => 100000,
        ]);

        // Admin cố gắng khôi phục đơn này sang confirmed
        $response = $this->actingAs($admin)->patch(route('admin.bookings.update-status', $cancelledBooking), [
            'status' => 'confirmed',
        ]);

        $response->assertSessionHas('error');
        expect($cancelledBooking->fresh()->status)->toBe('cancelled');
    });

    test('TC_UC018_001: chặn xem và tải vé điện tử PDF nếu đơn hàng chưa ở trạng thái confirmed', function () {
        $user = User::factory()->create();
        $user->assignRole('user');

        $pendingBooking = Booking::create([
            'user_id' => $user->id,
            'booking_code' => 'TBX-PENDING1',
            'total_price' => 200000,
            'status' => 'pending',
            'payment_method' => 'vietqr',
        ]);

        // 1. Xem vé /bookings/{id}/ticket
        $responseView = $this->actingAs($user)->get(route('bookings.ticket', $pendingBooking));
        $responseView->assertStatus(403);

        // 2. Tải file PDF /bookings/{id}/ticket/pdf
        $responsePdf = $this->actingAs($user)->get(route('bookings.ticket.pdf', $pendingBooking));
        $responsePdf->assertStatus(403);
    });
})->group('validation');

/*
|--------------------------------------------------------------------------
| NHÓM 4: RÀNG BUỘC BẢO MẬT, ĐỒNG THỜI & PHÂN QUYỀN (SECURITY & CONCURRENCY)
|--------------------------------------------------------------------------
*/
describe('Nhóm 4: Ràng buộc bảo mật, phân quyền và tranh chấp đồng thời', function () {
    test('TC_UC001_001: chặn admin tự khóa tài khoản hoặc tự hạ quyền chính mình', function () {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        // 1. Tự khóa tài khoản qua AJAX
        $responseLock = $this->actingAs($admin)->patchJson(route('admin.users.toggle-status', $admin));
        $responseLock->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Bạn không thể tự khóa tài khoản quản trị của chính mình.',
            ]);
        expect($admin->fresh()->is_active)->toBeTrue();

        // 2. Tự đổi vai trò chính mình
        $responseRole = $this->actingAs($admin)->patch(route('admin.users.toggle-role', $admin));
        $responseRole->assertSessionHas('error', 'Bạn không thể tự thay đổi vai trò của chính mình.');
        expect($admin->fresh()->hasRole('admin'))->toBeTrue();
    });

    test('TC_UC001_002: middleware EnsureUserIsActive tự động logout và chặn ngay lập tức tài khoản bị khóa', function () {
        $blockedUser = User::factory()->create(['is_active' => false]);
        $blockedUser->assignRole('user');

        // Gửi request khi tài khoản bị khóa
        $response = $this->actingAs($blockedUser)->get(route('profile.edit'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });

    test('TC_UC016_003: chống xung đột race condition khi 2 user cùng giữ 1 ghế (pessimistic lock)', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $event = Event::factory()->create(['status' => 'published', 'is_seated' => true]);
        $room = Room::factory()->create();
        $showtime = Showtime::create([
            'event_id' => $event->id,
            'room_id' => $room->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(2),
            'base_price' => 100000,
        ]);
        $seat = Seat::create(['room_id' => $room->id, 'row_label' => 'C', 'seat_number' => 5, 'type' => 'normal']);
        $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'available']);

        $service = app(SeatHoldService::class);

        // User A giữ trước thành công
        $service->hold($userA, $showtime, [$showtimeSeat->id]);
        expect($showtimeSeat->fresh()->status)->toBe('held')
            ->and($showtimeSeat->fresh()->held_by_user_id)->toBe($userA->id);

        // User B cố tình giữ cùng ghế đó -> Thất bại ngay
        expect(fn () => $service->hold($userB, $showtime, [$showtimeSeat->id]))
            ->toThrow(SeatHoldException::class, 'vừa có người giữ hoặc đã bán');
    });

    test('TC_UC018_002: chống IDOR user A không thể xem hoặc tải vé điện tử của user B', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $bookingB = Booking::create([
            'user_id' => $userB->id,
            'booking_code' => 'TBX-USER-B',
            'total_price' => 150000,
            'status' => 'confirmed',
            'payment_method' => 'card',
        ]);

        // User A cố tình xem vé của User B
        $response = $this->actingAs($userA)->get(route('bookings.ticket', $bookingB));
        $response->assertStatus(403);

        // User A cố tình tải PDF vé của User B
        $responsePdf = $this->actingAs($userA)->get(route('bookings.ticket.pdf', $bookingB));
        $responsePdf->assertStatus(403);
    });

    test('TC_UC021_002: chống IDOR user A không thể xóa bình luận của user B', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $event = Event::factory()->create(['status' => 'published']);

        $commentB = Comment::create([
            'user_id' => $userB->id,
            'event_id' => $event->id,
            'content' => 'Bình luận chân thực của User B',
            'rating' => 5,
        ]);

        // User A cố tình gửi request xóa bình luận của User B
        $response = $this->actingAs($userA)->deleteJson(route('comments.destroy', $commentB));

        $response->assertStatus(403);
        $this->assertDatabaseHas('comments', ['id' => $commentB->id]);
    });
})->group('validation');
