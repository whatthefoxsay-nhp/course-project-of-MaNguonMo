# Plan 2 — Trang public đọc dữ liệu thật từ DB

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Trang chủ, danh sách sự kiện (tìm kiếm + lọc + phân trang), chi tiết sự kiện và
trang chọn ghế đọc từ MySQL thay cho `App\Support\DemoCatalog`, giữ nguyên giao diện của nhóm.

**Architecture:** Bảng giá hạng vé chuyển từ `DemoCatalog::ticketTiers()` sang class
thuần `App\Support\TicketTiers` (nguồn giá duy nhất cho cả hiển thị và tính tiền ở Plan 3).
`App\Support\SeatMapPresenter` biến `showtime_seats` thành đúng "hình dạng" object mà
component `<x-seat-map>` đang dùng, nên view gần như không phải sửa. Seeder mới đưa toàn
bộ nội dung demo (8 sự kiện, lineup, lịch trình…) vào DB qua `RoomSeatGenerator` /
`ShowtimeSeatGenerator` của Plan 1.

**Tech Stack:** Laravel 11, Pest 4, Blade + Alpine.js, Tailwind.

**Spec:** `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md` (mục 4: "duyệt phim/sự kiện (search + filter theo category + pagination) → xem chi tiết → chọn suất chiếu → chọn ghế trên sơ đồ")
**Lộ trình:** `docs/superpowers/plans/2026-09-21-roadmap-hoan-thien.md`

## Global Constraints

- Nhánh: `feature/public-catalog-db`, rẽ từ `main` **sau khi Plan 1 đã merge**. Mỗi task = 1 commit.
- Cần từ Plan 1: `RoomSeatGenerator`, `ShowtimeSeatGenerator`, cột `events.is_seated`/`events.details`,
  accessor chi tiết trên `Event`, scope `Event::published()`, helper test `createAdmin()`/`createCustomer()`, factories.
- Không sửa migration đã có. Plan này **không thêm migration**.
- Giỏ vé (`cart/index`) và lịch sử (`bookings/history`) vẫn dùng `DemoCatalog` ở plan này —
  Plan 3 thay. Không xóa `DemoCatalog` trong plan này.
- Không đổi thiết kế giao diện; chỉ thay nguồn dữ liệu và thêm những phần plan ghi rõ.
- Chuỗi hiển thị tiếng Việt. Test bằng Pest.
- Sau khi đổi seeder: `php artisan migrate:fresh --seed` để DB local khớp.
- Trước khi mở PR: `php artisan test` xanh, `npm run build` thành công.

## File Structure

| File | Trách nhiệm |
|---|---|
| `app/Support/TicketTiers.php` (mới) | Bảng hạng vé + giá; ánh xạ loại ghế → hạng; hạng bán theo số lượng |
| `app/Support/DemoCatalog.php` (sửa) | `ticketTiers()` gọi sang `TicketTiers::for()` (tạm, Plan 3 xóa file) |
| `app/Models/ShowtimeSeat.php`, `Seat.php` (sửa) | Giá thực tế theo `TicketTiers`; `publicStatus()`; `isVip()` đúng loại ghế mới |
| `app/Models/Event.php` (sửa) | Accessor `base_price` (giá thấp nhất trong các suất) |
| `database/seeders/data/events.php` (mới) | Nội dung 8 sự kiện demo (chuyển từ `DemoCatalog`) |
| `database/seeders/DatabaseSeeder.php` (sửa) | Khán phòng, sự kiện, suất diễn, đơn mẫu từ dữ liệu trên |
| `app/Http/Controllers/{Home,Event,Showtime}Controller.php` (sửa) | Đọc DB |
| `app/Support/SeatMapPresenter.php` (mới) | `showtime_seats` → object cho `<x-seat-map>` |
| `resources/views/components/seat-map.blade.php` (sửa) | Thêm sơ đồ "khán phòng thường" cho phòng không phải sân vận động |
| `resources/views/{home,events/index,events/show,showtimes/seats,components/event-card}.blade.php` (sửa) | `poster_url`, phân trang |

---

### Task 1: `TicketTiers` — nguồn giá duy nhất

**Files:**
- Create: `app/Support/TicketTiers.php`
- Modify: `app/Support/DemoCatalog.php` (method `ticketTiers`, dòng 441–571)
- Modify: `app/Models/ShowtimeSeat.php`, `app/Models/Seat.php`
- Test: `tests/Unit/TicketTiersTest.php`, `tests/Feature/SeatPricingTest.php`

**Interfaces:**
- Produces:
  - `TicketTiers::for(int $basePrice, bool $isSeated): array<string, array{name,label,zone_code,gate,surcharge,price,color,badge,description,perks,icon}>` — y hệt output `DemoCatalog::ticketTiers()` cũ
  - `TicketTiers::normalizeSeatType(string $seatType): string` — `normal`→`cat2_wings`, `vip`→`vip_gold`, còn lại giữ nguyên
  - `TicketTiers::seatPrice(string $seatType, int $basePrice): int`
  - `TicketTiers::isPassTier(string $tier, bool $isSeated): bool` — hạng bán theo số lượng hợp lệ cho loại sự kiện (sự kiện có ghế: chỉ `standing_pit`; không ghế: `vip_pass`, `standard_pass`, `combo_pass`)
  - `TicketTiers::passSeatTypes(string $tier): list<string>` — loại ghế server được phép gán cho hạng đó
  - `TicketTiers::seatsPerUnit(string $tier): int` — `combo_pass` = 2, còn lại 1
  - `TicketTiers::pricePerSeat(string $tier, int $basePrice, bool $isSeated): int`
  - `ShowtimeSeat::$effective_price` (int), `ShowtimeSeat::publicStatus(): string` (`held` đã hết hạn → `available`)

- [ ] **Step 1: Viết test thất bại `tests/Unit/TicketTiersTest.php`**

```php
<?php

use App\Support\TicketTiers;

test('seated tiers price each zone from the base price', function () {
    $tiers = TicketTiers::for(200000, true);

    expect($tiers['svip_diamond']['price'])->toBe(350000)
        ->and($tiers['vip_gold']['price'])->toBe(280000)
        ->and($tiers['cat1_stand']['price'])->toBe(240000)
        ->and($tiers['cat2_wings']['price'])->toBe(200000)
        ->and($tiers['skybox_suite']['price'])->toBe(600000)
        ->and($tiers['standing_pit']['price'])->toBe(180000);
});

test('non seated events sell passes', function () {
    expect(array_keys(TicketTiers::for(200000, false)))->toBe(['vip_pass', 'standard_pass', 'combo_pass']);
});

test('legacy seat types map onto current zones', function () {
    expect(TicketTiers::normalizeSeatType('normal'))->toBe('cat2_wings')
        ->and(TicketTiers::normalizeSeatType('vip'))->toBe('vip_gold')
        ->and(TicketTiers::seatPrice('vip', 200000))->toBe(280000)
        ->and(TicketTiers::seatPrice('normal', 200000))->toBe(200000);
});

test('pass tiers are only valid for the matching event kind', function () {
    expect(TicketTiers::isPassTier('standing_pit', true))->toBeTrue()
        ->and(TicketTiers::isPassTier('standing_pit', false))->toBeFalse()
        ->and(TicketTiers::isPassTier('vip_pass', false))->toBeTrue()
        ->and(TicketTiers::isPassTier('vip_pass', true))->toBeFalse()
        ->and(TicketTiers::isPassTier('svip_diamond', true))->toBeFalse()
        ->and(TicketTiers::isPassTier('khong-ton-tai', false))->toBeFalse();
});

test('combo pass covers two seats at a shared price', function () {
    expect(TicketTiers::seatsPerUnit('combo_pass'))->toBe(2)
        ->and(TicketTiers::pricePerSeat('combo_pass', 200000, false))->toBe(170000)
        ->and(TicketTiers::passSeatTypes('combo_pass'))->toContain('cat1_stand');
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test tests/Unit/TicketTiersTest.php`
Expected: FAIL — `Class "App\Support\TicketTiers" not found`.

- [ ] **Step 3: Tạo `app/Support/TicketTiers.php`**

Tạo class với khung dưới đây. Thân method `for()` = **copy nguyên văn** thân method
`DemoCatalog::ticketTiers()` (dòng 441–571 của `app/Support/DemoCatalog.php`, từ
`if ($isSeatedConcert) {` tới `];` cuối cùng), rồi đổi tên biến `$isSeatedConcert` → `$isSeated`.

```php
<?php

namespace App\Support;

/**
 * Nguồn giá vé duy nhất của hệ thống.
 * Giá mỗi hạng = giá cơ bản của suất diễn + phụ thu hạng (xem `for()`).
 */
final class TicketTiers
{
    /** Loại ghế cũ trong DB -> hạng hiện hành. */
    private const LEGACY_SEAT_TYPES = ['normal' => 'cat2_wings', 'vip' => 'vip_gold'];

    /**
     * Hạng bán theo số lượng (khách không chọn ghế, server tự gán):
     * loại ghế được phép gán + số ghế chiếm cho mỗi 1 vé.
     */
    private const PASS_TIERS = [
        'standing_pit' => ['seat_types' => ['standing_pit'], 'seats_per_unit' => 1],
        'vip_pass' => ['seat_types' => ['svip_diamond', 'vip_gold', 'vip'], 'seats_per_unit' => 1],
        'standard_pass' => ['seat_types' => ['cat1_stand', 'cat2_wings', 'skybox_suite', 'normal'], 'seats_per_unit' => 1],
        'combo_pass' => ['seat_types' => ['cat1_stand', 'cat2_wings', 'skybox_suite', 'normal'], 'seats_per_unit' => 2],
    ];

    public static function for(int $basePrice, bool $isSeated): array
    {
        // <- dán nguyên văn thân DemoCatalog::ticketTiers() vào đây ($isSeatedConcert -> $isSeated)
    }

    public static function normalizeSeatType(string $seatType): string
    {
        return self::LEGACY_SEAT_TYPES[$seatType] ?? $seatType;
    }

    public static function seatPrice(string $seatType, int $basePrice): int
    {
        $tiers = self::for($basePrice, true);

        return (int) ($tiers[self::normalizeSeatType($seatType)]['price'] ?? $basePrice);
    }

    public static function isPassTier(string $tier, bool $isSeated): bool
    {
        return isset(self::PASS_TIERS[$tier])
            && array_key_exists($tier, self::for(0, $isSeated));
    }

    /** @return list<string> */
    public static function passSeatTypes(string $tier): array
    {
        return self::PASS_TIERS[$tier]['seat_types'] ?? [];
    }

    public static function seatsPerUnit(string $tier): int
    {
        return self::PASS_TIERS[$tier]['seats_per_unit'] ?? 1;
    }

    /** Giá ghi vào mỗi booking_item khi mua theo hạng (combo 2 người chia đôi giá gói). */
    public static function pricePerSeat(string $tier, int $basePrice, bool $isSeated): int
    {
        $unitPrice = (int) self::for($basePrice, $isSeated)[$tier]['price'];

        return intdiv($unitPrice, self::seatsPerUnit($tier));
    }
}
```

Sau đó thay **thân** `DemoCatalog::ticketTiers()` bằng một dòng (giữ chữ ký method):

```php
        return TicketTiers::for($basePrice, $isSeatedConcert);
```

- [ ] **Step 4: Chạy test unit**

Run: `php artisan test tests/Unit/TicketTiersTest.php`
Expected: PASS 5 test.

- [ ] **Step 5: Viết test thất bại `tests/Feature/SeatPricingTest.php`**

```php
<?php

use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;

function showtimeSeatOfType(string $type, array $attributes = []): ShowtimeSeat
{
    $showtime = Showtime::factory()->create(['base_price' => 200000]);
    $seat = Seat::create(['room_id' => $showtime->room_id, 'row_label' => 'A', 'seat_number' => 1, 'type' => $type]);

    return ShowtimeSeat::create(array_merge(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'available'], $attributes));
}

test('effective price follows the ticket tier of the seat', function () {
    expect(showtimeSeatOfType('vip_gold')->effective_price)->toBe(280000)
        ->and(showtimeSeatOfType('skybox_suite')->effective_price)->toBe(600000)
        ->and(showtimeSeatOfType('normal')->effective_price)->toBe(200000);
});

test('a price override wins over the tier price', function () {
    expect(showtimeSeatOfType('vip_gold', ['price_override' => 123000])->effective_price)->toBe(123000);
});

test('an expired hold is shown as available', function () {
    $expired = showtimeSeatOfType('cat1_stand', ['status' => 'held', 'held_until' => now()->subMinute()]);
    $active = showtimeSeatOfType('cat1_stand', ['status' => 'held', 'held_until' => now()->addMinutes(5)]);

    expect($expired->publicStatus())->toBe('available')
        ->and($active->publicStatus())->toBe('held');
});

test('vip seat types are recognised', function () {
    expect((new Seat(['type' => 'svip_diamond']))->isVip())->toBeTrue()
        ->and((new Seat(['type' => 'cat2_wings']))->isVip())->toBeFalse();
});
```

- [ ] **Step 6: Chạy test, xác nhận FAIL**

Run: `php artisan test tests/Feature/SeatPricingTest.php`
Expected: FAIL — giá VIP đang tính 225000 (logic cũ `+25000`), `publicStatus()` chưa có.

- [ ] **Step 7: Sửa model**

`app/Models/ShowtimeSeat.php`: thêm `use App\Support\TicketTiers;`, thay `getEffectivePriceAttribute()` và thêm `publicStatus()`:

```php
    public function getEffectivePriceAttribute(): int
    {
        if ($this->price_override !== null) {
            return $this->price_override;
        }

        return TicketTiers::seatPrice($this->seat?->type ?? 'normal', $this->showtime?->base_price ?? 0);
    }

    /** Trạng thái hiển thị cho khách: ghế giữ quá hạn coi như còn trống. */
    public function publicStatus(): string
    {
        if ($this->status === 'held' && $this->held_until !== null && $this->held_until->isPast()) {
            return 'available';
        }

        return $this->status;
    }
```

`app/Models/Seat.php`: thay `isVip()`:

```php
    public function isVip(): bool
    {
        return in_array($this->type, ['vip', 'vip_gold', 'svip_diamond', 'skybox_suite'], true);
    }
```

- [ ] **Step 8: Chạy toàn bộ test**

Run: `php artisan test`
Expected: PASS toàn bộ (các test `EventBookingFlowTest` cũ vẫn chạy trên `DemoCatalog`).

- [ ] **Step 9: Commit**

```bash
git add app/Support/TicketTiers.php app/Support/DemoCatalog.php app/Models/ShowtimeSeat.php app/Models/Seat.php tests/Unit/TicketTiersTest.php tests/Feature/SeatPricingTest.php
git commit -m "feat: add TicketTiers as the single source of ticket prices"
```

---

### Task 2: Seeder đưa nội dung demo vào DB

**Files:**
- Create: `database/seeders/data/events.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `app/Models/Event.php` (accessor `base_price`)
- Test: `tests/Feature/DatabaseSeederTest.php`

**Interfaces:**
- Consumes: `RoomSeatGenerator::generate()`, `ShowtimeSeatGenerator::generate()` (Plan 1), `TicketTiers::seatPrice()` (Task 1)
- Produces: dữ liệu seed cố định mà test ở Task 3–4 và Plan 3–5 dựa vào:
  - Khán phòng: `Sân Vận Động Quân Khu 7 (SVĐ QK7 Arena)` (`mega_concert`), `Nhà Hát Thành Phố (Saigon Opera House)` (`theater_hall`), `Trung Tâm Hội Nghị & Triển Lãm SECC Hall A` (`convention_center`), `Nhà Hát Hòa Bình Main Hall` (`theater_hall`) — đều có `latitude`/`longitude`
  - 8 sự kiện `published`, slug giữ nguyên như `DemoCatalog` (vd `live-concert-anh-trai-vuot-ngan-chong-gai-2026`, `vietnam-tech-summit-ai-expo-2026`)
  - Sự kiện có ghế (`is_seated = true`): concert Anh Trai (id demo 1), hòa nhạc (2), nhạc kịch (6); còn lại `false`
  - Đơn `TBX-89214` (confirmed, 2 ghế, của `user@ticketbox.vn`) và `TBX-77102` (cancelled, 1 ghế)
- Produces: `$event->base_price` — giá cơ bản thấp nhất trong các suất; `null` nếu chưa có suất

- [ ] **Step 1: Viết test thất bại `tests/Feature/DatabaseSeederTest.php`**

```php
<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;

beforeEach(fn () => $this->seed());

test('the seeder publishes the eight demo events with their details', function () {
    $concert = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026');

    expect(Event::published()->count())->toBe(8)
        ->and($concert->is_seated)->toBeTrue()
        ->and($concert->venue_name)->toContain('Quân Khu 7')
        ->and($concert->lineup)->not->toBeEmpty()
        ->and($concert->entry_policy)->toHaveKey('prohibited')
        ->and(Event::firstWhere('slug', 'vietnam-tech-summit-ai-expo-2026')->is_seated)->toBeFalse();
});

test('every showtime has one showtime seat per room seat', function () {
    Showtime::with('room')->get()->each(function (Showtime $showtime) {
        expect($showtime->showtimeSeats()->count())->toBe($showtime->room->seats()->count());
    });

    expect(Showtime::count())->toBeGreaterThanOrEqual(12);
});

test('the concert runs in the stadium room with a standing row', function () {
    $concert = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026');
    $room = $concert->showtimes()->first()->room;

    expect($room->layout_preset)->toBe('mega_concert')
        ->and($room->latitude)->not->toBeNull()
        ->and($room->seats()->where('type', 'standing_pit')->count())->toBe(40);
});

test('the demo booking is confirmed and its seats are sold', function () {
    $booking = Booking::with('items.showtimeSeat')->firstWhere('booking_code', 'TBX-89214');

    expect($booking->status)->toBe('confirmed')
        ->and($booking->items)->toHaveCount(2)
        ->and($booking->total_price)->toBe($booking->items->sum('price'))
        ->and($booking->items->every(fn ($item) => $item->showtimeSeat->status === 'booked'))->toBeTrue()
        ->and(Booking::firstWhere('booking_code', 'TBX-77102')->status)->toBe('cancelled');
});

test('the event base price is the cheapest showtime price', function () {
    $concert = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026');

    expect($concert->base_price)->toBe(250000);
});

test('seeding twice does not duplicate data', function () {
    $this->seed();

    expect(Event::count())->toBe(8)
        ->and(Room::count())->toBe(4)
        ->and(Booking::where('booking_code', 'TBX-89214')->count())->toBe(1);
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=DatabaseSeederTest`
Expected: FAIL (seeder cũ không có `is_seated`, `details`, khán phòng khác tên…).

- [ ] **Step 3: Tạo file dữ liệu `database/seeders/data/events.php`**

```php
<?php

/*
 * Nội dung 8 sự kiện demo (chuyển nguyên văn từ App\Support\DemoCatalog::movies()).
 * Mỗi phần tử: [demoId, type, title, slug, demoCategoryId, description,
 *               durationMinutes, basePrice, isSeated, details[]]
 */

return [
    // <- DÁN NGUYÊN VĂN các phần tử của mảng `$rows = [ ... ];`
    //    trong app/Support/DemoCatalog.php (dòng 30–347: từ `[` của sự kiện 1
    //    tới `],` của sự kiện 8). Không sửa nội dung.
];
```

Kiểm tra: `php -r "var_dump(count(require 'database/seeders/data/events.php'));"` → `int(8)`.

- [ ] **Step 4: Accessor `base_price` trong `app/Models/Event.php`**

```php
    /**
     * Giá cơ bản thấp nhất trong các suất diễn (hiển thị "Từ ...₫").
     * Controller danh sách nên gọi withMin('showtimes', 'base_price') để tránh N+1.
     */
    public function getBasePriceAttribute(): ?int
    {
        $min = array_key_exists('showtimes_min_base_price', $this->attributes)
            ? $this->attributes['showtimes_min_base_price']
            : $this->showtimes()->min('base_price');

        return $min === null ? null : (int) $min;
    }
```

- [ ] **Step 5: Viết lại `database/seeders/DatabaseSeeder.php`**

Giữ nguyên phần **1 (roles), 2 (users) và 3 (categories)** của `run()` hiện tại
(dòng 29 tới hết vòng `foreach ($categoriesData ...)`, ~dòng 137). Xóa mọi thứ từ comment
`// 4. Create Event Venues` tới cuối class (kể cả 2 method private cũ) và thay bằng code dưới.
Cập nhật khối `use` ở đầu file cho khớp (xóa `Movie`, `Seat`, `ShowtimeSeat`, `Hash` nếu không còn dùng; thêm các `use` bên dưới).

```php
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;
use App\Support\TicketTiers;
```

Đổi chữ ký: `public function run(RoomSeatGenerator $roomSeats, ShowtimeSeatGenerator $showtimeSeats): void`.

Phần thay thế (đặt ngay sau vòng `foreach` tạo categories):

```php
        // 4. Khán phòng / địa điểm
        $rooms = [];
        foreach (self::VENUES as $key => $venue) {
            $rooms[$key] = Room::updateOrCreate(['name' => $venue['name']], $venue);
            if ($rooms[$key]->seats()->doesntExist()) {
                $roomSeats->generate($rooms[$key], $venue['layout_preset'], []);
            }
        }

        // 5. Sự kiện (nội dung chuyển từ DemoCatalog)
        $events = [];
        $basePrices = [];
        foreach (require __DIR__.'/data/events.php' as $row) {
            [, $type, $title, $slug, $demoCategoryId, $description, $duration, $basePrice, $isSeated, $details] = $row;

            $events[$slug] = Event::updateOrCreate(['slug' => $slug], [
                'type' => $type,
                'title' => $title,
                'category_id' => $categories[self::CATEGORY_BY_DEMO_ID[$demoCategoryId]]->id,
                'description' => $description,
                'duration_minutes' => $duration,
                'poster_path' => "https://picsum.photos/seed/{$slug}/480/720",
                'status' => 'published',
                'is_seated' => $isSeated,
                'details' => $details,
                'release_date' => now()->toDateString(),
            ]);
            $basePrices[$slug] = $basePrice;
        }

        // 6. Suất diễn (không chồng giờ trong cùng khán phòng)
        $today = Carbon::today();
        $showtimes = [];
        foreach (self::SCHEDULE as [$slug, $roomKey, $dayOffset, $start, $end]) {
            $startAt = $today->copy()->addDays($dayOffset)->setTimeFromTimeString($start);
            $showtime = Showtime::firstOrCreate(
                ['event_id' => $events[$slug]->id, 'room_id' => $rooms[$roomKey]->id, 'start_time' => $startAt],
                [
                    'end_time' => $today->copy()->addDays($dayOffset)->setTimeFromTimeString($end),
                    'base_price' => $basePrices[$slug],
                ]
            );
            $showtimeSeats->generate($showtime);
            $showtimes[$slug] ??= $showtime;
        }

        // 7. Đơn đặt vé mẫu cho tài khoản demo
        if ($demoUser) {
            $this->seedBooking($demoUser, $showtimes['hoa-nhac-giao-huong-saigon-philharmonic'], 2, 'TBX-89214', 'confirmed');
            $this->seedBooking($demoUser, $showtimes['nhac-kich-les-miserables-vn'], 1, 'TBX-77102', 'cancelled');
        }

        // 8. Bình luận mẫu
        Comment::firstOrCreate(
            ['user_id' => $demoUser?->id ?? $admin->id, 'event_id' => $events['hoa-nhac-giao-huong-saigon-philharmonic']->id],
            ['content' => 'Khán phòng cách âm tuyệt vời, dàn nhạc giao hưởng chơi xuất thần và rất truyền cảm hứng!']
        );
        Comment::firstOrCreate(
            ['user_id' => $admin->id, 'event_id' => $events['live-concert-anh-trai-vuot-ngan-chong-gai-2026']->id],
            ['content' => 'Sân khấu quy mô khủng, hệ thống ghế ngồi khán đài được phân luồng rất khoa học.']
        );
    }

    /** id danh mục trong DemoCatalog -> slug danh mục đã seed ở bước 3 */
    private const CATEGORY_BY_DEMO_ID = [
        1 => 'concert', 2 => 'hoa-nhac', 3 => 'hoi-thao', 4 => 'trien-lam',
        5 => 'fan-meeting', 6 => 'san-khau-kich', 7 => 'festival', 8 => 'workshop',
    ];

    private const VENUES = [
        'qk7' => [
            'name' => 'Sân Vận Động Quân Khu 7 (SVĐ QK7 Arena)',
            'address' => 'Số 202 Hoàng Văn Thụ, Phường 9, Quận Phú Nhuận, TP.HCM',
            'latitude' => 10.8005, 'longitude' => 106.6658,
            'capacity' => 20000, 'layout_preset' => 'mega_concert',
        ],
        'opera' => [
            'name' => 'Nhà Hát Thành Phố (Saigon Opera House)',
            'address' => '07 Công Trường Lam Sơn, Bến Nghé, Quận 1, TP.HCM',
            'latitude' => 10.7766, 'longitude' => 106.7032,
            'capacity' => 800, 'layout_preset' => 'theater_hall',
        ],
        'secc' => [
            'name' => 'Trung Tâm Hội Nghị & Triển Lãm SECC Hall A',
            'address' => '799 Nguyễn Văn Linh, Tân Phú, Quận 7, TP.HCM',
            'latitude' => 10.7302, 'longitude' => 106.7218,
            'capacity' => 3000, 'layout_preset' => 'convention_center',
        ],
        'hoabinh' => [
            'name' => 'Nhà Hát Hòa Bình Main Hall',
            'address' => '240 đường 3 Tháng 2, Phường 12, Quận 10, TP.HCM',
            'latitude' => 10.7712, 'longitude' => 106.6745,
            'capacity' => 2500, 'layout_preset' => 'theater_hall',
        ],
    ];

    /** [slug sự kiện, khán phòng, cách hôm nay (ngày), giờ bắt đầu, giờ kết thúc] */
    private const SCHEDULE = [
        ['live-concert-anh-trai-vuot-ngan-chong-gai-2026', 'qk7', 1, '19:00', '23:00'],
        ['live-concert-anh-trai-vuot-ngan-chong-gai-2026', 'qk7', 8, '19:00', '23:00'],
        ['saigon-autumn-music-light-festival-2026', 'qk7', 4, '17:00', '23:00'],
        ['hoa-nhac-giao-huong-saigon-philharmonic', 'opera', 2, '19:30', '21:30'],
        ['hoa-nhac-giao-huong-saigon-philharmonic', 'opera', 9, '19:30', '21:30'],
        ['nhac-kich-les-miserables-vn', 'opera', 3, '19:30', '22:15'],
        ['nhac-kich-les-miserables-vn', 'opera', 10, '19:30', '22:15'],
        ['vietnam-tech-summit-ai-expo-2026', 'secc', 5, '08:30', '17:00'],
        ['trien-lam-nghe-thuat-van-gogh-impressionism', 'secc', 6, '09:00', '18:00'],
        ['trien-lam-nghe-thuat-van-gogh-impressionism', 'secc', 7, '09:00', '18:00'],
        ['masterclass-sang-tao-phim-ai-2026', 'secc', 11, '09:00', '17:00'],
        ['fan-meeting-running-man-vn-2026', 'hoabinh', 3, '18:30', '21:00'],
    ];

    private function seedBooking(User $user, Showtime $showtime, int $seatCount, string $code, string $status): void
    {
        if (Booking::where('booking_code', $code)->exists()) {
            return;
        }

        $seats = $showtime->showtimeSeats()->with('seat')
            ->where('status', 'available')
            ->orderBy('id')
            ->take($seatCount)
            ->get();

        $prices = $seats->mapWithKeys(fn ($showtimeSeat) => [
            $showtimeSeat->id => TicketTiers::seatPrice($showtimeSeat->seat->type, $showtime->base_price),
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'booking_code' => $code,
            'total_price' => $prices->sum(),
            'status' => $status,
        ]);

        foreach ($seats as $showtimeSeat) {
            BookingItem::create([
                'booking_id' => $booking->id,
                'showtime_seat_id' => $showtimeSeat->id,
                'price' => $prices[$showtimeSeat->id],
            ]);

            if ($status === 'confirmed') {
                $showtimeSeat->update(['status' => 'booked']);
            }
        }
    }
}
```

Ghi chú cho người thực thi:
- `Showtime::firstOrCreate` so khớp `start_time` theo chuỗi `Y-m-d H:i:s` — chạy seeder cùng ngày nhiều lần không nhân đôi; qua ngày khác sẽ tạo thêm suất mới (chấp nhận được với dữ liệu demo, dùng `migrate:fresh --seed` để làm sạch).

- [ ] **Step 6: Chạy test**

Run: `php artisan test --filter=DatabaseSeederTest`
Expected: PASS 6 test.

- [ ] **Step 7: Nạp lại DB local**

Run: `php artisan migrate:fresh --seed`
Expected: chạy hết không lỗi.

- [ ] **Step 8: Chạy toàn bộ test**

Run: `php artisan test`
Expected: PASS toàn bộ.

- [ ] **Step 9: Commit**

```bash
git add database/seeders app/Models/Event.php tests/Feature/DatabaseSeederTest.php
git commit -m "feat: seed demo events, venues, showtimes and bookings into the database"
```

---

### Task 3: Trang chủ, danh sách (tìm kiếm + lọc + phân trang), chi tiết sự kiện đọc DB

**Files:**
- Modify: `app/Http/Controllers/HomeController.php`, `app/Http/Controllers/EventController.php`
- Modify: `resources/views/home.blade.php` (dòng 67), `resources/views/events/show.blade.php` (dòng 24), `resources/views/components/event-card.blade.php` (dòng 12), `resources/views/events/index.blade.php`
- Test: `tests/Feature/PublicCatalogTest.php`; sửa `tests/Feature/EventBookingFlowTest.php`

**Interfaces:**
- Consumes: `Event::published()`, `$event->base_price`, `TicketTiers::for()`, dữ liệu seed (Task 2)
- Produces: `events.index` nhận query `q` (≤100 ký tự, tìm trong tiêu đề + mô tả) và `category` (id), phân trang 9/trang, giữ query string. View nhận `$events` (paginator), `$categories`, `$query`, `$categoryId`.
- Produces: `events.show` truyền `$event`/`$movie` (model), `$showtimes` (**array** các suất tương lai, có `room`), `$ticketTiers`. Sự kiện không `published` → 404.

- [ ] **Step 1: Viết test thất bại `tests/Feature/PublicCatalogTest.php`**

```php
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
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=PublicCatalogTest`
Expected: FAIL (controller vẫn đọc `DemoCatalog`).

- [ ] **Step 3: `app/Http/Controllers/HomeController.php` (thay toàn bộ)**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $events = Event::published()
            ->with('category')
            ->withMin('showtimes', 'base_price')
            ->latest('release_date')
            ->take(8)
            ->get();

        return view('home', ['movies' => $events->all()]);
    }
}
```

- [ ] **Step 4: `app/Http/Controllers/EventController.php` (thay toàn bộ)**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Support\TicketTiers;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $categoryId = $validated['category'] ?? null;

        $events = Event::published()
            ->with('category')
            ->withMin('showtimes', 'base_price')
            ->when($query !== '', fn ($builder) => $builder->where(function ($where) use ($query) {
                $where->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            }))
            ->when($categoryId, fn ($builder) => $builder->where('category_id', $categoryId))
            ->latest('release_date')
            ->paginate(9)
            ->withQueryString();

        return view('events.index', [
            'events' => $events,
            'movies' => $events,
            'categories' => Category::orderBy('name')->get(),
            'query' => $query,
            'categoryId' => $categoryId,
        ]);
    }

    public function show(string $slug): View
    {
        $event = Event::published()
            ->with('category')
            ->withMin('showtimes', 'base_price')
            ->where('slug', $slug)
            ->firstOrFail();

        $showtimes = $event->showtimes()
            ->with('room')
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->get();

        return view('events.show', [
            'event' => $event,
            'movie' => $event,
            // View đang dùng !empty($showtimes) và $showtimes[0] nên truyền array, không truyền Collection.
            'showtimes' => $showtimes->all(),
            'ticketTiers' => TicketTiers::for($event->base_price ?? 180000, $event->is_seated),
        ]);
    }
}
```

- [ ] **Step 5: Sửa view dùng `poster_url` và thêm phân trang**

1. `resources/views/home.blade.php` dòng 67: `{{ $leadEvent->poster_path }}` → `{{ $leadEvent->poster_url }}`.
2. `resources/views/events/show.blade.php` dòng 24: `{{ $movie->poster_path }}` → `{{ $movie->poster_url }}`.
3. `resources/views/components/event-card.blade.php` dòng 12: thay cả biểu thức trong `src="..."` bằng `{{ $item->poster_url }}`.
4. `resources/views/events/index.blade.php`: ngay sau thẻ `</div>` đóng lưới chứa
   `@foreach ($eventList as $event) ... @endforeach` (khoảng dòng 87), trong nhánh `@else`
   của `@if ($eventList->isEmpty())`, thêm:

```blade
            <div class="mt-10">
                {{ $eventList->links('vendor.pagination.custom') }}
            </div>
```

- [ ] **Step 6: Sửa 3 test đầu của `tests/Feature/EventBookingFlowTest.php` sang dữ liệu seed**

Thay `use App\Support\DemoCatalog;` ở đầu file bằng:

```php
use App\Models\Event;

beforeEach(fn () => $this->seed());
```

Trong 2 test chi tiết sự kiện (`event details page displays ...` và `fan meeting details page ...`),
đổi dòng `$movie = DemoCatalog::movieBySlug('<slug>');` thành `$movie = Event::firstWhere('slug', '<slug>');`
(giữ nguyên slug và toàn bộ `assertSee`). Hai test về trang chọn ghế sửa ở Task 4.

- [ ] **Step 7: Chạy test**

Run: `php artisan test --filter="PublicCatalogTest|EventBookingFlowTest"`
Expected: `PublicCatalogTest` PASS; trong `EventBookingFlowTest` chỉ 2 test trang chọn ghế còn FAIL (vì còn gọi `DemoCatalog::showtimesForMovie`) — sửa ở Task 4.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/HomeController.php app/Http/Controllers/EventController.php resources/views/home.blade.php resources/views/events resources/views/components/event-card.blade.php tests/Feature/PublicCatalogTest.php tests/Feature/EventBookingFlowTest.php
git commit -m "feat: serve home, event list and event detail pages from the database"
```

---

### Task 4: Trang chọn ghế đọc DB + sơ đồ khán phòng thường

**Files:**
- Create: `app/Support/SeatMapPresenter.php`
- Modify: `app/Http/Controllers/ShowtimeController.php`
- Modify: `resources/views/components/seat-map.blade.php` (khối `@php` dòng 3–22, container zoom dòng ~141–655)
- Modify: `resources/views/showtimes/seats.blade.php` (dòng 34)
- Test: `tests/Feature/SeatMapPageTest.php`; sửa `tests/Feature/EventBookingFlowTest.php`

**Interfaces:**
- Consumes: `TicketTiers::for()`, `TicketTiers::normalizeSeatType()`, `ShowtimeSeat::publicStatus()` (Task 1)
- Produces: `SeatMapPresenter::forShowtime(Showtime $showtime): list<object>` — mỗi object có
  `id` (= **id của `showtime_seats`**, Plan 3 gửi id này khi giữ ghế), `showtime_id`, `block`, `sector`
  (`floor|center_stand|upper_stand|left_stand|right_stand|skybox|general`), `sector_label`, `row_label`,
  `seat_number`, `code`, `type`, `type_name`, `type_badge`, `type_icon`, `gate`, `price`, `status`
  (`available|held|booked`), `perks`. Ghế `standing_pit` không nằm trong danh sách (bán theo số lượng).
- Produces: route `showtimes.seats` bind `Showtime` theo id; sự kiện không `published` → 404.

- [ ] **Step 1: Viết test thất bại `tests/Feature/SeatMapPageTest.php`**

```php
<?php

use App\Models\Event;
use App\Models\Showtime;

beforeEach(fn () => $this->seed());

function firstShowtimeOf(string $slug): Showtime
{
    return Event::firstWhere('slug', $slug)->showtimes()->orderBy('start_time')->first();
}

test('stadium showtime renders the stadium blueprint with real seats', function () {
    $showtime = firstShowtimeOf('live-concert-anh-trai-vuot-ngan-chong-gai-2026');

    $this->get(route('showtimes.seats', $showtime))
        ->assertOk()
        ->assertSee('SÂN KHẤU CHÍNH')
        ->assertSee('Khán Đài A')
        ->assertSee('Skybox VIP Suites')
        ->assertViewHas('seats', fn (array $seats) => count($seats) === 212
            && $seats[0]->id === $showtime->showtimeSeats()->orderBy('id')->value('id')
            && collect($seats)->doesntContain('type', 'standing_pit'));
});

test('theater showtime renders the plain seat grid instead of the stadium', function () {
    $showtime = firstShowtimeOf('hoa-nhac-giao-huong-saigon-philharmonic');

    $this->get(route('showtimes.seats', $showtime))
        ->assertOk()
        ->assertSee('ST-1')
        ->assertDontSee('CATWALK RUNWAY')
        ->assertViewHas('seats', fn (array $seats) => collect($seats)->every(fn ($seat) => $seat->sector === 'general'));
});

test('sold seats are reported as booked', function () {
    $showtime = firstShowtimeOf('hoa-nhac-giao-huong-saigon-philharmonic'); // có đơn TBX-89214

    $this->get(route('showtimes.seats', $showtime))
        ->assertViewHas('seats', fn (array $seats) => collect($seats)->where('status', 'booked')->count() === 2);
});

test('an expired hold is offered as available', function () {
    $showtime = firstShowtimeOf('nhac-kich-les-miserables-vn');
    $seat = $showtime->showtimeSeats()->where('status', 'available')->first();
    $seat->update(['status' => 'held', 'held_until' => now()->subMinute()]);

    $this->get(route('showtimes.seats', $showtime))
        ->assertViewHas('seats', fn (array $seats) => collect($seats)->firstWhere('id', $seat->id)->status === 'available');
});

test('non seated events show the ticket tier selector', function () {
    $this->get(route('showtimes.seats', firstShowtimeOf('vietnam-tech-summit-ai-expo-2026')))
        ->assertOk()
        ->assertSee('Chọn Hạng Vé & Số Lượng Tham Dự')
        ->assertSee('Vé Tiêu Chuẩn')
        ->assertViewHas('seats', []);
});

test('showtimes of unpublished events are hidden', function () {
    $showtime = firstShowtimeOf('fan-meeting-running-man-vn-2026');
    $showtime->event->update(['status' => 'draft']);

    $this->get(route('showtimes.seats', $showtime))->assertNotFound();
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=SeatMapPageTest`
Expected: FAIL (controller vẫn đọc `DemoCatalog`).

- [ ] **Step 3: Tạo `app/Support/SeatMapPresenter.php`**

```php
<?php

namespace App\Support;

use App\Models\Showtime;
use App\Models\ShowtimeSeat;

/**
 * Chuyển showtime_seats thành object đúng dạng mà <x-seat-map> đang render.
 */
final class SeatMapPresenter
{
    /** @return list<object> */
    public static function forShowtime(Showtime $showtime): array
    {
        $showtime->loadMissing('room');
        $tiers = TicketTiers::for($showtime->base_price, true);
        $isStadium = $showtime->room->layout_preset === 'mega_concert';

        return $showtime->showtimeSeats()
            ->with('seat')
            ->orderBy('id')
            ->get()
            ->reject(fn (ShowtimeSeat $showtimeSeat) => $showtimeSeat->seat->type === 'standing_pit')
            ->map(function (ShowtimeSeat $showtimeSeat) use ($tiers, $isStadium, $showtime) {
                $seat = $showtimeSeat->seat;
                $type = TicketTiers::normalizeSeatType($seat->type);
                $tier = $tiers[$type] ?? $tiers['cat2_wings'];
                [$sector, $sectorLabel] = self::sector($seat->row_label, $isStadium);

                return (object) [
                    'id' => $showtimeSeat->id,
                    'showtime_id' => $showtime->id,
                    'block' => $seat->row_label,
                    'sector' => $sector,
                    'sector_label' => $sectorLabel,
                    'row_label' => $seat->row_label,
                    'seat_number' => $seat->seat_number,
                    'code' => $seat->row_label.'-'.str_pad((string) $seat->seat_number, 2, '0', STR_PAD_LEFT),
                    'type' => $type,
                    'type_name' => $tier['name'],
                    'type_badge' => $tier['badge'],
                    'type_icon' => $tier['icon'],
                    'gate' => $tier['gate'],
                    'price' => (int) $tier['price'],
                    'status' => $showtimeSeat->publicStatus(),
                    'perks' => $tier['perks'],
                ];
            })
            ->values()
            ->all();
    }

    /** @return array{0: string, 1: string} [sector, nhãn hiển thị] */
    private static function sector(string $row, bool $isStadium): array
    {
        if (! $isStadium) {
            return ['general', "Hàng {$row}"];
        }

        return match (true) {
            str_starts_with($row, 'SVIP') => ['floor', 'SVIP B-Stage Hàng '.substr($row, -1)],
            str_starts_with($row, 'FL') => ['floor', 'VIP Floor Hàng '.substr($row, 3)],
            str_starts_with($row, 'SB') => ['skybox', 'Skybox VIP Suites (Tầng Thượng)'],
            str_starts_with($row, 'A') => ['center_stand', 'Khán Đài A Hàng '.substr($row, 1).' (Trung Tâm Tầng 1)'],
            str_starts_with($row, 'B') => ['upper_stand', 'Khán Đài B Hàng '.substr($row, 1).' (Trung Tâm Tầng 2)'],
            str_starts_with($row, 'C') => ['left_stand', 'Khán Đài C Hàng '.substr($row, 1).' (Cánh Trái)'],
            str_starts_with($row, 'D') => ['right_stand', 'Khán Đài D Hàng '.substr($row, 1).' (Cánh Phải)'],
            default => ['general', "Hàng {$row}"],
        };
    }
}
```

- [ ] **Step 4: `app/Http/Controllers/ShowtimeController.php` (thay toàn bộ)**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Showtime;
use App\Support\SeatMapPresenter;
use App\Support\TicketTiers;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function seats(Showtime $showtime): View
    {
        $showtime->load(['room', 'event.category']);
        $event = $showtime->event;

        abort_unless($event->status === 'published', 404);

        return view('showtimes.seats', [
            'showtime' => $showtime,
            'movie' => $event,
            'seats' => $event->is_seated ? SeatMapPresenter::forShowtime($showtime) : [],
            'ticketTiers' => TicketTiers::for($showtime->base_price, $event->is_seated),
        ]);
    }
}
```

Trong `resources/views/showtimes/seats.blade.php` dòng 34: `{{ $movie->poster_path }}` → `{{ $movie->poster_url }}`.

- [ ] **Step 5: Sửa khối `@php` đầu `resources/views/components/seat-map.blade.php` (dòng 3–22)**

Thay toàn bộ khối `@php ... @endphp` bằng:

```blade
@php
    $basePrice = $showtime->base_price ?? 180000;
    $isSeatedConcert = $movie->is_seated_concert ?? true;
    if (empty($ticketTiers)) {
        $ticketTiers = \App\Support\TicketTiers::for($basePrice, $isSeatedConcert);
    }
    $seatsCollection = collect($seats);
    $totalAvailable = $seatsCollection->where('status', 'available')->count();
    $totalBooked = $seatsCollection->where('status', 'booked')->count();
    $totalHeld = $seatsCollection->where('status', 'held')->count();

    // Sân vận động (preset mega_concert): nhóm theo khu
    $svipSeats = $seatsCollection->where('sector', 'floor')->where('type', 'svip_diamond');
    $vipFloorSeats = $seatsCollection->where('sector', 'floor')->where('type', 'vip_gold')->groupBy('row_label');
    $standASeats = $seatsCollection->where('sector', 'center_stand')->groupBy('row_label');
    $standBSeats = $seatsCollection->where('sector', 'upper_stand')->groupBy('row_label');
    $standCSeats = $seatsCollection->where('sector', 'left_stand');
    $standDSeats = $seatsCollection->where('sector', 'right_stand');
    $skyboxSeats = $seatsCollection->where('sector', 'skybox');

    // Khán phòng thường (nhà hát / hội nghị / lưới tùy chỉnh): vẽ lưới theo hàng
    $generalSeats = $seatsCollection->where('sector', 'general')->groupBy('row_label');
    $isStadiumLayout = $generalSeats->isEmpty();
@endphp
```

- [ ] **Step 6: Bọc sơ đồ sân vận động và thêm sơ đồ khán phòng thường**

Trong container zoom (`<!-- ZOOM CONTAINER -->`, ~dòng 140):
1. Chèn dòng `@if ($isStadiumLayout)` ngay **trước** comment `<!-- 1. MAIN STAGE + EXTENDED T-CATWALK RUNWAY -->`.
2. Tìm thẻ `</div>` đóng container zoom — là thẻ `</div>` đứng một mình ngay **trước** comment
   `<!-- LIVE FLOATING SEAT & SECTOR DETAIL INSPECTOR -->` (~dòng 655). Chèn ngay **trước** thẻ đó:

```blade
                @else
                    <!-- SƠ ĐỒ KHÁN PHÒNG THƯỜNG (nhà hát / hội nghị / lưới tùy chỉnh) -->
                    <div class="w-full space-y-6">
                        <div class="mx-auto w-2/3 py-3 rounded-b-[2rem] bg-gradient-to-b from-gold-antique/40 to-transparent border-t-4 border-gold-antique text-center text-[11px] font-black tracking-[0.3em] text-gold-light">
                            SÂN KHẤU
                        </div>

                        <div class="space-y-2">
                            @foreach ($generalSeats as $rowLabel => $rowSeats)
                                <div class="flex items-center justify-center gap-1.5">
                                    <span class="w-12 text-right pr-2 text-[10px] font-mono text-gray-400 font-bold">{{ $rowLabel }}</span>
                                    @foreach ($rowSeats as $seat)
                                        @php
                                            $isBooked = $seat->status === 'booked';
                                            $isHeld = $seat->status === 'held';
                                            $hoverData = [
                                                'code' => $seat->code,
                                                'row' => $seat->row_label,
                                                'number' => (string) $seat->seat_number,
                                                'sector' => $seat->sector_label,
                                                'gate' => $seat->gate,
                                                'type' => $seat->type_name,
                                                'icon' => $seat->type_icon,
                                                'price' => $seat->price,
                                                'status' => $seat->status,
                                                'perks' => $seat->perks,
                                            ];
                                        @endphp
                                        <button
                                            type="button"
                                            @disabled($isBooked || $isHeld)
                                            @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js($seat->row_label), @js($seat->type), @js($seat->type_name), @js($seat->status), {{ $seat->price }}, @js($seat->perks))"
                                            @mouseenter="setHoveredSeat(@js($hoverData))"
                                            @mouseleave="clearHoveredSeat()"
                                            :class="{
                                                'bg-gold-antique text-black font-black scale-110 z-10': isSeatSelected({{ $seat->id }}),
                                                @if ($isBooked)
                                                    'bg-gray-800 text-gray-600 border-gray-700 cursor-not-allowed opacity-30': true,
                                                @elseif ($isHeld)
                                                    'bg-amber-900/60 border-amber-500 text-amber-300 cursor-not-allowed': true,
                                                @else
                                                    'bg-[#F0F7F2] text-black border border-[#A3C9A8] hover:bg-emerald-200 hover:scale-110 cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                                @endif
                                            }"
                                            class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[10px] font-black flex items-center justify-center transition-all"
                                            title="{{ $seat->type_name }} · {{ number_format($seat->price) }}₫"
                                        >
                                            {{ $seat->seat_number }}
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
```

Kiểm tra cân thẻ: `php artisan view:cache` không báo lỗi; mở trang theater và stadium bằng trình duyệt thấy đúng sơ đồ.

- [ ] **Step 7: Sửa 2 test trang chọn ghế trong `tests/Feature/EventBookingFlowTest.php`**

Trong test `seat map page displays concert stadium arena blueprint for seated concerts`, thay 2 dòng đầu bằng:

```php
    $movie = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026');
    $showtime = $movie->showtimes()->orderBy('start_time')->first();
```

Trong test `ticket selection page renders direct tier selector for non-concert events`, thay 2 dòng đầu bằng:

```php
    $movie = Event::firstWhere('slug', 'vietnam-tech-summit-ai-expo-2026');
    $showtime = $movie->showtimes()->orderBy('start_time')->first();
```

Giữ nguyên các `assertSee`. Nếu `assertSee('SVIP B-Stage Floor')` hoặc `assertSee('GA STANDING')` fail
thì mở view kiểm tra chuỗi đó có nằm trong nhánh `@if ($isStadiumLayout)` không — phải có vì đây là phòng sân vận động.

- [ ] **Step 8: Chạy test + build**

Run: `php artisan test && npm run build`
Expected: PASS toàn bộ.

- [ ] **Step 9: Kiểm tra tay**

`php artisan serve --port=8080` → mở 1 concert (sân vận động), 1 hòa nhạc (nhà hát), 1 hội thảo (chọn hạng vé).
Di chuột trên ghế thấy khung thông tin, bấm chọn ghế thấy thanh "Tổng Tạm Tính" cộng đúng giá.
(Nút "Xác Nhận Giữ Chỗ" vẫn chỉ chuyển sang giỏ vé giả — Plan 3 xử lý.)

- [ ] **Step 10: Commit**

```bash
git add app/Support/SeatMapPresenter.php app/Http/Controllers/ShowtimeController.php resources/views/components/seat-map.blade.php resources/views/showtimes/seats.blade.php tests/Feature/SeatMapPageTest.php tests/Feature/EventBookingFlowTest.php
git commit -m "feat: render seat maps from showtime seats, add plain theater layout"
```

---

### Task 5: Tài liệu + PR

- [ ] **Step 1: Cập nhật `docs/architecture.md`**

Thêm vào bảng thay đổi:

```markdown
| **2026-09-21** | **Giá vé** | `App\Support\TicketTiers` là nguồn giá duy nhất: giá hạng = giá cơ bản của suất + phụ thu hạng; ghế cũ `normal`/`vip` ánh xạ sang `cat2_wings`/`vip_gold` | Trước đó giá hiển thị (DemoCatalog) và giá tính (`+25000` cho VIP) lệch nhau. |
| **2026-09-21** | **Sơ đồ ghế** | Phòng `mega_concert` vẽ sân vận động; các preset khác vẽ lưới theo hàng | Sơ đồ sân vận động chỉ đúng với bố cục khu SVIP/FL/A/B/C/D/SB. |
```

Sửa mục "Tiến độ hoàn thành": thêm `- **Public catalog (Plan 2)**: trang chủ, danh sách (tìm kiếm/lọc/phân trang), chi tiết sự kiện, sơ đồ ghế đọc DB.`

- [ ] **Step 2: Kiểm tra còn chỗ nào dùng DemoCatalog ngoài giỏ vé/lịch sử**

Run: `grep -rn "DemoCatalog" app resources tests`
Expected: chỉ còn `app/Support/DemoCatalog.php`, `CartController.php`, `BookingController.php`.

- [ ] **Step 3: Test + commit + PR**

```bash
php artisan test
git add docs/architecture.md
git commit -m "docs: record pricing and seat map decisions"
git push -u origin feature/public-catalog-db
gh pr create --base main --title "Plan 2: Trang public đọc dữ liệu thật" --body "Theo docs/superpowers/plans/2026-09-21-p2-public-catalog-db.md. Output php artisan test: <dán vào đây>"
```
