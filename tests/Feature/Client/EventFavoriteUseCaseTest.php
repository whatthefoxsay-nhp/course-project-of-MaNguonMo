<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    $this->user = User::factory()->create([
        'is_active' => true,
    ]);
    $this->user->assignRole('user');

    $this->event = Event::create([
        'category_id' => $this->category->id,
        'title' => 'Live Concert Sơn Tùng M-TP 2026',
        'slug' => 'live-concert-son-tung-mtp-2026',
        'description' => 'Đại nhạc hội bùng nổ cảm xúc với các ca khúc bất hủ.',
        'duration_minutes' => 150,
        'min_price' => 500000,
        'poster_url' => 'https://example.com/poster.jpg',
        'status' => 'published',
    ]);
});

test('TC_UC022_001: Thành viên đã đăng nhập gửi request AJAX thêm sự kiện published vào yêu thích', function () {
    $response = $this->actingAs($this->user)
        ->postJson(route('events.favorite.toggle', $this->event->id));

    $response->assertStatus(200)
        ->assertJson([
            'favorited' => true,
            'event_id' => $this->event->id,
            'favorites_count' => 1,
        ]);

    $this->assertDatabaseHas('event_user_favorites', [
        'user_id' => $this->user->id,
        'event_id' => $this->event->id,
    ]);
    expect($this->user->favoriteEvents()->count())->toBe(1);
});

test('TC_UC022_002: Kiểm tra cơ chế bật/tắt (Toggle) khi click lần thứ hai xóa bản ghi khỏi DB', function () {
    // Lần 1: Thêm vào yêu thích
    $this->user->favoriteEvents()->attach($this->event->id);
    expect($this->user->favoriteEvents()->count())->toBe(1);

    // Lần 2: Gửi toggle request để bỏ yêu thích
    $response = $this->actingAs($this->user)
        ->postJson(route('events.favorite.toggle', $this->event->id));

    $response->assertStatus(200)
        ->assertJson([
            'favorited' => false,
            'event_id' => $this->event->id,
            'favorites_count' => 0,
        ]);

    $this->assertDatabaseMissing('event_user_favorites', [
        'user_id' => $this->user->id,
        'event_id' => $this->event->id,
    ]);
    expect($this->user->favoriteEvents()->count())->toBe(0);
});

test('TC_UC022_003: Kiểm tra trạng thái hiển thị biểu tượng Trái tim khi tải lại trang (/events và /events/{slug})', function () {
    // User đã yêu thích $this->event
    $this->user->favoriteEvents()->attach($this->event->id);

    // Tạo thêm 1 sự kiện chưa yêu thích
    $unfavoritedEvent = Event::create([
        'category_id' => $this->category->id,
        'title' => 'Sự kiện chưa lưu',
        'slug' => 'su-kien-chua-luu',
        'status' => 'published',
    ]);

    // 1. Kiểm tra trang danh sách sự kiện /events
    $resEvents = $this->actingAs($this->user)->get(route('events.index'));
    $resEvents->assertStatus(200);
    // Sự kiện đã lưu có class text-crimson
    $resEvents->assertSee('text-crimson');

    // 2. Kiểm tra trang chi tiết sự kiện đã yêu thích /events/{slug}
    $resShowFav = $this->actingAs($this->user)->get(route('events.show', $this->event->slug));
    $resShowFav->assertStatus(200);
    $resShowFav->assertSee('text-crimson');

    // 3. Kiểm tra trang chi tiết sự kiện chưa yêu thích /events/{slug}
    $resShowUnfav = $this->actingAs($this->user)->get(route('events.show', $unfavoritedEvent->slug));
    $resShowUnfav->assertStatus(200);
    // Phải render method isFavoritedBy trả về false
    expect($unfavoritedEvent->isFavoritedBy($this->user))->toBeFalse();
    expect($this->event->isFavoritedBy($this->user))->toBeTrue();
});

test('TC_UC022_004: Kiểm tra chống trùng lặp bản ghi yêu thích (Unique constraint / Idempotency)', function () {
    // Đã có sẵn bản ghi trong DB
    $this->user->favoriteEvents()->attach($this->event->id);

    // Dùng syncWithoutDetaching hoặc thử insert trực tiếp để kiểm tra unique constraint
    $this->user->favoriteEvents()->syncWithoutDetaching([$this->event->id]);

    $count = DB::table('event_user_favorites')
        ->where('user_id', $this->user->id)
        ->where('event_id', $this->event->id)
        ->count();

    expect($count)->toBe(1);

    // Thử insert trực tiếp bản ghi trùng lặp phải quăng ngoại lệ QueryException (Unique violation)
    $threwDuplicateException = false;
    try {
        DB::table('event_user_favorites')->insert([
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } catch (\Illuminate\Database\QueryException $e) {
        $threwDuplicateException = true;
    }
    expect($threwDuplicateException)->toBeTrue();
});

test('TC_UC022_005: Truy cập trang Sự kiện yêu thích (/favorites hoặc /profile/favorites) hiển thị đầy đủ thông tin', function () {
    $this->user->favoriteEvents()->attach($this->event->id);

    $response = $this->actingAs($this->user)->get(route('favorites.index'));

    $response->assertStatus(200);
    $response->assertSee('Live Concert Sơn Tùng M-TP 2026');
    $response->assertSee('Âm nhạc');
    $response->assertSee(route('events.show', $this->event->slug));

    // Truy cập qua alias /profile/favorites
    $responseProfile = $this->actingAs($this->user)->get(route('profile.favorites'));
    $responseProfile->assertStatus(200);
    $responseProfile->assertSee('Live Concert Sơn Tùng M-TP 2026');
});

test('TC_UC022_006: Bỏ lưu trực tiếp tại trang danh sách yêu thích qua DELETE /favorites/{id}', function () {
    $this->user->favoriteEvents()->attach($this->event->id);
    expect($this->user->favoriteEvents()->count())->toBe(1);

    $response = $this->actingAs($this->user)
        ->deleteJson(route('favorites.destroy', $this->event->id));

    $response->assertStatus(200)
        ->assertJson([
            'favorited' => false,
            'favorites_count' => 0,
        ]);

    $this->assertDatabaseMissing('event_user_favorites', [
        'user_id' => $this->user->id,
        'event_id' => $this->event->id,
    ]);

    // Tải lại trang favorites xác nhận sự kiện đã biến mất khỏi danh sách
    $reloadResponse = $this->actingAs($this->user)->get(route('favorites.index'));
    $reloadResponse->assertStatus(200);
    expect($reloadResponse->viewData('favorites')->pluck('id'))->not->toContain($this->event->id);
    $reloadResponse->assertSee('Chưa có sự kiện yêu thích nào');
});

test('TC_UC022_007: Truy cập trang yêu thích khi chưa lưu sự kiện nào hiển thị giao diện rỗng (Empty State)', function () {
    // Không thêm sự kiện nào vào favorite
    expect($this->user->favoriteEvents()->count())->toBe(0);

    $response = $this->actingAs($this->user)->get(route('favorites.index'));

    $response->assertStatus(200);
    $response->assertSee('Chưa có sự kiện yêu thích nào');
    $response->assertSee(route('events.index'));
});

test('TC_UC022_008: Sự kiện đã lưu bị Admin chuyển trạng thái sang archived hoặc draft không gây lỗi trang', function () {
    $this->user->favoriteEvents()->attach($this->event->id);

    // Chuyển sang archived
    $this->event->update(['status' => 'archived']);

    $response = $this->actingAs($this->user)->get(route('favorites.index'));

    $response->assertStatus(200);
    $response->assertSee('Live Concert Sơn Tùng M-TP 2026');
    $response->assertSee('Ngừng mở bán');

    // Chuyển sang draft
    $this->event->update(['status' => 'draft']);
    $responseDraft = $this->actingAs($this->user)->get(route('favorites.index'));
    $responseDraft->assertStatus(200);
    $responseDraft->assertSee('Live Concert Sơn Tùng M-TP 2026');
    $responseDraft->assertSee('Bản nháp');
});

test('TC_UC022_009: Gửi request thêm/bỏ yêu thích với event_id không tồn tại trả về HTTP 404 Not Found', function () {
    $nonExistentId = 999999;

    $response = $this->actingAs($this->user)
        ->postJson(route('events.favorite.toggle', $nonExistentId));

    $response->assertStatus(404);

    $deleteResponse = $this->actingAs($this->user)
        ->deleteJson(route('favorites.destroy', $nonExistentId));

    $deleteResponse->assertStatus(404);
});

test('TC_UC022_010: Gửi request thêm yêu thích đối với sự kiện đang ở trạng thái bản nháp (draft) bị chặn 422', function () {
    $draftEvent = Event::create([
        'category_id' => $this->category->id,
        'title' => 'Sự kiện bản nháp kín',
        'slug' => 'su-kien-ban-nhap-kin',
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('events.favorite.toggle', $draftEvent->id));

    $response->assertStatus(422);

    $this->assertDatabaseMissing('event_user_favorites', [
        'user_id' => $this->user->id,
        'event_id' => $draftEvent->id,
    ]);
});

test('TC_UC022_011: Khách vãng lai (Guest) bấm Trái tim qua AJAX bị chặn HTTP 401 Unauthorized', function () {
    $response = $this->postJson(route('events.favorite.toggle', $this->event->id));

    $response->assertStatus(401);

    $this->assertDatabaseMissing('event_user_favorites', [
        'event_id' => $this->event->id,
    ]);
});

test('TC_UC022_012: Khách vãng lai truy cập trực tiếp URL trang yêu thích (/favorites) bị chuyển hướng về /login', function () {
    $response = $this->get(route('favorites.index'));

    $response->assertRedirect(route('login'));
});

test('TC_UC022_013: Tính cô lập danh sách yêu thích giữa các tài khoản (User A không thấy sự kiện của User B)', function () {
    $userB = User::factory()->create(['is_active' => true]);
    $userB->assignRole('user');

    $eventB = Event::create([
        'category_id' => $this->category->id,
        'title' => 'Đêm nhạc riêng của User B',
        'slug' => 'dem-nhac-rieng-cua-user-b',
        'status' => 'published',
    ]);

    // User A thích $this->event
    $this->user->favoriteEvents()->attach($this->event->id);

    // User B thích $eventB
    $userB->favoriteEvents()->attach($eventB->id);

    // User A vào trang của mình: chỉ thấy $this->event, không có $eventB
    $responseA = $this->actingAs($this->user)->get(route('favorites.index'));
    $responseA->assertStatus(200);
    $responseA->assertSee('Live Concert Sơn Tùng M-TP 2026');
    expect($responseA->viewData('favorites')->pluck('id'))->toContain($this->event->id);
    expect($responseA->viewData('favorites')->pluck('id'))->not->toContain($eventB->id);

    // User B vào trang của mình: chỉ thấy $eventB, không có $this->event
    $responseB = $this->actingAs($userB)->get(route('favorites.index'));
    $responseB->assertStatus(200);
    $responseB->assertSee('Đêm nhạc riêng của User B');
    expect($responseB->viewData('favorites')->pluck('id'))->toContain($eventB->id);
    expect($responseB->viewData('favorites')->pluck('id'))->not->toContain($this->event->id);
});

test('TC_UC022_014: Tài khoản người dùng đã bị khóa (is_active = false) bị EnsureUserIsActive chặn và đăng xuất', function () {
    $lockedUser = User::factory()->create([
        'is_active' => false,
    ]);
    $lockedUser->assignRole('user');

    $response = $this->actingAs($lockedUser)
        ->get(route('favorites.index'));

    // Chặn bởi middleware EnsureUserIsActive -> redirect về login với thông báo khóa
    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

