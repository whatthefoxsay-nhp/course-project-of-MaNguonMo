<?php

use App\Models\Event;

beforeEach(fn () => $this->seed());

test('suggestions match published events by title', function () {
    // Viết đúng hoa/thường như tiêu đề: SQLite (DB test) chỉ so LIKE không phân biệt hoa/thường với ký tự ASCII.
    $this->getJson(route('search.suggestions', ['q' => 'Hòa Nhạc']))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.url', route('events.show', 'hoa-nhac-giao-huong-saigon-philharmonic'))
        ->assertJsonPath('results.0.price_from', '180.000₫');
});

test('drafts are never suggested', function () {
    Event::factory()->create(['title' => 'Hòa nhạc bí mật', 'status' => 'draft']);

    $this->getJson(route('search.suggestions', ['q' => 'bí mật']))
        ->assertOk()
        ->assertJsonCount(0, 'results');
});

test('short queries return nothing and long ones are rejected', function () {
    $this->getJson(route('search.suggestions', ['q' => 'a']))->assertOk()->assertJsonCount(0, 'results');
    $this->getJson(route('search.suggestions', ['q' => str_repeat('a', 101)]))->assertStatus(422);
});

test('at most six suggestions are returned', function () {
    Event::factory()->count(8)->create(['title' => 'Liveshow đặc biệt']);

    $this->getJson(route('search.suggestions', ['q' => 'Liveshow']))->assertJsonCount(6, 'results');
});
