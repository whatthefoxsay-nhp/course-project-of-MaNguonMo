<?php

use App\Models\Event;

beforeEach(fn () => $this->seed());

test('seat grid is horizontally scrollable and passes seat data as JS literals', function () {
    $showtime = Event::firstWhere('slug', 'hoa-nhac-giao-huong-saigon-philharmonic')
        ->showtimes()->orderBy('start_time')->first();

    $html = $this->get(route('showtimes.seats', $showtime))
        ->assertOk()
        ->assertSee('overflow-x-auto overscroll-x-contain', false)
        ->assertSee('zoom: ${zoomScale};', false)
        ->getContent();

    // Hàng ghế được truyền qua @js (JSON, nháy kép được hex-escape) chứ không phải chuỗi '...'.
    expect($html)->not->toMatch("/row: '[^']*',/");
});
