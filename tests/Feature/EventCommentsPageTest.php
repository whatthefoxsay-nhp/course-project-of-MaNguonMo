<?php

use App\Models\Event;

beforeEach(fn () => $this->seed());

test('the event page renders stored comments instead of hard coded ones', function () {
    $this->get(route('events.show', 'hoa-nhac-giao-huong-saigon-philharmonic'))
        ->assertOk()
        ->assertDontSee('Dàn âm thanh L-Acoustics và visual pháo hoa đỉnh chóp')
        ->assertViewHas('comments', fn (array $comments) => count($comments) === 1
            && str_starts_with($comments[0]['text'], 'Khán phòng cách âm tuyệt vời')
            && $comments[0]['can_delete'] === false);
});

test('the author sees a delete option on their own comment', function () {
    $user = createCustomer();
    Event::firstWhere('slug', 'vietnam-tech-summit-ai-expo-2026')
        ->comments()->create(['user_id' => $user->id, 'content' => 'Rất bổ ích']);

    $this->actingAs($user)
        ->get(route('events.show', 'vietnam-tech-summit-ai-expo-2026'))
        ->assertViewHas('comments', fn (array $comments) => $comments[0]['can_delete'] === true);
});
