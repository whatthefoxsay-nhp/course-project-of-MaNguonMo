<?php

use App\Models\Room;
use App\Services\WeatherService;
use App\Support\MapEmbed;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config(['services.openweather.key' => 'test-key', 'services.google_maps.embed_key' => null]);
});

function roomAt(?float $lat, ?float $lng, ?string $address = '7 Công Trường Lam Sơn'): Room
{
    return Room::factory()->create(['latitude' => $lat, 'longitude' => $lng, 'address' => $address]);
}

function fakeForecast(Carbon $at, string $main = 'Clear'): void
{
    Http::fake([
        'api.openweathermap.org/*' => Http::response(['list' => [
            ['dt' => $at->copy()->subHours(3)->timestamp, 'main' => ['temp' => 25.2, 'humidity' => 70], 'wind' => ['speed' => 2], 'weather' => [['main' => 'Clouds', 'description' => 'mây rải rác', 'icon' => '03d']]],
            ['dt' => $at->timestamp, 'main' => ['temp' => 28.6, 'humidity' => 65], 'wind' => ['speed' => 3.4], 'weather' => [['main' => $main, 'description' => 'bầu trời quang đãng', 'icon' => '01n']]],
        ]]),
    ]);
}

test('map embed uses coordinates without a key', function () {
    expect(MapEmbed::url(roomAt(10.7766, 106.7032)))
        ->toBe('https://maps.google.com/maps?q=10.7766,106.7032&z=16&output=embed');
});

test('map embed uses the embed api when a key is configured', function () {
    config(['services.google_maps.embed_key' => 'abc']);

    expect(MapEmbed::url(roomAt(10.7766, 106.7032)))
        ->toBe('https://www.google.com/maps/embed/v1/place?key=abc&q=10.7766,106.7032&zoom=16');
});

test('map embed falls back to the address, or null when nothing is known', function () {
    expect(MapEmbed::url(roomAt(null, null, '7 Công Trường Lam Sơn')))->toContain('q=7%20C%C3%B4ng')
        ->and(MapEmbed::url(roomAt(null, null, null)))->toBeNull();
});

test('weather picks the forecast closest to the show time', function () {
    $at = now()->addDays(2)->setTime(19, 0);
    fakeForecast($at);

    $weather = app(WeatherService::class)->forecastFor(roomAt(10.7766, 106.7032), $at);

    expect($weather)->toMatchArray([
        'temp' => 29,
        'description' => 'Bầu trời quang đãng',
        'humidity' => 65,
        'wind_kmh' => 12,
        'icon_url' => 'https://openweathermap.org/img/wn/01n@2x.png',
        'is_favorable' => true,
    ]);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'lat=10.7766') && $request['appid'] === 'test-key' && $request['lang'] === 'vi');
});

test('rain is flagged as unfavourable', function () {
    $at = now()->addDay()->setTime(19, 0);
    fakeForecast($at, 'Rain');

    expect(app(WeatherService::class)->forecastFor(roomAt(10.8, 106.6), $at)['is_favorable'])->toBeFalse();
});

test('forecasts are cached per location', function () {
    $at = now()->addDay()->setTime(19, 0);
    fakeForecast($at);
    $room = roomAt(10.8, 106.6);

    app(WeatherService::class)->forecastFor($room, $at);
    app(WeatherService::class)->forecastFor($room, $at);

    Http::assertSentCount(1);
});

test('weather is null without key, coordinates, beyond five days, or when the api fails', function () {
    $room = roomAt(10.8, 106.6);
    $service = app(WeatherService::class);

    expect($service->forecastFor(roomAt(null, null), now()->addDay()))->toBeNull()
        ->and($service->forecastFor($room, now()->addDays(6)))->toBeNull();

    config(['services.openweather.key' => null]);
    expect($service->forecastFor($room, now()->addDay()))->toBeNull();

    config(['services.openweather.key' => 'test-key']);
    Http::fake(['api.openweathermap.org/*' => Http::response('boom', 500)]);
    expect($service->forecastFor(roomAt(11.0, 107.0), now()->addDay()))->toBeNull();
});
