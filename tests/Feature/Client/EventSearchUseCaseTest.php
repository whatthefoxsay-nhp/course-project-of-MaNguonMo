<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

test('TC_UC013_001: get /search/suggestions with valid keyword returns 200 with poster, date, price, url', function () {
    $category = Category::factory()->create(['name' => 'Âm Nhạc']);
    $event = Event::factory()->create([
        'title' => 'Concert Mùa Thu Hà Nội',
        'category_id' => $category->id,
        'status' => 'published',
        'release_date' => '2026-11-20',
        'poster_path' => 'posters/concert-autumn.jpg',
    ]);

    $room = Room::factory()->create();
    Showtime::factory()->create([
        'event_id' => $event->id,
        'room_id' => $room->id,
        'base_price' => 250000,
        'start_time' => now()->addDays(7),
        'end_time' => now()->addDays(7)->addHours(3),
    ]);

    $response = $this->getJson(route('search.suggestions', ['q' => 'Mùa Thu']));

    $response->assertOk();
    $response->assertJsonStructure([
        'results' => [
            '*' => [
                'title',
                'url',
                'category',
                'poster_url',
                'date',
                'price_from',
            ],
        ],
    ]);

    $results = $response->json('results');
    expect($results)->not->toBeEmpty();
    expect($results[0]['title'])->toBe('Concert Mùa Thu Hà Nội')
        ->and($results[0]['url'])->toContain(route('events.show', $event->slug))
        ->and($results[0]['category'])->toBe('Âm Nhạc')
        ->and($results[0]['price_from'])->toContain('250.000₫')
        ->and($results[0]['poster_url'])->not->toBeEmpty()
        ->and($results[0]['date'])->toBe('20/11/2026');
});

test('TC_UC013_002: live search suggestions are limited to at most 6 items', function () {
    Event::factory()->count(10)->create([
        'title' => 'Liveshow Rock Việt Nam Special',
        'status' => 'published',
    ]);

    $response = $this->getJson(route('search.suggestions', ['q' => 'Liveshow']));

    $response->assertOk();
    $results = $response->json('results');
    expect(count($results))->toBeLessThanOrEqual(6)
        ->and(count($results))->toBe(6);
});

test('TC_UC013_003: draft and archived events never appear in live search suggestions', function () {
    Event::factory()->create([
        'title' => 'Sự Kiện Nháp Bí Mật',
        'status' => 'draft',
    ]);
    Event::factory()->create([
        'title' => 'Sự Kiện Đã Lưu Trữ Quá Khứ',
        'status' => 'archived',
    ]);
    Event::factory()->create([
        'title' => 'Sự Kiện Đang Mở Bán Công Khai',
        'status' => 'published',
    ]);

    $resDraft = $this->getJson(route('search.suggestions', ['q' => 'Bí Mật']));
    $resDraft->assertOk()->assertJsonCount(0, 'results');

    $resArchived = $this->getJson(route('search.suggestions', ['q' => 'Lưu Trữ']));
    $resArchived->assertOk()->assertJsonCount(0, 'results');

    $resPublished = $this->getJson(route('search.suggestions', ['q' => 'Mở Bán']));
    $resPublished->assertOk()->assertJsonCount(1, 'results');
    expect($resPublished->json('results.0.title'))->toBe('Sự Kiện Đang Mở Bán Công Khai');
});

test('TC_UC013_004: empty or whitespace-only search query returns empty array safely', function () {
    $resEmpty = $this->getJson(route('search.suggestions', ['q' => '']));
    $resEmpty->assertOk()->assertJson(['results' => []]);

    $resSpaces = $this->getJson(route('search.suggestions', ['q' => '    ']));
    $resSpaces->assertOk()->assertJson(['results' => []]);
});

test('TC_UC013_005: search query matching no events returns empty suggestion list', function () {
    Event::factory()->create([
        'title' => 'Đại Nhạc Hội Mùa Đông',
        'status' => 'published',
    ]);

    $response = $this->getJson(route('search.suggestions', ['q' => 'KhôngTồnTại12345']));

    $response->assertOk();
    $response->assertJson(['results' => []]);
    expect($response->json('results'))->toBeEmpty();
});

test('TC_UC013_006: accessing /events displays only published events list', function () {
    $publishedEvent = Event::factory()->create([
        'title' => 'Lễ Hội Ánh Sáng Quốc Tế',
        'status' => 'published',
    ]);

    $response = $this->get(route('events.index'));

    $response->assertOk();
    $response->assertSee('Khám Phá Sự Kiện &amp; Show Diễn', false);
    $response->assertSee('Lễ Hội Ánh Sáng Quốc Tế');
});

test('TC_UC013_007: event catalog paginates 9 events per page by default', function () {
    Event::factory()->count(12)->create([
        'title' => 'Concert Series Đỉnh Cao',
        'status' => 'published',
    ]);

    // Trang 1: 9 bản ghi
    $responsePage1 = $this->get(route('events.index'));
    $responsePage1->assertOk();
    expect($responsePage1->viewData('events')->count())->toBe(9);
    expect($responsePage1->viewData('events')->total())->toBe(12);

    // Trang 2: 3 bản ghi còn lại
    $responsePage2 = $this->get(route('events.index', ['page' => 2]));
    $responsePage2->assertOk();
    expect($responsePage2->viewData('events')->count())->toBe(3);
});

test('TC_UC013_008: public catalog on /events strictly excludes draft and archived events', function () {
    $draftEvent = Event::factory()->create([
        'title' => 'Concert Nội Bộ Chưa Mở',
        'status' => 'draft',
    ]);
    $archivedEvent = Event::factory()->create([
        'title' => 'Hội Thảo Cũ Năm Ngoái',
        'status' => 'archived',
    ]);
    $publishedEvent = Event::factory()->create([
        'title' => 'Gala Âm Nhạc Trực Tiếp 2026',
        'status' => 'published',
    ]);

    $response = $this->get(route('events.index'));

    $response->assertOk();
    $response->assertSee('Gala Âm Nhạc Trực Tiếp 2026');
    $response->assertDontSee('Concert Nội Bộ Chưa Mở');
    $response->assertDontSee('Hội Thảo Cũ Năm Ngoái');
});

test('TC_UC013_009: search events by name matches case-insensitively', function () {
    Event::factory()->create([
        'title' => 'Festival Văn Hóa Dân Gian',
        'status' => 'published',
    ]);
    Event::factory()->create([
        'title' => 'Đua Xe Công Thức 1',
        'status' => 'published',
    ]);

    // Tìm kiếm chữ thường
    $response = $this->get(route('events.index', ['q' => 'Festival']));
    $response->assertOk();
    expect($response->viewData('events')->pluck('title'))
        ->toContain('Festival Văn Hóa Dân Gian')
        ->not->toContain('Đua Xe Công Thức 1');

    // Tìm kiếm với case khác
    $responseLower = $this->get(route('events.index', ['q' => 'festival']));
    $responseLower->assertOk();
    expect($responseLower->viewData('events')->pluck('title'))
        ->toContain('Festival Văn Hóa Dân Gian')
        ->not->toContain('Đua Xe Công Thức 1');
});

test('TC_UC013_010: search events by keyword matching description field', function () {
    Event::factory()->create([
        'title' => 'Đêm Nhạc Acoustic',
        'description' => 'Chương trình giao lưu đặc biệt cùng nghệ sĩ vĩ cầm hàng đầu thế giới.',
        'status' => 'published',
    ]);
    Event::factory()->create([
        'title' => 'Hội Chợ Sách Mùa Hè',
        'description' => 'Triển lãm các ấn bản sách kinh điển dành cho thiếu nhi.',
        'status' => 'published',
    ]);

    $response = $this->get(route('events.index', ['q' => 'vĩ cầm']));

    $response->assertOk();
    expect($response->viewData('events')->pluck('title'))
        ->toContain('Đêm Nhạc Acoustic')
        ->not->toContain('Hội Chợ Sách Mùa Hè');
});

test('TC_UC013_011: filter events by category_id returns only matching category events', function () {
    $catMusic = Category::factory()->create(['name' => 'Âm Nhạc Live']);
    $catWorkshop = Category::factory()->create(['name' => 'Workshop Kỹ Năng']);

    Event::factory()->create([
        'title' => 'Concert Mùa Hè Rực Rỡ',
        'category_id' => $catMusic->id,
        'status' => 'published',
    ]);
    Event::factory()->create([
        'title' => 'Khóa Học Lập Trình AI',
        'category_id' => $catWorkshop->id,
        'status' => 'published',
    ]);

    // Lọc bằng category_id
    $responseWithCatId = $this->get(route('events.index', ['category_id' => $catMusic->id]));
    $responseWithCatId->assertOk();
    expect($responseWithCatId->viewData('events')->pluck('title'))
        ->toContain('Concert Mùa Hè Rực Rỡ')
        ->not->toContain('Khóa Học Lập Trình AI');

    // Lọc bằng category param
    $responseWithCat = $this->get(route('events.index', ['category' => $catWorkshop->id]));
    $responseWithCat->assertOk();
    expect($responseWithCat->viewData('events')->pluck('title'))
        ->toContain('Khóa Học Lập Trình AI')
        ->not->toContain('Concert Mùa Hè Rực Rỡ');
});

test('TC_UC013_012: combines keyword search and category filter simultaneously', function () {
    $catConcert = Category::factory()->create(['name' => 'Concert']);
    $catDrama = Category::factory()->create(['name' => 'Kịch Nói']);

    // Khớp cả từ khóa "Sài Gòn" và danh mục Concert
    $eventTarget = Event::factory()->create([
        'title' => 'Concert Sài Gòn Đêm Nhớ',
        'category_id' => $catConcert->id,
        'status' => 'published',
    ]);

    // Khớp từ khóa "Sài Gòn" nhưng KHÁC danh mục (Kịch Nói)
    $eventDrama = Event::factory()->create([
        'title' => 'Kịch Sài Gòn Phố Lên Đèn',
        'category_id' => $catDrama->id,
        'status' => 'published',
    ]);

    // Khớp danh mục Concert nhưng KHÔNG chứa "Sài Gòn"
    $eventHanoi = Event::factory()->create([
        'title' => 'Concert Hà Nội Mùa Thu',
        'category_id' => $catConcert->id,
        'status' => 'published',
    ]);

    $response = $this->get(route('events.index', [
        'q' => 'Sài Gòn',
        'category_id' => $catConcert->id,
    ]));

    $response->assertOk();
    expect($response->viewData('events')->pluck('title'))
        ->toContain('Concert Sài Gòn Đêm Nhớ')
        ->not->toContain('Kịch Sài Gòn Phố Lên Đèn')
        ->not->toContain('Concert Hà Nội Mùa Thu');
});

test('TC_UC013_013: empty state UI is rendered safely when no matching events exist', function () {
    $response = $this->get(route('events.index', ['q' => 'TừKhóaHoànToànKhôngCó']));

    $response->assertOk();
    $response->assertSee('Không tìm thấy sự kiện phù hợp');
    $response->assertSee('Hãy thử thay đổi từ khóa tìm kiếm hoặc chọn một danh mục sự kiện khác.');
});

test('TC_UC013_014: prevents SQL injection payloads in search query and category parameters', function () {
    Event::factory()->create([
        'title' => 'Sự Kiện Bảo Mật Tuyệt Đối',
        'status' => 'published',
    ]);

    $sqliPayloads = [
        "' OR '1'='1",
        "' OR 1=1 --",
        "1; DROP TABLE events; --",
        "'; EXEC sp_executesql; --",
        "' UNION SELECT null, null, null --",
    ];

    foreach ($sqliPayloads as $payload) {
        // Query param
        $resQuery = $this->get(route('events.index', ['q' => $payload]));
        $resQuery->assertOk(); // không ném lỗi 500

        // Suggestions endpoint
        $resSuggestion = $this->getJson(route('search.suggestions', ['q' => $payload]));
        $resSuggestion->assertOk(); // không ném lỗi 500

        // Category parameter
        $resCategory = $this->get(route('events.index', ['category_id' => $payload]));
        $resCategory->assertOk();
    }
});

test('TC_UC013_015: prevents XSS attacks in search input and echoes escaped values', function () {
    $xssPayload = "<script>alert('XSS')</script>";

    $response = $this->get(route('events.index', ['q' => $xssPayload]));

    $response->assertOk();
    $response->assertDontSee($xssPayload, false);
    $response->assertSee(e($xssPayload), false);

    // Live search suggestions endpoint
    $resSuggestion = $this->getJson(route('search.suggestions', ['q' => $xssPayload]));
    $resSuggestion->assertOk();
});

test('TC_UC013_016: public access test: guests, users and admins can search and view events freely', function () {
    $event = Event::factory()->create([
        'title' => 'Triển Lãm Công Nghệ Quốc Tế',
        'status' => 'published',
    ]);

    // 1. Khách vãng lai (Guest)
    $this->assertGuest();
    $resGuest = $this->get(route('events.index'));
    $resGuest->assertOk()->assertSee('Triển Lãm Công Nghệ Quốc Tế');
    $this->getJson(route('search.suggestions', ['q' => 'Triển Lãm']))->assertOk();

    // 2. Thành viên thông thường (User)
    $user = User::factory()->create();
    $user->assignRole('user');
    $resUser = $this->actingAs($user)->get(route('events.index'));
    $resUser->assertOk()->assertSee('Triển Lãm Công Nghệ Quốc Tế');
    $this->actingAs($user)->getJson(route('search.suggestions', ['q' => 'Triển Lãm']))->assertOk();

    // 3. Quản trị viên (Admin)
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $resAdmin = $this->actingAs($admin)->get(route('events.index'));
    $resAdmin->assertOk()->assertSee('Triển Lãm Công Nghệ Quốc Tế');
    $this->actingAs($admin)->getJson(route('search.suggestions', ['q' => 'Triển Lãm']))->assertOk();
});
