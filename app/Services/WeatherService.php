<?php

namespace App\Services;

use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/** Dự báo thời tiết OpenWeatherMap tại giờ diễn; lỗi / thiếu key / ngoài 5 ngày → null, trang không bao giờ lỗi vì API ngoài. */
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
