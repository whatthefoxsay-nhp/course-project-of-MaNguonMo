# Plan 5 — Tích hợp API ngoài: Google Maps + OpenWeatherMap

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Trang chi tiết sự kiện hiển thị bản đồ Google Maps thật của địa điểm và dự báo thời
tiết thật (OpenWeatherMap) tại giờ diễn của suất gần nhất; admin nhập được tọa độ khán phòng.

**Architecture:** `App\Support\MapEmbed` dựng URL iframe (dùng Maps Embed API nếu có key,
không có key thì dùng link nhúng công khai của Google Maps). `App\Services\WeatherService`
gọi API dự báo 5 ngày / 3 giờ qua `Http` facade, cache 30 phút theo tọa độ, lỗi mạng/thiếu
key/ngoài 5 ngày → trả `null` và view hiện thông báo thay thế, **trang không bao giờ lỗi vì API ngoài**.

**Tech Stack:** Laravel 11 (`Http` client, `Cache`), Pest 4 (`Http::fake`).

**Spec:** `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md` (mục 2 "API ngoài", Phase 5)
**Lộ trình:** `docs/superpowers/plans/2026-09-21-roadmap-hoan-thien.md`

## Global Constraints

- Nhánh: `feature/external-apis`, rẽ từ `main` **sau khi Plan 2 đã merge**. Nếu Plan 4 merge trước,
  rebase lên `main`; `EventController@show` sẽ có cả `$comments` (Plan 4) lẫn `$mapUrl`/`$weather` (plan này) — giữ cả hai.
- Cần từ Plan 2: `EventController@show` đọc DB, seeder có `latitude`/`longitude` cho 4 khán phòng.
- Key đặt trong `.env`: `GOOGLE_MAPS_EMBED_KEY`, `OPENWEATHER_API_KEY`. **Không commit key.** Chỉ thêm tên biến rỗng vào `.env.example`.
- Test không được gọi mạng thật: luôn `Http::fake()` / `Http::preventStrayRequests()`.
- Timeout gọi API: 5 giây. Cache: 30 phút.
- Hiển thị thời tiết cho mọi địa điểm có tọa độ (spec ghi "sự kiện ngoài trời"; không có cờ trong nhà/ngoài trời
  nên áp dụng cho tất cả — ghi vào `docs/architecture.md`).

## File Structure

| File | Trách nhiệm |
|---|---|
| `config/services.php`, `.env.example` (sửa) | Khai báo key |
| `app/Support/MapEmbed.php` (mới) | URL iframe bản đồ |
| `app/Services/WeatherService.php` (mới) | Gọi + cache + chọn mốc dự báo gần giờ diễn |
| `app/Http/Controllers/Admin/RoomController.php`, `resources/views/admin/rooms/builder.blade.php` (sửa) | Nhập tọa độ |
| `app/Http/Controllers/EventController.php` (sửa) | Truyền `$mapUrl`, `$weather` |
| `resources/views/events/show.blade.php` (sửa, khối `<!-- 7. WEATHER INTEGRATION & LOCATION QUICK VIEW -->` ~dòng 494–533) | |

---

### Task 1: `MapEmbed` + `WeatherService`

**Files:**
- Modify: `config/services.php`, `.env.example`
- Create: `app/Support/MapEmbed.php`
- Create: `app/Services/WeatherService.php`
- Test: `tests/Feature/ExternalApiServicesTest.php`

**Interfaces:**
- Produces: `MapEmbed::url(Room $room): ?string` — `null` nếu phòng không có tọa độ lẫn địa chỉ
- Produces: `WeatherService::forecastFor(Room $room, CarbonInterface $at): ?array` trả
  `['temp' => int, 'description' => string, 'humidity' => int, 'wind_kmh' => int, 'icon_url' => string, 'is_favorable' => bool, 'forecast_at' => Carbon]` hoặc `null`
- Produces: `WeatherService::FORECAST_DAYS = 5`

- [ ] **Step 1: Config**

Cuối mảng trong `config/services.php` (trước `];` cuối file) thêm:

```php
    'google_maps' => [
        'embed_key' => env('GOOGLE_MAPS_EMBED_KEY'),
    ],

    'openweather' => [
        'key' => env('OPENWEATHER_API_KEY'),
        'base_url' => env('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5'),
    ],
```

Cuối `.env.example` thêm:

```
GOOGLE_MAPS_EMBED_KEY=
OPENWEATHER_API_KEY=
```

- [ ] **Step 2: Viết test thất bại `tests/Feature/ExternalApiServicesTest.php`**

```php
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
```

- [ ] **Step 3: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=ExternalApiServicesTest`
Expected: FAIL — `Class "App\Support\MapEmbed" not found`.

- [ ] **Step 4: `app/Support/MapEmbed.php`**

```php
<?php

namespace App\Support;

use App\Models\Room;

final class MapEmbed
{
    public static function url(Room $room): ?string
    {
        $query = self::query($room);
        if ($query === null) {
            return null;
        }

        $key = config('services.google_maps.embed_key');

        return $key
            ? "https://www.google.com/maps/embed/v1/place?key={$key}&q={$query}&zoom=16"
            : "https://maps.google.com/maps?q={$query}&z=16&output=embed";
    }

    private static function query(Room $room): ?string
    {
        if ($room->latitude !== null && $room->longitude !== null) {
            return "{$room->latitude},{$room->longitude}";
        }

        return $room->address ? rawurlencode($room->address) : null;
    }
}
```

- [ ] **Step 5: `app/Services/WeatherService.php`**

```php
<?php

namespace App\Services;

use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class WeatherService
{
    /** API miễn phí chỉ dự báo 5 ngày (bước 3 giờ). */
    public const FORECAST_DAYS = 5;

    private const UNFAVOURABLE = ['Rain', 'Thunderstorm', 'Snow', 'Drizzle', 'Squall', 'Tornado'];

    public function forecastFor(Room $room, CarbonInterface $at): ?array
    {
        $key = config('services.openweather.key');

        if (! $key || $room->latitude === null || $room->longitude === null) {
            return null;
        }
        if ($at->isPast() || $at->gt(now()->addDays(self::FORECAST_DAYS))) {
            return null;
        }

        $entries = $this->forecastEntries($room->latitude, $room->longitude, $key);
        if ($entries === []) {
            return null;
        }

        $closest = collect($entries)->sortBy(fn (array $entry) => abs($entry['dt'] - $at->getTimestamp()))->first();

        return [
            'temp' => (int) round($closest['main']['temp']),
            'description' => Str::ucfirst($closest['weather'][0]['description'] ?? ''),
            'humidity' => (int) ($closest['main']['humidity'] ?? 0),
            'wind_kmh' => (int) round(($closest['wind']['speed'] ?? 0) * 3.6),
            'icon_url' => 'https://openweathermap.org/img/wn/'.($closest['weather'][0]['icon'] ?? '01d').'@2x.png',
            'is_favorable' => ! in_array($closest['weather'][0]['main'] ?? '', self::UNFAVOURABLE, true),
            'forecast_at' => Carbon::createFromTimestamp($closest['dt']),
        ];
    }

    /** @return list<array> Danh sách mốc dự báo; [] khi lỗi (không cache lỗi). */
    private function forecastEntries(float $lat, float $lng, string $key): array
    {
        $cacheKey = 'weather:forecast:'.round($lat, 4).','.round($lng, 4);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $entries = Http::timeout(5)
                ->get(config('services.openweather.base_url').'/forecast', [
                    'lat' => $lat,
                    'lon' => $lng,
                    'units' => 'metric',
                    'lang' => 'vi',
                    'appid' => $key,
                ])
                ->throw()
                ->json('list', []);
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }

        Cache::put($cacheKey, $entries, now()->addMinutes(30));

        return $entries;
    }
}
```

- [ ] **Step 6: Chạy test**

Run: `php artisan test --filter=ExternalApiServicesTest`
Expected: PASS 7 test.

Test cache dựa vào `<env name="CACHE_STORE" value="array"/>` trong `phpunit.xml` (Plan 1 Task 1 đã thêm lại).
Kiểm tra: `grep -n CACHE_STORE phpunit.xml` phải ra đúng dòng đó; nếu thiếu thì thêm vào khối `<php>`.

- [ ] **Step 7: Commit**

```bash
git add config/services.php .env.example app/Support/MapEmbed.php app/Services/WeatherService.php tests/Feature/ExternalApiServicesTest.php
git commit -m "feat: add Google Maps embed URL builder and cached OpenWeatherMap forecast service"
```

---

### Task 2: Admin nhập tọa độ khán phòng

**Files:**
- Modify: `app/Http/Controllers/Admin/RoomController.php` (`store`, `update`)
- Modify: `resources/views/admin/rooms/builder.blade.php` (sau ô "Địa chỉ chi tiết", ~dòng 178)
- Test: `tests/Feature/AdminRoomCoordinatesTest.php`

**Interfaces:**
- Produces: form khán phòng gửi `latitude` (-90..90), `longitude` (-180..180), cả hai nullable

- [ ] **Step 1: Viết test thất bại `tests/Feature/AdminRoomCoordinatesTest.php`**

```php
<?php

use App\Models\Room;

function roomForm(array $overrides = []): array
{
    return array_merge([
        'name' => 'Nhà hát Bến Thành',
        'address' => '6 Mạc Đĩnh Chi, Quận 1',
        'capacity' => 500,
        'layout_preset' => 'theater_hall',
        'latitude' => '10.7837',
        'longitude' => '106.7005',
    ], $overrides);
}

test('admin saves venue coordinates', function () {
    $this->actingAs(createAdmin())->post(route('admin.rooms.store'), roomForm())->assertRedirect(route('admin.rooms.index'));

    $room = Room::firstWhere('name', 'Nhà hát Bến Thành');
    expect($room->latitude)->toBe(10.7837)->and($room->longitude)->toBe(106.7005);
});

test('coordinates must be valid', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.rooms.store'), roomForm(['latitude' => '123', 'longitude' => 'abc']))
        ->assertSessionHasErrors(['latitude', 'longitude']);
});

test('coordinates can be updated on a room that already has showtimes', function () {
    $room = Room::factory()->create();
    \App\Models\Showtime::factory()->for($room)->create();

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), roomForm(['name' => $room->name, 'latitude' => '10.5', 'longitude' => '106.5']));

    expect($room->fresh()->latitude)->toBe(10.5);
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=AdminRoomCoordinatesTest`
Expected: FAIL (tọa độ không được lưu).

- [ ] **Step 3: `RoomController`**

Trong **cả** `store()` và `update()`, thêm 2 rule vào `$request->validate([...])`:

```php
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
```

Thêm vào **mọi** mảng truyền cho `Room::create([...])` và `$room->update([...])` (kể cả nhánh "đã có suất diễn" do Plan 1 thêm):

```php
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
```

- [ ] **Step 4: Ô nhập trong `resources/views/admin/rooms/builder.blade.php`**

Ngay sau `</div>` đóng khối "Địa chỉ chi tiết" (khối chứa `name="address"`, ~dòng 178), chèn:

```blade
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 font-bold uppercase tracking-wider mb-1.5">Vĩ độ (latitude)</label>
                                    <input type="number" step="0.000001" name="latitude" value="{{ old('latitude', $room->latitude) }}"
                                           placeholder="10.8005" class="glass-input w-full rounded-xl px-4 py-2.5 text-xs font-mono">
                                    @error('latitude')<p class="mt-1 text-[11px] font-bold text-[#CC0000]">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-gray-700 font-bold uppercase tracking-wider mb-1.5">Kinh độ (longitude)</label>
                                    <input type="number" step="0.000001" name="longitude" value="{{ old('longitude', $room->longitude) }}"
                                           placeholder="106.6658" class="glass-input w-full rounded-xl px-4 py-2.5 text-xs font-mono">
                                    @error('longitude')<p class="mt-1 text-[11px] font-bold text-[#CC0000]">{{ $message }}</p>@enderror
                                </div>
                                <p class="col-span-2 text-[11px] text-gray-500">Lấy từ Google Maps: chuột phải vào địa điểm → bấm dòng tọa độ để sao chép. Dùng cho bản đồ và dự báo thời tiết.</p>
                            </div>
```

- [ ] **Step 5: Test + commit**

Run: `php artisan test`
Expected: PASS toàn bộ.

```bash
git add app/Http/Controllers/Admin/RoomController.php resources/views/admin/rooms/builder.blade.php tests/Feature/AdminRoomCoordinatesTest.php
git commit -m "feat: let admins set venue coordinates"
```

---

### Task 3: Hiển thị bản đồ + thời tiết trên trang chi tiết sự kiện

**Files:**
- Modify: `app/Http/Controllers/EventController.php` (method `show`)
- Modify: `resources/views/events/show.blade.php` (khối `<!-- 7. WEATHER INTEGRATION & LOCATION QUICK VIEW -->`)
- Test: `tests/Feature/EventVenueInfoTest.php`

**Interfaces:**
- Consumes: `MapEmbed::url()`, `WeatherService::forecastFor()` (Task 1)
- Produces: view `events.show` nhận thêm `$mapUrl` (`?string`), `$weather` (`?array` như Task 1), `$weatherShowtime` (`?Showtime` — suất dùng để dự báo)

- [ ] **Step 1: Viết test thất bại `tests/Feature/EventVenueInfoTest.php`**

```php
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
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=EventVenueInfoTest`
Expected: FAIL (view vẫn hiện "28°C · Nắng nhẹ" cố định).

- [ ] **Step 3: `EventController@show`**

Thêm `use App\Services\WeatherService;` và `use App\Support\MapEmbed;`. Thêm tham số cuối cùng
`WeatherService $weatherService` vào chữ ký `show(...)` (giữ các tham số hiện có). Trước `return view(...)`:

```php
        $nextShowtime = $showtimes->first();
        $venue = $nextShowtime?->room;
```

Thêm vào mảng truyền cho view:

```php
            'mapUrl' => $venue ? MapEmbed::url($venue) : null,
            'weather' => $venue ? $weatherService->forecastFor($venue, $nextShowtime->start_time) : null,
            'weatherShowtime' => $nextShowtime,
```

(`$showtimes` ở đây là Collection trước khi gọi `->all()`; nếu code hiện tại đã gọi `->all()` ngay khi query,
hãy giữ Collection trong biến `$showtimes` và chỉ gọi `->all()` ở mảng truyền view.)

- [ ] **Step 4: Thay khối bản đồ + thời tiết trong `resources/views/events/show.blade.php`**

Thay toàn bộ khối từ `<!-- 7. WEATHER INTEGRATION & LOCATION QUICK VIEW -->` tới `</div>` đóng lưới
`grid grid-cols-1 sm:grid-cols-2 gap-4` (ngay trước `<!-- 8. COMMENTS / REVIEWS SECTION -->`) bằng:

```blade
                        <!-- 7. WEATHER INTEGRATION & LOCATION QUICK VIEW -->
                        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Location Card (Google Maps) -->
                            <div class="bg-[#FAF9F6] rounded-3xl p-5 border border-black/10 shadow-sm">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-4 h-4 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-xs font-bold text-black uppercase tracking-wider">Địa Điểm Tổ Chức Sự Kiện</span>
                                </div>
                                <p class="text-sm font-bold text-black">{{ $movie->venue_name ?? $weatherShowtime?->room->name ?? 'Đang cập nhật' }}</p>
                                <p class="text-[11px] text-gray-500 mb-3">{{ $movie->venue_address ?? $weatherShowtime?->room->address }}</p>
                                @if ($mapUrl)
                                    <iframe
                                        src="{{ $mapUrl }}"
                                        class="w-full h-48 rounded-2xl border border-black/10"
                                        loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"
                                        title="Bản đồ địa điểm"
                                    ></iframe>
                                @else
                                    <div class="h-48 rounded-2xl bg-white border border-black/10 flex items-center justify-center text-xs text-gray-500">Chưa có bản đồ cho địa điểm này.</div>
                                @endif
                            </div>

                            <!-- Weather Card (OpenWeatherMap) -->
                            <div class="bg-[#FAF9F6] rounded-3xl p-5 border border-black/10 shadow-sm">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-4 h-4 text-sage-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" />
                                    </svg>
                                    <span class="text-xs font-bold text-black uppercase tracking-wider">Dự Báo Thời Tiết Sự Kiện</span>
                                </div>
                                @if ($weather)
                                    <div class="rounded-2xl bg-white border border-black/10 p-4 flex items-center gap-4 shadow-inner">
                                        <img src="{{ $weather['icon_url'] }}" alt="{{ $weather['description'] }}" class="w-16 h-16">
                                        <div class="space-y-0.5 text-xs">
                                            <div class="font-display font-black text-2xl text-black">{{ $weather['temp'] }}°C</div>
                                            <div class="font-bold text-black">{{ $weather['description'] }}</div>
                                            <div class="text-gray-600">Độ ẩm: {{ $weather['humidity'] }}% · Gió: {{ $weather['wind_kmh'] }} km/h</div>
                                            <div class="{{ $weather['is_favorable'] ? 'text-sage-forest' : 'text-[#CC0000]' }} font-bold">
                                                {{ $weather['is_favorable'] ? 'Thuận lợi tổ chức' : 'Có thể mưa — nhớ mang áo mưa' }}
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-[10px] text-gray-500">
                                        Dự báo lúc {{ $weather['forecast_at']->timezone(config('app.timezone'))->format('H:i d/m') }} cho suất {{ $weatherShowtime->start_time->format('H:i d/m/Y') }} · Nguồn: OpenWeatherMap
                                    </p>
                                @else
                                    <div class="h-28 rounded-2xl bg-white border border-black/10 flex items-center justify-center text-center p-3 text-xs text-gray-500 shadow-inner">
                                        Chưa có dự báo — dự báo chỉ có trong vòng {{ \App\Services\WeatherService::FORECAST_DAYS }} ngày trước giờ diễn.
                                    </div>
                                @endif
                            </div>
                        </div>
```

- [ ] **Step 5: Test + kiểm tra tay**

Run: `php artisan test`
Expected: PASS toàn bộ.

Kiểm tra tay: đăng ký key miễn phí tại https://home.openweathermap.org/api_keys (key mới có thể mất tới ~1 giờ mới hoạt động),
đặt `OPENWEATHER_API_KEY=...` vào `.env`, `php artisan config:clear`, mở concert (suất sau 1 ngày) → thấy thời tiết thật + bản đồ QK7.
Không có key → trang vẫn mở, hiện "Chưa có dự báo".

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/EventController.php resources/views/events/show.blade.php tests/Feature/EventVenueInfoTest.php
git commit -m "feat: show venue map and live weather forecast on event pages"
```

---

### Task 4: Tài liệu + PR

- [ ] **Step 1:** `docs/architecture.md` — thêm vào bảng thay đổi:

```markdown
| **2026-09-21** | **API ngoài** | Google Maps: dùng Maps Embed API khi có `GOOGLE_MAPS_EMBED_KEY`, không có key thì dùng link nhúng công khai. OpenWeatherMap: dự báo 5 ngày/3 giờ, chọn mốc gần giờ diễn nhất, cache 30 phút, lỗi → ẩn khối thời tiết | Trang không phụ thuộc vào việc API ngoài sống hay chết; demo được cả khi chưa có key Maps. |
| **2026-09-21** | **Phạm vi thời tiết** | Hiển thị cho mọi địa điểm có tọa độ, không chỉ sự kiện ngoài trời | Chưa có cờ trong nhà/ngoài trời trong dữ liệu; thêm cờ sẽ là mở rộng phạm vi. |
```

Mục "Tiến độ hoàn thành" thêm: `- **API ngoài (Plan 5)**: bản đồ Google Maps + dự báo thời tiết OpenWeatherMap trên trang chi tiết; admin nhập tọa độ khán phòng.`

(Hướng dẫn cấu hình key cho người dùng cuối nằm trong README mới ở Plan 6 — không sửa README ở plan này.)

- [ ] **Step 2: Test + commit + PR**

```bash
php artisan test
git add docs/architecture.md
git commit -m "docs: document external API integration"
git push -u origin feature/external-apis
gh pr create --base main --title "Plan 5: Google Maps + OpenWeatherMap" --body "Theo docs/superpowers/plans/2026-09-21-p5-external-apis.md. Output php artisan test: <dán vào đây>"
```
