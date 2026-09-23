<?php

use App\Models\Event;

beforeEach(fn () => $this->seed());

test('events and movies index page renders successfully', function () {
    $response = $this->get(route('events.index'));
    $response->assertStatus(200);
    $response->assertSee('Khám Phá');

    $response2 = $this->get(route('movies.index'));
    $response2->assertStatus(200);
    $response2->assertSee('Khám Phá');
});

test('event details page displays real-world event business information for anh trai vuot ngan chong gai concert', function () {
    $movie = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026');

    $response = $this->get(route('movies.show', $movie->slug));

    $response->assertStatus(200);
    $response->assertSee($movie->title);
    $response->assertSee('Sân Vận Động Quân Khu 7');
    $response->assertSee('Số 202 Hoàng Văn Thụ');
    $response->assertSee('Phân luồng cổng vào');
    $response->assertSee('Bãi Đỗ Xe Máy & Ô Tô');
    $response->assertSee('Lịch Trình Chi Tiết Đêm Diễn');
    $response->assertSee('Check-in & Đổi Vòng Tay');
    $response->assertSee('Soundcheck Rehearsal');
    $response->assertSee('Dàn 33 Anh Tài & Khách Mời Đặc Biệt');
    $response->assertSee('Soobin Hoàng Sơn');
    $response->assertSee('Bằng Kiều');
    $response->assertSee('NSND Tự Long');
    $response->assertSee('Hồ Ngọc Hà');
    $response->assertSee('MC Anh Tuấn');
    $response->assertSee('Đơn Vị Tổ Chức & Tài Trợ');
    $response->assertSee('TicketBox');
    $response->assertSee('Techcombank');
    $response->assertSee('Quy Định Vào Cổng & An Ninh Sự Kiện');
    $response->assertSee('Vật dụng NGHIÊM CẤM');
});

test('fan meeting details page displays specific cast members and fansign hosts', function () {
    $movie = Event::firstWhere('slug', 'fan-meeting-running-man-vn-2026');

    $response = $this->get(route('movies.show', $movie->slug));

    $response->assertStatus(200);
    $response->assertSee($movie->title);
    $response->assertSee('Dàn Cast Running Man Vietnam');
    $response->assertSee('Ninh Dương Lan Ngọc');
    $response->assertSee('Ngô Kiến Huy');
    $response->assertSee('Jun Phạm');
    $response->assertSee('MC Quang Bảo');
});

test('seat map page displays concert stadium arena blueprint for seated concerts', function () {
    $movie = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026');
    $showtime = $movie->showtimes()->orderBy('start_time')->first();

    $response = $this->get(route('showtimes.seats', $showtime->id));

    $response->assertStatus(200);
    $response->assertSee($movie->title);
    $response->assertSee($showtime->room->name);
    $response->assertSee('SÂN KHẤU CHÍNH');
    $response->assertSee('CATWALK RUNWAY');
    $response->assertSee('GA STANDING');
    $response->assertSee('SVIP B-Stage Floor');
    $response->assertSee('Khán Đài A');
    $response->assertSee('Skybox VIP Suites');
    $response->assertSee('Tổng Tạm Tính');
});

test('ticket selection page renders direct tier selector for non-concert events', function () {
    $movie = Event::firstWhere('slug', 'vietnam-tech-summit-ai-expo-2026');
    $showtime = $movie->showtimes()->orderBy('start_time')->first();

    $response = $this->get(route('showtimes.seats', $showtime->id));

    $response->assertStatus(200);
    $response->assertSee($movie->title);
    $response->assertSee('Chọn Hạng Vé & Số Lượng Tham Dự');
    $response->assertSee('Vé VIP Toàn Diện');
    $response->assertSee('Vé Tiêu Chuẩn');
    $response->assertSee('Combo Nhóm 2 Người');
    $response->assertDontSee('SÂN KHẤU CHÍNH (MAIN STAGE)');
});

test('unauthenticated guest is redirected to login when accessing cart', function () {
    $response = $this->get(route('cart.index'));

    $response->assertRedirect(route('login'));
});

test('cart page renders checkout flow with multi-method payment and e-ticket pass for authenticated user', function () {
    $user = \App\Models\User::factory()->create();
    $showtime = Event::firstWhere('slug', 'hoa-nhac-giao-huong-saigon-philharmonic')->showtimes()->orderBy('start_time')->first();
    app(\App\Services\SeatHoldService::class)->hold($user, $showtime, [$showtime->showtimeSeats()->where('status', 'available')->value('id')]);

    $response = $this->actingAs($user)->get(route('cart.index'));

    $response->assertStatus(200);
    $response->assertSee('Giỏ Vé');
    $response->assertSee('Thông Tin Người Đặt & Nhận Vé');
    $response->assertSee('Chọn Phương Thức Thanh Toán');
    $response->assertSee('Chuyển Khoản VietQR 24/7');
    $response->assertSee('Ví Điện Tử MoMo');
    $response->assertSee('Ví Điện Tử ZaloPay');
    $response->assertSee('Thẻ Quốc Tế');
    $response->assertSee('Techcombank');
    $response->assertSee('1903 8888 6688');
    $response->assertSee('CONG TY CP TICKETBOX VIETNAM');
    $response->assertSee('Vé Điện Tử E-Ticket Đã Được Kích Hoạt');
    $response->assertSee('Lưu vé về máy (PNG)');
    $response->assertDontSee('giả lập');
});

test('booking history page displays e-ticket passes and png download button for authenticated user', function () {
    $user = \App\Models\User::firstWhere('email', 'user@ticketbox.vn');

    $response = $this->actingAs($user)->get(route('bookings.history'));

    $response->assertStatus(200);
    $response->assertSee('Lịch Sử Vé Đã Đặt');
    $response->assertSee('TBX-89214');
    $response->assertSee('Lưu vé về máy (PNG)');
});

test('non-existent movie slug returns 404', function () {
    $response = $this->get('/movies/non-existent-event-slug-9999');

    $response->assertStatus(404);
});

test('non-existent showtime id returns 404', function () {
    $response = $this->get('/showtimes/999999/seats');

    $response->assertStatus(404);
});
