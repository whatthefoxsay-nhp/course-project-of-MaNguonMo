<?php

use App\Models\Category;
use App\Models\Event;

beforeEach(fn () => $this->seed());

test('home page lists published events from the database', function () {
    Event::factory()->create(['title' => 'Sự kiện nháp bí mật', 'status' => 'draft']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Live Concert: Anh Trai Vượt Ngàn Chông Gai 2026')
        ->assertDontSee('Sự kiện nháp bí mật');
});

test('event list searches by keyword', function () {
    $this->get(route('events.index', ['q' => 'Van Gogh']))
        ->assertOk()
        ->assertSee('Triển Lãm Đa Giác Quan: Van Gogh')
        ->assertDontSee('Vietnam Tech Summit');
});

test('event list filters by category', function () {
    $workshop = Category::firstWhere('slug', 'workshop');

    $this->get(route('events.index', ['category' => $workshop->id]))
        ->assertOk()
        ->assertSee('Masterclass: Nghệ Thuật Kể Chuyện')
        ->assertDontSee('Fan Meeting 2026');
});

test('event list paginates nine events per page', function () {
    Event::factory()->count(5)->create(['category_id' => Category::first()->id]);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertViewHas('events', fn ($page) => $page->count() === 9 && $page->total() === 13);

    $this->get(route('events.index', ['page' => 2]))
        ->assertViewHas('events', fn ($page) => $page->count() === 4);
});

test('search keyword longer than 100 characters is rejected', function () {
    $this->get(route('events.index', ['q' => str_repeat('a', 101)]))
        ->assertSessionHasErrors('q');
});

test('a draft event page returns 404', function () {
    $draft = Event::factory()->create(['status' => 'draft']);

    $this->get(route('events.show', $draft->slug))->assertNotFound();
});

test('event page lists upcoming showtimes only', function () {
    $concert = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026');
    $concert->showtimes()->first()->update(['start_time' => now()->subDay(), 'end_time' => now()->subDay()->addHours(3)]);

    $this->get(route('events.show', $concert->slug))
        ->assertOk()
        ->assertViewHas('showtimes', fn (array $showtimes) => count($showtimes) === 1);
});
