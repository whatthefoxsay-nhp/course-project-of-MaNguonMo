<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\SeatZone;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->admin = User::factory()->create(['name' => 'Quản Trị Viên']);
    $this->admin->assignRole('admin');

    $this->customer = User::factory()->create([
        'name' => 'Nguyễn Văn Khách',
        'email' => 'khachhang@gmail.com',
        'phone' => '0901234567',
    ]);
    $this->customer->assignRole('user');

    $this->otherCustomer = User::factory()->create([
        'name' => 'Trần Thị Người Mua Khác',
        'email' => 'nguoimuakhac@gmail.com',
    ]);
    $this->otherCustomer->assignRole('user');

    $this->category = Category::create([
        'name' => 'Concert Âm Nhạc',
        'slug' => 'concert-am-nhac',
        'description' => 'Đại nhạc hội',
    ]);

    $this->event = Event::create([
        'category_id' => $this->category->id,
        'title' => 'Skyline Music Festival 2026',
        'slug' => 'skyline-music-festival-2026',
        'description' => 'Đêm nhạc acoustic sống động',
        'duration_minutes' => 150,
        'release_date' => now()->addDays(7)->toDateString(),
        'status' => 'published',
        'is_seated' => true,
    ]);

    $this->room = Room::create([
        'name' => 'Khán Phòng Hòa Bình',
        'address' => 'Số 1 Nguyễn Thị Minh Khai, Q.1',
        'capacity' => 500,
        'layout_preset' => 'theater',
    ]);

    $this->vipZone = SeatZone::create([
        'room_id' => $this->room->id,
        'code' => 'VIP',
        'name' => 'Khu Ghế VIP',
        'kind' => 'seated',
        'color' => '#D4AF37',
        'surcharge' => 100000,
    ]);

    $this->seat1 = Seat::create([
        'room_id' => $this->room->id,
        'zone_id' => $this->vipZone->id,
        'row_label' => 'A',
        'seat_number' => 1,
        'type' => 'vip',
    ]);

    $this->seat2 = Seat::create([
        'room_id' => $this->room->id,
        'zone_id' => $this->vipZone->id,
        'row_label' => 'A',
        'seat_number' => 2,
        'type' => 'vip',
    ]);

    $this->seat3 = Seat::create([
        'room_id' => $this->room->id,
        'zone_id' => $this->vipZone->id,
        'row_label' => 'A',
        'seat_number' => 3,
        'type' => 'vip',
    ]);

    $this->showtime = Showtime::create([
        'event_id' => $this->event->id,
        'room_id' => $this->room->id,
        'start_time' => now()->addDays(5)->setTime(19, 30),
        'end_time' => now()->addDays(5)->setTime(22, 0),
        'base_price' => 400000,
    ]);

    $this->stSeat1 = ShowtimeSeat::create([
        'showtime_id' => $this->showtime->id,
        'seat_id' => $this->seat1->id,
        'price' => 500000,
        'status' => 'booked',
    ]);

    $this->stSeat2 = ShowtimeSeat::create([
        'showtime_id' => $this->showtime->id,
        'seat_id' => $this->seat2->id,
        'price' => 500000,
        'status' => 'booked',
    ]);

    $this->stSeat3 = ShowtimeSeat::create([
        'showtime_id' => $this->showtime->id,
        'seat_id' => $this->seat3->id,
        'price' => 500000,
        'status' => 'booked',
    ]);
});

test('TC_UC007_001: admin sees booking list with code, buyer, event, showtime, seat count, total price, payment method and status', function () {
    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-BOOK001',
        'total_price' => 1000000,
        'status' => 'confirmed',
        'payment_method' => 'vietqr',
    ]);

    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat2->id, 'price' => 500000]);

    $response = $this->actingAs($this->admin)->get(route('admin.bookings.index'));

    $response->assertStatus(200);
    $response->assertSee('TBX-BOOK001');
    $response->assertSee('Nguyễn Văn Khách');
    $response->assertSee('Skyline Music Festival 2026');
    $response->assertSee('Khán Phòng Hòa Bình');
    $response->assertSee($this->showtime->start_time->format('H:i'));
    $response->assertSee('2 vé');
    $response->assertSee('1.000.000₫');
    $response->assertSee('VietQR');
    $response->assertSee('Đã xác nhận');
});

test('TC_UC007_002: search and filter booking list by booking code, buyer name/email, and status', function () {
    $bConfirmed = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-CONFIRM01',
        'total_price' => 500000,
        'status' => 'confirmed',
        'payment_method' => 'card',
    ]);
    BookingItem::create(['booking_id' => $bConfirmed->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);

    $bPending = Booking::create([
        'user_id' => $this->otherCustomer->id,
        'booking_code' => 'TBX-PENDING02',
        'total_price' => 500000,
        'status' => 'pending',
        'payment_method' => 'momo',
    ]);
    BookingItem::create(['booking_id' => $bPending->id, 'showtime_seat_id' => $this->stSeat2->id, 'price' => 500000]);

    $bCancelled = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-CANCEL03',
        'total_price' => 500000,
        'status' => 'cancelled',
        'payment_method' => 'zalopay',
    ]);
    BookingItem::create(['booking_id' => $bCancelled->id, 'showtime_seat_id' => $this->stSeat3->id, 'price' => 500000]);

    // 1. Filter by exact booking code
    $resCode = $this->actingAs($this->admin)->get(route('admin.bookings.index', ['search' => 'TBX-CONFIRM01']));
    $resCode->assertOk();
    $resCode->assertSee('TBX-CONFIRM01');
    $resCode->assertDontSee('TBX-PENDING02');
    $resCode->assertDontSee('TBX-CANCEL03');

    // 2. Filter by buyer name/email
    $resUser = $this->actingAs($this->admin)->get(route('admin.bookings.index', ['search' => 'nguoimuakhac@gmail.com']));
    $resUser->assertOk();
    $resUser->assertSee('TBX-PENDING02');
    $resUser->assertDontSee('TBX-CONFIRM01');

    // 3. Filter by status 'pending'
    $resPending = $this->actingAs($this->admin)->get(route('admin.bookings.index', ['status' => 'pending']));
    $resPending->assertOk();
    $resPending->assertSee('TBX-PENDING02');
    $resPending->assertDontSee('TBX-CONFIRM01');
    $resPending->assertDontSee('TBX-CANCEL03');

    // 4. Filter by status 'cancelled'
    $resCancel = $this->actingAs($this->admin)->get(route('admin.bookings.index', ['status' => 'cancelled']));
    $resCancel->assertOk();
    $resCancel->assertSee('TBX-CANCEL03');
    $resCancel->assertDontSee('TBX-CONFIRM01');
});

test('TC_UC007_003: view booking details displaying buyer information, booking items, seats, and payment method', function () {
    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-DETAIL01',
        'total_price' => 1000000,
        'status' => 'confirmed',
        'payment_method' => 'vietqr',
    ]);

    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat2->id, 'price' => 500000]);

    $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));

    $response->assertStatus(200);
    $response->assertSee('TBX-DETAIL01');
    $response->assertSee('Nguyễn Văn Khách');
    $response->assertSee('khachhang@gmail.com');
    $response->assertSee('0901234567');
    $response->assertSee('Skyline Music Festival 2026');
    $response->assertSee('Khán Phòng Hòa Bình');
    $response->assertSee('Hàng A - Ghế 1');
    $response->assertSee('Hàng A - Ghế 2');
    $response->assertSee('1.000.000₫');
    $response->assertSee('Chuyển khoản VietQR');
});

test('TC_UC007_004: accessing booking details with non-existent id returns 404', function () {
    $this->actingAs($this->admin)->get('/admin/bookings/999999')
        ->assertStatus(404);
});

test('TC_UC007_005: confirming a pending booking updates status to confirmed', function () {
    $this->stSeat1->update(['status' => 'held', 'held_by_user_id' => $this->customer->id, 'held_until' => now()->addMinutes(10)]);

    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-PENDING05',
        'total_price' => 500000,
        'status' => 'pending',
        'payment_method' => 'vietqr',
    ]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);

    $response = $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), [
        'status' => 'confirmed',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($booking->fresh()->status)->toBe('confirmed');
});

test('TC_UC007_006: confirming a pending booking locks all seats to booked in database', function () {
    $this->stSeat1->update(['status' => 'held', 'held_by_user_id' => $this->customer->id, 'held_until' => now()->addMinutes(10)]);
    $this->stSeat2->update(['status' => 'held', 'held_by_user_id' => $this->customer->id, 'held_until' => now()->addMinutes(10)]);

    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-LOCK006',
        'total_price' => 1000000,
        'status' => 'pending',
    ]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat2->id, 'price' => 500000]);

    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), [
        'status' => 'confirmed',
    ]);

    expect($this->stSeat1->fresh()->status)->toBe('booked');
    expect($this->stSeat1->fresh()->held_by_user_id)->toBeNull();
    expect($this->stSeat2->fresh()->status)->toBe('booked');
    expect($this->stSeat2->fresh()->held_by_user_id)->toBeNull();
});

test('TC_UC007_007: confirming an already confirmed booking preserves confirmed state safely (idempotent)', function () {
    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-IDEMP07',
        'total_price' => 500000,
        'status' => 'confirmed',
    ]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);

    $response = $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), [
        'status' => 'confirmed',
    ]);

    $response->assertRedirect();
    expect($booking->fresh()->status)->toBe('confirmed');
    expect($this->stSeat1->fresh()->status)->toBe('booked');
});

test('TC_UC007_008: cancelling a pending booking marks it cancelled and releases seats back to available', function () {
    $this->stSeat1->update(['status' => 'held', 'held_by_user_id' => $this->customer->id, 'held_until' => now()->addMinutes(10)]);

    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-CANCEL08',
        'total_price' => 500000,
        'status' => 'pending',
    ]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);

    $response = $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), [
        'status' => 'cancelled',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($booking->fresh()->status)->toBe('cancelled');
    expect($this->stSeat1->fresh()->status)->toBe('available');
    expect($this->stSeat1->fresh()->held_by_user_id)->toBeNull();
});

test('TC_UC007_009: cancelling a confirmed booking marks it cancelled and releases seats back to available for resale', function () {
    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-CANCEL09',
        'total_price' => 500000,
        'status' => 'confirmed',
    ]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);

    $response = $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), [
        'status' => 'cancelled',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($booking->fresh()->status)->toBe('cancelled');
    expect($this->stSeat1->fresh()->status)->toBe('available');
});

test('TC_UC007_010: multi-seat booking releases all seats simultaneously upon cancellation', function () {
    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-MULTI10',
        'total_price' => 1500000,
        'status' => 'confirmed',
    ]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat2->id, 'price' => 500000]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat3->id, 'price' => 500000]);

    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), [
        'status' => 'cancelled',
    ]);

    expect($booking->fresh()->status)->toBe('cancelled');
    expect($this->stSeat1->fresh()->status)->toBe('available');
    expect($this->stSeat2->fresh()->status)->toBe('available');
    expect($this->stSeat3->fresh()->status)->toBe('available');
});

test('TC_UC007_011: another user can hold and purchase released seats after booking is cancelled', function () {
    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-RESALE11',
        'total_price' => 500000,
        'status' => 'confirmed',
    ]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);

    // 1. Cancel original booking
    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), [
        'status' => 'cancelled',
    ]);
    expect($this->stSeat1->fresh()->status)->toBe('available');

    // 2. New user holds and books the same seat
    $this->stSeat1->update([
        'status' => 'held',
        'held_by_user_id' => $this->otherCustomer->id,
        'held_until' => now()->addMinutes(10),
    ]);

    $newBooking = Booking::create([
        'user_id' => $this->otherCustomer->id,
        'booking_code' => 'TBX-NEWBUYER11',
        'total_price' => 500000,
        'status' => 'pending',
    ]);
    BookingItem::create(['booking_id' => $newBooking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);

    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $newBooking), [
        'status' => 'confirmed',
    ]);

    expect($newBooking->fresh()->status)->toBe('confirmed');
    expect($this->stSeat1->fresh()->status)->toBe('booked');
});

test('TC_UC007_012: re-confirming a cancelled booking is rejected even when seats are still available', function () {
    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-CANCEL12',
        'total_price' => 500000,
        'status' => 'confirmed',
    ]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);

    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled']);
    expect($booking->fresh()->status)->toBe('cancelled');
    expect($this->stSeat1->fresh()->status)->toBe('available');

    // Attempting to re-confirm cancelled booking
    $response = $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), [
        'status' => 'confirmed',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($booking->fresh()->status)->toBe('cancelled');
});

test('TC_UC007_013: re-confirming a cancelled booking is blocked when seat is already booked by another user', function () {
    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-CANCEL13',
        'total_price' => 500000,
        'status' => 'confirmed',
    ]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);

    // Cancel and sell seat to other customer
    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled']);

    $resaleBooking = Booking::create([
        'user_id' => $this->otherCustomer->id,
        'booking_code' => 'TBX-RESOLD13',
        'total_price' => 500000,
        'status' => 'confirmed',
    ]);
    BookingItem::create(['booking_id' => $resaleBooking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);
    $this->stSeat1->refresh();
    $this->stSeat1->update(['status' => 'booked']);

    // Attempt re-confirm
    $response = $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), [
        'status' => 'confirmed',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($booking->fresh()->status)->toBe('cancelled');
    expect($this->stSeat1->fresh()->status)->toBe('booked');
});

test('TC_UC007_014: confirming a booking is blocked when seat is temporarily held by another customer', function () {
    $this->stSeat1->update([
        'status' => 'held',
        'held_by_user_id' => $this->otherCustomer->id,
        'held_until' => now()->addMinutes(8),
    ]);

    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-CONFLICT14',
        'total_price' => 500000,
        'status' => 'pending',
    ]);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 500000]);

    $response = $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $booking), [
        'status' => 'confirmed',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($booking->fresh()->status)->toBe('pending');
    expect($this->stSeat1->fresh()->held_by_user_id)->toBe($this->otherCustomer->id);
});

test('TC_UC007_015: guest accessing admin booking routes is redirected to login', function () {
    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-GUEST15',
        'total_price' => 500000,
        'status' => 'confirmed',
    ]);

    $this->get(route('admin.bookings.index'))
        ->assertRedirect('/login');

    $this->get(route('admin.bookings.show', $booking))
        ->assertRedirect('/login');

    $this->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled'])
        ->assertRedirect('/login');
});

test('TC_UC007_016: regular user accessing admin booking routes is forbidden 403', function () {
    $booking = Booking::create([
        'user_id' => $this->customer->id,
        'booking_code' => 'TBX-FORBID16',
        'total_price' => 500000,
        'status' => 'confirmed',
    ]);

    $this->actingAs($this->customer)->get(route('admin.bookings.index'))
        ->assertStatus(403);

    $this->actingAs($this->customer)->get(route('admin.bookings.show', $booking))
        ->assertStatus(403);

    $this->actingAs($this->customer)->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled'])
        ->assertStatus(403);
});
