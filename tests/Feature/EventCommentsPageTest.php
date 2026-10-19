<?php

use App\Models\Event;

test('the event page renders stored comments instead of hard coded ones', function () {
    $event = Event::factory()->create(['status' => 'published']);
    $event->comments()->create([
        'user_id' => createCustomer(['name' => 'Hoa'])->id,
        'content' => 'Khán phòng cách âm tuyệt vời',
    ]);

    $this->get(route('events.show', $event->slug))
        ->assertOk()
        ->assertSee('Khán phòng cách âm tuyệt vời')
        ->assertSee('1 cảm nhận khán giả')
        ->assertDontSee('Dàn âm thanh L-Acoustics và visual pháo hoa đỉnh chóp')
        ->assertDontSee('1.200+ đánh giá xác thực')
        ->assertSee('Đăng nhập để bình luận')
        ->assertViewHas('commentsTotal', 1)
        ->assertViewHas('commentsNextUrl', null)
        ->assertViewHas('comments', fn (array $comments) => count($comments) === 1 && $comments[0]['can_delete'] === false);
});

test('the author sees a delete option on their own comment and the comment form', function () {
    $user = createCustomer();
    $event = Event::factory()->create(['status' => 'published']);
    $event->comments()->create(['user_id' => $user->id, 'content' => 'Rất bổ ích']);

    $this->actingAs($user)
        ->get(route('events.show', $event->slug))
        ->assertOk()
        ->assertDontSee('Đăng nhập để bình luận')
        ->assertSee('comment-input', false)
        ->assertViewHas('comments', fn (array $comments) => $comments[0]['can_delete'] === true);
});

test('only the first page of comments is embedded and the rest loads via ajax', function () {
    $event = Event::factory()->create(['status' => 'published']);
    $author = createCustomer();
    foreach (range(1, 13) as $i) {
        $event->comments()->create(['user_id' => $author->id, 'content' => "Cảm nhận số {$i}"]);
    }

    $response = $this->get(route('events.show', $event->slug))
        ->assertOk()
        ->assertViewHas('commentsTotal', 13)
        ->assertViewHas('comments', fn (array $comments) => count($comments) === 10 && $comments[0]['text'] === 'Cảm nhận số 13');

    $nextUrl = $response->viewData('commentsNextUrl');
    expect($nextUrl)->toStartWith(route('comments.index', $event));

    $this->getJson($nextUrl)
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.2.text', 'Cảm nhận số 1');
});
