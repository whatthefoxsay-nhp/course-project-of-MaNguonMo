<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\Event;
use App\Models\User;
use App\Notifications\CommentReplyNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->admin = User::factory()->create(['name' => 'Admin Quản Trị']);
    $this->admin->assignRole('admin');

    $this->author = User::factory()->create([
        'name' => 'Trần Văn Tác Giả',
        'email' => 'tacgia@gmail.com',
    ]);
    $this->author->assignRole('user');

    $this->otherUser = User::factory()->create([
        'name' => 'Lê Thị Người Dùng Khác',
        'email' => 'nguoidungkhac@gmail.com',
    ]);
    $this->otherUser->assignRole('user');

    $this->category = Category::create([
        'name' => 'Live Concert',
        'slug' => 'live-concert',
    ]);

    $this->event = Event::create([
        'category_id' => $this->category->id,
        'title' => 'Đêm Nhạc Giao Hưởng Mùa Thu',
        'slug' => 'dem-nhac-giao-huong-mua-thu',
        'duration_minutes' => 120,
        'release_date' => now()->toDateString(),
        'status' => 'published',
        'is_seated' => true,
    ]);

    $this->event2 = Event::create([
        'category_id' => $this->category->id,
        'title' => 'Rock Arena Festival 2026',
        'slug' => 'rock-arena-festival-2026',
        'duration_minutes' => 180,
        'release_date' => now()->toDateString(),
        'status' => 'published',
        'is_seated' => false,
    ]);
});

test('TC_UC009_001: admin sees review management page with total reviews, rated count, and accurate average rating cards', function () {
    Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Buổi hòa nhạc rất tuyệt vời!',
        'rating' => 5,
    ]);
    Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->otherUser->id,
        'content' => 'Âm thanh hơi to một chút.',
        'rating' => 4,
    ]);
    Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Không có chấm sao, chỉ nhận xét thêm.',
        'rating' => null,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reviews.index'));

    $response->assertStatus(200);
    $response->assertSee('Đêm Nhạc Giao Hưởng Mùa Thu');
    $response->assertSee('Trần Văn Tác Giả');
    $response->assertSee('Buổi hòa nhạc rất tuyệt vời!');
    $response->assertSee('3');         // Tổng bình luận
    $response->assertSee('2');         // Có chấm sao
    $response->assertSee('4.5 / 5');   // Điểm trung bình (5 + 4) / 2 = 4.5
});

test('TC_UC009_002: average rating calculates safely without division by zero when there are no reviews', function () {
    expect(Comment::count())->toBe(0);

    $response = $this->actingAs($this->admin)->get(route('admin.reviews.index'));

    $response->assertStatus(200);
    $response->assertSee('0.0 / 5');
});

test('TC_UC009_003: filter reviews list by specific star rating returns only matching comments', function () {
    $c5 = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Bình luận 5 sao đỉnh cao',
        'rating' => 5,
    ]);
    $c3 = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->otherUser->id,
        'content' => 'Bình luận 3 sao trung bình',
        'rating' => 3,
    ]);

    $res5 = $this->actingAs($this->admin)->get(route('admin.reviews.index', ['rating' => 5]));
    $res5->assertOk();
    $res5->assertSee('Bình luận 5 sao đỉnh cao');
    $res5->assertDontSee('Bình luận 3 sao trung bình');

    $res3 = $this->actingAs($this->admin)->get(route('admin.reviews.index', ['rating' => 3]));
    $res3->assertOk();
    $res3->assertSee('Bình luận 3 sao trung bình');
    $res3->assertDontSee('Bình luận 5 sao đỉnh cao');
});

test('TC_UC009_004: search reviews by event title returns matching comments', function () {
    Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Cảm nhận đêm nhạc giao hưởng',
        'rating' => 5,
    ]);
    Comment::create([
        'event_id' => $this->event2->id,
        'user_id' => $this->otherUser->id,
        'content' => 'Không khí lễ hội rock cuồng nhiệt',
        'rating' => 5,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reviews.index', ['search' => 'Giao Hưởng']));

    $response->assertOk();
    $response->assertSee('Cảm nhận đêm nhạc giao hưởng');
    $response->assertDontSee('Không khí lễ hội rock cuồng nhiệt');
});

test('TC_UC009_005: search reviews by content, author name or author email', function () {
    Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Vị trí ghế ngồi hàng VIP rất đẹp',
        'rating' => 5,
    ]);
    Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->otherUser->id,
        'content' => 'Khán phòng điều hòa hơi lạnh',
        'rating' => 4,
    ]);

    // Search by content keyword
    $resContent = $this->actingAs($this->admin)->get(route('admin.reviews.index', ['search' => 'điều hòa']));
    $resContent->assertOk();
    $resContent->assertSee('Khán phòng điều hòa hơi lạnh');
    $resContent->assertDontSee('hàng VIP rất đẹp');

    // Search by author email
    $resEmail = $this->actingAs($this->admin)->get(route('admin.reviews.index', ['search' => 'tacgia@gmail.com']));
    $resEmail->assertOk();
    $resEmail->assertSee('hàng VIP rất đẹp');
    $resEmail->assertDontSee('Khán phòng điều hòa hơi lạnh');
});

test('TC_UC009_006: combine star rating filter and keyword search simultaneously', function () {
    Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Âm thanh tuyệt hảo 5 sao',
        'rating' => 5,
    ]);
    Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->otherUser->id,
        'content' => 'Âm thanh bình thường 3 sao',
        'rating' => 3,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reviews.index', [
        'search' => 'Âm thanh',
        'rating' => 5,
    ]));

    $response->assertOk();
    $response->assertSee('Âm thanh tuyệt hảo 5 sao');
    $response->assertDontSee('Âm thanh bình thường 3 sao');
});

test('TC_UC009_007: admin submits official reply for review and database updates admin_reply and timestamp', function () {
    $comment = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Sân khấu có trang bị màn hình LED phụ không?',
        'rating' => 5,
    ]);

    $response = $this->actingAs($this->admin)->patch(route('admin.reviews.reply', $comment), [
        'reply' => 'Cảm ơn bạn, sự kiện có trang bị 2 màn hình LED phụ hai bên cánh gà.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $comment->refresh();
    expect($comment->admin_reply)->toBe('Cảm ơn bạn, sự kiện có trang bị 2 màn hình LED phụ hai bên cánh gà.');
    expect($comment->admin_replied_at)->not->toBeNull();
});

test('TC_UC009_008: admin reply triggers CommentReplyNotification to the comment author', function () {
    Notification::fake();

    $comment = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Bao giờ mở bán vé đợt tiếp theo?',
        'rating' => 5,
    ]);

    $this->actingAs($this->admin)->patch(route('admin.reviews.reply', $comment), [
        'reply' => 'Đợt bán tiếp theo sẽ bắt đầu vào 9h sáng thứ Hai tới.',
    ]);

    Notification::assertSentTo(
        $this->author,
        CommentReplyNotification::class,
        function (CommentReplyNotification $notification) use ($comment) {
            return true;
        }
    );
});

test('TC_UC009_009: CommentReplyNotification stores event id, slug, reply excerpt, and redirect url', function () {
    $comment = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Thời gian bắt đầu check-in là mấy giờ?',
        'rating' => 5,
    ]);

    $replyText = 'Cổng soát vé mở trước giờ diễn 60 phút bạn nhé.';

    $this->actingAs($this->admin)->patch(route('admin.reviews.reply', $comment), [
        'reply' => $replyText,
    ]);

    $notification = $this->author->notifications()->first();
    expect($notification)->not->toBeNull();

    $data = $notification->data;
    expect($data['event_id'])->toBe($this->event->id);
    expect($data['event_slug'])->toBe($this->event->slug);
    expect($data['url'])->toBe('/events/'.$this->event->slug);
    expect($data['reply'])->toBe($replyText);
    expect($data['kind'])->toBe('comment_reply');
});

test('TC_UC009_010: admin updates previously submitted reply overwriting content and refreshing timestamp', function () {
    $comment = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Bãi đỗ xe có rộng không?',
        'rating' => 4,
        'admin_reply' => 'Phản hồi ban đầu.',
        'admin_replied_at' => now()->subDay(),
    ]);

    $oldTime = $comment->admin_replied_at;

    $this->actingAs($this->admin)->patch(route('admin.reviews.reply', $comment), [
        'reply' => 'Bãi giữ xe tầng hầm có sức chứa 500 ô tô và 2000 xe máy.',
    ]);

    $comment->refresh();
    expect($comment->admin_reply)->toBe('Bãi giữ xe tầng hầm có sức chứa 500 ô tô và 2000 xe máy.');
    expect($comment->admin_replied_at->timestamp)->toBeGreaterThanOrEqual($oldTime->timestamp);
});

test('TC_UC009_011: public event page renders admin reply in comment public array', function () {
    $comment = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Chất lượng dàn nhạc đỉnh cao!',
        'rating' => 5,
        'admin_reply' => 'Cảm ơn bạn đã đồng hành cùng TicketBox!',
        'admin_replied_at' => now(),
    ]);

    $response = $this->get(route('events.show', ['slug' => $this->event->slug]));

    $response->assertStatus(200);
    $response->assertSee('Phản hồi từ TicketBox');
    $response->assertViewHas('comments', function (array $comments) {
        return count($comments) >= 1
            && $comments[0]['text'] === 'Chất lượng dàn nhạc đỉnh cao!'
            && $comments[0]['admin_reply'] === 'Cảm ơn bạn đã đồng hành cùng TicketBox!';
    });
});

test('TC_UC009_012: admin deletes violating review and comment is removed from database and public view', function () {
    $comment = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Spam link quảng cáo cờ bạc trái phép',
        'rating' => 1,
    ]);

    $response = $this->actingAs($this->admin)->delete(route('comments.destroy', $comment));

    $response->assertRedirect();
    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);

    $this->get(route('events.show', ['slug' => $this->event->slug]))
        ->assertDontSee('Spam link quảng cáo cờ bạc trái phép');
});

test('TC_UC009_013: system and event average rating recalculates accurately after deleting review', function () {
    Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => '5 sao tuyệt vời',
        'rating' => 5,
    ]);
    $badComment = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->otherUser->id,
        'content' => '1 sao phá hoại',
        'rating' => 1,
    ]);

    // Average is (5 + 1) / 2 = 3.0
    expect(round((float) $this->event->comments()->whereNotNull('rating')->avg('rating'), 1))->toBe(3.0);

    // Delete 1-star review
    $this->actingAs($this->admin)->delete(route('comments.destroy', $badComment));

    // Average recalculates back to 5.0
    expect(round((float) $this->event->comments()->whereNotNull('rating')->avg('rating'), 1))->toBe(5.0);
});

test('TC_UC009_014: replying to or deleting non-existent review id returns 404', function () {
    $this->actingAs($this->admin)->patch('/admin/reviews/999999/reply', [
        'reply' => 'Phản hồi thất bại',
    ])->assertStatus(404);

    $this->actingAs($this->admin)->delete('/comments/999999')
        ->assertStatus(404);
});

test('TC_UC009_015: submitting empty or whitespace-only reply fails validation and triggers no notification', function () {
    Notification::fake();

    $comment = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Bình luận hợp lệ',
        'rating' => 5,
    ]);

    $resEmpty = $this->actingAs($this->admin)->patch(route('admin.reviews.reply', $comment), [
        'reply' => '',
    ]);
    $resEmpty->assertSessionHasErrors(['reply']);

    $resSpaces = $this->actingAs($this->admin)->patch(route('admin.reviews.reply', $comment), [
        'reply' => '     ',
    ]);
    $resSpaces->assertSessionHasErrors(['reply']);

    Notification::assertNothingSent();
    expect($comment->fresh()->admin_reply)->toBeNull();
});

test('TC_UC009_016: boundary validation rejects reply exceeding maximum characters (1000)', function () {
    $comment = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Bình luận thử',
        'rating' => 5,
    ]);

    // 1000 chars: valid
    $res1000 = $this->actingAs($this->admin)->patch(route('admin.reviews.reply', $comment), [
        'reply' => str_repeat('A', 1000),
    ]);
    $res1000->assertSessionDoesntHaveErrors(['reply']);
    expect(strlen($comment->fresh()->admin_reply))->toBe(1000);

    // 1001 chars: invalid
    $res1001 = $this->actingAs($this->admin)->patch(route('admin.reviews.reply', $comment), [
        'reply' => str_repeat('B', 1001),
    ]);
    $res1001->assertSessionHasErrors(['reply']);
});

test('TC_UC009_017: xss script payloads in comment or reply are safely escaped or encoded', function () {
    $xss = '<script>alert("hacked")</script>';

    $comment = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => $xss,
        'rating' => 5,
        'admin_reply' => $xss,
        'admin_replied_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reviews.index'));

    $response->assertStatus(200);
    // Plain text is safe, raw unescaped script tag must not execute in DOM
    $response->assertDontSee($xss, false); // assert raw unescaped script is absent in raw HTML
});

test('TC_UC009_018: guest accessing review management or replying is redirected to login', function () {
    $comment = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Bình luận khách',
        'rating' => 5,
    ]);

    $this->get(route('admin.reviews.index'))
        ->assertRedirect('/login');

    $this->patch(route('admin.reviews.reply', $comment), ['reply' => 'Khách trả lời'])
        ->assertRedirect('/login');

    $this->delete(route('comments.destroy', $comment))
        ->assertRedirect('/login');
});

test('TC_UC009_019: regular user attempting to access admin reviews, reply as admin or delete others review is forbidden 403', function () {
    $comment = Comment::create([
        'event_id' => $this->event->id,
        'user_id' => $this->author->id,
        'content' => 'Bình luận của tác giả',
        'rating' => 5,
    ]);

    // Regular user cannot view admin reviews
    $this->actingAs($this->otherUser)->get(route('admin.reviews.index'))
        ->assertStatus(403);

    // Regular user cannot submit admin reply
    $this->actingAs($this->otherUser)->patch(route('admin.reviews.reply', $comment), [
        'reply' => 'User mạo danh admin trả lời',
    ])->assertStatus(403);

    // Regular user cannot delete another user's review
    $this->actingAs($this->otherUser)->delete(route('comments.destroy', $comment))
        ->assertStatus(403);
});
