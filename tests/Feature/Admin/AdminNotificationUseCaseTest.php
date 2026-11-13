<?php

use App\Models\User;
use App\Notifications\SystemAnnouncement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->admin = User::factory()->create(['name' => 'Quản Trị Viên']);
    $this->admin->assignRole('admin');

    $this->activeUser1 = User::factory()->create(['name' => 'Khách Hàng 1', 'is_active' => true]);
    $this->activeUser1->assignRole('user');

    $this->activeUser2 = User::factory()->create(['name' => 'Khách Hàng 2', 'is_active' => true]);
    $this->activeUser2->assignRole('user');

    $this->lockedUser = User::factory()->create(['name' => 'Khách Bị Khóa', 'is_active' => false]);
    $this->lockedUser->assignRole('user');
});

test('TC_UC010_001: admin sees notification management page with recent announcements history table and columns', function () {
    Notification::send($this->activeUser1, new SystemAnnouncement('Bảo trì máy chủ', 'Hệ thống bảo trì từ 2h đến 4h sáng.'));

    $response = $this->actingAs($this->admin)->get(route('admin.notifications.index'));

    $response->assertStatus(200);
    $response->assertSee('Thông Báo Hệ Thống');
    $response->assertSee('Thông báo đã gửi gần đây');
    $response->assertSee('Bảo trì máy chủ');
    $response->assertSee('Hệ thống bảo trì từ 2h đến 4h sáng.');
    $response->assertSee(now()->format('d/m/Y'));
});

test('TC_UC010_002: announcements history is sorted with most recent announcement first', function () {
    $user = $this->activeUser1;

    // Send old announcement
    $oldAnnouncement = new SystemAnnouncement('Tin tức cũ', 'Nội dung tin tức cũ');
    $user->notify($oldAnnouncement);
    DB::table('notifications')->where('type', SystemAnnouncement::class)->update(['created_at' => now()->subDays(2)]);

    // Send new announcement
    $newAnnouncement = new SystemAnnouncement('Tin tức mới nhất', 'Nội dung tin tức mới nhất');
    $user->notify($newAnnouncement);

    $response = $this->actingAs($this->admin)->get(route('admin.notifications.index'));

    $response->assertStatus(200);
    $content = $response->getContent();

    $posNew = strpos($content, 'Tin tức mới nhất');
    $posOld = strpos($content, 'Tin tức cũ');

    expect($posNew)->not->toBeFalse();
    expect($posOld)->not->toBeFalse();
    expect($posNew)->toBeLessThan($posOld);
});

test('TC_UC010_003: empty state displays safely when no announcements have been published yet', function () {
    expect(DB::table('notifications')->count())->toBe(0);

    $response = $this->actingAs($this->admin)->get(route('admin.notifications.index'));

    $response->assertStatus(200);
    $response->assertSee('Chưa gửi thông báo nào.');
});

test('TC_UC010_004: sending valid announcement broadcasts SystemAnnouncement and reports accurate recipient count', function () {
    Notification::fake();

    $response = $this->actingAs($this->admin)->post(route('admin.notifications.store'), [
        'title' => 'Mở bán vé đợt 2',
        'message' => 'Các vé khu VIP cho đêm nhạc đã chính thức mở bán trở lại.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Đã gửi thông báo đến 2 tài khoản khách hàng đang hoạt động.');

    Notification::assertSentTo(
        [$this->activeUser1, $this->activeUser2],
        SystemAnnouncement::class,
        function (SystemAnnouncement $notification) {
            return $notification->title === 'Mở bán vé đợt 2';
        }
    );
});

test('TC_UC010_005: actual database notification records store exact SystemAnnouncement type and JSON payload', function () {
    $this->actingAs($this->admin)->post(route('admin.notifications.store'), [
        'title' => 'Cập nhật sơ đồ khán phòng',
        'message' => 'Sơ đồ chỗ ngồi đã được tối ưu lối đi thông thoáng hơn.',
    ]);

    $record1 = $this->activeUser1->notifications()->first();
    $record2 = $this->activeUser2->notifications()->first();

    expect($record1)->not->toBeNull();
    expect($record2)->not->toBeNull();

    expect($record1->type)->toBe(SystemAnnouncement::class);
    expect($record1->data['title'])->toBe('Cập nhật sơ đồ khán phòng');
    expect($record1->data['message'])->toBe('Sơ đồ chỗ ngồi đã được tối ưu lối đi thông thoáng hơn.');
    expect($record1->data['kind'])->toBe('announcement');
    expect($record1->data['url'])->toBe('/');
});

test('TC_UC010_006: inactive locked users are excluded from receiving system announcements', function () {
    Notification::fake();

    $this->actingAs($this->admin)->post(route('admin.notifications.store'), [
        'title' => 'Thông báo chọn lọc',
        'message' => 'Nội dung thông báo cho người dùng hoạt động.',
    ]);

    Notification::assertSentTo([$this->activeUser1, $this->activeUser2], SystemAnnouncement::class);
    Notification::assertNotSentTo($this->lockedUser, SystemAnnouncement::class);
});

test('TC_UC010_007: admin accounts do not receive client announcements', function () {
    Notification::fake();

    $this->actingAs($this->admin)->post(route('admin.notifications.store'), [
        'title' => 'Thông báo gửi khách',
        'message' => 'Chỉ gửi tới khách hàng, không gửi tới ban quản trị.',
    ]);

    Notification::assertNotSentTo($this->admin, SystemAnnouncement::class);
});

test('TC_UC010_008: batch processing with chunkById handles large user bases without missing users', function () {
    Notification::fake();

    // Create 248 additional active users (total active users = 2 + 248 = 250)
    $extraUsers = User::factory()->count(248)->create(['is_active' => true]);
    foreach ($extraUsers as $user) {
        $user->assignRole('user');
    }

    expect(User::where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'user'))->count())->toBe(250);

    $response = $this->actingAs($this->admin)->post(route('admin.notifications.store'), [
        'title' => 'Thông báo đại trà',
        'message' => 'Gửi thông báo tới toàn thể 250 người dùng trong hệ thống.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Đã gửi thông báo đến 250 tài khoản khách hàng đang hoạt động.');

    Notification::assertSentTimes(SystemAnnouncement::class, 250);
});

test('TC_UC010_009: edge case when system has zero active users executes safely and reports 0 recipients', function () {
    // Deactivate existing users
    $this->activeUser1->update(['is_active' => false]);
    $this->activeUser2->update(['is_active' => false]);

    $response = $this->actingAs($this->admin)->post(route('admin.notifications.store'), [
        'title' => 'Thông báo không người nhận',
        'message' => 'Không có khách hàng hoạt động nào trong hệ thống.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Đã gửi thông báo đến 0 tài khoản khách hàng đang hoạt động.');
});

test('TC_UC010_010: missing title or message fails validation and sends no notification', function () {
    Notification::fake();

    $res1 = $this->actingAs($this->admin)->post(route('admin.notifications.store'), [
        'title' => '',
        'message' => 'Có nội dung nhưng không có tiêu đề.',
    ]);
    $res1->assertSessionHasErrors(['title']);

    $res2 = $this->actingAs($this->admin)->post(route('admin.notifications.store'), [
        'title' => 'Có tiêu đề nhưng không có nội dung',
        'message' => '',
    ]);
    $res2->assertSessionHasErrors(['message']);

    Notification::assertNothingSent();
});

test('TC_UC010_011: whitespace-only title or message fails validation', function () {
    Notification::fake();

    $response = $this->actingAs($this->admin)->post(route('admin.notifications.store'), [
        'title' => '     ',
        'message' => '     ',
    ]);

    $response->assertSessionHasErrors(['title', 'message']);
    Notification::assertNothingSent();
});

test('TC_UC010_012: boundary validation rejects title exceeding maximum characters (255)', function () {
    // 255 chars is accepted
    $res255 = $this->actingAs($this->admin)->post(route('admin.notifications.store'), [
        'title' => str_repeat('A', 255),
        'message' => 'Nội dung thông báo hợp lệ.',
    ]);
    $res255->assertSessionDoesntHaveErrors(['title']);

    // 256 chars is rejected
    $res256 = $this->actingAs($this->admin)->post(route('admin.notifications.store'), [
        'title' => str_repeat('B', 256),
        'message' => 'Nội dung thông báo hợp lệ.',
    ]);
    $res256->assertSessionHasErrors(['title']);
});

test('TC_UC010_013: xss script payloads in title and message are safely escaped on admin and client views', function () {
    $xss = '<script>alert("XSS")</script>';

    $this->actingAs($this->admin)->post(route('admin.notifications.store'), [
        'title' => $xss,
        'message' => $xss,
    ]);

    // 1. Check Admin history view: raw unescaped script tag must not execute
    $resAdmin = $this->actingAs($this->admin)->get(route('admin.notifications.index'));
    $resAdmin->assertStatus(200);
    $resAdmin->assertDontSee($xss, false);

    // 2. Check Client notification bell view: raw unescaped script tag must not execute
    $resClient = $this->actingAs($this->activeUser1)->get(route('home'));
    $resClient->assertStatus(200);
    $resClient->assertDontSee($xss, false);
});

test('TC_UC010_014: guest accessing admin notifications is redirected to login', function () {
    $this->get(route('admin.notifications.index'))
        ->assertRedirect('/login');

    $this->post(route('admin.notifications.store'), [
        'title' => 'Khách gửi',
        'message' => 'Khách không có quyền',
    ])->assertRedirect('/login');
});

test('TC_UC010_015: regular user attempting to access or broadcast announcements is forbidden 403', function () {
    Notification::fake();

    $this->actingAs($this->activeUser1)->get(route('admin.notifications.index'))
        ->assertStatus(403);

    $this->actingAs($this->activeUser1)->post(route('admin.notifications.store'), [
        'title' => 'User phát tán',
        'message' => 'Spam thông báo hệ thống',
    ])->assertStatus(403);

    Notification::assertNothingSent();
});

