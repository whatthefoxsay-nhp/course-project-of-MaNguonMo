# Plan 3 — Engine đặt vé: giữ ghế AJAX, giỏ vé, thanh toán giả lập, lịch sử

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Khách chọn ghế (hoặc hạng vé + số lượng) → giữ chỗ 10 phút bằng AJAX, không thể
có 2 người cùng giữ/mua một ghế → giỏ vé đọc từ DB → thanh toán giả lập tạo `bookings` thật →
vé hiện trong lịch sử và trong trang admin. Xóa hẳn `App\Support\DemoCatalog`.

**Architecture:** Theo spec mục 3: **không có bảng giỏ hàng** — một dòng `showtime_seats`
`status = held` + `held_by_user_id` + `held_until` chính là giỏ của user. `SeatHoldService`
làm mọi thao tác giữ/nhả trong transaction với `lockForUpdate()` (tất cả hoặc không gì cả).
Giá được chốt vào `showtime_seats.price_override` tại lúc giữ và xóa khi nhả. `CheckoutService`
biến ghế đang giữ thành `booking` + `booking_items`. Ghế giữ quá hạn được coi là trống ngay
(không phụ thuộc cron), còn lệnh `seats:release-expired` chạy mỗi phút để dọn dữ liệu.
Mỗi user chỉ giữ ghế của **một suất diễn** tại một thời điểm (giữ suất mới sẽ nhả suất cũ),
nên mỗi booking luôn thuộc đúng một suất.

**Tech Stack:** Laravel 11 (DB transaction, pessimistic lock, scheduler), Pest 4, Alpine.js + `fetch()`.

**Spec:** `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md` (mục 3 "Xử lý tránh double-booking", mục 4, mục 5, Phase 2 + 3)
**Lộ trình:** `docs/superpowers/plans/2026-09-21-roadmap-hoan-thien.md`

## Global Constraints

- Nhánh: `feature/booking-engine`, rẽ từ `main` **sau khi Plan 2 đã merge**. Mỗi task = 1 commit.
- Cần từ Plan 1–2: `RoomSeatGenerator`, `ShowtimeSeatGenerator`, `TicketTiers` (`seatPrice`, `isPassTier`,
  `passSeatTypes`, `seatsPerUnit`, `pricePerSeat`, `for`), `ShowtimeSeat::publicStatus()`,
  `SeatMapPresenter` (object ghế có `id` = id `showtime_seats`), seeder (đơn `TBX-89214` của `user@ticketbox.vn`),
  helper test `createAdmin()`/`createCustomer()`, factories.
- Không tích hợp cổng thanh toán thật (spec mục 5). Không dùng WebSocket — đồng bộ trạng thái ghế bằng polling 15 giây.
- Thời gian giữ ghế: **10 phút**. Tối đa **8 chỗ** mỗi lượt.
- Mọi endpoint AJAX trả JSON `{ "message": "..." }` bằng tiếng Việt khi lỗi (HTTP 422), nằm trong middleware `auth` (khách chưa đăng nhập nhận 401).
- DB test là SQLite (bỏ qua `lockForUpdate`) — test chống đặt trùng kiểm tra bằng 2 request nối tiếp; khóa thật chạy trên MySQL.
- Chạy `npm run build` sau mỗi task có sửa JS. `php artisan test` xanh trước PR.

## File Structure

| File | Trách nhiệm |
|---|---|
| `database/migrations/2026_09_21_000002_add_payment_method_to_bookings_table.php` (mới) | Lưu phương thức thanh toán giả lập |
| `app/Exceptions/SeatHoldException.php` (mới) | Lỗi nghiệp vụ giữ ghế/checkout, message hiển thị thẳng cho khách |
| `app/Models/ShowtimeSeat.php` (sửa) | Scope `holdable`, `heldBy`; `isHoldable()` |
| `app/Models/Booking.php` (sửa) | Mã đơn không trùng, `payment_method` |
| `app/Services/SeatHoldService.php` (mới) | Giữ / nhả / nhả hết hạn |
| `app/Services/CheckoutService.php` (mới) | Ghế đang giữ → đơn đặt vé |
| `app/Console/Commands/ReleaseExpiredSeatHolds.php` (mới), `routes/console.php` (sửa) | Dọn ghế hết hạn mỗi phút |
| `app/Http/Requests/{HoldSeatsRequest,CheckoutRequest}.php` (mới) | Validation |
| `app/Http/Controllers/{SeatHoldController,CheckoutController}.php` (mới) | Endpoint JSON |
| `app/Http/Controllers/{ShowtimeController,CartController,BookingController}.php` (sửa) | Trạng thái ghế, giỏ, lịch sử từ DB |
| `resources/js/app.js` (sửa) | `jsonHeaders()`, giữ chỗ, polling, bỏ vé, thanh toán |
| `resources/views/components/seat-map.blade.php`, `cart/index.blade.php`, `bookings/history.blade.php` (sửa) | Nối AJAX |
| `app/Support/DemoCatalog.php` (xóa) | |

---

### Task 1: `SeatHoldService` — giữ ghế an toàn, tất cả hoặc không

**Files:**
- Create: `database/migrations/2026_09_21_000002_add_payment_method_to_bookings_table.php`
- Create: `app/Exceptions/SeatHoldException.php`
- Modify: `app/Models/ShowtimeSeat.php`
- Create: `app/Services/SeatHoldService.php`
- Test: `tests/Feature/SeatHoldServiceTest.php`

**Interfaces:**
- Produces: `App\Exceptions\SeatHoldException extends RuntimeException`
- Produces: `ShowtimeSeat::scopeHoldable(Builder)` (trống, hoặc đang giữ nhưng đã quá hạn), `ShowtimeSeat::scopeHeldBy(Builder, User)` (đang giữ, còn hạn, của user), `ShowtimeSeat::isHoldable(): bool`
- Produces: `SeatHoldService::HOLD_MINUTES = 10`, `SeatHoldService::MAX_SEATS = 8`
- Produces: `SeatHoldService::hold(User $user, Showtime $showtime, array $showtimeSeatIds, array $tierQuantities = []): Collection<int, ShowtimeSeat>` — ném `SeatHoldException`
- Produces: `SeatHoldService::release(User $user, ShowtimeSeat $seat): void`, `SeatHoldService::releaseExpired(): int`
- Produces: cột `bookings.payment_method` (string 20, nullable) — dùng ở Task 3

- [ ] **Step 1: Viết test thất bại `tests/Feature/SeatHoldServiceTest.php`**

```php
<?php

use App\Exceptions\SeatHoldException;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\RoomSeatGenerator;
use App\Services\SeatHoldService;
use App\Services\ShowtimeSeatGenerator;

/** Suất diễn có ghế, phòng lưới 3 hàng x 4 ghế (hàng A,B = svip_diamond, C = vip_gold), giá cơ bản 200.000đ. */
function seatedShowtime(string $preset = 'custom_grid', array $eventAttributes = []): Showtime
{
    $room = Room::factory()->create(['layout_preset' => $preset]);
    app(RoomSeatGenerator::class)->generate($room, $preset, ['rows' => 3, 'cols' => 4]);
    $event = Event::factory()->create(array_merge(['is_seated' => true], $eventAttributes));
    $showtime = Showtime::factory()->for($room)->for($event)->create(['base_price' => 200000]);
    app(ShowtimeSeatGenerator::class)->generate($showtime);

    return $showtime;
}

function seatIds(Showtime $showtime, int $count): array
{
    return $showtime->showtimeSeats()->orderBy('id')->take($count)->pluck('id')->all();
}

function holds(): SeatHoldService
{
    return app(SeatHoldService::class);
}

test('holding seats marks them held for ten minutes at the tier price', function () {
    $showtime = seatedShowtime();
    $user = createCustomer();

    $held = holds()->hold($user, $showtime, seatIds($showtime, 2));

    expect($held)->toHaveCount(2);
    $held->each(function (ShowtimeSeat $seat) use ($user) {
        $seat->refresh();
        expect($seat->status)->toBe('held')
            ->and($seat->held_by_user_id)->toBe($user->id)
            ->and($seat->held_until->between(now()->addMinutes(9), now()->addMinutes(11)))->toBeTrue()
            ->and($seat->price_override)->toBe(350000); // svip_diamond = 200.000 + 150.000
    });
});

test('a seat held by someone else cannot be held again (no double booking)', function () {
    $showtime = seatedShowtime();
    [$seatId] = seatIds($showtime, 1);
    $first = createCustomer();

    holds()->hold($first, $showtime, [$seatId]);

    expect(fn () => holds()->hold(createCustomer(), $showtime, [$seatId]))
        ->toThrow(SeatHoldException::class, 'vừa có người giữ');

    expect(ShowtimeSeat::find($seatId)->held_by_user_id)->toBe($first->id);
});

test('holding is all or nothing', function () {
    $showtime = seatedShowtime();
    [$free, $taken] = seatIds($showtime, 2);
    holds()->hold(createCustomer(), $showtime, [$taken]);

    expect(fn () => holds()->hold(createCustomer(), $showtime, [$free, $taken]))
        ->toThrow(SeatHoldException::class);

    expect(ShowtimeSeat::find($free)->status)->toBe('available');
});

test('an expired hold can be taken by another user', function () {
    $showtime = seatedShowtime();
    [$seatId] = seatIds($showtime, 1);
    ShowtimeSeat::whereKey($seatId)->update(['status' => 'held', 'held_by_user_id' => createCustomer()->id, 'held_until' => now()->subMinute()]);
    $second = createCustomer();

    holds()->hold($second, $showtime, [$seatId]);

    expect(ShowtimeSeat::find($seatId)->held_by_user_id)->toBe($second->id);
});

test('booked seats cannot be held', function () {
    $showtime = seatedShowtime();
    [$seatId] = seatIds($showtime, 1);
    ShowtimeSeat::whereKey($seatId)->update(['status' => 'booked']);

    expect(fn () => holds()->hold(createCustomer(), $showtime, [$seatId]))->toThrow(SeatHoldException::class);
});

test('at most eight seats per user and showtime', function () {
    $showtime = seatedShowtime();
    $user = createCustomer();
    holds()->hold($user, $showtime, seatIds($showtime, 6));

    $nextThree = $showtime->showtimeSeats()->where('status', 'available')->orderBy('id')->take(3)->pluck('id')->all();

    expect(fn () => holds()->hold($user, $showtime, $nextThree))->toThrow(SeatHoldException::class, 'tối đa 8');
});

test('holding in a new showtime releases the previous one', function () {
    $first = seatedShowtime();
    $second = seatedShowtime();
    $user = createCustomer();
    [$oldSeat] = seatIds($first, 1);

    holds()->hold($user, $first, [$oldSeat]);
    holds()->hold($user, $second, seatIds($second, 1));

    expect(ShowtimeSeat::find($oldSeat)->status)->toBe('available')
        ->and(ShowtimeSeat::find($oldSeat)->price_override)->toBeNull();
});

test('seats of another showtime are rejected', function () {
    $showtime = seatedShowtime();
    $other = seatedShowtime();

    expect(fn () => holds()->hold(createCustomer(), $showtime, seatIds($other, 1)))
        ->toThrow(SeatHoldException::class, 'không thuộc suất diễn');
});

test('a showtime that already started cannot be booked', function () {
    $showtime = seatedShowtime();
    $showtime->update(['start_time' => now()->subMinute()]);

    expect(fn () => holds()->hold(createCustomer(), $showtime, seatIds($showtime, 1)))
        ->toThrow(SeatHoldException::class, 'đã bắt đầu');
});

test('non seated events sell passes and the server assigns seats', function () {
    $showtime = seatedShowtime('convention_center', ['is_seated' => false]);
    $user = createCustomer();

    $held = holds()->hold($user, $showtime, [], ['vip_pass' => 2, 'combo_pass' => 1]);

    expect($held)->toHaveCount(4)
        ->and($held->where('price_override', 300000)->count())->toBe(2)  // vip_pass = 200.000 + 100.000
        ->and($held->where('price_override', 170000)->count())->toBe(2); // combo 2 người = 340.000 / 2
});

test('non seated events reject hand picked seats and unknown tiers', function () {
    $showtime = seatedShowtime('convention_center', ['is_seated' => false]);

    expect(fn () => holds()->hold(createCustomer(), $showtime, seatIds($showtime, 1)))->toThrow(SeatHoldException::class)
        ->and(fn () => holds()->hold(createCustomer(), $showtime, [], ['svip_diamond' => 1]))->toThrow(SeatHoldException::class, 'Hạng vé không hợp lệ');
});

test('a pass tier fails when there are not enough seats left', function () {
    $showtime = seatedShowtime('convention_center', ['is_seated' => false]);
    $showtime->showtimeSeats()->update(['status' => 'booked']);

    expect(fn () => holds()->hold(createCustomer(), $showtime, [], ['standard_pass' => 1]))
        ->toThrow(SeatHoldException::class, 'chỉ còn 0 chỗ');
});

test('standing tickets are assigned from the hidden standing row', function () {
    $showtime = seatedShowtime('mega_concert');

    $held = holds()->hold(createCustomer(), $showtime, [], ['standing_pit' => 3]);

    expect($held)->toHaveCount(3)
        ->and($held->every(fn ($seat) => $seat->seat->type === 'standing_pit'))->toBeTrue();
});

test('a user can release only their own hold', function () {
    $showtime = seatedShowtime();
    $owner = createCustomer();
    $held = holds()->hold($owner, $showtime, seatIds($showtime, 1))->first();

    expect(fn () => holds()->release(createCustomer(), $held))->toThrow(SeatHoldException::class);

    holds()->release($owner, $held);
    expect($held->fresh()->status)->toBe('available');
});

test('expired holds are released in bulk', function () {
    $showtime = seatedShowtime();
    [$expired, $active] = seatIds($showtime, 2);
    ShowtimeSeat::whereKey($expired)->update(['status' => 'held', 'held_until' => now()->subMinute(), 'price_override' => 1]);
    ShowtimeSeat::whereKey($active)->update(['status' => 'held', 'held_until' => now()->addMinute()]);

    expect(holds()->releaseExpired())->toBe(1)
        ->and(ShowtimeSeat::find($expired)->status)->toBe('available')
        ->and(ShowtimeSeat::find($expired)->price_override)->toBeNull()
        ->and(ShowtimeSeat::find($active)->status)->toBe('held');
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=SeatHoldServiceTest`
Expected: FAIL — `Class "App\Exceptions\SeatHoldException" not found`.

- [ ] **Step 3: Migration `payment_method`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Phương thức khách chọn ở bước thanh toán giả lập: vietqr | momo | zalopay | card
            $table->string('payment_method', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
```

Run: `php artisan migrate`

- [ ] **Step 4: `app/Exceptions/SeatHoldException.php`**

```php
<?php

namespace App\Exceptions;

use RuntimeException;

/** Lỗi nghiệp vụ khi giữ ghế / thanh toán. Message được trả thẳng cho khách. */
class SeatHoldException extends RuntimeException
{
}
```

- [ ] **Step 5: Scope trên `app/Models/ShowtimeSeat.php`**

Thêm `use Illuminate\Database\Eloquent\Builder;` và vào class:

```php
    /** Ghế có thể giữ: đang trống, hoặc đang giữ nhưng đã quá hạn. */
    public function scopeHoldable(Builder $query): Builder
    {
        return $query->where(function (Builder $where) {
            $where->where('showtime_seats.status', 'available')
                ->orWhere(fn (Builder $expired) => $expired
                    ->where('showtime_seats.status', 'held')
                    ->where('showtime_seats.held_until', '<', now()));
        });
    }

    /** Ghế đang nằm trong giỏ (còn hạn giữ) của user. */
    public function scopeHeldBy(Builder $query, User $user): Builder
    {
        return $query->where('showtime_seats.status', 'held')
            ->where('showtime_seats.held_by_user_id', $user->id)
            ->where('showtime_seats.held_until', '>', now());
    }

    public function isHoldable(): bool
    {
        return $this->publicStatus() === 'available';
    }
```

Và sửa docblock/ghi chú cột: thêm comment ngay trên `'price_override'` trong `$fillable`:
`// Giá chốt tại lúc khách giữ ghế (xóa khi nhả); booking_items.price lấy từ đây.`

- [ ] **Step 6: `app/Services/SeatHoldService.php`**

```php
<?php

namespace App\Services;

use App\Exceptions\SeatHoldException;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Support\TicketTiers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SeatHoldService
{
    public const HOLD_MINUTES = 10;

    public const MAX_SEATS = 8;

    private const RELEASED = [
        'status' => 'available',
        'held_by_user_id' => null,
        'held_until' => null,
        'price_override' => null,
    ];

    /**
     * Giữ chỗ cho user — tất cả hoặc không gì cả.
     *
     * @param  list<int>  $showtimeSeatIds  ghế khách tự chọn trên sơ đồ (id bảng showtime_seats)
     * @param  array<string, int>  $tierQuantities  hạng bán theo số lượng => số vé (server tự gán ghế)
     * @return Collection<int, ShowtimeSeat>
     *
     * @throws SeatHoldException
     */
    public function hold(User $user, Showtime $showtime, array $showtimeSeatIds, array $tierQuantities = []): Collection
    {
        $showtime->loadMissing('event');
        $event = $showtime->event;
        $tierQuantities = array_filter($tierQuantities, fn (int $quantity) => $quantity > 0);

        if ($event->status !== 'published') {
            throw new SeatHoldException('Sự kiện hiện không mở bán.');
        }
        if ($showtime->start_time->isPast()) {
            throw new SeatHoldException('Suất diễn đã bắt đầu, không thể đặt vé.');
        }
        if ($showtimeSeatIds !== [] && ! $event->is_seated) {
            throw new SeatHoldException('Sự kiện này không chọn ghế trên sơ đồ, vui lòng chọn hạng vé.');
        }
        foreach (array_keys($tierQuantities) as $tier) {
            if (! TicketTiers::isPassTier($tier, $event->is_seated)) {
                throw new SeatHoldException('Hạng vé không hợp lệ.');
            }
        }

        $requestedSeats = count($showtimeSeatIds);
        foreach ($tierQuantities as $tier => $quantity) {
            $requestedSeats += $quantity * TicketTiers::seatsPerUnit($tier);
        }
        if ($requestedSeats === 0) {
            throw new SeatHoldException('Vui lòng chọn ít nhất 1 vé.');
        }

        return DB::transaction(function () use ($user, $showtime, $event, $showtimeSeatIds, $tierQuantities, $requestedSeats) {
            // Mỗi user chỉ giữ ghế của 1 suất tại 1 thời điểm.
            ShowtimeSeat::where('held_by_user_id', $user->id)
                ->where('status', 'held')
                ->where('showtime_id', '!=', $showtime->id)
                ->update(self::RELEASED);

            $alreadyHeld = ShowtimeSeat::where('showtime_id', $showtime->id)->heldBy($user)->count();
            if ($alreadyHeld + $requestedSeats > self::MAX_SEATS) {
                throw new SeatHoldException('Mỗi lượt đặt tối đa '.self::MAX_SEATS." chỗ (bạn đang giữ {$alreadyHeld} chỗ).");
            }

            $heldUntil = now()->addMinutes(self::HOLD_MINUTES);
            $held = new Collection;

            // 1) Ghế khách tự chọn: khóa dòng rồi mới kiểm tra còn trống (chống 2 người cùng giữ).
            if ($showtimeSeatIds !== []) {
                $picked = ShowtimeSeat::with('seat')
                    ->where('showtime_id', $showtime->id)
                    ->whereIn('id', $showtimeSeatIds)
                    ->lockForUpdate()
                    ->get();

                if ($picked->count() !== count(array_unique($showtimeSeatIds))) {
                    throw new SeatHoldException('Có ghế không thuộc suất diễn này.');
                }
                if ($picked->contains(fn (ShowtimeSeat $seat) => $seat->seat->type === 'standing_pit')) {
                    throw new SeatHoldException('Vé đứng được bán theo số lượng, không chọn theo ghế.');
                }

                $unavailable = $picked->reject(fn (ShowtimeSeat $seat) => $seat->isHoldable());
                if ($unavailable->isNotEmpty()) {
                    $codes = $unavailable->map(fn (ShowtimeSeat $seat) => $seat->seat->full_code)->implode(', ');
                    throw new SeatHoldException("Ghế {$codes} vừa có người giữ hoặc đã bán. Vui lòng chọn ghế khác.");
                }

                foreach ($picked as $seat) {
                    $this->markHeld($seat, $user, $heldUntil, TicketTiers::seatPrice($seat->seat->type, $showtime->base_price));
                }
                $held = $held->merge($picked);
            }

            // 2) Hạng bán theo số lượng: server tự lấy ghế trống đúng loại (ghi ngay từng hạng
            //    để hạng sau không lấy trùng ghế của hạng trước).
            foreach ($tierQuantities as $tier => $quantity) {
                $needed = $quantity * TicketTiers::seatsPerUnit($tier);

                $assigned = ShowtimeSeat::select('showtime_seats.*')
                    ->join('seats', 'seats.id', '=', 'showtime_seats.seat_id')
                    ->where('showtime_seats.showtime_id', $showtime->id)
                    ->whereIn('seats.type', TicketTiers::passSeatTypes($tier))
                    ->holdable()
                    ->orderBy('showtime_seats.id')
                    ->limit($needed)
                    ->lockForUpdate()
                    ->get();

                if ($assigned->count() < $needed) {
                    $tierName = TicketTiers::for($showtime->base_price, $event->is_seated)[$tier]['name'];
                    throw new SeatHoldException("Hạng vé \"{$tierName}\" chỉ còn {$assigned->count()} chỗ.");
                }

                $pricePerSeat = TicketTiers::pricePerSeat($tier, $showtime->base_price, $event->is_seated);
                foreach ($assigned as $seat) {
                    $this->markHeld($seat, $user, $heldUntil, $pricePerSeat);
                }
                $held = $held->merge($assigned->load('seat'));
            }

            return $held;
        });
    }

    /** @throws SeatHoldException */
    public function release(User $user, ShowtimeSeat $seat): void
    {
        $released = ShowtimeSeat::whereKey($seat->id)
            ->where('status', 'held')
            ->where('held_by_user_id', $user->id)
            ->update(self::RELEASED);

        if ($released === 0) {
            throw new SeatHoldException('Ghế này không nằm trong giỏ vé của bạn.');
        }
    }

    public function releaseExpired(): int
    {
        return ShowtimeSeat::where('status', 'held')
            ->where('held_until', '<', now())
            ->update(self::RELEASED);
    }

    private function markHeld(ShowtimeSeat $seat, User $user, $heldUntil, int $price): void
    {
        $seat->update([
            'status' => 'held',
            'held_by_user_id' => $user->id,
            'held_until' => $heldUntil,
            'price_override' => $price,
        ]);
    }
}
```

- [ ] **Step 7: Chạy test**

Run: `php artisan test --filter=SeatHoldServiceTest`
Expected: PASS 15 test.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_09_21_000002_add_payment_method_to_bookings_table.php app/Exceptions/SeatHoldException.php app/Models/ShowtimeSeat.php app/Services/SeatHoldService.php tests/Feature/SeatHoldServiceTest.php
git commit -m "feat: add seat hold service with row locking and pass tier assignment"
```

---

### Task 2: Lệnh dọn ghế hết hạn + endpoint giữ/nhả/trạng thái ghế

**Files:**
- Create: `app/Console/Commands/ReleaseExpiredSeatHolds.php`
- Modify: `routes/console.php`
- Create: `app/Http/Requests/HoldSeatsRequest.php`
- Create: `app/Http/Controllers/SeatHoldController.php`
- Modify: `app/Http/Controllers/ShowtimeController.php` (thêm `seatStatus`)
- Modify: `routes/web.php`
- Test: `tests/Feature/SeatHoldHttpTest.php`

**Interfaces:**
- Consumes: `SeatHoldService` (Task 1)
- Produces: lệnh `php artisan seats:release-expired`, lên lịch mỗi phút
- Produces routes:
  - `POST /showtimes/{showtime}/holds` → `showtimes.holds.store` (auth, throttle 30/phút). Body JSON `{ "seat_ids": [int], "tiers": { "<tier>": int } }`. 200 → `{ message, held_until, redirect }`; 422 → `{ message }`; 401 nếu chưa đăng nhập.
  - `DELETE /cart/seats/{showtimeSeat}` → `cart.seats.destroy` (auth). 200 → `{ message }`; 422 nếu không phải ghế của mình.
  - `GET /showtimes/{showtime}/seat-status` → `showtimes.seat-status` (public). 200 → `{ "unavailable": [id, ...] }` (ghế đã bán + ghế đang giữ còn hạn)

- [ ] **Step 1: Viết test thất bại `tests/Feature/SeatHoldHttpTest.php`**

```php
<?php

use App\Models\Event;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;

beforeEach(fn () => $this->seed());

function concertShowtime(): Showtime
{
    return Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026')
        ->showtimes()->orderBy('start_time')->first();
}

function freeSeatIds(Showtime $showtime, int $count): array
{
    return $showtime->showtimeSeats()->where('status', 'available')->orderBy('id')->take($count)->pluck('id')->all();
}

test('guests must log in before holding seats', function () {
    $showtime = concertShowtime();

    $this->postJson(route('showtimes.holds.store', $showtime), ['seat_ids' => freeSeatIds($showtime, 1)])
        ->assertUnauthorized();
});

test('a customer holds seats and is sent to the cart', function () {
    $showtime = concertShowtime();
    $ids = freeSeatIds($showtime, 2);

    $this->actingAs(createCustomer())
        ->postJson(route('showtimes.holds.store', $showtime), ['seat_ids' => $ids])
        ->assertOk()
        ->assertJsonPath('redirect', route('cart.index'))
        ->assertJsonStructure(['message', 'held_until', 'redirect']);

    expect(ShowtimeSeat::whereIn('id', $ids)->where('status', 'held')->count())->toBe(2);
});

test('the second customer gets a clear conflict message', function () {
    $showtime = concertShowtime();
    $ids = freeSeatIds($showtime, 1);
    $this->actingAs(createCustomer())->postJson(route('showtimes.holds.store', $showtime), ['seat_ids' => $ids])->assertOk();

    $this->actingAs(createCustomer())
        ->postJson(route('showtimes.holds.store', $showtime), ['seat_ids' => $ids])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Ghế SVIP-A1 vừa có người giữ hoặc đã bán. Vui lòng chọn ghế khác.']);
});

test('an empty selection is rejected', function () {
    $this->actingAs(createCustomer())
        ->postJson(route('showtimes.holds.store', concertShowtime()), ['seat_ids' => [], 'tiers' => []])
        ->assertStatus(422);
});

test('standing tickets can be held by quantity', function () {
    $this->actingAs(createCustomer())
        ->postJson(route('showtimes.holds.store', concertShowtime()), ['tiers' => ['standing_pit' => 2]])
        ->assertOk();

    expect(ShowtimeSeat::where('status', 'held')->count())->toBe(2);
});

test('a customer removes a seat from their cart', function () {
    $showtime = concertShowtime();
    $user = createCustomer();
    [$id] = freeSeatIds($showtime, 1);
    $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime), ['seat_ids' => [$id]]);

    $this->actingAs($user)->deleteJson(route('cart.seats.destroy', $id))->assertOk();

    expect(ShowtimeSeat::find($id)->status)->toBe('available');
});

test('a customer cannot remove somebody else\'s seat', function () {
    $showtime = concertShowtime();
    [$id] = freeSeatIds($showtime, 1);
    $this->actingAs(createCustomer())->postJson(route('showtimes.holds.store', $showtime), ['seat_ids' => [$id]]);

    $this->actingAs(createCustomer())->deleteJson(route('cart.seats.destroy', $id))->assertStatus(422);

    expect(ShowtimeSeat::find($id)->status)->toBe('held');
});

test('seat status lists sold and actively held seats', function () {
    $showtime = concertShowtime();
    [$sold, $held, $expired] = freeSeatIds($showtime, 3);
    ShowtimeSeat::whereKey($sold)->update(['status' => 'booked']);
    ShowtimeSeat::whereKey($held)->update(['status' => 'held', 'held_until' => now()->addMinutes(5)]);
    ShowtimeSeat::whereKey($expired)->update(['status' => 'held', 'held_until' => now()->subMinute()]);

    $unavailable = $this->getJson(route('showtimes.seat-status', $showtime))->assertOk()->json('unavailable');

    expect($unavailable)->toContain($sold, $held)->not->toContain($expired);
});

test('the release command frees expired holds', function () {
    $showtime = concertShowtime();
    [$id] = freeSeatIds($showtime, 1);
    ShowtimeSeat::whereKey($id)->update(['status' => 'held', 'held_until' => now()->subMinute(), 'held_by_user_id' => User::first()->id]);

    $this->artisan('seats:release-expired')->assertSuccessful();

    expect(ShowtimeSeat::find($id)->status)->toBe('available');
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=SeatHoldHttpTest`
Expected: FAIL — route `showtimes.holds.store` chưa có.

- [ ] **Step 3: Lệnh `app/Console/Commands/ReleaseExpiredSeatHolds.php`**

```php
<?php

namespace App\Console\Commands;

use App\Services\SeatHoldService;
use Illuminate\Console\Command;

class ReleaseExpiredSeatHolds extends Command
{
    protected $signature = 'seats:release-expired';

    protected $description = 'Trả các ghế giữ quá hạn về trạng thái còn trống';

    public function handle(SeatHoldService $holds): int
    {
        $count = $holds->releaseExpired();
        $this->info("Đã nhả {$count} ghế hết hạn giữ.");

        return self::SUCCESS;
    }
}
```

Cuối `routes/console.php` thêm:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('seats:release-expired')->everyMinute();
```

(đặt dòng `use` lên đầu file cùng các `use` khác.)

- [ ] **Step 4: `app/Http/Requests/HoldSeatsRequest.php`**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class HoldSeatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'seat_ids' => ['nullable', 'array', 'max:8'],
            'seat_ids.*' => ['integer', 'distinct'],
            'tiers' => ['nullable', 'array', 'max:6'],
            'tiers.*' => ['integer', 'min:0', 'max:8'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->seatIds() === [] && array_sum($this->tierQuantities()) === 0) {
                    $validator->errors()->add('seat_ids', 'Vui lòng chọn ít nhất 1 vé.');
                }
            },
        ];
    }

    /** @return list<int> */
    public function seatIds(): array
    {
        return array_map('intval', $this->input('seat_ids', []) ?? []);
    }

    /** @return array<string, int> */
    public function tierQuantities(): array
    {
        return array_map('intval', $this->input('tiers', []) ?? []);
    }
}
```

- [ ] **Step 5: `app/Http/Controllers/SeatHoldController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Exceptions\SeatHoldException;
use App\Http\Requests\HoldSeatsRequest;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\SeatHoldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeatHoldController extends Controller
{
    public function store(HoldSeatsRequest $request, Showtime $showtime, SeatHoldService $holds): JsonResponse
    {
        try {
            $seats = $holds->hold($request->user(), $showtime, $request->seatIds(), $request->tierQuantities());
        } catch (SeatHoldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Đã giữ '.$seats->count().' chỗ trong '.SeatHoldService::HOLD_MINUTES.' phút.',
            'held_until' => $seats->first()->fresh()->held_until->toIso8601String(),
            'redirect' => route('cart.index'),
        ]);
    }

    public function destroy(Request $request, ShowtimeSeat $showtimeSeat, SeatHoldService $holds): JsonResponse
    {
        try {
            $holds->release($request->user(), $showtimeSeat);
        } catch (SeatHoldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Đã bỏ ghế khỏi giỏ vé.']);
    }
}
```

- [ ] **Step 6: `seatStatus` trong `app/Http/Controllers/ShowtimeController.php`**

Thêm `use Illuminate\Http\JsonResponse;` và method:

```php
    public function seatStatus(Showtime $showtime): JsonResponse
    {
        $unavailable = $showtime->showtimeSeats()
            ->where(fn ($query) => $query->where('status', 'booked')
                ->orWhere(fn ($held) => $held->where('status', 'held')->where('held_until', '>', now())))
            ->pluck('id');

        return response()->json(['unavailable' => $unavailable]);
    }
```

- [ ] **Step 7: Routes trong `routes/web.php`**

Thêm `use App\Http\Controllers\SeatHoldController;`. Ngay dưới route `showtimes.seats`:

```php
Route::get('/showtimes/{showtime}/seat-status', [ShowtimeController::class, 'seatStatus'])->name('showtimes.seat-status');
```

Trong group `Route::middleware('auth')->group(...)` đầu tiên (group chứa `cart.index`), thêm:

```php
    Route::post('/showtimes/{showtime}/holds', [SeatHoldController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('showtimes.holds.store');
    Route::delete('/cart/seats/{showtimeSeat}', [SeatHoldController::class, 'destroy'])->name('cart.seats.destroy');
```

- [ ] **Step 8: Chạy test**

Run: `php artisan test --filter="SeatHoldHttpTest|SeatHoldServiceTest"`
Expected: PASS toàn bộ.

- [ ] **Step 9: Commit**

```bash
git add app/Console/Commands/ReleaseExpiredSeatHolds.php routes/console.php app/Http/Requests/HoldSeatsRequest.php app/Http/Controllers/SeatHoldController.php app/Http/Controllers/ShowtimeController.php routes/web.php tests/Feature/SeatHoldHttpTest.php
git commit -m "feat: add seat hold, release and seat status endpoints with expiry command"
```

---

### Task 3: `CheckoutService` + endpoint thanh toán giả lập

**Files:**
- Modify: `app/Models/Booking.php`
- Create: `app/Services/CheckoutService.php`
- Create: `app/Http/Requests/CheckoutRequest.php`
- Create: `app/Http/Controllers/CheckoutController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/CheckoutTest.php`

**Interfaces:**
- Consumes: `ShowtimeSeat::heldBy()`, `SeatHoldException`, cột `bookings.payment_method` (Task 1)
- Produces: `CheckoutService::PAYMENT_METHODS = ['vietqr', 'momo', 'zalopay', 'card']`
- Produces: `CheckoutService::checkout(User $user, string $paymentMethod): Booking` — ném `SeatHoldException` khi giỏ trống/hết hạn
- Produces: route `POST /checkout` → `checkout.store` (auth, throttle 10/phút). Body `{ "payment_method": "vietqr" }`. 200 → `{ message, booking_code, total_price, redirect }`; 422 → `{ message }`.
- Produces: mã đơn dạng `TBX-XXXXXXXX` (8 ký tự in hoa/số), không trùng

- [ ] **Step 1: Viết test thất bại `tests/Feature/CheckoutTest.php`**

```php
<?php

use App\Models\Booking;
use App\Models\Event;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\SeatHoldService;

beforeEach(fn () => $this->seed());

function heldCart($user, int $count = 2): Showtime
{
    $showtime = Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026')
        ->showtimes()->orderBy('start_time')->first();
    $ids = $showtime->showtimeSeats()->where('status', 'available')->orderBy('id')->take($count)->pluck('id')->all();
    app(SeatHoldService::class)->hold($user, $showtime, $ids);

    return $showtime;
}

test('checkout turns held seats into a confirmed booking', function () {
    $user = createCustomer();
    heldCart($user, 2);

    $response = $this->actingAs($user)
        ->postJson(route('checkout.store'), ['payment_method' => 'momo'])
        ->assertOk()
        ->assertJsonPath('redirect', route('bookings.history'));

    $booking = Booking::with('items.showtimeSeat')->firstWhere('booking_code', $response->json('booking_code'));

    expect($booking->user_id)->toBe($user->id)
        ->and($booking->status)->toBe('confirmed')
        ->and($booking->payment_method)->toBe('momo')
        ->and($booking->booking_code)->toMatch('/^TBX-[A-Z0-9]{8}$/')
        ->and($booking->items)->toHaveCount(2)
        ->and($booking->total_price)->toBe(800000) // SVIP-A1 + SVIP-A2, mỗi ghế 250.000 + 150.000
        ->and($booking->items->every(fn ($item) => $item->showtimeSeat->status === 'booked' && $item->showtimeSeat->held_by_user_id === null))->toBeTrue();
});

test('checkout with an empty cart is rejected', function () {
    $this->actingAs(createCustomer())
        ->postJson(route('checkout.store'), ['payment_method' => 'vietqr'])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Giỏ vé trống hoặc đã hết thời gian giữ chỗ. Vui lòng chọn lại ghế.']);
});

test('expired holds cannot be checked out', function () {
    $user = createCustomer();
    heldCart($user, 1);
    ShowtimeSeat::where('held_by_user_id', $user->id)->update(['held_until' => now()->subMinute()]);

    $this->actingAs($user)
        ->postJson(route('checkout.store'), ['payment_method' => 'vietqr'])
        ->assertStatus(422);

    expect(Booking::where('user_id', $user->id)->exists())->toBeFalse();
});

test('payment method must be one of the supported ones', function () {
    $user = createCustomer();
    heldCart($user, 1);

    $this->actingAs($user)
        ->postJson(route('checkout.store'), ['payment_method' => 'bitcoin'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('payment_method');
});

test('a user cannot check out somebody else\'s held seats', function () {
    heldCart(createCustomer(), 2);

    $this->actingAs(createCustomer())
        ->postJson(route('checkout.store'), ['payment_method' => 'card'])
        ->assertStatus(422);
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=CheckoutTest`
Expected: FAIL — route `checkout.store` chưa có.

- [ ] **Step 3: `app/Models/Booking.php`**

Thêm `'payment_method'` vào `$fillable`. Thay closure `static::creating(...)` trong `boot()` bằng:

```php
        static::creating(function (Booking $booking) {
            if (empty($booking->booking_code)) {
                do {
                    $code = 'TBX-'.strtoupper(Str::random(8));
                } while (static::where('booking_code', $code)->exists());

                $booking->booking_code = $code;
            }
        });
```

- [ ] **Step 4: `app/Services/CheckoutService.php`**

```php
<?php

namespace App\Services;

use App\Exceptions\SeatHoldException;
use App\Models\Booking;
use App\Models\ShowtimeSeat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public const PAYMENT_METHODS = ['vietqr', 'momo', 'zalopay', 'card'];

    /**
     * Thanh toán giả lập (spec mục 5): ghế đang giữ -> đơn "confirmed".
     *
     * @throws SeatHoldException
     */
    public function checkout(User $user, string $paymentMethod): Booking
    {
        return DB::transaction(function () use ($user, $paymentMethod) {
            $seats = ShowtimeSeat::heldBy($user)
                ->with(['seat', 'showtime'])
                ->lockForUpdate()
                ->get();

            if ($seats->isEmpty()) {
                throw new SeatHoldException('Giỏ vé trống hoặc đã hết thời gian giữ chỗ. Vui lòng chọn lại ghế.');
            }

            $priceOf = fn (ShowtimeSeat $seat) => $seat->price_override ?? $seat->effective_price;

            $booking = Booking::create([
                'user_id' => $user->id,
                'total_price' => $seats->sum($priceOf),
                'status' => 'confirmed',
                'payment_method' => $paymentMethod,
            ]);

            foreach ($seats as $seat) {
                $booking->items()->create([
                    'showtime_seat_id' => $seat->id,
                    'price' => $priceOf($seat),
                ]);

                $seat->update([
                    'status' => 'booked',
                    'held_by_user_id' => null,
                    'held_until' => null,
                ]);
            }

            return $booking;
        });
    }
}
```

- [ ] **Step 5: `app/Http/Requests/CheckoutRequest.php`**

```php
<?php

namespace App\Http\Requests;

use App\Services\CheckoutService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(CheckoutService::PAYMENT_METHODS)],
        ];
    }

    public function attributes(): array
    {
        return ['payment_method' => 'phương thức thanh toán'];
    }
}
```

- [ ] **Step 6: `app/Http/Controllers/CheckoutController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Exceptions\SeatHoldException;
use App\Http\Requests\CheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, CheckoutService $checkout): JsonResponse
    {
        try {
            $booking = $checkout->checkout($request->user(), $request->validated('payment_method'));
        } catch (SeatHoldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Thanh toán thành công! Vé điện tử đã được phát hành.',
            'booking_code' => $booking->booking_code,
            'total_price' => $booking->total_price,
            'redirect' => route('bookings.history'),
        ]);
    }
}
```

- [ ] **Step 7: Route**

Thêm `use App\Http\Controllers\CheckoutController;` và trong group `auth` đầu tiên:

```php
    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('checkout.store');
```

- [ ] **Step 8: Chạy test**

Run: `php artisan test --filter=CheckoutTest`
Expected: PASS 5 test.

- [ ] **Step 9: Commit**

```bash
git add app/Models/Booking.php app/Services/CheckoutService.php app/Http/Requests/CheckoutRequest.php app/Http/Controllers/CheckoutController.php routes/web.php tests/Feature/CheckoutTest.php
git commit -m "feat: add mock checkout that turns held seats into bookings"
```

---

### Task 4: Giỏ vé và lịch sử đọc DB

**Files:**
- Modify: `app/Http/Controllers/CartController.php`, `app/Http/Controllers/BookingController.php`
- Modify: `resources/views/cart/index.blade.php` (dòng 1–30 khối `@php` + `x-data`; dòng ~101 `countdownTimer(10)`; dòng 153 và ~1121 `poster_path`; khối item ~dòng 170–184)
- Modify: `resources/views/bookings/history.blade.php` (dòng 46)
- Test: `tests/Feature/CartAndHistoryTest.php`; sửa `tests/Feature/EventBookingFlowTest.php` (2 test giỏ vé + lịch sử)

**Interfaces:**
- Consumes: `ShowtimeSeat::heldBy()`, `TicketTiers::for()`, `TicketTiers::normalizeSeatType()`
- Produces: `cart.index` truyền `$items` (**array** object `{ id, showtime, movie, seat{row_label, seat_number, type}, price }`, `id` = id `showtime_seats`), `$expiresInSeconds` (int, giây còn lại của ghế sắp hết hạn nhất)
- Produces: `bookings.history` truyền `$bookings` (**array** object `{ id, booking_code, movie, showtime, seats[] mã ghế, total_price, status, created_at }`), chỉ đơn của user đang đăng nhập, mới nhất trước

- [ ] **Step 1: Viết test thất bại `tests/Feature/CartAndHistoryTest.php`**

```php
<?php

use App\Models\Event;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Services\SeatHoldService;

beforeEach(fn () => $this->seed());

function holdForCart(User $user, int $count): void
{
    $showtime = Event::firstWhere('slug', 'hoa-nhac-giao-huong-saigon-philharmonic')->showtimes()->orderBy('start_time')->first();
    $ids = $showtime->showtimeSeats()->where('status', 'available')->orderBy('id')->take($count)->pluck('id')->all();
    app(SeatHoldService::class)->hold($user, $showtime, $ids);
}

test('the cart shows the seats the user is holding', function () {
    $user = createCustomer();
    holdForCart($user, 2);

    $this->actingAs($user)->get(route('cart.index'))
        ->assertOk()
        ->assertSee('Đêm Hòa Nhạc Giao Hưởng')
        ->assertViewHas('items', fn (array $items) => count($items) === 2)
        ->assertViewHas('expiresInSeconds', fn (int $seconds) => $seconds > 500 && $seconds <= 600);
});

test('expired holds are not shown in the cart', function () {
    $user = createCustomer();
    holdForCart($user, 1);
    ShowtimeSeat::where('held_by_user_id', $user->id)->update(['held_until' => now()->subMinute()]);

    $this->actingAs($user)->get(route('cart.index'))
        ->assertOk()
        ->assertSee('Giỏ vé của bạn đang trống')
        ->assertViewHas('items', []);
});

test('booking history lists only the user\'s own bookings from the database', function () {
    $demo = User::firstWhere('email', 'user@ticketbox.vn');

    $this->actingAs($demo)->get(route('bookings.history'))
        ->assertOk()
        ->assertSee('TBX-89214')
        ->assertSee('TBX-77102')
        ->assertViewHas('bookings', fn (array $bookings) => count($bookings) === 2 && count($bookings[0]->seats) >= 1);

    $this->actingAs(createCustomer())->get(route('bookings.history'))
        ->assertOk()
        ->assertDontSee('TBX-89214');
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=CartAndHistoryTest`
Expected: FAIL (giỏ và lịch sử vẫn đọc `DemoCatalog`).

- [ ] **Step 3: `app/Http/Controllers/CartController.php` (thay toàn bộ)**

```php
<?php

namespace App\Http\Controllers;

use App\Models\ShowtimeSeat;
use App\Support\TicketTiers;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $seats = ShowtimeSeat::heldBy($request->user())
            ->with(['seat', 'showtime.room', 'showtime.event.category'])
            ->orderBy('id')
            ->get();

        $items = $seats->map(function (ShowtimeSeat $held) {
            $tiers = TicketTiers::for($held->showtime->base_price, true);
            $type = TicketTiers::normalizeSeatType($held->seat->type);

            return (object) [
                'id' => $held->id,
                'showtime' => $held->showtime,
                'movie' => $held->showtime->event,
                'seat' => (object) [
                    'row_label' => $held->seat->row_label,
                    'seat_number' => $held->seat->seat_number,
                    'type' => $tiers[$type]['label'] ?? $type,
                ],
                'price' => $held->price_override ?? $held->effective_price,
            ];
        })->all();

        $expiresInSeconds = $seats->isEmpty()
            ? 0
            : (int) max(0, now()->diffInSeconds($seats->min('held_until')));

        return view('cart.index', [
            'items' => $items,
            'total' => array_sum(array_map(fn ($item) => $item->price, $items)),
            'expiresInSeconds' => $expiresInSeconds,
        ]);
    }
}
```

- [ ] **Step 4: `app/Http/Controllers/BookingController.php` (thay toàn bộ)**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function history(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->with([
                'items.showtimeSeat.seat',
                'items.showtimeSeat.showtime.room',
                'items.showtimeSeat.showtime.event.category',
            ])
            ->latest()
            ->get()
            ->map(function (Booking $booking) {
                // Mỗi đơn luôn thuộc 1 suất (SeatHoldService chỉ cho giữ ghế của 1 suất tại 1 thời điểm).
                $showtime = $booking->items->first()?->showtimeSeat?->showtime;

                return (object) [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'movie' => $showtime?->event,
                    'showtime' => $showtime,
                    'seats' => $booking->items->map(fn ($item) => $item->showtimeSeat->seat->full_code)->all(),
                    'total_price' => $booking->total_price,
                    'status' => $booking->status,
                    'created_at' => $booking->created_at,
                ];
            })
            ->filter(fn (object $booking) => $booking->showtime !== null)
            ->values()
            ->all();

        return view('bookings.history', ['bookings' => $bookings]);
    }
}
```

- [ ] **Step 5: Sửa view**

1. `resources/views/bookings/history.blade.php` dòng 46: `{{ $booking->movie->poster_path }}` → `{{ $booking->movie->poster_url }}`.
2. `resources/views/cart/index.blade.php`:
   - Dòng 7: `'poster_path' => $item->movie->poster_path,` → `'poster_path' => $item->movie->poster_url,`
   - Dòng 153 và ~1121: `->poster_path }}` → `->poster_url }}`
   - Dòng 13: `'seat_type' => strtoupper($item->seat->type ?? 'STANDARD'),` → `'seat_type' => $item->seat->type ?? 'Tiêu chuẩn',`
   - Dòng ~173: `({{ strtoupper($item->seat->type) }})` → `({{ $item->seat->type }})`
   - Dòng ~101: `x-data="countdownTimer(10)"` → `x-data="countdownTimer(0, {{ $expiresInSeconds }})"`

(Nút "Bỏ vé" và nối thanh toán làm ở Task 5.)

- [ ] **Step 6: Sửa 2 test giỏ vé / lịch sử trong `tests/Feature/EventBookingFlowTest.php`**

Test `cart page renders checkout flow ...`: thay 3 dòng đầu thân test bằng:

```php
    $user = \App\Models\User::factory()->create();
    $showtime = Event::firstWhere('slug', 'hoa-nhac-giao-huong-saigon-philharmonic')->showtimes()->orderBy('start_time')->first();
    app(\App\Services\SeatHoldService::class)->hold($user, $showtime, [$showtime->showtimeSeats()->where('status', 'available')->value('id')]);

    $response = $this->actingAs($user)->get(route('cart.index'));
```

Test `booking history page displays ...`: đổi `$user = \App\Models\User::factory()->create();` thành
`$user = \App\Models\User::firstWhere('email', 'user@ticketbox.vn');`.

- [ ] **Step 7: Chạy test**

Run: `php artisan test`
Expected: PASS toàn bộ.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/CartController.php app/Http/Controllers/BookingController.php resources/views/cart/index.blade.php resources/views/bookings/history.blade.php tests/Feature/CartAndHistoryTest.php tests/Feature/EventBookingFlowTest.php
git commit -m "feat: read cart and booking history from the database"
```

---

### Task 5: Nối AJAX trên giao diện (giữ chỗ, polling, bỏ vé, thanh toán)

**Files:**
- Modify: `resources/js/app.js` (đầu file; `seatBookingManager` dòng 7–~160; `countdownTimer` dòng 244–268; `ticketPaymentManager` dòng 270+)
- Modify: `resources/views/components/seat-map.blade.php` (`x-data` dòng ~24; nút "Xác Nhận Giữ Chỗ" dòng ~921)
- Modify: `resources/views/cart/index.blade.php` (`x-data` dòng ~26; khối giá mỗi vé ~dòng 178–183)

**Interfaces:**
- Consumes: routes `showtimes.holds.store`, `showtimes.seat-status`, `cart.seats.destroy`, `checkout.store` (Task 2–3), `login`
- Produces: helper JS `jsonHeaders()` ở đầu `resources/js/app.js` (Plan 4 dùng lại — nếu Plan 4 merge trước và đã có hàm này thì không thêm lần nữa)
- Produces: config mới của `seatBookingManager`: `holdUrl`, `statusUrl`, `loginUrl`, `standingPrice`, `pollStatus` (bool)
- Produces: config mới của `ticketPaymentManager`: `checkoutUrl`, `removeUrl` (chứa `__ID__`)

- [ ] **Step 1: Helper `jsonHeaders()` ở đầu `resources/js/app.js`**

Ngay sau dòng `window.Alpine = Alpine;`:

```js
// Header chuẩn cho request AJAX tới Laravel (CSRF + nhận JSON)
const jsonHeaders = () => ({
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
});
```

- [ ] **Step 2: Sửa `seatBookingManager`**

1. Thêm vào state (sau `toastTimeout: null,`):

```js
    isSubmitting: false,
    unavailableIds: [],
```

2. Đổi dòng `standingPrice: ...` thành:

```js
    standingPrice: config.standingPrice || 0,
```

3. Trong `init()`, cuối hàm thêm:

```js
        if (config.pollStatus && config.statusUrl) {
            setInterval(() => this.refreshSeatStatus(), 15000);
        }
```

4. Trong `toggleSeat(...)`, ngay sau khối `if (status === 'held') { ... }` thêm:

```js
        if (this.unavailableIds.includes(seatId)) {
            this.notify(`Ghế ${rowLabel}${seatNumber} vừa có người giữ. Vui lòng chọn ghế khác!`, 'warning');
            return;
        }
```

5. Thêm 2 method mới (đặt ngay trước `notify(msg, type = 'info') {`):

```js
    // Đồng bộ trạng thái ghế mỗi 15 giây (spec: AJAX polling, không realtime)
    async refreshSeatStatus() {
        try {
            const response = await fetch(config.statusUrl, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) return;
            const { unavailable } = await response.json();
            this.unavailableIds = unavailable;

            const lost = this.selectedSeats.filter(seat => unavailable.includes(seat.id));
            if (lost.length > 0) {
                this.selectedSeats = this.selectedSeats.filter(seat => !unavailable.includes(seat.id));
                this.notify(`Ghế ${lost.map(seat => seat.row + seat.number).join(', ')} vừa được người khác giữ.`, 'warning');
            }
        } catch (error) {
            // mất mạng tạm thời: bỏ qua, lần sau thử lại
        }
    },

    // Gửi ghế/hạng vé đang chọn lên server để giữ chỗ 10 phút
    async confirmHold() {
        if (this.totalTicketCount === 0 || this.isSubmitting) return;
        this.isSubmitting = true;

        const tiers = {};
        Object.entries(this.tierQuantities).forEach(([key, quantity]) => {
            if (quantity > 0) tiers[key] = quantity;
        });
        if (this.standingCount > 0) tiers.standing_pit = this.standingCount;

        try {
            const response = await fetch(config.holdUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ seat_ids: this.selectedSeats.map(seat => seat.id), tiers }),
            });

            if (response.status === 401) {
                window.location.href = config.loginUrl;
                return;
            }

            const data = await response.json();
            if (!response.ok) {
                this.notify(data.message || 'Không thể giữ chỗ, vui lòng thử lại.', 'error');
                if (config.pollStatus) this.refreshSeatStatus();
                return;
            }

            window.location.href = data.redirect;
        } catch (error) {
            this.notify('Mất kết nối tới máy chủ, vui lòng thử lại.', 'error');
        } finally {
            this.isSubmitting = false;
        }
    },
```

- [ ] **Step 3: Sửa `countdownTimer` nhận số giây và tải lại khi hết hạn**

Thay toàn bộ `Alpine.data('countdownTimer', ...)` bằng:

```js
Alpine.data('countdownTimer', (initialMinutes = 10, initialSeconds = null) => ({
    totalSeconds: initialSeconds ?? initialMinutes * 60,
    timerInterval: null,
    expired: false,

    init() {
        this.timerInterval = setInterval(() => {
            if (this.totalSeconds > 0) {
                this.totalSeconds--;
                return;
            }
            this.expired = true;
            clearInterval(this.timerInterval);
            // Giỏ vé thật: hết hạn giữ ghế thì tải lại để server trả giỏ trống
            if (initialSeconds !== null && initialSeconds > 0) {
                window.location.reload();
            }
        }, 1000);
    },

    get minutes() {
        return String(Math.floor(this.totalSeconds / 60)).padStart(2, '0');
    },

    get seconds() {
        return String(this.totalSeconds % 60).padStart(2, '0');
    }
}));
```

- [ ] **Step 4: Sửa `ticketPaymentManager`**

1. Thay toàn bộ method `simulatePaymentSuccess()` bằng:

```js
    async simulatePaymentSuccess() {
        if (this.isProcessingPayment || this.paymentSuccess) return;

        this.isProcessingPayment = true;
        this.notify('Đang kiểm tra và xác nhận giao dịch thanh toán...');

        try {
            const response = await fetch(config.checkoutUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ payment_method: this.selectedMethod }),
            });
            const data = await response.json();

            if (!response.ok) {
                this.notify(data.message || 'Thanh toán thất bại, vui lòng thử lại.');
                return;
            }

            this.orderCode = data.booking_code;
            this.paymentSuccess = true;
            this.step = 'e_ticket';
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.notify(data.message);
        } catch (error) {
            this.notify('Mất kết nối tới máy chủ, vui lòng thử lại.');
        } finally {
            this.isProcessingPayment = false;
        }
    },
```

2. Thêm method mới ngay sau `simulatePaymentSuccess`:

```js
    async removeItem(showtimeSeatId) {
        try {
            const response = await fetch(config.removeUrl.replace('__ID__', showtimeSeatId), {
                method: 'DELETE',
                headers: jsonHeaders(),
            });
            const data = await response.json();
            if (!response.ok) {
                this.notify(data.message || 'Không thể bỏ vé này.');
                return;
            }
            window.location.reload();
        } catch (error) {
            this.notify('Mất kết nối tới máy chủ, vui lòng thử lại.');
        }
    },
```

- [ ] **Step 5: Truyền config trong Blade**

`resources/views/components/seat-map.blade.php`, thay khối `x-data="seatBookingManager({ ... })"` (~dòng 24–27) bằng:

```blade
    x-data="seatBookingManager({
        basePrice: {{ $basePrice }},
        tiers: {{ json_encode($ticketTiers) }},
        standingPrice: {{ (int) ($ticketTiers['standing_pit']['price'] ?? 0) }},
        holdUrl: @js(route('showtimes.holds.store', $showtime->id)),
        statusUrl: @js(route('showtimes.seat-status', $showtime->id)),
        loginUrl: @js(route('login')),
        pollStatus: @js((bool) $isSeatedConcert),
    })"
```

Thay nút "Xác Nhận Giữ Chỗ" (thẻ `<a href="{{ route('cart.index') }}" class="btn-rose px-8 ...">` ~dòng 921–930) bằng:

```blade
            <button
                type="button"
                @click="confirmHold()"
                :disabled="totalTicketCount === 0 || isSubmitting"
                class="btn-rose px-8 py-3.5 rounded-full font-black text-sm flex items-center gap-2.5 shadow-lg hover:shadow-xl transition-all"
                :class="{ 'opacity-50 pointer-events-none grayscale': totalTicketCount === 0 || isSubmitting }"
            >
                <span x-text="isSubmitting ? 'Đang giữ chỗ...' : 'Xác Nhận Giữ Chỗ'">Xác Nhận Giữ Chỗ</span>
                <span x-show="totalTicketCount > 0 && !isSubmitting" x-text="'(' + totalTicketCount + ' vé)'"></span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
            </button>
```

`resources/views/cart/index.blade.php`, trong `x-data="ticketPaymentManager({ ... })"` (~dòng 26–32) thêm 2 dòng trước `})`:

```blade
            checkoutUrl: @js(route('checkout.store')),
            removeUrl: @js(route('cart.seats.destroy', '__ID__')),
```

(nhớ thêm dấu phẩy sau dòng `userPhone: ...`.)

Trong vòng `@foreach ($items as $item)`, ở khối giá (`<div class="text-right shrink-0">`, ~dòng 178), thay dòng
`<div class="text-[11px] text-sage-forest font-bold mt-0.5">E-Ticket QR</div>` bằng:

```blade
                                            <button
                                                type="button"
                                                x-show="step === 'cart'"
                                                @click="removeItem({{ $item->id }})"
                                                class="mt-1 text-[11px] font-bold text-[#CC0000] hover:underline"
                                            >
                                                Bỏ vé
                                            </button>
```

- [ ] **Step 6: Build + test**

Run: `npm run build && php artisan test`
Expected: build thành công, test PASS toàn bộ.

- [ ] **Step 7: Kiểm tra tay trên MySQL thật (bắt buộc — đây là luồng chính)**

```bash
php artisan migrate:fresh --seed
php artisan serve --port=8080
```

1. Chưa đăng nhập: vào concert → chọn 2 ghế → "Xác Nhận Giữ Chỗ" → bị chuyển tới trang đăng nhập.
2. Đăng nhập `user@ticketbox.vn` / `password` → chọn lại 2 ghế + 1 vé đứng → giữ chỗ → sang giỏ, đồng hồ đếm ngược ~10:00, 3 vé đúng giá.
3. Bấm "Bỏ vé" 1 vé → trang tải lại còn 2 vé.
4. Mở cửa sổ ẩn danh, đăng nhập `lethutrang@gmail.com` / `password`, vào đúng suất đó → trong ≤15 giây, bấm vào ghế đang bị giữ sẽ báo "vừa có người giữ"; cố giữ bằng request sẽ nhận thông báo 422.
5. Quay lại cửa sổ 1 → điền thông tin → thanh toán MoMo → màn hình E-Ticket hiện mã `TBX-XXXXXXXX` thật.
6. Vào "Lịch sử vé" thấy đơn mới; đăng nhập admin thấy đơn ở `/admin/bookings`, doanh thu tăng ở dashboard.
7. Hội thảo (không ghế): chọn 1 "Combo Nhóm 2 Người" → giỏ có 2 chỗ.

Ghi kết quả từng bước vào mô tả PR.

- [ ] **Step 8: Commit**

```bash
git add resources/js/app.js resources/views/components/seat-map.blade.php resources/views/cart/index.blade.php
git commit -m "feat: wire seat holding, status polling, cart removal and checkout to the server"
```

---

### Task 6: Xóa `DemoCatalog`, tài liệu, PR

**Files:**
- Delete: `app/Support/DemoCatalog.php`
- Modify: `docs/architecture.md`

- [ ] **Step 1: Xác nhận không còn chỗ dùng**

Run: `grep -rn "DemoCatalog" app resources tests database routes`
Expected: chỉ còn chính file `app/Support/DemoCatalog.php`. Nếu còn chỗ khác → sửa sang dữ liệu DB tương ứng trước.

- [ ] **Step 2: Xóa file và chạy test**

```bash
git rm app/Support/DemoCatalog.php
php artisan test
```

Expected: PASS toàn bộ.

- [ ] **Step 3: Cập nhật `docs/architecture.md`**

Thêm vào bảng thay đổi:

```markdown
| **2026-09-21** | **Engine đặt vé** | Giữ ghế = `showtime_seats.status=held` (+`held_by_user_id`, `held_until` 10 phút, `price_override` = giá chốt); khóa dòng `lockForUpdate()` trong transaction, tất cả-hoặc-không; mỗi user chỉ giữ ghế của 1 suất; tối đa 8 chỗ; lệnh `seats:release-expired` chạy mỗi phút | Đúng spec mục 3, không có bảng giỏ hàng riêng. |
| **2026-09-21** | **Thanh toán** | Thanh toán giả lập: chọn VietQR/MoMo/ZaloPay/Thẻ, server tạo booking `confirmed`, lưu `bookings.payment_method` | Spec mục 5: không tích hợp cổng thật. |
| **2026-09-21** | **Đồng bộ ghế** | Polling `GET /showtimes/{id}/seat-status` mỗi 15 giây | Spec mục 5: không dùng WebSocket. |
```

Trong mục "Tiến độ hoàn thành" thêm: `- **Engine đặt vé (Plan 3)**: giữ ghế AJAX chống đặt trùng, giỏ vé, thanh toán giả lập, lịch sử — đã bỏ hoàn toàn dữ liệu giả DemoCatalog.`

Thêm mục mới cuối file:

```markdown
## 5. Chạy lịch dọn ghế hết hạn

Môi trường dev: `php artisan schedule:work` (hoặc chạy tay `php artisan seats:release-expired`).
Kể cả không chạy lịch, ghế giữ quá hạn vẫn được coi là trống khi người khác giữ — lịch chỉ để dọn dữ liệu và hiển thị đúng.
```

- [ ] **Step 4: Commit + PR**

```bash
git add docs/architecture.md
git commit -m "chore: remove DemoCatalog, document the booking engine"
git push -u origin feature/booking-engine
gh pr create --base main --title "Plan 3: Engine đặt vé (giữ ghế AJAX, giỏ, thanh toán giả lập, lịch sử)" --body "Theo docs/superpowers/plans/2026-09-21-p3-booking-engine.md. Output php artisan test + kết quả kiểm tra tay Task 5 Step 7: <dán vào đây>"
```
