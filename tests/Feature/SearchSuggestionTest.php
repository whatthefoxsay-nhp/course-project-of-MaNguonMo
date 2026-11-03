<?php

use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;

test('suggestions match published events by title', function () {
    // Viết đúng hoa/thường như tiêu đề: SQLite (DB test) chỉ so LIKE không phân biệt hoa/thường với ký tự ASCII.
    $event = Event::factory()->create(['title' => 'Hòa Nhạc Giao Hưởng Mùa Thu']);
    Event::factory()->create(['title' => 'Hội thảo công nghệ']);
    Showtime::factory()->create(['event_id' => $event->id, 'base_price' => 180000]);

    $this->getJson(route('search.suggestions', ['q' => 'Hòa Nhạc']))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.title', 'Hòa Nhạc Giao Hưởng Mùa Thu')
        ->assertJsonPath('results.0.url', route('events.show', $event->slug))
        ->assertJsonPath('results.0.category', $event->category->name)
        ->assertJsonPath('results.0.price_from', '180.000₫');
});

test('drafts are never suggested', function () {
    Event::factory()->create(['title' => 'Hòa nhạc bí mật', 'status' => 'draft']);

    $this->getJson(route('search.suggestions', ['q' => 'bí mật']))
        ->assertOk()
        ->assertJsonCount(0, 'results');
});

test('short queries return no results but popular events, and long ones are rejected', function () {
    Event::factory()->count(2)->create();
    Event::factory()->create(['status' => 'draft']);

    $this->getJson(route('search.suggestions', ['q' => 'a']))
        ->assertOk()
        ->assertJsonCount(0, 'results')
        ->assertJsonCount(2, 'popular');
    $this->getJson(route('search.suggestions', ['q' => str_repeat('a', 101)]))->assertStatus(422);
});

test('at most six suggestions are returned', function () {
    Event::factory()->count(8)->sequence(fn ($sequence) => ['title' => "Liveshow đặc biệt {$sequence->index}"])->create();

    $this->getJson(route('search.suggestions', ['q' => 'Liveshow']))->assertJsonCount(6, 'results');
});

test('like wildcards in the query are matched literally', function () {
    Event::factory()->create(['title' => 'Giảm 50% vé sớm']);
    Event::factory()->create(['title' => 'Đêm nhạc acoustic']);

    $this->getJson(route('search.suggestions', ['q' => '0%']))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.title', 'Giảm 50% vé sớm');
    $this->getJson(route('search.suggestions', ['q' => '%%']))->assertJsonCount(0, 'results');
});

test('suggestions are rate limited', function () {
    foreach (range(1, 60) as $i) {
        $this->getJson(route('search.suggestions', ['q' => 'ab']))->assertOk();
    }

    $this->getJson(route('search.suggestions', ['q' => 'ab']))->assertStatus(429);
});

test('the header search modal is wired to the suggestion endpoint', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-suggest-url="'.route('search.suggestions').'"', false)
        ->assertDontSee('Workshop AI');
});
