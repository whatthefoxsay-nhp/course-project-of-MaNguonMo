<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

    $this->category = Category::create([
        'name' => 'Âm nhạc',
        'slug' => 'am-nhac',
        'icon' => 'music',
    ]);

    $this->room = Room::create([
        'name' => 'Sân Vận Động Quân Khu 7',
        'rows' => 10,
        'cols' => 10,
        'capacity' => 100,
        'has_standing_zone' => true,
    ]);

    $this->event = Event::create([
        'category_id' => $this->category->id,
        'title' => 'Live Concert Anh Trai Vượt Ngàn Chông Gai 2026',
        'slug' => 'live-concert-anh-trai-vuot-ngan-chong-gai-2026',
        'description' => 'Đại nhạc hội bùng nổ cảm xúc với các ca khúc bất hủ.',
        'duration_minutes' => 180,
        'min_price' => 500000,
        'poster_url' => 'https://example.com/poster-chong-gai.jpg',
        'status' => 'published',
    ]);

    $this->showtime = Showtime::create([
        'event_id' => $this->event->id,
        'room_id' => $this->room->id,
        'start_time' => now()->addDays(7)->setTime(19, 30),
        'end_time' => now()->addDays(7)->setTime(22, 30),
        'base_price' => 500000,
    ]);
});

test('TC_UC023_001: Kiểm tra render thành phần Chatbot trên giao diện toàn cục (/, /events, /events/{slug})', function () {
    // 1. Trang chủ (/)
    $resHome = $this->get(route('home'));
    $resHome->assertStatus(200);
    $resHome->assertSee('ai-chatbot-widget');
    $resHome->assertSee('data-component="ai-chatbot"', false);
    $resHome->assertSee('TicketBot AI');
    $resHome->assertSee('Mở trợ lý ảo AI');

    // 2. Danh sách sự kiện (/events)
    $resEvents = $this->get(route('events.index'));
    $resEvents->assertStatus(200);
    $resEvents->assertSee('ai-chatbot-widget');
    $resEvents->assertSee('data-component="ai-chatbot"', false);
    $resEvents->assertSee('TicketBot AI');

    // 3. Chi tiết sự kiện (/events/{slug})
    $resDetail = $this->get(route('events.show', $this->event->slug));
    $resDetail->assertStatus(200);
    $resDetail->assertSee('ai-chatbot-widget');
    $resDetail->assertSee('data-component="ai-chatbot"', false);
    $resDetail->assertSee('TicketBot AI');
});

test('TC_UC023_002: Kiểm tra hiển thị bóng chào mừng tự động sau 1.8 giây', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    // Xác nhận có hàm initBot với thời gian trễ 1800ms
    $response->assertSee('initBot()');
    $response->assertSee('1800');
    $response->assertSee('showGreeting');
    $response->assertSee('Xin chào! Tôi có thể giúp bạn tìm vé concert hot');
});

test('TC_UC023_003: Kiểm tra thao tác mở và đóng hộp thoại Chatbot và hiển thị 6 quick chips', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    // Kiểm tra các methods đóng mở
    $response->assertSee('toggleChat()');
    $response->assertSee('openChat()');
    $response->assertSee('isOpen = false');

    // Kiểm tra danh sách 6 Quick Chips
    $response->assertSee('Concert hot nhất');
    $response->assertSee('Cách giữ chỗ & đặt vé', false);
    $response->assertSee('Thanh toán VietQR & MoMo', false);
    $response->assertSee('Cách tải vé E-Ticket');
    $response->assertSee('Sơ đồ SVĐ Quân Khu 7');
    $response->assertSee('Chính sách đổi trả');

    // Khung input và nút gửi
    $response->assertSee('placeholder="Hỏi về sự kiện, giá vé, thanh toán..."', false);
    $response->assertSee('sendMessage()');
});

test('TC_UC023_004: Click nút gợi ý Concert hot nhất tuần này phản hồi chính xác sự kiện published', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    // Phải nhúng thông tin sự kiện published vào script chatbot
    $response->assertSee('Live Concert Anh Trai Vượt Ngàn Chông Gai 2026');
    $response->assertSee(route('events.show', $this->event->slug));
    $response->assertSee('Sân Vận Động Quân Khu 7');
    $response->assertSee('500.000');
    $response->assertSee('Concert hot nhất đang mở bán');
});

test('TC_UC023_005: Kiểm tra dữ liệu thẻ sự kiện khi hệ thống chưa có sự kiện published nào', function () {
    // Xóa toàn bộ showtimes và chuyển event sang draft
    $this->event->update(['status' => 'draft']);

    $response = $this->get(route('home'));
    $response->assertStatus(200);

    // Chatbot xử lý an toàn với fallback null
    $response->assertSee('ticketBotAssistant(null)', false);
    $response->assertSee('Hiện tại hệ thống chưa có sự kiện mở bán nào mới');
});

test('TC_UC023_006: Click nút gợi ý Quy trình giữ chỗ 10 phút nêu rõ cơ chế khóa ghế và nhả ghế khi hết hạn', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    $response->assertSee('Quy trình đặt vé và giữ chỗ 3 bước siêu tốc');
    $response->assertSee('tự động khóa ghế và giữ chỗ an toàn trong 10 phút');
    $response->assertSee('tự động nhả ghế');
});

test('TC_UC023_007: Click nút gợi ý Hướng dẫn thanh toán VietQR & MoMo liệt kê đủ 4 phương thức', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    $response->assertSee('Phương thức thanh toán được hỗ trợ');
    $response->assertSee('Chuyển khoản VietQR');
    $response->assertSee('Ví điện tử MoMo');
    $response->assertSee('Ví điện tử ZaloPay');
    $response->assertSee('Thẻ ngân hàng');
});

test('TC_UC023_008: Click nút gợi ý Làm sao để tải vé điện tử E-Ticket QR Code hướng dẫn /bookings/history', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    $response->assertSee('Hướng dẫn xem mã QR và tải vé điện tử');
    $response->assertSee('/bookings/history');
    $response->assertSee('mã QR Code bảo mật');
    $response->assertSee('Tải file PDF vé điện tử');
});

test('TC_UC023_009: Click nút gợi ý Sơ đồ SVĐ Quân Khu 7 và Chính sách hoàn hủy đổi vé', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    // Sơ đồ SVĐ Quân Khu 7
    $response->assertSee('Phân khu Sân Vận Động Quân Khu 7');
    $response->assertSee('SVIP B-Stage Floor');
    $response->assertSee('GA Standing (Fanzone)');
    $response->assertSee('Khán Đài A & B', false);

    // Chính sách đổi trả
    $response->assertSee('Chính sách hoàn hủy & đổi vé', false);
    $response->assertSee('không áp dụng hoàn tiền');
    $response->assertSee('chuyển nhượng vé cho bạn bè/người thân');
});

test('TC_UC023_010: Nhập câu hỏi tự do chứa từ khóa nghiệp vụ hợp lệ (giá vé, thanh toán, mã giảm giá, đặt vé, qr code)', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    // Nhận diện giá vé
    $response->assertSee('q.includes(\'giá vé\')', false);
    $response->assertSee('Thông tin giá vé');

    // Nhận diện mã giảm giá
    $response->assertSee('q.includes(\'mã giảm giá\')', false);
    $response->assertSee('Mã giảm giá & Ưu đãi', false);

    // Nhận diện đặt vé & thanh toán & qr code
    $response->assertSee('q.includes(\'đặt vé\')', false);
    $response->assertSee('q.includes(\'thanh toán\')', false);
    $response->assertSee('q.includes(\'qr\')', false);
});

test('TC_UC023_011: Phân tích từ khóa không phân biệt chữ hoa/thường và dấu câu (.toLowerCase().trim())', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    $response->assertSee('.toLowerCase().trim()', false);
});

test('TC_UC023_012: Nhập câu hỏi ngoài phạm vi nghiệp vụ trả về câu phản hồi mặc định thân thiện (Fallback)', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    $response->assertSee('Cảm ơn bạn đã gửi câu hỏi:');
    $response->assertSee('Tôi có thể hỗ trợ bạn tìm kiếm sự kiện âm nhạc, giải đáp sơ đồ sân khấu');
});

test('TC_UC023_013: Gửi tin nhắn rỗng hoặc chỉ chứa khoảng trắng bị chặn gửi an toàn', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    $response->assertSee('const query = (this.userInput || \'\').trim()', false);
    $response->assertSee('if (!query) return', false);
    $response->assertSee(':disabled="!userInput.trim() || isTyping"', false);
});

test('TC_UC023_014: Kiểm tra chống lỗi XSS khi người dùng nhập chuỗi mã độc trong khung chat', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    // Tin nhắn người dùng dùng x-text để auto escape
    $response->assertSee('<span x-text="msg.text"></span>', false);

    // Phản hồi fallback escape ký tự đặc biệt
    $response->assertSee('.replace(/</g, \'&lt;\')', false);
    $response->assertSee('.replace(/>/g, \'&gt;\')', false);
});

test('TC_UC023_015: Nhập chuỗi văn bản cực dài giao diện bong bóng chat tự động ngắt dòng (break-words)', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);

    $response->assertSee('break-words');
});

test('TC_UC023_016: Quyền truy cập không yêu cầu đăng nhập (Guest và Authenticated User đều dùng được)', function () {
    // 1. Khách vãng lai (Guest)
    $guestResponse = $this->get(route('home'));
    $guestResponse->assertStatus(200);
    $guestResponse->assertSee('data-component="ai-chatbot"', false);
    $guestResponse->assertSee('ticketBotAssistant');

    // 2. Thành viên đăng nhập role user
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('user');
    $userResponse = $this->actingAs($user)->get(route('home'));
    $userResponse->assertStatus(200);
    $userResponse->assertSee('data-component="ai-chatbot"', false);

    // 3. Admin đăng nhập
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('admin');
    $adminResponse = $this->actingAs($admin)->get(route('home'));
    $adminResponse->assertStatus(200);
    $adminResponse->assertSee('data-component="ai-chatbot"', false);
});
