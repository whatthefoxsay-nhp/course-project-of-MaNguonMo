<?php

namespace App\Services;

use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Dự báo thời tiết OpenWeatherMap (API 5 ngày / bước 3 giờ) tại giờ diễn.
 * Mọi lỗi (thiếu key, thiếu tọa độ, ngoài 5 ngày, API lỗi) đều trả null để view ẩn khối thời tiết.
 */
class WeatherService
{
    /** API miễn phí chỉ dự báo 5 ngày (bước 3 giờ). */
    public const FORECAST_DAYS = 5;

    /** Mốc dự báo phải cách giờ diễn tối đa chừng này giây (= 1 bước 3 giờ). */
    private const MAX_GAP_SECONDS = 3 * 3600;

    private const CACHE_MINUTES = 30;

    /** Khi API lỗi, tạm nghỉ gọi lại để trang không phải chờ timeout ở mỗi lượt xem. */
    private const FAILURE_CACHE_MINUTES = 5;

    private const UNFAVOURABLE = ['Rain', 'Thunderstorm', 'Snow', 'Drizzle', 'Squall', 'Tornado'];

    /**
     * @return array{temp: int, description: string, humidity: int, wind_kmh: int, icon_url: string, is_favorable: bool, forecast_at: Carbon}|null
     */
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

        $closest = collect($entries)
            ->filter(fn ($entry) => is_array($entry) && isset($entry['dt'], $entry['main']['temp']))
            ->sortBy(fn (array $entry) => abs($entry['dt'] - $at->getTimestamp()))
            ->first();

        if ($closest === null || abs($closest['dt'] - $at->getTimestamp()) > self::MAX_GAP_SECONDS) {
            return null;
        }

        return [
            'temp' => (int) round($closest['main']['temp']),
            'description' => Str::ucfirst($closest['weather'][0]['description'] ?? ''),
            'humidity' => (int) ($closest['main']['humidity'] ?? 0),
            'wind_kmh' => (int) round(($closest['wind']['speed'] ?? 0) * 3.6),
            'icon_url' => 'https://openweathermap.org/img/wn/'.($closest['weather'][0]['icon'] ?? '01d').'@2x.png',
            'is_favorable' => ! in_array($closest['weather'][0]['main'] ?? '', self::UNFAVOURABLE, true),
            'forecast_at' => Carbon::createFromTimestamp($closest['dt'], config('app.timezone')),
        ];
    }

    /** @return list<array> Danh sách mốc dự báo; [] khi lỗi. */
    private function forecastEntries(float $lat, float $lng, string $key): array
    {
        $cacheKey = 'weather:forecast:'.round($lat, 4).','.round($lng, 4);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
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
                ->json('list');
        } catch (Throwable $exception) {
            report($exception);
            $entries = null;
        }

        if (! is_array($entries) || $entries === []) {
            Cache::put($cacheKey, [], now()->addMinutes(self::FAILURE_CACHE_MINUTES));

            return [];
        }

        Cache::put($cacheKey, $entries, now()->addMinutes(self::CACHE_MINUTES));

        return $entries;
    }
}
