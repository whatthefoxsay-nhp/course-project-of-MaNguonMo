<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed();
    Http::preventStrayRequests();
});

test('the event page embeds the venue map and the forecast for the next show', function () {
    config(['services.openweather.key' => 'test-key']);
    Http::fake(['api.openweathermap.org/*' => Http::response(['list' => [
        ['dt' => now()->addDay()->setTime(19, 0)->timestamp, 'main' => ['temp' => 27, 'humidity' => 60], 'wind' => ['speed' => 1], 'weather' => [['main' => 'Clear', 'description' => 'trời quang', 'icon' => '01n']]],
    ]])]);

    $this->get(route('events.show', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026'))
        ->assertOk()
        ->assertSee('maps.google.com/maps?q=10.8005,106.6658', false)
        ->assertSee('27°C')
        ->assertSee('Trời quang')
        ->assertDontSee('28°C · Nắng nhẹ');
});

test('the page still renders when no forecast is available', function () {
    config(['services.openweather.key' => null]);

    $this->get(route('events.show', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026'))
        ->assertOk()
        ->assertSee('Chưa có dự báo')
        ->assertViewHas('weather', null);
});
