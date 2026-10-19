<?php

use App\Models\Comment;
use App\Models\Event;

beforeEach(function () {
    $this->event = Event::factory()->create(['status' => 'published']);
});

test('guests cannot comment', function () {
    $this->postJson(route('comments.store', $this->event), ['content' => 'Hay quá'])->assertUnauthorized();
});

test('a customer posts a comment and gets it back as json', function () {
    $user = createCustomer(['name' => 'Đặng Minh Anh']);

    $this->actingAs($user)
        ->postJson(route('comments.store', $this->event), ['content' => '  Sân khấu rất đẹp!  '])
        ->assertCreated()
        ->assertJsonPath('comment.user', 'Đặng Minh Anh')
        ->assertJsonPath('comment.initials', 'ĐA')
        ->assertJsonPath('comment.text', 'Sân khấu rất đẹp!')
        ->assertJsonPath('comment.can_delete', true);

    expect(Comment::where('user_id', $user->id)->where('event_id', $this->event->id)->exists())->toBeTrue();
});

test('comment content is validated', function (string $content) {
    $this->actingAs(createCustomer())
        ->postJson(route('comments.store', $this->event), ['content' => $content])
        ->assertStatus(422)
        ->assertJsonValidationErrors('content');
})->with(['rỗng' => '', 'quá ngắn' => 'ok', 'chỉ khoảng trắng' => '     ', 'quá dài' => str_repeat('a', 1001)]);

test('comments cannot be posted on or listed for unpublished events', function () {
    $draft = Event::factory()->create(['status' => 'draft']);

    $this->actingAs(createCustomer())
        ->postJson(route('comments.store', $draft), ['content' => 'Bình luận thử'])
        ->assertNotFound();
    $this->getJson(route('comments.index', $draft))->assertNotFound();
});

test('posting comments is rate limited', function () {
    $user = createCustomer();

    foreach (range(1, 10) as $i) {
        $this->actingAs($user)
            ->postJson(route('comments.store', $this->event), ['content' => "Bình luận số {$i}"])
            ->assertCreated();
    }

    $this->actingAs($user)
        ->postJson(route('comments.store', $this->event), ['content' => 'Bình luận thứ 11'])
        ->assertStatus(429);
});

test('anyone can list comments newest first with pagination', function () {
    $author = createCustomer(['name' => 'Lan']);
    foreach (range(1, 12) as $i) {
        $this->event->comments()->create([
            'user_id' => $author->id,
            'content' => "Bình luận {$i}",
        ])->forceFill(['created_at' => now()->subMinutes(20 - $i)])->save();
    }

    $first = $this->getJson(route('comments.index', $this->event))
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('total', 12)
        ->assertJsonPath('data.0.text', 'Bình luận 12')
        ->assertJsonPath('data.0.can_delete', false);

    expect($first->json('next_page_url'))->not->toBeNull();

    $this->getJson($first->json('next_page_url'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('next_page_url', null);
});

test('a tampered pagination cursor is rejected instead of crashing', function () {
    $this->getJson(route('comments.index', [$this->event, 'cursor' => base64_encode('{}')]))->assertStatus(400);
    $this->getJson(route('comments.index', [$this->event, 'cursor' => 'not-a-cursor']))->assertStatus(400);
});

test('comment content is returned raw, never as html', function () {
    $user = createCustomer();

    $this->actingAs($user)
        ->postJson(route('comments.store', $this->event), ['content' => '<script>alert(1)</script>'])
        ->assertCreated()
        ->assertJsonPath('comment.text', '<script>alert(1)</script>');

    // Trang chi tiết không được in nội dung này dưới dạng HTML thô.
    $this->get(route('events.show', $this->event->slug))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('the author can delete their comment', function () {
    $user = createCustomer();
    $comment = Comment::create(['user_id' => $user->id, 'event_id' => $this->event->id, 'content' => 'Xóa tôi đi']);

    $this->actingAs($user)->deleteJson(route('comments.destroy', $comment))->assertOk();

    expect(Comment::find($comment->id))->toBeNull();
});

test('guests cannot delete comments', function () {
    $comment = Comment::create(['user_id' => createCustomer()->id, 'event_id' => $this->event->id, 'content' => 'Bình luận']);

    $this->deleteJson(route('comments.destroy', $comment))->assertUnauthorized();
    expect(Comment::find($comment->id))->not->toBeNull();
});

test('another customer cannot delete it but an admin can', function () {
    $comment = Comment::create(['user_id' => createCustomer()->id, 'event_id' => $this->event->id, 'content' => 'Bình luận']);

    $this->actingAs(createCustomer())->deleteJson(route('comments.destroy', $comment))->assertForbidden();
    $this->actingAs(createAdmin())->deleteJson(route('comments.destroy', $comment))->assertOk();

    expect(Comment::find($comment->id))->toBeNull();
});
