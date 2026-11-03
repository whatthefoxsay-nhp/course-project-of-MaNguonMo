<?php

use App\Models\Event;

beforeEach(fn () => $this->seed());

test('seat grid is horizontally scrollable and passes seat data as JS literals', function () {
    $showtime = Event::firstWhere('slug', 'hoa-nhac-giao-huong-saigon-philharmonic')
        ->showtimes()->orderBy('start_time')->first();

    $html = $this->get(route('showtimes.seats', $showtime))
        ->assertOk()
        ->assertSee('relative overflow-x-auto', false)
        ->assertSee('transform: scale(${zoomScale})', false)
        ->getContent();

    // Dữ liệu ghế đi qua @js (JSON, nháy kép được hex-escape) chứ không ghép chuỗi '...' vào thuộc tính Alpine.
    expect($html)->toContain('toggleSeat(JSON.parse(')
        ->and($html)->not->toMatch("/toggleSeat\\('/")
        ->and($html)->not->toMatch("/row: '[^']*',/");
});
