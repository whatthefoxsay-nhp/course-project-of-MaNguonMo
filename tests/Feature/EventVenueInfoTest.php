<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed();
    Http::preventStrayRequests();
    config(['services.google_maps.embed_key' => null]);
});

function fakeConcertForecast(): void
{
    Http::fake(['api.openweathermap.org/*' => Http::response(['list' => [
        ['dt' => today()->addDay()->setTime(19, 0)->timestamp, 'main' => ['temp' => 27, 'humidity' => 60], 'wind' => ['speed' => 1], 'weather' => [['main' => 'Clear', 'description' => 'trời quang', 'icon' => '01n']]],
    ]])]);
}

test('an outdoor event embeds the venue map, directions and the forecast for the next show', function () {
    config(['services.openweather.key' => 'test-key']);
    fakeConcertForecast();

    $this->get(route('events.show', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026'))
        ->assertOk()
        ->assertSee('maps.google.com/maps?q=10.8005,106.6658', false)
        ->assertSee('google.com/maps/dir/?api=1&amp;destination=10.8005,106.6658', false)
        ->assertSee('Chỉ đường')
        ->assertSee('27°C')
        ->assertSee('Trời quang')
        ->assertDontSee('28°C · Nắng nhẹ');
});

test('the weather card is hidden when no forecast is available', function () {
    config(['services.openweather.key' => null]);

    $this->get(route('events.show', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026'))
        ->assertOk()
        ->assertViewHas('weather', null)
        ->assertSee('maps.google.com/maps?q=10.8005,106.6658', false)
        ->assertDontSee('Dự Báo Thời Tiết Sự Kiện');

    Http::assertNothingSent();
});

test('the weather card is hidden when the weather api fails', function () {
    config(['services.openweather.key' => 'test-key']);
    Http::fake(['api.openweathermap.org/*' => Http::response('boom', 500)]);

    $this->get(route('events.show', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026'))
        ->assertOk()
        ->assertDontSee('Dự Báo Thời Tiết Sự Kiện');
});

test('indoor venues show the map but never call the weather api', function () {
    config(['services.openweather.key' => 'test-key']);
    fakeConcertForecast();

    $this->get(route('events.show', 'hoa-nhac-giao-huong-saigon-philharmonic'))
        ->assertOk()
        ->assertSee('maps.google.com/maps?q=10.7766,106.7032', false)
        ->assertDontSee('Dự Báo Thời Tiết Sự Kiện');

    Http::assertNothingSent();
});
