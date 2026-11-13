<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatZone;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

function createBookingForUser(User $user, string $status = 'confirmed', int $seatCount = 1, array $eventAttributes = [], array $bookingAttributes = []): Booking
{
    $room = Room::factory()->create(['name' => 'Khán Phòng Hòa Bình Sài Gòn', 'layout_preset' => 'custom_grid']);
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid');

    $event = Event::factory()->create(array_merge([
        'title' => 'Liveshow Âm Nhạc Mùa Thu 2026',
        'status' => 'published',
        'poster_path' => 'posters/autumn-live.jpg',
    ], $eventAttributes));

    $showtime = Showtime::factory()->create([
        'event_id' => $event->id,
        'room_id' => $room->id,
        'base_price' => 300000,
        'start_time' => now()->addDays(5)->setTime(20, 0),
        'end_time' => now()->addDays(5)->setTime(23, 0),
    ]);

    app(ShowtimeSeatGenerator::class)->generate($showtime);

    $seats = $showtime->showtimeSeats()->take($seatCount)->get();

    $booking = Booking::create(array_merge([
        'user_id' => $user->id,
        'booking_code' => 'TBX-' . strtoupper(fake()->bothify('??######')),
        'total_price' => $seats->sum('effective_price'),
        'status' => $status,
        'payment_method' => 'vietqr',
    ], $bookingAttributes));

    foreach ($seats as $seat) {
        $seat->update(['status' => 'booked']);
        BookingItem::create([
            'booking_id' => $booking->id,
            'showtime_seat_id' => $seat->id,
            'price' => $seat->effective_price,
        ]);
    }

    return $booking;
}

test('TC_UC017_001: authenticated user accesses /bookings/history successfully displaying full booking details', function () {
    $user = createCustomer();
    $booking = createBookingForUser($user, 'confirmed', 2, [
        'title' => 'Concert Giao Hưởng Mùa Thu',
    ], [
        'payment_method' => 'vietqr',
    ]);

    $response = $this->actingAs($user)->get(route('bookings.history'));

    $response->assertOk();
    $response->assertSee($booking->booking_code);
    $response->assertSee('Concert Giao Hưởng Mùa Thu');
    $response->assertSee('Khán Phòng Hòa Bình Sài Gòn');
    $response->assertSee('VIETQR');
    $response->assertSee(number_format($booking->total_price) . '₫');
});

test('TC_UC017_002: displays all 3 distinct booking statuses accurately: confirmed, pending, and cancelled', function () {
    $user = createCustomer();

    $confirmedBooking = createBookingForUser($user, 'confirmed', 1, ['title' => 'Sự Kiện Đã Xác Nhận']);
    $pendingBooking = createBookingForUser($user, 'pending', 1, ['title' => 'Sự Kiện Chờ Thanh Toán']);
    $cancelledBooking = createBookingForUser($user, 'cancelled', 1, ['title' => 'Sự Kiện Đã Hủy']);

    $response = $this->actingAs($user)->get(route('bookings.history'));

    $response->assertOk();
    $response->assertSee('Đã Xác Nhận');
    $response->assertSee('Chờ Thanh Toán');
    $response->assertSee('Đã Hủy');
});

test('TC_UC017_003: displays "Xem vé điện tử" button linking to /bookings/{id}/ticket for confirmed bookings', function () {
    $user = createCustomer();
    $confirmedBooking = createBookingForUser($user, 'confirmed');

    $response = $this->actingAs($user)->get(route('bookings.history'));

    $response->assertOk();
    $response->assertSee('Xem vé điện tử');
    $response->assertSee(route('bookings.ticket', $confirmedBooking->id));

    // Truy cập trực tiếp link xem vé điện tử thành công HTTP 200
    $ticketResponse = $this->actingAs($user)->get(route('bookings.ticket', $confirmedBooking->id));
    $ticketResponse->assertOk()
        ->assertSee($confirmedBooking->booking_code)
        ->assertSee('XUẤT TRÌNH TẠI CỔNG KIỂM SOÁT');
});

test('TC_UC017_004: hides or suppresses "Xem vé điện tử" button for pending and cancelled bookings', function () {
    $user = createCustomer();
    $pendingBooking = createBookingForUser($user, 'pending');
    $cancelledBooking = createBookingForUser($user, 'cancelled');

    $response = $this->actingAs($user)->get(route('bookings.history'));

    $response->assertOk();
    // Đơn pending & cancelled không có link xem vé điện tử
    $response->assertDontSee(route('bookings.ticket', $pendingBooking->id));
    $response->assertDontSee(route('bookings.ticket', $cancelledBooking->id));
    $response->assertSee('Đơn hàng đang chờ thanh toán');
    $response->assertSee('Đơn hàng đã bị hủy bỏ');

    // Cố truy cập trực tiếp route ticket của đơn pending hoặc cancelled bị 403 (chỉ cho phép đơn confirmed)
    $this->actingAs($user)->get(route('bookings.ticket', $pendingBooking->id))->assertForbidden();
    $this->actingAs($user)->get(route('bookings.ticket', $cancelledBooking->id))->assertForbidden();
});

test('TC_UC017_005: verifies sorting order latest first and pagination mechanism across multiple bookings', function () {
    $user = createCustomer();

    // Tạo 6 đơn hàng (vượt quá 5 đơn/trang)
    for ($i = 1; $i <= 6; $i++) {
        createBookingForUser($user, 'confirmed', 1, [
            'title' => "Concert Đơn Thứ {$i}",
        ]);
    }

    $response = $this->actingAs($user)->get(route('bookings.history'));

    $response->assertOk();
    $bookingsPaginator = $response->viewData('bookings');

    // Paginator instance
    expect($bookingsPaginator->total())->toBe(6)
        ->and($bookingsPaginator->perPage())->toBe(5)
        ->and($bookingsPaginator->count())->toBe(5);

    // Đơn mới nhất (Concert Đơn Thứ 6) hiển thị ở trang đầu
    $response->assertSee('Concert Đơn Thứ 6');
    $response->assertDontSee('Concert Đơn Thứ 1'); // Đơn 1 nằm ở trang 2

    // Truy cập trang 2
    $page2Response = $this->actingAs($user)->get(route('bookings.history', ['page' => 2]));
    $page2Response->assertOk()
        ->assertSee('Concert Đơn Thứ 1');
});

test('TC_UC017_006: user with zero bookings views safe empty state interface without null pointer errors', function () {
    $user = createCustomer();

    $response = $this->actingAs($user)->get(route('bookings.history'));

    $response->assertOk();
    $response->assertSee('Bạn chưa có đơn đặt vé nào');
    $response->assertSee('Khám phá sự kiện ngay');
});

test('TC_UC017_007: booking history retains full event and ticket data even when event is archived or past', function () {
    $user = createCustomer();

    // Sự kiện đã chuyển sang trạng thái archived và suất diễn trong quá khứ
    $pastShowtimeStart = now()->subMonth();
    $archivedBooking = createBookingForUser($user, 'confirmed', 2, [
        'title' => 'Concert Huyền Thoại Đã Lưu Trữ',
        'status' => 'archived',
    ]);
    $archivedBooking->items->first()->showtimeSeat->showtime->update([
        'start_time' => $pastShowtimeStart,
    ]);

    $response = $this->actingAs($user)->get(route('bookings.history'));

    $response->assertOk();
    $response->assertSee($archivedBooking->booking_code);
    $response->assertSee('Concert Huyền Thoại Đã Lưu Trữ');
    $response->assertSee('Khán Phòng Hòa Bình Sài Gòn');
});

test('TC_UC017_008: multi-item bookings accurately list all seat codes and calculate correct total amount', function () {
    $user = createCustomer();
    $booking = createBookingForUser($user, 'confirmed', 3, [
        'title' => 'Đại Nhạc Hội EDM 3 Ghế',
    ]);

    $response = $this->actingAs($user)->get(route('bookings.history'));

    $response->assertOk();
    $seatCodes = $booking->items->map(fn ($i) => $i->showtimeSeat->seat->full_code)->all();

    foreach ($seatCodes as $code) {
        $response->assertSee($code);
    }
    $response->assertSee(number_format($booking->total_price) . '₫');
});

test('TC_UC017_009: user data isolation test: User A views only User A bookings without leakage of User B data', function () {
    $userA = createCustomer(['name' => 'Nguyễn Khách A', 'email' => 'khach_a@test.com']);
    $userB = createCustomer(['name' => 'Trần Khách B', 'email' => 'khach_b@test.com']);

    $bookingA = createBookingForUser($userA, 'confirmed', 1, ['title' => 'Concert Của Khách A']);
    $bookingB = createBookingForUser($userB, 'confirmed', 1, ['title' => 'Concert Của Khách B']);

    // User A đăng nhập
    $responseA = $this->actingAs($userA)->get(route('bookings.history'));
    $responseA->assertOk();
    $responseA->assertSee($bookingA->booking_code);
    $responseA->assertSee('Concert Của Khách A');
    expect($responseA->viewData('bookings')->pluck('booking_code'))
        ->toContain($bookingA->booking_code)
        ->not->toContain($bookingB->booking_code);

    // User A cố truy cập vé của User B bị 403 Forbidden
    $this->actingAs($userA)->get(route('bookings.ticket', $bookingB->id))->assertForbidden();
});

test('TC_UC017_010: unauthenticated guest accessing /bookings/history is intercepted and redirected to /login', function () {
    $this->assertGuest();

    $response = $this->get(route('bookings.history'));

    $response->assertRedirect(route('login'));
});

test('TC_UC017_011: locked user with is_active = false is intercepted by EnsureUserIsActive and logged out', function () {
    $lockedUser = createCustomer([
        'is_active' => false,
    ]);

    $response = $this->actingAs($lockedUser)->get(route('bookings.history'));

    // Chuyển hướng về login và người dùng bị đăng xuất
    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

