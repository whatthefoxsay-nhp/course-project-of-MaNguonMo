# Plan 1 — Admin CRUD lõi + sửa lỗi an toàn dữ liệu

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Admin tạo/sửa/xóa được danh mục, sự kiện (có upload poster) và suất diễn; sửa
lỗi sửa khán phòng làm mất vé đã bán; user bị khóa bị đá ra ngay ở request kế tiếp.

**Architecture:** Controller mỏng + Form Request cho validation, logic sinh ghế tách ra
2 service (`RoomSeatGenerator`, `ShowtimeSeatGenerator`) để seeder ở Plan 2 và booking ở
Plan 3 dùng lại. Chi tiết mô tả sự kiện (địa điểm, lineup, lịch trình…) lưu trong cột JSON
`events.details`, đọc qua accessor để các view hiện có (đang đọc `$event->venue_name`,
`$event->lineup`…) chạy được mà không phải sửa.

**Tech Stack:** Laravel 11, Pest 4, spatie/laravel-permission, Blade + Tailwind + Alpine.

**Spec:** `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md` (mục 3, 4, 8 — Phase 1)
**Lộ trình:** `docs/superpowers/plans/2026-09-21-roadmap-hoan-thien.md`

## Global Constraints

- Nhánh: `feature/admin-crud-core`, rẽ từ `main`. Mỗi task = 1 commit. Không commit vào `main`.
- Roles: chỉ `admin` và `user`. Mọi route quản trị nằm trong group `admin.*` +
  middleware `['auth', 'role:admin']` đã có sẵn trong `routes/web.php`.
- Không sửa migration đã có trên `main`, chỉ thêm migration mới.
- Test: Pest, feature test (`RefreshDatabase` đã bật trong `tests/Pest.php`, DB test là
  SQLite in-memory theo `phpunit.xml`).
- Mọi chuỗi hiển thị cho người dùng bằng tiếng Việt.
- Không động vào trang mã giảm giá, chatbot, báo cáo (quyết định của user: giữ nguyên).
- Không đổi các view public (`home`, `events/*`, `showtimes/*`, `cart/*`, `bookings/*`) —
  việc đó thuộc Plan 2/3.
- Commit message kết thúc bằng dòng `Co-Authored-By` theo quy ước repo nếu có; không bắt buộc.
- Trước khi mở PR: `php artisan test` xanh toàn bộ và `npm run build` thành công.

## File Structure

| File | Trách nhiệm |
|---|---|
| `tests/Pest.php` (sửa) | Helper `createAdmin()`, `createCustomer()` |
| `database/factories/{Category,Event,Room,Showtime}Factory.php` (mới) | Factory cho test |
| `database/factories/UserFactory.php` (sửa) | `is_active => true` |
| `app/Services/RoomSeatGenerator.php` (mới) | Sinh ghế vật lý theo preset khán phòng (kể cả hàng vé đứng `GA`) |
| `app/Services/ShowtimeSeatGenerator.php` (mới) | Sinh `showtime_seats` cho 1 suất diễn |
| `app/Http/Controllers/Admin/RoomController.php` (sửa) | Dùng generator, chặn sinh lại ghế / xóa phòng khi đã có suất diễn |
| `resources/views/admin/partials/flash.blade.php` (mới) | Hiển thị flash `success` / `warning` / `error` cho mọi trang admin |
| `app/Http/Middleware/EnsureUserIsActive.php` (mới) | Đăng xuất user bị khóa |
| `app/Models/Concerns/GeneratesUniqueSlug.php` (mới) | Sinh slug không trùng |
| `app/Http/Requests/Admin/{Category,Event,Showtime}Request.php` (mới) | Validation |
| `app/Http/Controllers/Admin/{Category,Event,Showtime}Controller.php` | CRUD |
| `resources/views/components/admin/field.blade.php` (mới) | Ô nhập liệu dùng chung cho form admin |
| `resources/views/admin/categories/{index,form}.blade.php` (mới) | Giao diện danh mục |
| `resources/views/admin/events/form.blade.php` (mới), `index.blade.php` (sửa) | Giao diện sự kiện |
| `resources/views/admin/showtimes/form.blade.php` (mới), `index.blade.php` (sửa) | Giao diện suất diễn |
| `database/migrations/2026_09_21_000001_add_seating_and_details_to_events_table.php` (mới) | Cột `is_seated`, `details` |

---

### Task 1: Test helpers, factories, service sinh ghế, chặn mất vé khi sửa khán phòng

**Files:**
- Modify: `phpunit.xml` (khôi phục `CACHE_STORE=array`)
- Modify: `tests/Pest.php`
- Modify: `database/factories/UserFactory.php`
- Create: `database/factories/CategoryFactory.php`, `EventFactory.php`, `RoomFactory.php`, `ShowtimeFactory.php`
- Create: `app/Services/RoomSeatGenerator.php`
- Modify: `app/Http/Controllers/Admin/RoomController.php`
- Create: `resources/views/admin/partials/flash.blade.php`
- Modify: `resources/views/layouts/admin.blade.php` (dòng `{{ $slot }}` ~283)
- Modify: `resources/views/admin/rooms/index.blade.php` (xóa khối `@if(session('success'))` ở dòng 4–~10 vì layout đã hiển thị)
- Test: `tests/Feature/AdminRoomSafetyTest.php`

**Interfaces:**
- Produces: `createAdmin(array $attrs = []): User`, `createCustomer(array $attrs = []): User` (global Pest helpers)
- Produces: `App\Services\RoomSeatGenerator::generate(Room $room, string $preset, array $config): int` — xóa ghế cũ của phòng rồi sinh mới, trả số ghế
- Produces: `RoomSeatGenerator::PRESETS` (list preset hợp lệ), `RoomSeatGenerator::STANDING_ROW = 'GA'`
- Produces: loại ghế (`seats.type`): `svip_diamond`, `vip_gold`, `cat1_stand`, `cat2_wings`, `skybox_suite`, `standing_pit` (Plan 2 định giá theo các key này)
- Produces: factories `Category::factory()`, `Event::factory()`, `Room::factory()`, `Showtime::factory()`

- [ ] **Step 1: Thêm helper test vào `tests/Pest.php`**

Thay khối `function something() { }` ở cuối file bằng:

```php
function createAdmin(array $attributes = []): \App\Models\User
{
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create($attributes);
    $user->assignRole('admin');

    return $user;
}

function createCustomer(array $attributes = []): \App\Models\User
{
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create($attributes);
    $user->assignRole('user');

    return $user;
}
```

- [ ] **Step 2: Sửa `UserFactory` và tạo 4 factory mới**

Trong `database/factories/UserFactory.php`, thêm vào mảng trả về của `definition()`:

```php
            'is_active' => true,
```

`database/factories/CategoryFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
        ];
    }
}
```

`database/factories/EventFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'type' => 'event',
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'category_id' => Category::factory(),
            'duration_minutes' => 120,
            'status' => 'published',
            'release_date' => now()->addWeek(),
        ];
    }
}
```

`database/factories/RoomFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Khán phòng '.fake()->unique()->word(),
            'address' => fake()->address(),
            'capacity' => 200,
            'layout_preset' => 'custom_grid',
            'seat_config' => ['rows' => 3, 'cols' => 4],
        ];
    }
}
```

`database/factories/ShowtimeFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Showtime>
 */
class ShowtimeFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(3)->setTime(19, 0);

        return [
            'event_id' => Event::factory(),
            'room_id' => Room::factory(),
            'start_time' => $start,
            'end_time' => $start->copy()->addHours(3),
            'base_price' => 200000,
        ];
    }
}
```

- [ ] **Step 2b: Khôi phục cache `array` cho test**

Nhánh UI đã lỡ xóa dòng này khỏi `phpunit.xml`, khiến test dùng cache `database` của `.env`.
Thêm lại vào khối `<php>`, ngay dưới `<env name="BCRYPT_ROUNDS" value="4"/>`:

```xml
        <env name="CACHE_STORE" value="array"/>
```

- [ ] **Step 3: Viết test thất bại `tests/Feature/AdminRoomSafetyTest.php`**

```php
<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\RoomSeatGenerator;

function roomWithSoldTicket(): array
{
    $room = Room::factory()->create(['layout_preset' => 'custom_grid']);
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 3, 'cols' => 4]);
    $showtime = Showtime::factory()->for($room)->create();
    $seat = $room->seats()->first();
    $showtimeSeat = ShowtimeSeat::create([
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'status' => 'booked',
    ]);
    $customer = createCustomer();
    $booking = Booking::create(['user_id' => $customer->id, 'total_price' => 200000, 'status' => 'confirmed']);
    BookingItem::create(['booking_id' => $booking->id, 'showtime_seat_id' => $showtimeSeat->id, 'price' => 200000]);

    return [$room, $showtime];
}

test('generator builds a custom grid with the requested size', function () {
    $room = Room::factory()->create();

    $count = app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 3, 'cols' => 4]);

    expect($count)->toBe(12)
        ->and($room->seats()->count())->toBe(12)
        ->and($room->seats()->where('row_label', 'A')->value('type'))->toBe('svip_diamond');
});

test('mega concert preset includes a hidden standing row', function () {
    $room = Room::factory()->create();

    app(RoomSeatGenerator::class)->generate($room, 'mega_concert', []);

    expect($room->seats()->where('row_label', RoomSeatGenerator::STANDING_ROW)->where('type', 'standing_pit')->count())
        ->toBe(RoomSeatGenerator::STANDING_CAPACITY);
});

test('updating a room that already has showtimes keeps seats and sold tickets', function () {
    [$room] = roomWithSoldTicket();
    $seatIdsBefore = $room->seats()->pluck('id')->all();

    $this->actingAs(createAdmin())
        ->put(route('admin.rooms.update', $room), [
            'name' => 'Tên mới',
            'address' => 'Địa chỉ mới',
            'capacity' => 500,
            'layout_preset' => 'custom_grid',
            'rows' => 10,
            'cols' => 10,
        ])
        ->assertRedirect(route('admin.rooms.index'))
        ->assertSessionHas('warning');

    expect($room->fresh()->name)->toBe('Tên mới')
        ->and($room->seats()->pluck('id')->all())->toBe($seatIdsBefore)
        ->and(BookingItem::count())->toBe(1);
});

test('deleting a room that has showtimes is refused', function () {
    [$room] = roomWithSoldTicket();

    $this->actingAs(createAdmin())
        ->delete(route('admin.rooms.destroy', $room))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Room::find($room->id))->not->toBeNull()
        ->and(BookingItem::count())->toBe(1);
});
```

- [ ] **Step 4: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=AdminRoomSafetyTest`
Expected: FAIL — `Class "App\Services\RoomSeatGenerator" not found`.

- [ ] **Step 5: Tạo `app/Services/RoomSeatGenerator.php`**

Logic sinh ghế giữ nguyên như `RoomController::generateSeatsForRoom()` hiện tại, chỉ thêm
hàng vé đứng `GA` cho `mega_concert`.

```php
<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Seat;

class RoomSeatGenerator
{
    public const PRESETS = ['mega_concert', 'theater_hall', 'convention_center', 'custom_grid'];

    /** Hàng ghế ẩn dùng cho vé đứng (GA) — không vẽ trên sơ đồ, server tự gán. */
    public const STANDING_ROW = 'GA';

    public const STANDING_CAPACITY = 40;

    /**
     * Xóa toàn bộ ghế cũ của phòng và sinh lại theo preset.
     * CHỈ gọi khi phòng chưa có suất diễn (xóa ghế sẽ cascade xóa showtime_seats).
     */
    public function generate(Room $room, string $preset, array $config): int
    {
        $room->seats()->delete();

        $now = now();
        $inserts = [];

        foreach ($this->rows($preset, $config) as [$rowLabel, $count, $type]) {
            for ($number = 1; $number <= $count; $number++) {
                $inserts[] = [
                    'room_id' => $room->id,
                    'row_label' => $rowLabel,
                    'seat_number' => $number,
                    'type' => $type,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($inserts, 200) as $chunk) {
            Seat::insert($chunk);
        }

        return count($inserts);
    }

    /**
     * @return list<array{0: string, 1: int, 2: string}> [row_label, số ghế, loại ghế]
     */
    private function rows(string $preset, array $config): array
    {
        return match ($preset) {
            'mega_concert' => [
                ['SVIP-A', 12, 'svip_diamond'], ['SVIP-B', 12, 'svip_diamond'],
                ['FL-1', 14, 'vip_gold'], ['FL-2', 14, 'vip_gold'], ['FL-3', 14, 'vip_gold'], ['FL-4', 14, 'vip_gold'],
                ['A1', 16, 'cat1_stand'], ['A2', 16, 'cat1_stand'], ['A3', 16, 'cat1_stand'],
                ['B1', 14, 'cat1_stand'], ['B2', 14, 'cat1_stand'],
                ['C1', 12, 'cat2_wings'], ['C2', 12, 'cat2_wings'],
                ['D1', 12, 'cat2_wings'], ['D2', 12, 'cat2_wings'],
                ['SB', 8, 'skybox_suite'],
                [self::STANDING_ROW, self::STANDING_CAPACITY, 'standing_pit'],
            ],
            'theater_hall' => [
                ['ST-1', 16, 'vip_gold'], ['ST-2', 16, 'vip_gold'],
                ['ST-3', 16, 'cat1_stand'], ['DC-1', 16, 'cat1_stand'], ['DC-2', 16, 'cat1_stand'],
                ['GL-1', 16, 'cat2_wings'], ['GL-2', 16, 'cat2_wings'],
            ],
            'convention_center' => [
                ['KN-A', 18, 'svip_diamond'], ['KN-B', 18, 'svip_diamond'],
                ['STD-1', 18, 'cat1_stand'], ['STD-2', 18, 'cat1_stand'],
                ['STD-3', 18, 'cat1_stand'], ['STD-4', 18, 'cat1_stand'],
            ],
            default => $this->customGridRows($config),
        };
    }

    private function customGridRows(array $config): array
    {
        $rowsCount = max(2, min(26, (int) ($config['rows'] ?? 10)));
        $colsCount = max(4, min(40, (int) ($config['cols'] ?? 14)));
        $alphabet = range('A', 'Z');
        $rows = [];

        for ($r = 0; $r < $rowsCount; $r++) {
            $type = $r < 2 ? 'svip_diamond' : ($r < 5 ? 'vip_gold' : 'cat1_stand');
            $rows[] = [$alphabet[$r], $colsCount, $type];
        }

        return $rows;
    }
}
```

- [ ] **Step 6: Sửa `app/Http/Controllers/Admin/RoomController.php`**

1. Thêm `use App\Services\RoomSeatGenerator;`.
2. Trong `store()` và `update()`, đổi rule `layout_preset` thành
   `'required|string|in:'.implode(',', RoomSeatGenerator::PRESETS)`.
3. `store(Request $request, RoomSeatGenerator $generator)`: thay dòng
   `$this->generateSeatsForRoom($room, ...)` bằng
   `$generator->generate($room, $validated['layout_preset'], $seatConfig);`
4. Thay toàn bộ `update()` phần sau khi tạo `$seatConfig` bằng:

```php
        if ($room->showtimes()->exists()) {
            // Đã có suất diễn: KHÔNG sinh lại ghế (sẽ cascade xóa vé đã bán).
            $room->update([
                'name' => $validated['name'],
                'address' => $validated['address'] ?? $room->address,
                'capacity' => (int) $validated['capacity'],
            ]);

            return redirect()->route('admin.rooms.index')
                ->with('warning', "Đã cập nhật thông tin [{$room->name}]. Khán phòng đã có suất diễn nên sơ đồ ghế được giữ nguyên.");
        }

        $room->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? $room->address,
            'capacity' => (int) $validated['capacity'],
            'layout_preset' => $validated['layout_preset'],
            'seat_config' => $seatConfig,
        ]);

        $generator->generate($room, $validated['layout_preset'], $seatConfig);

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã cập nhật khán phòng [{$room->name}] và tái tạo sơ đồ ghế thành công!");
```

   và đổi chữ ký thành `update(Request $request, Room $room, RoomSeatGenerator $generator)`.
5. Thay `destroy()` bằng:

```php
    public function destroy(Room $room): RedirectResponse
    {
        if ($room->showtimes()->exists()) {
            return back()->with('error', 'Không thể xóa khán phòng đã có suất diễn. Hãy xóa các suất diễn trước.');
        }

        $room->delete();

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Đã xóa khán phòng thành công!');
    }
```

6. Xóa hẳn method `generateSeatsForRoom()` và `use App\Models\Seat;` (không còn dùng).

- [ ] **Step 7: Flash message dùng chung cho admin**

Tạo `resources/views/admin/partials/flash.blade.php`:

```blade
@php
    $flashStyles = [
        'success' => 'bg-[#3A5A40]/10 border-[#3A5A40]/30 text-[#3A5A40]',
        'warning' => 'bg-[#D4AF37]/10 border-[#D4AF37]/40 text-[#8A6D1D]',
        'error' => 'bg-[#CC0000]/10 border-[#CC0000]/30 text-[#CC0000]',
    ];
@endphp

@foreach ($flashStyles as $key => $classes)
    @if (session($key))
        <div class="mb-6 rounded-2xl border px-5 py-3 text-xs font-bold {{ $classes }}" role="alert">
            {{ session($key) }}
        </div>
    @endif
@endforeach
```

Trong `resources/views/layouts/admin.blade.php`, sửa khối `<main>`:

```blade
        <main class="p-6 sm:p-8 flex-1">
            @include('admin.partials.flash')
            {{ $slot }}
        </main>
```

Trong `resources/views/admin/rooms/index.blade.php`, xóa khối `@if(session('success')) ... @endif`
ở đầu file (tránh hiện 2 lần).

- [ ] **Step 8: Chạy test**

Run: `php artisan test --filter="AdminRoomSafetyTest|AdminRoomBuilderTest"`
Expected: PASS toàn bộ (test cũ `admin can update room and regenerate seat map` vẫn pass vì phòng đó chưa có suất diễn).

- [ ] **Step 9: Commit**

```bash
git add phpunit.xml tests/Pest.php database/factories app/Services/RoomSeatGenerator.php app/Http/Controllers/Admin/RoomController.php resources/views/admin/partials/flash.blade.php resources/views/layouts/admin.blade.php resources/views/admin/rooms/index.blade.php tests/Feature/AdminRoomSafetyTest.php
git commit -m "fix: stop room edits from deleting sold tickets, extract seat generator"
```

---

### Task 2: Đá user bị khóa ra ở request kế tiếp

Hiện tại chỉ chặn lúc đăng nhập (`LoginRequest`). User đang đăng nhập mà bị khóa vẫn dùng tiếp được.

**Files:**
- Create: `app/Http/Middleware/EnsureUserIsActive.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/LockedUserTest.php`

**Interfaces:**
- Produces: middleware chạy trong group `web` cho mọi request.

- [ ] **Step 1: Viết test thất bại `tests/Feature/LockedUserTest.php`**

```php
<?php

test('an active user can keep browsing', function () {
    $this->actingAs(createCustomer())
        ->get(route('profile.edit'))
        ->assertOk();
});

test('a user locked mid-session is logged out on the next request', function () {
    $user = createCustomer();
    $this->actingAs($user);
    $user->update(['is_active' => false]);

    $this->get(route('profile.edit'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a locked user gets a 403 json response on ajax requests', function () {
    $user = createCustomer();
    $this->actingAs($user);
    $user->update(['is_active' => false]);

    $this->getJson(route('profile.edit'))->assertForbidden();
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=LockedUserTest`
Expected: 2 test sau FAIL (vẫn trả 200).

- [ ] **Step 3: Tạo middleware**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    private const MESSAGE = 'Tài khoản của bạn đã bị tạm khóa bởi Quản trị viên. Vui lòng liên hệ ban quản trị để được hỗ trợ.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // So sánh chặt với false: model vừa tạo trong bộ nhớ có thể chưa nạp giá trị mặc định (null).
        if ($user && $user->is_active === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => self::MESSAGE], 403);
            }

            return redirect()->route('login')->withErrors(['email' => self::MESSAGE]);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Đăng ký middleware trong `bootstrap/app.php`**

```php
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
```

- [ ] **Step 5: Chạy toàn bộ test**

Run: `php artisan test`
Expected: PASS toàn bộ.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/EnsureUserIsActive.php bootstrap/app.php tests/Feature/LockedUserTest.php
git commit -m "feat: log out locked users on their next request"
```

---

### Task 3: CRUD danh mục (categories)

**Files:**
- Create: `app/Models/Concerns/GeneratesUniqueSlug.php`
- Modify: `app/Models/Category.php`
- Create: `app/Http/Requests/Admin/CategoryRequest.php`
- Create: `app/Http/Controllers/Admin/CategoryController.php`
- Create: `resources/views/components/admin/field.blade.php`
- Modify: `resources/css/app.css`
- Create: `resources/views/admin/categories/index.blade.php`, `resources/views/admin/categories/form.blade.php`
- Modify: `routes/web.php`, `resources/views/layouts/admin.blade.php` (sidebar)
- Test: `tests/Feature/AdminCategoryCrudTest.php`

**Interfaces:**
- Produces: trait `App\Models\Concerns\GeneratesUniqueSlug` với `static uniqueSlug(string $source, ?int $ignoreId = null): string` (Task 4 dùng cho Event)
- Produces: routes `admin.categories.{index,create,store,edit,update,destroy}`
- Produces: Blade component `<x-admin.field label="" name="" type="text|number|date|datetime-local|file|textarea" :value="" required hint="" />`
- Produces: CSS class `.admin-input`

- [ ] **Step 1: Viết test thất bại `tests/Feature/AdminCategoryCrudTest.php`**

```php
<?php

use App\Models\Category;
use App\Models\Event;

test('admin sees the category list', function () {
    Category::factory()->create(['name' => 'Concert']);

    $this->actingAs(createAdmin())
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertSee('Concert');
});

test('admin creates a category with an auto slug', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.categories.store'), ['name' => 'Hòa Nhạc', 'description' => 'Giao hưởng'])
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHas('success');

    expect(Category::where('slug', 'hoa-nhac')->exists())->toBeTrue();
});

test('category name must be unique', function () {
    Category::factory()->create(['name' => 'Concert']);

    $this->actingAs(createAdmin())
        ->post(route('admin.categories.store'), ['name' => 'Concert'])
        ->assertSessionHasErrors('name');
});

test('admin updates a category', function () {
    $category = Category::factory()->create(['name' => 'Cũ']);

    $this->actingAs(createAdmin())
        ->put(route('admin.categories.update', $category), ['name' => 'Mới'])
        ->assertRedirect(route('admin.categories.index'));

    expect($category->fresh()->name)->toBe('Mới');
});

test('admin deletes an empty category', function () {
    $category = Category::factory()->create();

    $this->actingAs(createAdmin())
        ->delete(route('admin.categories.destroy', $category))
        ->assertSessionHas('success');

    expect(Category::find($category->id))->toBeNull();
});

test('a category that still has events cannot be deleted', function () {
    $category = Category::factory()->create();
    Event::factory()->for($category)->create();

    $this->actingAs(createAdmin())
        ->delete(route('admin.categories.destroy', $category))
        ->assertSessionHas('error');

    expect(Category::find($category->id))->not->toBeNull();
});

test('a regular user cannot manage categories', function () {
    $this->actingAs(createCustomer())
        ->get(route('admin.categories.index'))
        ->assertForbidden();
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=AdminCategoryCrudTest`
Expected: FAIL — `Route [admin.categories.index] not defined`.

- [ ] **Step 3: Trait sinh slug `app/Models/Concerns/GeneratesUniqueSlug.php`**

```php
<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait GeneratesUniqueSlug
{
    public static function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'muc';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
```

Trong `app/Models/Category.php`: thêm `use App\Models\Concerns\GeneratesUniqueSlug;` và đổi
dòng trait thành `use GeneratesUniqueSlug, HasFactory;`. Trong `boot()`, đổi
`$category->slug = Str::slug($category->name);` thành
`$category->slug = static::uniqueSlug($category->name);`.

- [ ] **Step 4: Form Request `app/Http/Requests/Admin/CategoryRequest.php`**

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route đã nằm sau middleware role:admin
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($this->route('category'))],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'tên danh mục', 'description' => 'mô tả'];
    }
}
```

- [ ] **Step 5: Controller `app/Http/Controllers/Admin/CategoryController.php`**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::withCount('events')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', "Đã tạo danh mục [{$category->name}].");
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', "Đã cập nhật danh mục [{$category->name}].");
    }

    public function destroy(Category $category): RedirectResponse
    {
        // FK events.category_id là cascade: xóa danh mục sẽ xóa luôn sự kiện → chặn.
        if ($category->events()->exists()) {
            return back()->with('error', "Danh mục [{$category->name}] vẫn còn sự kiện, không thể xóa.");
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Đã xóa danh mục.');
    }
}
```

- [ ] **Step 6: Routes**

Trong `routes/web.php`: thêm `use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;`
và trong group admin, ngay sau dòng `reports.pdf`:

```php
    Route::resource('categories', AdminCategoryController::class)->except('show');
```

- [ ] **Step 7: CSS `.admin-input` và component `x-admin.field`**

Cuối `resources/css/app.css` thêm:

```css
@layer components {
    .admin-input {
        @apply w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 text-xs text-black placeholder-gray-400 font-medium focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10;
    }
}
```

`resources/views/components/admin/field.blade.php`:

```blade
@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null])

@php
    // "details[venue_name]" -> "details.venue_name" để dùng với old() và @error
    $dotKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $inputId = str_replace('.', '_', $dotKey);
@endphp

<div>
    <label for="{{ $inputId }}" class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1.5">
        {{ $label }}
        @if ($required)
            <span class="text-[#CC0000]">*</span>
        @endif
    </label>

    @if ($type === 'textarea')
        <textarea id="{{ $inputId }}" name="{{ $name }}" @required($required) {{ $attributes->merge(['class' => 'admin-input', 'rows' => 4]) }}>{{ old($dotKey, $value) }}</textarea>
    @elseif ($type === 'file')
        <input id="{{ $inputId }}" name="{{ $name }}" type="file" @required($required) {{ $attributes->merge(['class' => 'admin-input']) }}>
    @else
        <input id="{{ $inputId }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($dotKey, $value) }}" @required($required) {{ $attributes->merge(['class' => 'admin-input']) }}>
    @endif

    @if ($hint)
        <p class="mt-1 text-[11px] text-gray-500">{{ $hint }}</p>
    @endif

    @error($dotKey)
        <p class="mt-1 text-[11px] font-bold text-[#CC0000]">{{ $message }}</p>
    @enderror
</div>
```

- [ ] **Step 8: View `resources/views/admin/categories/index.blade.php`**

```blade
<x-admin-layout :header="'Quản Lý Danh Mục'">
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-black/10 shadow-sm">
            <div>
                <h2 class="font-display font-black text-xl text-black">Danh Mục Sự Kiện</h2>
                <p class="text-xs text-gray-500 mt-0.5">Phân loại concert, hòa nhạc, hội thảo, triển lãm…</p>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="btn-rose px-5 py-3 rounded-2xl text-xs font-black shadow-md">+ Thêm Danh Mục</a>
        </div>

        <form method="GET" action="{{ route('admin.categories.index') }}" class="bg-white p-5 rounded-3xl border border-black/10 shadow-sm flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên danh mục…" class="admin-input">
            <button type="submit" class="btn-dark px-5 rounded-2xl text-xs font-black">Lọc</button>
        </form>

        <div class="bg-white rounded-3xl border border-black/10 shadow-sm overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#FAF9F6] text-[11px] font-black uppercase tracking-wider text-gray-600">
                    <tr>
                        <th class="py-3 px-6">Tên</th>
                        <th class="py-3 px-6">Slug</th>
                        <th class="py-3 px-6">Số sự kiện</th>
                        <th class="py-3 px-6 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/5">
                    @forelse ($categories as $category)
                        <tr>
                            <td class="py-4 px-6">
                                <div class="font-bold text-black">{{ $category->name }}</div>
                                <div class="text-gray-500 line-clamp-1">{{ $category->description }}</div>
                            </td>
                            <td class="py-4 px-6 font-mono text-gray-600">{{ $category->slug }}</td>
                            <td class="py-4 px-6 font-bold">{{ $category->events_count }}</td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="px-3.5 py-1.5 rounded-xl font-bold bg-[#FAF9F6] border border-black/10 hover:bg-black hover:text-white">Sửa</a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Xóa danh mục này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3.5 py-1.5 rounded-xl font-bold text-[#CC0000] border border-[#CC0000]/30 hover:bg-[#CC0000] hover:text-white">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-12 text-center text-gray-500">Chưa có danh mục nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $categories->links() }}</div>
    </div>
</x-admin-layout>
```

- [ ] **Step 9: View `resources/views/admin/categories/form.blade.php`**

```blade
@php $isEdit = $category->exists; @endphp

<x-admin-layout :header="$isEdit ? 'Sửa Danh Mục' : 'Thêm Danh Mục'">
    <form method="POST"
          action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
          class="max-w-2xl bg-white p-6 rounded-3xl border border-black/10 shadow-sm space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <x-admin.field label="Tên danh mục" name="name" :value="$category->name" required />
        <x-admin.field label="Mô tả" name="description" type="textarea" :value="$category->description" />

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn-rose px-6 py-3 rounded-2xl text-xs font-black">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo danh mục' }}</button>
            <a href="{{ route('admin.categories.index') }}" class="px-6 py-3 rounded-2xl text-xs font-bold border border-black/10">Hủy</a>
        </div>
    </form>
</x-admin-layout>
```

- [ ] **Step 10: Link sidebar**

Trong `resources/views/layouts/admin.blade.php`, ở group "Quản Trị Nội Dung" (khối `<div class="space-y-1">`
chứa link `admin.events.index`, ~dòng 111), chèn **trước** thẻ `<a` của sự kiện một link mới
có cùng cấu trúc với link sự kiện, thay 3 chỗ:
`route('admin.events.index')` → `route('admin.categories.index')`,
`request()->routeIs('admin.events.*')` → `request()->routeIs('admin.categories.*')` (2 chỗ),
tiêu đề/nhãn `Quản Lý Sự Kiện` → `Danh Mục`. Icon dùng path:
`d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"`.

- [ ] **Step 11: Chạy test + build**

Run: `php artisan test --filter=AdminCategoryCrudTest && npm run build`
Expected: 7 test PASS, build thành công.

- [ ] **Step 12: Commit**

```bash
git add app/Models/Concerns app/Models/Category.php app/Http/Requests/Admin/CategoryRequest.php app/Http/Controllers/Admin/CategoryController.php resources/views/components/admin resources/css/app.css resources/views/admin/categories routes/web.php resources/views/layouts/admin.blade.php tests/Feature/AdminCategoryCrudTest.php
git commit -m "feat: add admin category CRUD"
```

---

### Task 4: Cột `is_seated`/`details` cho sự kiện + CRUD sự kiện có upload poster

**Files:**
- Create: `database/migrations/2026_09_21_000001_add_seating_and_details_to_events_table.php`
- Modify: `app/Models/Event.php`
- Modify: `database/factories/EventFactory.php`
- Create: `app/Http/Requests/Admin/EventRequest.php`
- Modify: `app/Http/Controllers/Admin/EventController.php`
- Create: `resources/views/admin/events/form.blade.php`
- Modify: `resources/views/admin/events/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AdminEventCrudTest.php`

**Interfaces:**
- Consumes: `GeneratesUniqueSlug::uniqueSlug()` (Task 3), `<x-admin.field>` (Task 3)
- Produces: cột `events.is_seated` (bool, default true), `events.details` (JSON, nullable)
- Produces: `Event::DETAIL_KEYS`; mọi key trong đó đọc được như thuộc tính: `$event->venue_name`, `$event->lineup`, `$event->timeline`, `$event->organizers`, `$event->entry_policy`, …
- Produces: `$event->is_seated_concert` (bool, alias của `is_seated` — view cũ dùng tên này)
- Produces: scope `Event::published()`
- Produces: routes `admin.events.{index,create,store,edit,update,destroy}`

- [ ] **Step 1: Viết test thất bại `tests/Feature/AdminEventCrudTest.php`**

```php
<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

function eventPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Đêm Nhạc Trịnh',
        'category_id' => Category::factory()->create()->id,
        'status' => 'published',
        'is_seated' => '1',
        'duration_minutes' => 150,
        'release_date' => now()->addWeek()->toDateString(),
        'description' => 'Mô tả',
        'details' => ['venue_name' => 'Nhà hát Hòa Bình', 'venue_address' => '240 3/2, Q.10'],
        'lineup_text' => "Hà Anh Tuấn | Ca sĩ | Khách mời | Headliner\nPhương Linh | Ca sĩ",
        'timeline_text' => "18:00 | Check-in | Đổi vé\n19:30 | Mở màn",
    ], $overrides);
}

test('admin creates an event with a poster and structured details', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), eventPayload([
            'poster' => UploadedFile::fake()->image('poster.jpg', 480, 720),
        ]))
        ->assertRedirect(route('admin.events.index'))
        ->assertSessionHas('success');

    $event = Event::firstWhere('title', 'Đêm Nhạc Trịnh');

    expect($event->slug)->toBe('dem-nhac-trinh')
        ->and($event->is_seated)->toBeTrue()
        ->and($event->venue_name)->toBe('Nhà hát Hòa Bình')
        ->and($event->lineup)->toHaveCount(2)
        ->and($event->lineup[0])->toBe(['name' => 'Hà Anh Tuấn', 'role' => 'Ca sĩ', 'tag' => 'Khách mời', 'badge' => 'Headliner'])
        ->and($event->lineup[1]['badge'])->toBe('')
        ->and($event->timeline[1])->toBe(['time' => '19:30', 'title' => 'Mở màn', 'desc' => '']);

    Storage::disk('public')->assertExists($event->poster_path);
});

test('updating an event keeps detail keys the form does not edit', function () {
    $event = Event::factory()->create([
        'details' => ['organizers' => ['lead' => 'TicketBox'], 'venue_name' => 'Cũ'],
    ]);

    $this->actingAs(createAdmin())
        ->put(route('admin.events.update', $event), eventPayload(['title' => 'Tên mới']))
        ->assertRedirect(route('admin.events.index'));

    $event->refresh();
    expect($event->title)->toBe('Tên mới')
        ->and($event->venue_name)->toBe('Nhà hát Hòa Bình')
        ->and($event->organizers)->toBe(['lead' => 'TicketBox']);
});

test('poster must be an image', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), eventPayload([
            'poster' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('poster');
});

test('admin deletes an event without sold tickets and its poster file', function () {
    Storage::disk('public')->put('posters/a.jpg', 'x');
    $event = Event::factory()->create(['poster_path' => 'posters/a.jpg']);

    $this->actingAs(createAdmin())
        ->delete(route('admin.events.destroy', $event))
        ->assertSessionHas('success');

    expect(Event::find($event->id))->toBeNull();
    Storage::disk('public')->assertMissing('posters/a.jpg');
});

test('an event with sold tickets cannot be deleted', function () {
    $room = Room::factory()->create();
    $seat = Seat::create(['room_id' => $room->id, 'row_label' => 'A', 'seat_number' => 1, 'type' => 'cat1_stand']);
    $showtime = Showtime::factory()->for($room)->create();
    ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'booked']);

    $this->actingAs(createAdmin())
        ->delete(route('admin.events.destroy', $showtime->event))
        ->assertSessionHas('error');

    expect(Event::find($showtime->event_id))->not->toBeNull();
});

test('admin event pages render', function () {
    $event = Event::factory()->create();
    $admin = createAdmin();

    $this->actingAs($admin)->get(route('admin.events.index'))->assertOk()->assertSee($event->title);
    $this->actingAs($admin)->get(route('admin.events.create'))->assertOk();
    $this->actingAs($admin)->get(route('admin.events.edit', $event))->assertOk()->assertSee($event->title);
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=AdminEventCrudTest`
Expected: FAIL — route `admin.events.store` chưa có.

- [ ] **Step 3: Migration**

`database/migrations/2026_09_21_000001_add_seating_and_details_to_events_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // true: khách chọn ghế trên sơ đồ; false: khách chọn hạng vé + số lượng, server tự gán ghế
            $table->boolean('is_seated')->default(true)->after('status');
            // Thông tin mô tả: venue_name, venue_address, lineup[], timeline[], organizers{}, entry_policy{}...
            $table->json('details')->nullable()->after('is_seated');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['is_seated', 'details']);
        });
    }
};
```

Run: `php artisan migrate`

- [ ] **Step 4: Cập nhật `app/Models/Event.php`**

1. Thêm `use App\Models\Concerns\GeneratesUniqueSlug;` và `use Illuminate\Database\Eloquent\Builder;`, đổi dòng trait thành `use GeneratesUniqueSlug, HasFactory;`.
2. Thêm `'is_seated', 'details'` vào `$fillable`.
3. `$casts` thành:

```php
    protected $casts = [
        'release_date' => 'date',
        'duration_minutes' => 'integer',
        'is_seated' => 'boolean',
        'details' => 'array',
    ];
```

4. Trong `boot()`, đổi `$event->slug = Str::slug($event->title);` thành `$event->slug = static::uniqueSlug($event->title);`.
5. Thêm vào class:

```php
    /** Các key trong cột JSON `details`, đọc được như thuộc tính thường: $event->venue_name */
    public const DETAIL_KEYS = [
        'venue_name', 'venue_address', 'venue_gates', 'parking_info',
        'participants_title', 'participants_summary', 'host_mc', 'special_guests',
        'timeline', 'lineup', 'organizers', 'entry_policy',
    ];

    public function getAttribute($key)
    {
        if (in_array($key, self::DETAIL_KEYS, true)) {
            return $this->details[$key] ?? null;
        }

        return parent::getAttribute($key);
    }

    /** Tên cũ các view đang dùng. */
    public function getIsSeatedConcertAttribute(): bool
    {
        return (bool) $this->is_seated;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
```

6. Xóa method `isEvent()` nếu `grep -rn "isEvent" app resources` không còn chỗ nào dùng; nếu còn thì giữ.

Trong `database/factories/EventFactory.php`, thêm vào `definition()`:

```php
            'is_seated' => true,
            'details' => null,
```

- [ ] **Step 5: Form Request `app/Http/Requests/Admin/EventRequest.php`**

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route đã nằm sau middleware role:admin
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_seated' => $this->boolean('is_seated')]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'is_seated' => ['boolean'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:1440'],
            'release_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'details' => ['nullable', 'array'],
            'details.venue_name' => ['nullable', 'string', 'max:255'],
            'details.venue_address' => ['nullable', 'string', 'max:500'],
            'details.host_mc' => ['nullable', 'string', 'max:255'],
            'details.special_guests' => ['nullable', 'string', 'max:500'],
            'details.participants_summary' => ['nullable', 'string', 'max:500'],
            'lineup_text' => ['nullable', 'string', 'max:5000'],
            'timeline_text' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'tên sự kiện',
            'category_id' => 'danh mục',
            'status' => 'trạng thái',
            'duration_minutes' => 'thời lượng',
            'release_date' => 'ngày mở bán',
            'poster' => 'poster',
        ];
    }

    /** Các cột thường của bảng events. */
    public function eventAttributes(): array
    {
        return $this->safe()->only([
            'title', 'category_id', 'status', 'is_seated', 'duration_minutes', 'release_date', 'description',
        ]);
    }

    /**
     * Gộp các ô chi tiết của form vào `details` hiện có.
     * Key form không quản lý (organizers, entry_policy, venue_gates…) được giữ nguyên.
     */
    public function mergedDetails(array $existing): array
    {
        $details = array_merge($existing, $this->validated('details') ?? []);
        $details['lineup'] = $this->parseLines($this->validated('lineup_text'), ['name', 'role', 'tag', 'badge']);
        $details['timeline'] = $this->parseLines($this->validated('timeline_text'), ['time', 'title', 'desc']);

        return array_filter($details, fn ($value) => $value !== null && $value !== []);
    }

    /** Mỗi dòng "a | b | c" -> ['key1' => 'a', 'key2' => 'b', ...], thiếu cột thì để chuỗi rỗng. */
    private function parseLines(?string $text, array $keys): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(function (string $line) use ($keys) {
                $parts = array_map('trim', explode('|', $line));

                return array_combine($keys, array_pad(array_slice($parts, 0, count($keys)), count($keys), ''));
            })
            ->values()
            ->all();
    }
}
```

- [ ] **Step 6: Controller `app/Http/Controllers/Admin/EventController.php` (thay toàn bộ file)**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Category;
use App\Models\Event;
use App\Models\ShowtimeSeat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $query = Event::with('category')->withCount('showtimes');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $events = $query->latest()->paginate(10)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.events.index', compact('events', 'categories'));
    }

    public function create(): View
    {
        return view('admin.events.form', [
            'event' => new Event(['status' => 'draft', 'is_seated' => true]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $event = new Event($request->eventAttributes());
        $event->details = $request->mergedDetails([]);

        if ($request->hasFile('poster')) {
            $event->poster_path = $request->file('poster')->store('posters', 'public');
        }

        $event->save();

        return redirect()->route('admin.events.index')
            ->with('success', "Đã tạo sự kiện [{$event->title}].");
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', [
            'event' => $event,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $event->fill($request->eventAttributes());
        $event->details = $request->mergedDetails($event->details ?? []);

        if ($request->hasFile('poster')) {
            $this->deleteStoredPoster($event);
            $event->poster_path = $request->file('poster')->store('posters', 'public');
        }

        $event->save();

        return redirect()->route('admin.events.index')
            ->with('success', "Đã cập nhật sự kiện [{$event->title}].");
    }

    public function destroy(Event $event): RedirectResponse
    {
        $hasSoldTickets = ShowtimeSeat::whereIn('showtime_id', $event->showtimes()->select('id'))
            ->where('status', 'booked')
            ->exists();

        if ($hasSoldTickets) {
            return back()->with('error', 'Sự kiện đã có vé bán ra, không thể xóa. Hãy chuyển trạng thái sang "Lưu trữ".');
        }

        $this->deleteStoredPoster($event);
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Đã xóa sự kiện.');
    }

    /** Chỉ xóa file đã upload lên disk public, bỏ qua poster là URL ngoài (dữ liệu seed). */
    private function deleteStoredPoster(Event $event): void
    {
        if ($event->poster_path && ! Str::startsWith($event->poster_path, ['http://', 'https://'])) {
            Storage::disk('public')->delete($event->poster_path);
        }
    }
}
```

- [ ] **Step 7: Routes**

Trong `routes/web.php`, thay dòng `Route::get('/events', [AdminEventController::class, 'index'])->name('events.index');`
bằng:

```php
    Route::resource('events', AdminEventController::class)->except('show');
```

- [ ] **Step 8: View form `resources/views/admin/events/form.blade.php`**

```blade
@php
    $isEdit = $event->exists;
    $lineupText = collect($event->lineup ?? [])->map(fn ($row) => implode(' | ', array_values($row)))->implode("\n");
    $timelineText = collect($event->timeline ?? [])->map(fn ($row) => implode(' | ', array_values($row)))->implode("\n");
@endphp

<x-admin-layout :header="$isEdit ? 'Sửa Sự Kiện' : 'Thêm Sự Kiện'">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $isEdit ? route('admin.events.update', $event) : route('admin.events.store') }}"
          class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-black/10 shadow-sm space-y-5">
            <h3 class="font-display font-black text-lg">Thông tin chính</h3>

            <x-admin.field label="Tên sự kiện" name="title" :value="$event->title" required />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="category_id" class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1.5">Danh mục <span class="text-[#CC0000]">*</span></label>
                    <select id="category_id" name="category_id" class="admin-input" required>
                        <option value="">— Chọn danh mục —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $event->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="mt-1 text-[11px] font-bold text-[#CC0000]">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="status" class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1.5">Trạng thái <span class="text-[#CC0000]">*</span></label>
                    <select id="status" name="status" class="admin-input" required>
                        @foreach (['draft' => 'Nháp (ẩn)', 'published' => 'Đang mở bán', 'archived' => 'Lưu trữ (ẩn)'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $event->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="mt-1 text-[11px] font-bold text-[#CC0000]">{{ $message }}</p>@enderror
                </div>
                <x-admin.field label="Thời lượng (phút)" name="duration_minutes" type="number" :value="$event->duration_minutes" min="15" max="1440" />
                <x-admin.field label="Ngày mở bán" name="release_date" type="date" :value="$event->release_date?->format('Y-m-d')" />
            </div>

            <label class="flex items-start gap-3 bg-[#F5F5DC] p-4 rounded-2xl border border-[#D8D8A8] text-xs">
                <input type="hidden" name="is_seated" value="0">
                <input type="checkbox" name="is_seated" value="1" class="mt-0.5" @checked(old('is_seated', $event->is_seated ?? true))>
                <span>
                    <strong class="block text-black">Chọn ghế trên sơ đồ</strong>
                    Bỏ chọn nếu là hội thảo / triển lãm / workshop: khách chỉ chọn hạng vé + số lượng, hệ thống tự gán chỗ.
                </span>
            </label>

            <x-admin.field label="Mô tả" name="description" type="textarea" :value="$event->description" rows="5" />

            <h3 class="font-display font-black text-lg pt-2">Chi tiết hiển thị</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.field label="Tên địa điểm" name="details[venue_name]" :value="$event->venue_name" />
                <x-admin.field label="Địa chỉ địa điểm" name="details[venue_address]" :value="$event->venue_address" />
                <x-admin.field label="MC / Người dẫn" name="details[host_mc]" :value="$event->host_mc" />
                <x-admin.field label="Khách mời đặc biệt" name="details[special_guests]" :value="$event->special_guests" />
            </div>
            <x-admin.field label="Tóm tắt thành phần tham gia" name="details[participants_summary]" :value="$event->participants_summary" />
            <x-admin.field label="Lineup (mỗi dòng: Tên | Vai trò | Nhóm | Huy hiệu)" name="lineup_text" type="textarea" :value="$lineupText" rows="5" />
            <x-admin.field label="Lịch trình (mỗi dòng: Giờ | Tiêu đề | Mô tả)" name="timeline_text" type="textarea" :value="$timelineText" rows="5" />
        </div>

        <div class="space-y-6">
            <div class="bg-white p-6 rounded-3xl border border-black/10 shadow-sm space-y-4">
                <h3 class="font-display font-black text-lg">Poster</h3>
                @if ($isEdit)
                    <img src="{{ $event->poster_url }}" alt="{{ $event->title }}" class="w-full aspect-[2/3] object-cover rounded-2xl border border-black/10">
                @endif
                <x-admin.field label="Tải ảnh mới" name="poster" type="file" accept="image/jpeg,image/png,image/webp" hint="JPG/PNG/WEBP, tối đa 2MB." />
            </div>

            <div class="bg-white p-6 rounded-3xl border border-black/10 shadow-sm flex flex-col gap-3">
                <button type="submit" class="btn-rose px-6 py-3 rounded-2xl text-xs font-black">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo sự kiện' }}</button>
                <a href="{{ route('admin.events.index') }}" class="text-center px-6 py-3 rounded-2xl text-xs font-bold border border-black/10">Hủy</a>
            </div>
        </div>
    </form>
</x-admin-layout>
```

- [ ] **Step 9: Sửa `resources/views/admin/events/index.blade.php`**

1. Nút "Thêm Sự Kiện Mới" (khối `<button type="button" class="btn-rose ...">` ~dòng 19–22): đổi thẻ
   `<button type="button"` thành `<a href="{{ route('admin.events.create') }}"` và `</button>` thành `</a>`.
2. Ảnh poster (~dòng 82): đổi `{{ $event->poster_path ?? '…' }}` thành `{{ $event->poster_url }}`.
3. Thay nút "Sửa" giả (khối `<button type="button" class="px-3.5 py-1.5 ...">Sửa</button>` ~dòng 129–131) bằng:

```blade
                                        <a href="{{ route('admin.events.edit', $event) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#FAF9F6] border border-black/10 hover:bg-black hover:text-white transition-all">Sửa</a>
                                        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Xóa sự kiện này? Toàn bộ suất diễn chưa bán vé sẽ bị xóa theo.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-[#CC0000] border border-[#CC0000]/30 hover:bg-[#CC0000] hover:text-white transition-all">Xóa</button>
                                        </form>
```

- [ ] **Step 10: Tạo symlink storage (một lần trên máy dev)**

Run: `php artisan storage:link`
Expected: `The [public/storage] link has been connected to [storage/app/public].`
(`public/storage` đã nằm trong `.gitignore` của Laravel — không commit.)

- [ ] **Step 11: Chạy test + build**

Run: `php artisan test && npm run build`
Expected: PASS toàn bộ (kể cả `AdminManagementRoutesTest` cũ).

- [ ] **Step 12: Commit**

```bash
git add database/migrations/2026_09_21_000001_add_seating_and_details_to_events_table.php app/Models/Event.php database/factories/EventFactory.php app/Http/Requests/Admin/EventRequest.php app/Http/Controllers/Admin/EventController.php resources/views/admin/events routes/web.php tests/Feature/AdminEventCrudTest.php
git commit -m "feat: add admin event CRUD with poster upload and structured details"
```

---

### Task 5: CRUD suất diễn + sinh ghế theo suất

**Files:**
- Create: `app/Services/ShowtimeSeatGenerator.php`
- Modify: `app/Models/Showtime.php` (bỏ `movie_id` khỏi `$fillable` — bảng không có cột này)
- Create: `app/Http/Requests/Admin/ShowtimeRequest.php`
- Modify: `app/Http/Controllers/Admin/ShowtimeController.php`
- Create: `resources/views/admin/showtimes/form.blade.php`
- Modify: `resources/views/admin/showtimes/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AdminShowtimeCrudTest.php`

**Interfaces:**
- Consumes: `RoomSeatGenerator` (Task 1), `<x-admin.field>` (Task 3)
- Produces: `App\Services\ShowtimeSeatGenerator::generate(Showtime $showtime): int` (tạo 1 dòng `showtime_seats` trạng thái `available` cho mỗi ghế của phòng, bỏ qua dòng đã có) và `regenerate(Showtime $showtime): int` (xóa rồi tạo lại). Plan 2 seeder dùng `generate()`.
- Produces: routes `admin.showtimes.{index,create,store,edit,update,destroy}`

- [ ] **Step 1: Viết test thất bại `tests/Feature/AdminShowtimeCrudTest.php`**

```php
<?php

use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;

function roomWithSeats(): Room
{
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'custom_grid', ['rows' => 3, 'cols' => 4]);

    return $room;
}

function showtimePayload(Room $room, array $overrides = []): array
{
    $start = now()->addDays(5)->setTime(19, 0);

    return array_merge([
        'event_id' => Event::factory()->create()->id,
        'room_id' => $room->id,
        'start_time' => $start->format('Y-m-d\TH:i'),
        'end_time' => $start->copy()->addHours(2)->format('Y-m-d\TH:i'),
        'base_price' => 250000,
    ], $overrides);
}

test('creating a showtime generates one available seat per room seat', function () {
    $room = roomWithSeats();

    $this->actingAs(createAdmin())
        ->post(route('admin.showtimes.store'), showtimePayload($room))
        ->assertRedirect(route('admin.showtimes.index'))
        ->assertSessionHas('success');

    $showtime = Showtime::first();
    expect($showtime->showtimeSeats()->count())->toBe(12)
        ->and($showtime->showtimeSeats()->where('status', 'available')->count())->toBe(12);
});

test('a showtime cannot overlap another one in the same room', function () {
    $room = roomWithSeats();
    $start = now()->addDays(5)->setTime(19, 0);
    Showtime::factory()->for($room)->create(['start_time' => $start, 'end_time' => $start->copy()->addHours(3)]);

    $this->actingAs(createAdmin())
        ->post(route('admin.showtimes.store'), showtimePayload($room, [
            'start_time' => $start->copy()->addHour()->format('Y-m-d\TH:i'),
            'end_time' => $start->copy()->addHours(4)->format('Y-m-d\TH:i'),
        ]))
        ->assertSessionHasErrors('start_time');

    expect(Showtime::count())->toBe(1);
});

test('end time must be after start time and start must be in the future', function () {
    $room = roomWithSeats();

    $this->actingAs(createAdmin())
        ->post(route('admin.showtimes.store'), showtimePayload($room, [
            'start_time' => now()->subDay()->format('Y-m-d\TH:i'),
            'end_time' => now()->subDays(2)->format('Y-m-d\TH:i'),
        ]))
        ->assertSessionHasErrors(['start_time', 'end_time']);
});

test('the room of a showtime with sold seats cannot be changed', function () {
    $showtime = Showtime::factory()->for(roomWithSeats())->create();
    app(ShowtimeSeatGenerator::class)->generate($showtime);
    $showtime->showtimeSeats()->first()->update(['status' => 'booked']);
    $otherRoom = roomWithSeats();

    $this->actingAs(createAdmin())
        ->put(route('admin.showtimes.update', $showtime), showtimePayload($otherRoom, ['event_id' => $showtime->event_id]))
        ->assertSessionHas('error');

    expect($showtime->fresh()->room_id)->not->toBe($otherRoom->id);
});

test('a showtime without sold seats can be deleted', function () {
    $showtime = Showtime::factory()->for(roomWithSeats())->create();
    app(ShowtimeSeatGenerator::class)->generate($showtime);

    $this->actingAs(createAdmin())
        ->delete(route('admin.showtimes.destroy', $showtime))
        ->assertSessionHas('success');

    expect(Showtime::count())->toBe(0)->and(ShowtimeSeat::count())->toBe(0);
});

test('a showtime with sold seats cannot be deleted', function () {
    $showtime = Showtime::factory()->for(roomWithSeats())->create();
    app(ShowtimeSeatGenerator::class)->generate($showtime);
    $showtime->showtimeSeats()->first()->update(['status' => 'booked']);

    $this->actingAs(createAdmin())
        ->delete(route('admin.showtimes.destroy', $showtime))
        ->assertSessionHas('error');

    expect(Showtime::count())->toBe(1);
});

test('the showtime list filters by event', function () {
    $a = Showtime::factory()->create();
    $b = Showtime::factory()->create();

    // Không dùng assertDontSee: dropdown lọc trên trang liệt kê mọi sự kiện.
    $this->actingAs(createAdmin())
        ->get(route('admin.showtimes.index', ['movie_id' => $a->event_id]))
        ->assertOk()
        ->assertViewHas('showtimes', fn ($page) => $page->pluck('id')->all() === [$a->id]);
});
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `php artisan test --filter=AdminShowtimeCrudTest`
Expected: FAIL — `Class "App\Services\ShowtimeSeatGenerator" not found` / route chưa có.
(Lưu ý test cuối hiện đang lỗi SQL vì controller lọc theo cột `movie_id` không tồn tại.)

- [ ] **Step 3: Service `app/Services/ShowtimeSeatGenerator.php`**

```php
<?php

namespace App\Services;

use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;

class ShowtimeSeatGenerator
{
    public function generate(Showtime $showtime): int
    {
        $now = now();

        $rows = Seat::where('room_id', $showtime->room_id)
            ->pluck('id')
            ->map(fn (int $seatId) => [
                'showtime_id' => $showtime->id,
                'seat_id' => $seatId,
                'status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            ShowtimeSeat::insertOrIgnore($chunk); // unique(showtime_id, seat_id) -> chạy lại không nhân đôi
        }

        return count($rows);
    }

    public function regenerate(Showtime $showtime): int
    {
        $showtime->showtimeSeats()->delete();

        return $this->generate($showtime);
    }
}
```

Trong `app/Models/Showtime.php`, xóa `'movie_id',` khỏi `$fillable`.

- [ ] **Step 4: Form Request `app/Http/Requests/Admin/ShowtimeRequest.php`**

```php
<?php

namespace App\Http\Requests\Admin;

use App\Models\Showtime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ShowtimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route đã nằm sau middleware role:admin
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'start_time' => ['required', 'date', 'after:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'base_price' => ['required', 'integer', 'min:10000', 'max:50000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'event_id' => 'sự kiện',
            'room_id' => 'khán phòng',
            'start_time' => 'giờ bắt đầu',
            'end_time' => 'giờ kết thúc',
            'base_price' => 'giá vé cơ bản',
        ];
    }

    /** Chặn 2 suất diễn chồng giờ trong cùng một khán phòng. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $current = $this->route('showtime');

                $overlaps = Showtime::where('room_id', $this->integer('room_id'))
                    ->when($current, fn ($query) => $query->whereKeyNot($current->id))
                    ->where('start_time', '<', $this->date('end_time'))
                    ->where('end_time', '>', $this->date('start_time'))
                    ->exists();

                if ($overlaps) {
                    $validator->errors()->add('start_time', 'Khán phòng đã có suất diễn khác trùng khung giờ này.');
                }
            },
        ];
    }
}
```

- [ ] **Step 5: Controller `app/Http/Controllers/Admin/ShowtimeController.php` (thay toàn bộ file)**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShowtimeRequest;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Services\ShowtimeSeatGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function index(Request $request): View
    {
        // Giữ eager-load qua alias `movie` vì view index đang đọc $showtime->movie.
        $query = Showtime::with(['movie.category', 'room', 'showtimeSeats']);

        // Tham số giữ tên `movie_id` vì view filter hiện có đang dùng tên này.
        if ($request->filled('movie_id')) {
            $query->where('event_id', $request->input('movie_id'));
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->input('room_id'));
        }

        $showtimes = $query->orderBy('start_time')->paginate(10)->withQueryString();
        $events = Event::orderBy('title')->get();
        $rooms = Room::orderBy('name')->get();

        return view('admin.showtimes.index', compact('showtimes', 'events', 'rooms'));
    }

    public function create(): View
    {
        return view('admin.showtimes.form', $this->formData(new Showtime(['base_price' => 200000])));
    }

    public function store(ShowtimeRequest $request, ShowtimeSeatGenerator $seats): RedirectResponse
    {
        $showtime = DB::transaction(function () use ($request, $seats) {
            $showtime = Showtime::create($request->validated());
            $seats->generate($showtime);

            return $showtime;
        });

        return redirect()->route('admin.showtimes.index')
            ->with('success', 'Đã tạo suất diễn với '.$showtime->showtimeSeats()->count().' chỗ.');
    }

    public function edit(Showtime $showtime): View
    {
        return view('admin.showtimes.form', $this->formData($showtime));
    }

    public function update(ShowtimeRequest $request, Showtime $showtime, ShowtimeSeatGenerator $seats): RedirectResponse
    {
        $roomChanged = (int) $request->validated('room_id') !== $showtime->room_id;

        if ($roomChanged && $showtime->showtimeSeats()->whereIn('status', ['held', 'booked'])->exists()) {
            return back()->withInput()->with('error', 'Suất diễn đã có ghế được giữ hoặc đã bán, không thể đổi khán phòng.');
        }

        DB::transaction(function () use ($request, $showtime, $seats, $roomChanged) {
            $showtime->update($request->validated());

            if ($roomChanged) {
                $seats->regenerate($showtime);
            }
        });

        return redirect()->route('admin.showtimes.index')->with('success', 'Đã cập nhật suất diễn.');
    }

    public function destroy(Showtime $showtime): RedirectResponse
    {
        if ($showtime->showtimeSeats()->where('status', 'booked')->exists()) {
            return back()->with('error', 'Suất diễn đã có vé bán ra, không thể xóa.');
        }

        $showtime->delete();

        return redirect()->route('admin.showtimes.index')->with('success', 'Đã xóa suất diễn.');
    }

    private function formData(Showtime $showtime): array
    {
        return [
            'showtime' => $showtime,
            'events' => Event::orderBy('title')->get(),
            'rooms' => Room::withCount('seats')->orderBy('name')->get(),
        ];
    }
}
```

- [ ] **Step 6: Routes**

Trong `routes/web.php`, thay dòng `Route::get('/showtimes', [AdminShowtimeController::class, 'index'])->name('showtimes.index');` bằng:

```php
    Route::resource('showtimes', AdminShowtimeController::class)->except('show');
```

- [ ] **Step 7: View form `resources/views/admin/showtimes/form.blade.php`**

```blade
@php $isEdit = $showtime->exists; @endphp

<x-admin-layout :header="$isEdit ? 'Sửa Suất Diễn' : 'Thêm Suất Diễn'">
    <form method="POST"
          action="{{ $isEdit ? route('admin.showtimes.update', $showtime) : route('admin.showtimes.store') }}"
          class="max-w-3xl bg-white p-6 rounded-3xl border border-black/10 shadow-sm space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div>
            <label for="event_id" class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1.5">Sự kiện <span class="text-[#CC0000]">*</span></label>
            <select id="event_id" name="event_id" class="admin-input" required>
                <option value="">— Chọn sự kiện —</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected(old('event_id', $showtime->event_id) == $event->id)>{{ $event->title }}</option>
                @endforeach
            </select>
            @error('event_id')<p class="mt-1 text-[11px] font-bold text-[#CC0000]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="room_id" class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1.5">Khán phòng <span class="text-[#CC0000]">*</span></label>
            <select id="room_id" name="room_id" class="admin-input" required>
                <option value="">— Chọn khán phòng —</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}" @selected(old('room_id', $showtime->room_id) == $room->id)>{{ $room->name }} ({{ $room->seats_count }} chỗ)</option>
                @endforeach
            </select>
            @error('room_id')<p class="mt-1 text-[11px] font-bold text-[#CC0000]">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-admin.field label="Bắt đầu" name="start_time" type="datetime-local" :value="$showtime->start_time?->format('Y-m-d\TH:i')" required />
            <x-admin.field label="Kết thúc" name="end_time" type="datetime-local" :value="$showtime->end_time?->format('Y-m-d\TH:i')" required />
            <x-admin.field label="Giá vé cơ bản (₫)" name="base_price" type="number" :value="$showtime->base_price" min="10000" step="1000" required hint="Giá từng hạng ghế = giá cơ bản + phụ thu hạng." />
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn-rose px-6 py-3 rounded-2xl text-xs font-black">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo suất diễn' }}</button>
            <a href="{{ route('admin.showtimes.index') }}" class="px-6 py-3 rounded-2xl text-xs font-bold border border-black/10">Hủy</a>
        </div>
    </form>
</x-admin-layout>
```

- [ ] **Step 8: Sửa `resources/views/admin/showtimes/index.blade.php`**

1. Nút đầu trang `<button type="button" class="btn-dark ...">` (~dòng 19–22): đổi thành
   `<a href="{{ route('admin.showtimes.create') }}" class="btn-dark ...">` và `</button>` → `</a>` (giữ nguyên class và nội dung).
2. Trong cột thao tác (khối `<div class="flex items-center justify-end gap-2">` ~dòng 115), thêm
   sau thẻ `</a>` "Xem Sơ Đồ":

```blade
                                        <a href="{{ route('admin.showtimes.edit', $showtime) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#FAF9F6] border border-black/10 hover:bg-black hover:text-white transition-all">Sửa</a>
                                        <form method="POST" action="{{ route('admin.showtimes.destroy', $showtime) }}" onsubmit="return confirm('Xóa suất diễn này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-[#CC0000] border border-[#CC0000]/30 hover:bg-[#CC0000] hover:text-white transition-all">Xóa</button>
                                        </form>
```

- [ ] **Step 9: Chạy test + build**

Run: `php artisan test && npm run build`
Expected: PASS toàn bộ.

- [ ] **Step 10: Kiểm tra tay trên trình duyệt**

`php artisan serve --port=8080`, đăng nhập `admin@ticketbox.vn` / `password`:
tạo danh mục → tạo sự kiện có poster → tạo suất diễn → bấm "Xem Sơ Đồ" không lỗi.
(Trang public vẫn là dữ liệu giả — bình thường, Plan 2 xử lý.)

- [ ] **Step 11: Commit**

```bash
git add app/Services/ShowtimeSeatGenerator.php app/Models/Showtime.php app/Http/Requests/Admin/ShowtimeRequest.php app/Http/Controllers/Admin/ShowtimeController.php resources/views/admin/showtimes routes/web.php tests/Feature/AdminShowtimeCrudTest.php
git commit -m "feat: add admin showtime CRUD with per-showtime seat generation"
```

---

### Task 6: Cập nhật tài liệu + mở PR

**Files:**
- Modify: `docs/architecture.md`

- [ ] **Step 1: Thêm vào bảng "Các thay đổi & Cập nhật so với Spec ban đầu" trong `docs/architecture.md`**

```markdown
| **2026-09-21** | **Chi tiết sự kiện** | Thêm cột `events.is_seated` (bool) và `events.details` (JSON: địa điểm, lineup, lịch trình, BTC, quy định) | Giữ nguyên giao diện chi tiết sự kiện giàu thông tin của nhóm mà không tạo thêm 5–6 bảng phụ. |
| **2026-09-21** | **An toàn dữ liệu** | Không sinh lại ghế / không xóa khán phòng khi đã có suất diễn; không xóa danh mục còn sự kiện; không xóa sự kiện/suất diễn đã bán vé | Các FK đang `cascade` — xóa nhầm sẽ mất vé đã bán. |
| **2026-09-21** | **Vé đứng (GA)** | Preset `mega_concert` sinh thêm hàng ghế ẩn `GA` loại `standing_pit` | Vé đứng dùng chung engine `showtime_seats`, server tự gán chỗ. |
```

Và sửa mục "Tiến độ hoàn thành" thêm dòng:
`- **Admin CRUD (Plan 1)**: danh mục, sự kiện (upload poster), suất diễn (chặn trùng giờ, tự sinh ghế theo suất).`

- [ ] **Step 2: Chạy toàn bộ test lần cuối**

Run: `php artisan test`
Expected: PASS toàn bộ. Dán output vào mô tả PR.

- [ ] **Step 3: Commit + push + PR**

```bash
git add docs/architecture.md
git commit -m "docs: record plan 1 schema and safety decisions"
git push -u origin feature/admin-crud-core
gh pr create --base main --title "Plan 1: Admin CRUD lõi + sửa lỗi mất vé khi sửa khán phòng" --body "Theo docs/superpowers/plans/2026-09-21-p1-admin-crud-core.md. Output php artisan test: <dán vào đây>"
```
