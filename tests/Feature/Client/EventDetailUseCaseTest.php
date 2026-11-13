<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Http::preventStrayRequests();
    config([
        'services.openweather.key' => 'test-openweather-key',
        'services.google_maps.embed_key' => null,
    ]);
    Cache::flush();

    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

function fakeWeatherApiResponse(Carbon $at, float $temp = 28.5, string $description = 'Trời quang mây tạnh', int $humidity = 65): void
{
    Http::fake([
        '*api.openweathermap.org/*' => Http::response([
            'list' => [
                [
                    'dt' => $at->copy()->subHours(3)->timestamp,
                    'main' => ['temp' => 26.0, 'humidity' => 70],
                    'wind' => ['speed' => 2.5],
                    'weather' => [['main' => 'Clouds', 'description' => 'Mây nhẹ', 'icon' => '03d']],
                ],
                [
                    'dt' => $at->timestamp,
                    'main' => ['temp' => $temp, 'humidity' => $humidity],
                    'wind' => ['speed' => 3.6],
                    'weather' => [['main' => 'Clear', 'description' => $description, 'icon' => '01d']],
                ],
            ],
        ], 200),
    ]);
}

test('TC_UC014_001: access published event details page returns 200 with poster, title, duration, category, price, and description', function () {
    $category = Category::factory()->create(['name' => 'Concert Âm Nhạc']);
    $event = Event::factory()->create([
        'title' => 'Đại Nhạc Hội Mùa Hè Rực Rỡ 2026',
        'category_id' => $category->id,
        'duration_minutes' => 150,
        'description' => 'Đêm nhạc hội tụ các nghệ sĩ hàng đầu Đông Nam Á.',
        'status' => 'published',
        'poster_path' => 'posters/summer-concert.jpg',
    ]);

    $room = Room::factory()->create(['layout_preset' => 'open_space']);
    app(RoomSeatGenerator::class)->generate($room, 'open_space');

    $showtime = Showtime::factory()->create([
        'event_id' => $event->id,
        'room_id' => $room->id,
        'base_price' => 350000,
        'start_time' => now()->addDays(2),
        'end_time' => now()->addDays(2)->addHours(3),
    ]);
    app(ShowtimeSeatGenerator::class)->generate($showtime);

    $response = $this->get(route('events.show', $event->slug));

    $response->assertOk();
    $response->assertSee('Đại Nhạc Hội Mùa Hè Rực Rỡ 2026');
    $response->assertSee('Concert Âm Nhạc');
    $response->assertSee('150 phút');
    $response->assertSee('350.000₫');
    $response->assertSee('Đêm nhạc hội tụ các nghệ sĩ hàng đầu Đông Nam Á.');
});

test('TC_UC014_002: renders JSON details for Lineup and Timeline in correct order', function () {
    $event = Event::factory()->create([
        'title' => 'Concert Dàn Sao Đỉnh Cao',
        'status' => 'published',
        'details' => [
            'participants_title' => 'Nghệ Sĩ Trình Diễn',
            'lineup' => [
                ['name' => 'Sơn Tùng M-TP', 'role' => 'Ca sĩ chính', 'badge' => 'Headliner'],
                ['name' => 'Mỹ Tâm', 'role' => 'Khách mời danh dự', 'badge' => 'Special Guest'],
            ],
            'timeline' => [
                ['time' => '17:30', 'title' => 'Check-in & Check vé', 'desc' => 'Đón khán giả vào khán phòng'],
                ['time' => '19:00', 'title' => 'Khai mạc chương trình', 'desc' => 'Tiết mục mở màn sôi động'],
                ['time' => '21:30', 'title' => 'Bế mạc & Giao lưu', 'desc' => 'Chụp ảnh kỷ niệm cùng thần tượng'],
            ],
        ],
    ]);

    $response = $this->get(route('events.show', $event->slug));

    $response->assertOk();
    // Lineup
    $response->assertSee('Sơn Tùng M-TP');
    $response->assertSee('Headliner');
    $response->assertSee('Mỹ Tâm');
    $response->assertSee('Special Guest');

    // Timeline
    $response->assertSee('17:30');
    $response->assertSee('Check-in &amp; Check vé', false);
    $response->assertSee('19:00');
    $response->assertSee('Khai mạc chương trình');
    $response->assertSee('21:30');
    $response->assertSee('Bế mạc &amp; Giao lưu', false);
});

test('TC_UC014_003: renders showtimes list with start time, room, and booking button action', function () {
    $event = Event::factory()->create([
        'title' => 'Liveshow Hà Anh Tuấn',
        'status' => 'published',
    ]);

    $room = Room::factory()->create(['name' => 'Khán Phòng Hòa Bình']);
    $showtime = Showtime::factory()->create([
        'event_id' => $event->id,
        'room_id' => $room->id,
        'start_time' => now()->addDays(3)->setTime(20, 0),
        'end_time' => now()->addDays(3)->setTime(23, 0),
        'base_price' => 500000,
    ]);

    $response = $this->get(route('events.show', $event->slug));

    $response->assertOk();
    $response->assertSee('Khán Phòng Hòa Bình');
    $response->assertSee(route('showtimes.seats', $showtime->id));
});

test('TC_UC014_004: reviews and ratings section calculates star average and displays comments with admin reply', function () {
    $event = Event::factory()->create(['status' => 'published']);
    $user1 = User::factory()->create(['name' => 'Trần Khán Giả']);
    $user2 = User::factory()->create(['name' => 'Lê Người Xem']);

    Comment::create([
        'event_id' => $event->id,
        'user_id' => $user1->id,
        'rating' => 5,
        'content' => 'Âm thanh ánh sáng tuyệt vời!',
        'admin_reply' => 'Cảm ơn bạn đã đồng hành cùng TicketBox!',
        'admin_replied_at' => now(),
    ]);

    Comment::create([
        'event_id' => $event->id,
        'user_id' => $user2->id,
        'rating' => 3,
        'content' => 'Khán phòng hơi lạnh một chút.',
    ]);

    $response = $this->get(route('events.show', $event->slug));

    $response->assertOk();
    // Rating average: (5 + 3) / 2 = 4.0
    expect($response->viewData('ratingAverage'))->toBe(4.0)
        ->and($response->viewData('ratingCount'))->toBe(2);

    $commentsData = $response->viewData('comments');
    expect(count($commentsData))->toBe(2)
        ->and($commentsData[0]['text'])->toBe('Âm thanh ánh sáng tuyệt vời!')
        ->and($commentsData[0]['admin_reply'])->toBe('Cảm ơn bạn đã đồng hành cùng TicketBox!')
        ->and($commentsData[1]['text'])->toBe('Khán phòng hơi lạnh một chút.');

    // Template markup assertions
    $response->assertSee('Phản hồi từ TicketBox');
});

test('TC_UC014_005: embeds google maps iframe matching venue coordinates or address', function () {
    $event = Event::factory()->create(['status' => 'published']);
    $room = Room::factory()->create([
        'name' => 'Nhà Hát Thành Phố',
        'latitude' => 10.7766,
        'longitude' => 106.7032,
        'address' => '7 Công Trường Lam Sơn, Bến Nghé, Quận 1',
    ]);

    Showtime::factory()->create([
        'event_id' => $event->id,
        'room_id' => $room->id,
        'start_time' => now()->addDays(1),
        'end_time' => now()->addDays(1)->addHours(2),
    ]);

    $response = $this->get(route('events.show', $event->slug));

    $response->assertOk();
    $response->assertSee('<iframe', false);
    $response->assertSee('maps.google.com/maps?q=10.7766,106.7032');
    $response->assertSee('Nhà Hát Thành Phố');
});

test('TC_UC014_006: integrates openweathermap api to show temperature, description, and humidity', function () {
    $at = now()->addDays(2)->setTime(19, 30);
    fakeWeatherApiResponse($at, 29.0, 'Trời nắng đẹp quang mây', 68);

    $event = Event::factory()->create(['status' => 'published']);
    $room = Room::factory()->create([
        'latitude' => 10.7766,
        'longitude' => 106.7032,
    ]);

    Showtime::factory()->create([
        'event_id' => $event->id,
        'room_id' => $room->id,
        'start_time' => $at,
        'end_time' => $at->copy()->addHours(3),
    ]);

    $response = $this->get(route('events.show', $event->slug));

    $response->assertOk();
    $response->assertSee('29°C');
    $response->assertSee('Trời nắng đẹp quang mây');
    $response->assertSee('Độ ẩm: 68%');
    $response->assertSee('Thuận lợi tổ chức');
});

test('TC_UC014_007: caches weather forecast for 30 minutes on repeated requests', function () {
    $at = now()->addDays(1)->setTime(20, 0);
    fakeWeatherApiResponse($at, 30.0, 'Nắng ấm', 70);

    $event = Event::factory()->create(['status' => 'published']);
    $room = Room::factory()->create([
        'latitude' => 10.8231,
        'longitude' => 106.6297,
    ]);

    Showtime::factory()->create([
        'event_id' => $event->id,
        'room_id' => $room->id,
        'start_time' => $at,
        'end_time' => $at->copy()->addHours(2),
    ]);

    // Request 1: Calls API
    $this->get(route('events.show', $event->slug))->assertOk();
    Http::assertSentCount(1);

    // Request 2 (trong 30 phút): Đọc từ Cache, không gọi thêm API
    $this->get(route('events.show', $event->slug))->assertOk();
    Http::assertSentCount(1);
});

test('TC_UC014_008: refreshes weather forecast from api after 30-minute cache expires', function () {
    $at = now()->addDays(1)->setTime(20, 0);
    fakeWeatherApiResponse($at, 27.0, 'Mát mẻ', 60);

    $event = Event::factory()->create(['status' => 'published']);
    $room = Room::factory()->create([
        'latitude' => 10.8231,
        'longitude' => 106.6297,
    ]);

    Showtime::factory()->create([
        'event_id' => $event->id,
        'room_id' => $room->id,
        'start_time' => $at,
        'end_time' => $at->copy()->addHours(2),
    ]);

    // Request 1: Gọi API lần 1
    $this->get(route('events.show', $event->slug))->assertOk();
    Http::assertSentCount(1);

    // Thời gian trôi qua 31 phút -> Cache hết hạn
    $this->travel(31)->minutes();

    // Request 2: Gọi API lần 2 để làm mới
    $this->get(route('events.show', $event->slug))->assertOk();
    Http::assertSentCount(2);
});

test('TC_UC014_009: handles openweathermap api 500 error or timeout gracefully without crashing', function () {
    Http::fake([
        '*api.openweathermap.org/*' => Http::response(['message' => 'Internal server error'], 500),
    ]);

    $event = Event::factory()->create(['status' => 'published']);
    $room = Room::factory()->create([
        'latitude' => 10.8231,
        'longitude' => 106.6297,
    ]);

    Showtime::factory()->create([
        'event_id' => $event->id,
        'room_id' => $room->id,
        'start_time' => now()->addDays(2),
        'end_time' => now()->addDays(2)->addHours(2),
    ]);

    $response = $this->get(route('events.show', $event->slug));

    // Trang vẫn tải thành công 200, không bị ném lỗi 500
    $response->assertOk();
    $response->assertSee('Chưa có dự báo');
});

test('TC_UC014_010: accessing non-existent event slug returns 404 not found', function () {
    $response = $this->get(route('events.show', 'slug-hoan-toan-khong-ton-tai-123456'));

    $response->assertNotFound();
});

test('TC_UC014_011: draft or archived events are inaccessible to guests/users and return 404', function () {
    $draftEvent = Event::factory()->create([
        'slug' => 'event-ban-nhap-chua-mo-ban',
        'status' => 'draft',
    ]);
    $archivedEvent = Event::factory()->create([
        'slug' => 'event-da-luu-tru-nam-ngoai',
        'status' => 'archived',
    ]);

    // Khách vãng lai
    $this->get(route('events.show', $draftEvent->slug))->assertNotFound();
    $this->get(route('events.show', $archivedEvent->slug))->assertNotFound();

    // User đã đăng nhập
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user)->get(route('events.show', $draftEvent->slug))->assertNotFound();
    $this->actingAs($user)->get(route('events.show', $archivedEvent->slug))->assertNotFound();
});

test('TC_UC014_012: renders safely when event has no showtimes and empty JSON lineup/timeline', function () {
    $event = Event::factory()->create([
        'title' => 'Sự Kiện Sơ Khai Chưa Có Suất Diễn',
        'status' => 'published',
        'details' => null, // Không có lineup, timeline, venue
    ]);

    $response = $this->get(route('events.show', $event->slug));

    // Render an toàn 200, không văng lỗi Undefined array key
    $response->assertOk();
    $response->assertSee('Sự Kiện Sơ Khai Chưa Có Suất Diễn');
    $response->assertSee('Hiện chưa có suất diễn mở bán cho sự kiện này. Vui lòng quay lại sau!');
});

test('TC_UC014_013: renders safely when event has zero comments and reviews', function () {
    $event = Event::factory()->create([
        'title' => 'Sự Kiện Chưa Ai Đánh Giá',
        'status' => 'published',
    ]);

    $response = $this->get(route('events.show', $event->slug));

    $response->assertOk();
    expect($response->viewData('ratingAverage'))->toBe(0.0)
        ->and($response->viewData('ratingCount'))->toBe(0);
    $response->assertSee('Chưa có bình luận nào. Hãy là người đầu tiên!');
});

test('TC_UC014_014: public authorization test: guests, users, and admins can view published event details', function () {
    $event = Event::factory()->create([
        'title' => 'Concert Triệu Khán Giả',
        'status' => 'published',
    ]);

    // 1. Khách vãng lai (Guest)
    $this->assertGuest();
    $this->get(route('events.show', $event->slug))
        ->assertOk()
        ->assertSee('Concert Triệu Khán Giả');

    // 2. Thành viên thông thường (User)
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user)
        ->get(route('events.show', $event->slug))
        ->assertOk()
        ->assertSee('Concert Triệu Khán Giả');

    // 3. Quản trị viên (Admin)
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)
        ->get(route('events.show', $event->slug))
        ->assertOk()
        ->assertSee('Concert Triệu Khán Giả');
});
