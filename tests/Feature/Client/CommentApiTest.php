<?php

use App\Models\Comment;
use App\Models\Event;

beforeEach(fn () => $this->seed());

function concert(): Event
{
    return Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026');
}

test('guests cannot comment', function () {
    $this->postJson(route('comments.store', concert()), ['content' => 'Hay quá'])->assertUnauthorized();
});

test('a customer posts a comment and gets it back as json', function () {
    $user = createCustomer(['name' => 'Minh Anh']);

    $this->actingAs($user)
        ->postJson(route('comments.store', concert()), ['content' => '  Sân khấu rất đẹp!  '])
        ->assertCreated()
        ->assertJsonPath('comment.user', 'Minh Anh')
        ->assertJsonPath('comment.text', 'Sân khấu rất đẹp!')
        ->assertJsonPath('comment.can_delete', true);

    expect(Comment::where('user_id', $user->id)->where('event_id', concert()->id)->exists())->toBeTrue();
});

test('comment content is validated', function (string $content) {
    $this->actingAs(createCustomer())
        ->postJson(route('comments.store', concert()), ['content' => $content])
        ->assertStatus(422)
        ->assertJsonValidationErrors('content');
})->with(['rỗng' => '', 'quá ngắn' => 'ok', 'quá dài' => str_repeat('a', 1001)]);

test('comments cannot be posted on unpublished events', function () {
    $draft = Event::factory()->create(['status' => 'draft']);

    $this->actingAs(createCustomer())
        ->postJson(route('comments.store', $draft), ['content' => 'Bình luận thử'])
        ->assertNotFound();
});

test('the author can delete their comment', function () {
    $user = createCustomer();
    $comment = Comment::create(['user_id' => $user->id, 'event_id' => concert()->id, 'content' => 'Xóa tôi đi']);

    $this->actingAs($user)->deleteJson(route('comments.destroy', $comment))->assertOk();

    expect(Comment::find($comment->id))->toBeNull();
});

test('another customer cannot delete it but an admin can', function () {
    $comment = Comment::create(['user_id' => createCustomer()->id, 'event_id' => concert()->id, 'content' => 'Bình luận']);

    $this->actingAs(createCustomer())->deleteJson(route('comments.destroy', $comment))->assertForbidden();
    $this->actingAs(createAdmin())->deleteJson(route('comments.destroy', $comment))->assertOk();

    expect(Comment::find($comment->id))->toBeNull();
});
