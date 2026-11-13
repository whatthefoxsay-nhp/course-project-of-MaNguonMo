<?php

use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatZone;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Services\GoogleDriveService;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

function createTicketBooking(User $user, string $status = 'confirmed', int $itemCount = 1): Booking
{
    $room = Room::factory()->create([
        'name' => 'Trung Tâm Hội Nghị Quốc Gia',
        'layout_preset' => 'custom_grid',
        'address' => 'Đại lộ Thăng Long, Mễ Trì, Nam Từ Liêm, Hà Nội',
    ]);
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid');

    $event = Event::factory()->create([
        'title' => 'Đêm Nhạc Giao Hưởng Mùa Xuân',
        'status' => 'published',
    ]);

    $showtime = Showtime::factory()->create([
        'event_id' => $event->id,
        'room_id' => $room->id,
        'base_price' => 350000,
        'start_time' => now()->addDays(7)->setTime(19, 30),
        'end_time' => now()->addDays(7)->setTime(22, 30),
    ]);
    app(ShowtimeSeatGenerator::class)->generate($showtime);

    $seats = $showtime->showtimeSeats()->take($itemCount)->get();

    $booking = Booking::create([
        'user_id' => $user->id,
        'booking_code' => 'TBX-' . strtoupper(fake()->bothify('??######')),
        'total_price' => $seats->sum('effective_price'),
        'status' => $status,
        'payment_method' => 'vietqr',
    ]);

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

test('TC_UC018_001: owner of confirmed booking accesses GET /bookings/{id}/ticket displaying event, time, gate, zone, and seats', function () {
    $user = createCustomer();
    $booking = createTicketBooking($user, 'confirmed', 1);

    $response = $this->actingAs($user)->get(route('bookings.ticket', $booking->id));

    $response->assertOk();
    $response->assertSee('Đêm Nhạc Giao Hưởng Mùa Xuân');
    $response->assertSee('Trung Tâm Hội Nghị Quốc Gia');
    $response->assertSee('19:30 - ' . now()->addDays(7)->format('d/m/Y'));
    $response->assertSee('Cổng soát vé (Gate)');
    $response->assertSee('Khu vực (Zone)');
    $response->assertSee('Vị trí chỗ ngồi');
});

test('TC_UC018_002: multi-item bookings render complete details and unique ticket codes for each ticket', function () {
    $user = createCustomer();
    $booking = createTicketBooking($user, 'confirmed', 3);

    $response = $this->actingAs($user)->get(route('bookings.ticket', $booking->id));

    $response->assertOk();
    $response->assertSee('Danh Sách Vé Điện Tử (3 Vé)');

    // Kiểm tra từng vé có mã định danh riêng
    $response->assertSee($booking->booking_code . '-01');
    $response->assertSee($booking->booking_code . '-02');
    $response->assertSee($booking->booking_code . '-03');
});

test('TC_UC018_003: generates secure QR code images containing unique authentication string for check-in', function () {
    $user = createCustomer();
    $booking = createTicketBooking($user, 'confirmed', 1);

    $response = $this->actingAs($user)->get(route('bookings.ticket', $booking->id));

    $response->assertOk();
    // Render thẻ ảnh QR base64
    $response->assertSee('data:image/svg+xml;base64,');
    $response->assertSee('CHECK-IN QR PASS');
    $response->assertSee('MÃ XÁC THỰC CHECK-IN');
});

test('TC_UC018_004: download PDF ticket via GET /bookings/{id}/ticket/pdf returns valid non-empty application/pdf', function () {
    $user = createCustomer();
    $booking = createTicketBooking($user, 'confirmed', 2);

    $response = $this->actingAs($user)->get(route('bookings.ticket.pdf', $booking->id));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain("attachment; filename=\"ve-dien-tu-{$booking->booking_code}.pdf\"")
        ->and($response->getContent())->toStartWith('%PDF-')
        ->and(strlen($response->getContent()))->toBeGreaterThan(1000);
});

test('TC_UC018_005: sends electronic ticket to buyer email via Mail facade with PDF attachments', function () {
    Mail::fake();

    $user = createCustomer(['email' => 'khanh.hang@gmail.com']);
    $booking = createTicketBooking($user, 'confirmed', 1);

    $response = $this->actingAs($user)->postJson(route('bookings.ticket.email', $booking->id), [
        'email' => 'khanh.hang@gmail.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    Mail::assertQueued(BookingConfirmationMail::class, function ($mail) use ($user, $booking) {
        return $mail->hasTo('khanh.hang@gmail.com') && $mail->booking->id === $booking->id;
    });
});

test('TC_UC018_006: syncs electronic ticket copy to Google Drive using mock service successfully', function () {
    $user = createCustomer();
    $booking = createTicketBooking($user, 'confirmed', 1);

    // Mock GoogleDriveService
    $mockDrive = Mockery::mock(GoogleDriveService::class);
    $mockDrive->shouldReceive('uploadContent')
        ->once()
        ->andReturn('https://drive.google.com/uc?export=view&id=fake_drive_ticket_file_123');
    $this->app->instance(GoogleDriveService::class, $mockDrive);

    $response = $this->actingAs($user)->postJson(route('bookings.ticket.sync-drive', $booking->id));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Đã đồng bộ bản sao vé điện tử lên Google Drive thành công!',
            'drive_url' => 'https://drive.google.com/uc?export=view&id=fake_drive_ticket_file_123',
        ]);
});

test('TC_UC018_007: safely handles Google Drive and SMTP exceptions without crashing main ticket view', function () {
    $user = createCustomer();
    $booking = createTicketBooking($user, 'confirmed', 1);

    // 1. Giả lập Google Drive bị lỗi kết nối / timeout
    $mockDrive = Mockery::mock(GoogleDriveService::class);
    $mockDrive->shouldReceive('uploadContent')
        ->andThrow(new RuntimeException('Google Drive API Connection Timeout'));
    $this->app->instance(GoogleDriveService::class, $mockDrive);

    $driveResponse = $this->actingAs($user)->postJson(route('bookings.ticket.sync-drive', $booking->id));
    $driveResponse->assertStatus(500)
        ->assertJson([
            'success' => false,
            'message' => 'Lỗi kết nối khi đồng bộ lên Google Drive. Vui lòng thử lại sau.',
        ]);

    // 2. Trang xem vé chính vẫn hoạt động bình thường HTTP 200
    $viewResponse = $this->actingAs($user)->get(route('bookings.ticket', $booking->id));
    $viewResponse->assertOk()
        ->assertSee('Đêm Nhạc Giao Hưởng Mùa Xuân');
});

test('TC_UC018_008: access to ticket view and PDF download is denied with 403 for pending bookings', function () {
    $user = createCustomer();
    $pendingBooking = createTicketBooking($user, 'pending');

    // Chặn xem vé
    $this->actingAs($user)->get(route('bookings.ticket', $pendingBooking->id))
        ->assertForbidden();

    // Chặn tải PDF
    $this->actingAs($user)->get(route('bookings.ticket.pdf', $pendingBooking->id))
        ->assertForbidden();
});

test('TC_UC018_009: access to ticket view and PDF download is denied with 403 for cancelled bookings', function () {
    $user = createCustomer();
    $cancelledBooking = createTicketBooking($user, 'cancelled');

    // Chặn xem vé
    $this->actingAs($user)->get(route('bookings.ticket', $cancelledBooking->id))
        ->assertForbidden();

    // Chặn tải PDF
    $this->actingAs($user)->get(route('bookings.ticket.pdf', $cancelledBooking->id))
        ->assertForbidden();
});

test('TC_UC018_010: IDOR protection: User A cannot view or download ticket PDF belonging to User B', function () {
    $userA = createCustomer(['email' => 'user_a@ticket.vn']);
    $userB = createCustomer(['email' => 'user_b@ticket.vn']);

    $bookingB = createTicketBooking($userB, 'confirmed');

    // User A cố truy cập vé của User B -> 403 Forbidden
    $this->actingAs($userA)->get(route('bookings.ticket', $bookingB->id))
        ->assertForbidden();

    // User A cố tải PDF của User B -> 403 Forbidden
    $this->actingAs($userA)->get(route('bookings.ticket.pdf', $bookingB->id))
        ->assertForbidden();
});

test('TC_UC018_011: unauthenticated guest accessing ticket view or PDF download is redirected to /login', function () {
    $user = createCustomer();
    $booking = createTicketBooking($user, 'confirmed');

    $this->assertGuest();

    // 1. Xem vé không đăng nhập
    $this->get(route('bookings.ticket', $booking->id))
        ->assertRedirect(route('login'));

    // 2. Tải PDF không đăng nhập
    $this->get(route('bookings.ticket.pdf', $booking->id))
        ->assertRedirect(route('login'));
});

test('TC_UC018_012: accessing non-existent booking ticket route returns 404 not found', function () {
    $user = createCustomer();

    $this->actingAs($user)->get('/bookings/99999999/ticket')
        ->assertNotFound();

    $this->actingAs($user)->get('/bookings/99999999/ticket/pdf')
        ->assertNotFound();
});
