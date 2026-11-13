<?php

use App\Models\Comment;
use App\Models\Event;
use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->withoutMiddleware(ThrottleRequests::class);
});

function createCommentCustomer(array $attributes = []): User
{
    $user = User::factory()->create(array_merge([
        'name' => 'Nguyễn Khán Giả',
        'email' => 'reviewer_'.uniqid().'@ticketbox.test',
        'phone' => '0912345678',
        'is_active' => true,
    ], $attributes));

    $user->assignRole('user');

    return $user;
}

function createCommentAdmin(array $attributes = []): User
{
    $admin = User::factory()->create(array_merge([
        'name' => 'Ban Quản Trị TicketBox',
        'email' => 'admin_'.uniqid().'@ticketbox.test',
        'is_active' => true,
    ], $attributes));

    $admin->assignRole('admin');

    return $admin;
}

function createPublishedCommentEvent(array $attributes = []): Event
{
    return Event::factory()->create(array_merge([
        'title' => 'Sự Kiện Âm Nhạc Trải Nghiệm',
        'status' => 'published',
    ], $attributes));
}

test('TC_UC021_001: authenticated user submits a valid review with rating and text via AJAX successfully', function () {
    $user = createCommentCustomer(['name' => 'Lê Hoàng Long']);
    $event = createPublishedCommentEvent();

    $response = $this->actingAs($user)
        ->postJson(route('comments.store', $event->slug), [
            'content' => 'Chương trình được đầu tư sân khấu ánh sáng rất mãn nhãn!',
            'rating' => 5,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'comment' => [
                'id',
                'user',
                'text',
                'rating',
                'time',
                'can_delete',
            ],
            'rating_average',
            'rating_count',
        ])
        ->assertJsonPath('comment.user', 'Lê Hoàng Long')
        ->assertJsonPath('comment.text', 'Chương trình được đầu tư sân khấu ánh sáng rất mãn nhãn!')
        ->assertJsonPath('comment.rating', 5)
        ->assertJsonPath('comment.can_delete', true);

    $comment = Comment::where('user_id', $user->id)->where('event_id', $event->id)->first();
    expect($comment)->not->toBeNull()
        ->and($comment->rating)->toBe(5)
        ->and($comment->content)->toBe('Chương trình được đầu tư sân khấu ánh sáng rất mãn nhãn!');
});

test('TC_UC021_002: boundary rating values (1 star and 5 stars) are stored accurately and update event average rating', function () {
    $event = createPublishedCommentEvent();

    $user1 = createCommentCustomer();
    $res1 = $this->actingAs($user1)
        ->postJson(route('comments.store', $event->slug), [
            'content' => 'Trải nghiệm không được như kỳ vọng của tôi.',
            'rating' => 1,
        ])
        ->assertCreated();

    expect($res1->json('rating_average'))->toEqual(1)
        ->and($res1->json('rating_count'))->toBe(1);

    $user2 = createCommentCustomer();
    $res2 = $this->actingAs($user2)
        ->postJson(route('comments.store', $event->slug), [
            'content' => 'Hoàn hảo từ âm thanh tới cách tổ chức check-in!',
            'rating' => 5,
        ])
        ->assertCreated();

    expect($res2->json('rating_average'))->toEqual(3)
        ->and($res2->json('rating_count'))->toBe(2);

    expect(Comment::where('event_id', $event->id)->pluck('rating')->all())->toBe([1, 5]);
});

test('TC_UC021_003: event show page displays comments sorted newest first (latest order)', function () {
    $event = createPublishedCommentEvent();
    $user = createCommentCustomer();

    $oldComment = Comment::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'content' => 'Bình luận gửi đầu tiên',
        'rating' => 4,
    ]);

    // Nhảy thời gian 2 giờ để bình luận mới có created_at mới hơn hẳn
    $this->travel(2)->hours();

    $newComment = Comment::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'content' => 'Bình luận gửi sau cùng mới nhất',
        'rating' => 5,
    ]);

    $response = $this->actingAs($user)->get(route('events.show', $event->slug));

    $response->assertOk()
        ->assertViewHas('comments', function (array $comments) use ($newComment, $oldComment) {
            return count($comments) === 2
                && $comments[0]['id'] === $newComment->id
                && $comments[1]['id'] === $oldComment->id;
        });
});

test('TC_UC021_004: displays admin reply nested under the user review on the event details page', function () {
    $event = createPublishedCommentEvent();
    $user = createCommentCustomer(['name' => 'Trần Thu Trang']);

    $comment = Comment::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'content' => 'Sự kiện có phục vụ nước uống tại fanzone không ạ?',
        'rating' => 5,
        'admin_reply' => 'Chào bạn, BTC có quầy tiếp nước miễn phí cho toàn bộ khán giả khu GA Fanzone nhé!',
        'admin_replied_at' => now(),
    ]);

    $response = $this->get(route('events.show', $event->slug));

    $response->assertOk()
        ->assertViewHas('comments', function (array $comments) use ($comment) {
            return count($comments) === 1
                && $comments[0]['id'] === $comment->id
                && $comments[0]['admin_reply'] === 'Chào bạn, BTC có quầy tiếp nước miễn phí cho toàn bộ khán giả khu GA Fanzone nhé!';
        })
        ->assertSee('Phản hồi từ TicketBox');
});

test('TC_UC021_005: comment author can delete their own review and recalculate event average score', function () {
    $event = createPublishedCommentEvent();
    $user = createCommentCustomer();

    $comment = Comment::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'content' => 'Bình luận cần xóa',
        'rating' => 2,
    ]);

    $response = $this->actingAs($user)
        ->deleteJson(route('comments.destroy', $comment));

    $response->assertOk()
        ->assertJsonPath('message', 'Đã xóa bình luận.')
        ->assertJsonPath('rating_count', 0);

    expect(Comment::find($comment->id))->toBeNull();
});

test('TC_UC021_006: User A is forbidden from deleting a comment created by User B (CommentPolicy)', function () {
    $event = createPublishedCommentEvent();
    $userB = createCommentCustomer(['name' => 'User B']);
    $userA = createCommentCustomer(['name' => 'User A']);

    $commentB = Comment::create([
        'user_id' => $userB->id,
        'event_id' => $event->id,
        'content' => 'Bình luận độc quyền của User B',
        'rating' => 5,
    ]);

    $response = $this->actingAs($userA)
        ->deleteJson(route('comments.destroy', $commentB));

    $response->assertForbidden();

    expect(Comment::find($commentB->id))->not->toBeNull();
});

test('TC_UC021_007: deleting a non-existent comment returns 404 not found', function () {
    $user = createCommentCustomer();

    $this->actingAs($user)
        ->deleteJson('/comments/99999999')
        ->assertNotFound();
});

test('TC_UC021_008: review submission fails when content is blank, only whitespace, or rating is missing', function () {
    $event = createPublishedCommentEvent();
    $user = createCommentCustomer();

    // 1. Bỏ trống nội dung
    $this->actingAs($user)
        ->postJson(route('comments.store', $event->slug), [
            'content' => '',
            'rating' => 5,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('content');

    // 2. Nội dung chỉ chứa khoảng trắng
    $this->actingAs($user)
        ->postJson(route('comments.store', $event->slug), [
            'content' => '     ',
            'rating' => 5,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('content');

    // 3. Gửi trường rating nhưng để rỗng/null
    $this->actingAs($user)
        ->postJson(route('comments.store', $event->slug), [
            'content' => 'Nội dung hợp lệ trên 3 ký tự',
            'rating' => null,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('rating');
});

test('TC_UC021_009: review content length boundary validation fails at 2 chars and succeeds at 3 chars', function () {
    $event = createPublishedCommentEvent();
    $user = createCommentCustomer();

    // Dưới biên tối thiểu: 2 ký tự -> thất bại
    $this->actingAs($user)
        ->postJson(route('comments.store', $event->slug), [
            'content' => 'Ok',
            'rating' => 5,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('content');

    // Mốc biên tối thiểu chuẩn: 3 ký tự -> thành công
    $this->actingAs($user)
        ->postJson(route('comments.store', $event->slug), [
            'content' => 'Hay',
            'rating' => 5,
        ])
        ->assertCreated()
        ->assertJsonPath('comment.text', 'Hay');
});

test('TC_UC021_010: review content length boundary validation succeeds at 1000 chars and fails at 1001 chars', function () {
    $event = createPublishedCommentEvent();
    $user = createCommentCustomer();

    // Đúng mốc biên tối đa: 1000 ký tự -> thành công
    $exact1000 = str_repeat('A', 1000);
    $this->actingAs($user)
        ->postJson(route('comments.store', $event->slug), [
            'content' => $exact1000,
            'rating' => 5,
        ])
        ->assertCreated();

    // Vượt mốc biên tối đa: 1001 ký tự -> thất bại
    $overflow1001 = str_repeat('B', 1001);
    $this->actingAs($user)
        ->postJson(route('comments.store', $event->slug), [
            'content' => $overflow1001,
            'rating' => 5,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('content');
});

test('TC_UC021_011: rating validation rejects out-of-range values (0, negative, >5, decimal, non-numeric)', function () {
    $event = createPublishedCommentEvent();
    $user = createCommentCustomer();

    $invalidRatings = [0, -1, 6, 4.5, 'five'];

    foreach ($invalidRatings as $invalidRating) {
        $this->actingAs($user)
            ->postJson(route('comments.store', $event->slug), [
                'content' => 'Đánh giá thử nghiệm với số sao không hợp lệ',
                'rating' => $invalidRating,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rating');
    }
});

test('TC_UC021_012: posting review to a non-existent or unpublished (draft/archived) event is blocked', function () {
    $user = createCommentCustomer();

    // 1. Sự kiện không tồn tại
    $this->actingAs($user)
        ->postJson('/events/non-existent-event-slug-xyz/comments', [
            'content' => 'Bình luận vào sự kiện ảo',
            'rating' => 5,
        ])
        ->assertNotFound();

    // 2. Sự kiện đang ở trạng thái draft
    $draftEvent = Event::factory()->create(['status' => 'draft']);
    $this->actingAs($user)
        ->postJson(route('comments.store', $draftEvent->slug), [
            'content' => 'Bình luận vào sự kiện nháp',
            'rating' => 5,
        ])
        ->assertNotFound();

    // 3. Sự kiện đang ở trạng thái archived
    $archivedEvent = Event::factory()->create(['status' => 'archived']);
    $this->actingAs($user)
        ->postJson(route('comments.store', $archivedEvent->slug), [
            'content' => 'Bình luận vào sự kiện lưu trữ',
            'rating' => 5,
        ])
        ->assertNotFound();
});

test('TC_UC021_013: XSS protection escapes malicious script tag in comment content on event page and admin panel', function () {
    $event = createPublishedCommentEvent();
    $user = createCommentCustomer(['name' => 'Tester XSS']);
    $admin = createCommentAdmin();

    $xssContent = "<script>alert('XSS_COMMENT')</script>";

    $this->actingAs($user)
        ->postJson(route('comments.store', $event->slug), [
            'content' => $xssContent,
            'rating' => 5,
        ])
        ->assertCreated();

    // Trang chi tiết sự kiện công khai không chứa thẻ script thực thi
    $response = $this->get(route('events.show', $event->slug));
    $response->assertOk()
        ->assertDontSee($xssContent, false);

    // Trang quản trị đánh giá Admin escape an toàn
    $adminResponse = $this->actingAs($admin)->get(route('admin.reviews.index'));
    $adminResponse->assertOk()
        ->assertDontSee($xssContent, false)
        ->assertSee('&lt;script&gt;alert(&#039;XSS_COMMENT&#039;)&lt;/script&gt;', false);
});

test('TC_UC021_014: unauthenticated guest is blocked by auth middleware when posting or deleting comment', function () {
    $event = createPublishedCommentEvent();
    $author = createCommentCustomer();
    $comment = Comment::create([
        'user_id' => $author->id,
        'event_id' => $event->id,
        'content' => 'Bình luận của thành viên',
        'rating' => 5,
    ]);

    // Guest gửi postJson bị 401 Unauthorized
    $this->postJson(route('comments.store', $event->slug), [
        'content' => 'Khách vãng lai gửi nhận xét',
        'rating' => 5,
    ])->assertUnauthorized();

    // Guest gửi deleteJson bị 401 Unauthorized
    $this->deleteJson(route('comments.destroy', $comment))
        ->assertUnauthorized();
});

test('TC_UC021_015: locked user with is_active = false is intercepted by EnsureUserIsActive middleware and logged out', function () {
    $event = createPublishedCommentEvent();
    $lockedUser = createCommentCustomer(['is_active' => false]);

    $this->actingAs($lockedUser)
        ->postJson(route('comments.store', $event->slug), [
            'content' => 'Tài khoản bị khóa cố tình bình luận',
            'rating' => 5,
        ])
        ->assertForbidden();

    expect(auth()->check())->toBeFalse();
});
