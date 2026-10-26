# Plan 8 — Sơ đồ chỗ ngồi theo khu vực (mỗi loại sự kiện một kiểu bố trí)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mỗi địa điểm có sơ đồ riêng gồm nhiều **khu vực** (ghế đánh số, bàn tiệc, khu đứng/vào cửa tự do) với tên, giá, màu do admin đặt — thay cho 6 loại ghế "concert" đang viết cứng — và sửa lỗi đặt trùng ghế ở trang quản trị đơn.

**Architecture:** Thêm bảng `seat_zones` (thuộc `rooms`); mỗi `seats` trỏ tới 1 khu (`zone_id`) và có vị trí cột (`pos_col`) để vẽ lối đi. Khu đứng vẫn dùng ghế ẩn trong cùng engine `showtime_seats` (không đổi cơ chế khóa dòng chống trùng). Mẫu bố trí là dữ liệu PHP (`LayoutTemplates`) dùng chung cho seeder, trình dựng admin và test; trang chọn ghế có một trình vẽ chung theo khu thay cho 4 bản vẽ viết tay.

**Tech Stack:** Laravel 11, Pest, Blade + Tailwind + Alpine.js, MySQL 8 (test: SQLite in-memory).

**Spec:** `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md` (mục 3 rooms/seats/showtime_seats, chống double-booking). Plan này mở rộng mục 3 bằng bảng `seat_zones` — thiết kế ở mục "Thiết kế" ngay dưới (user giao cho supervisor chọn hướng, 2026-10-26).
**Lộ trình:** `docs/superpowers/plans/2026-09-21-roadmap-hoan-thien.md` mục 7.

## Thiết kế

### Ba kiểu khu (`seat_zones.kind`)

| kind | Khách đặt thế nào | Ghế trong DB |
|---|---|---|
| `seated` | Chọn từng ghế trên sơ đồ | Ghế thật, có `row_label`, `seat_number`, `pos_col` |
| `table` | Chọn từng ghế quanh bàn, hoặc bấm "cả bàn" | Mỗi bàn là một `row_label` (B01, B02…), ghế 1..n |
| `standing` | Chọn số lượng | `capacity` ghế ẩn, `row_label` = mã khu, `pos_col` null |

Sự kiện `is_seated = false` (hội thảo, triển lãm…): **mọi khu** bán theo số lượng, server tự gán ghế đầu tiên còn trống trong khu (hàng trước được gán trước).

### Giá

Giá 1 vé = `showtimes.base_price` + `seat_zones.surcharge` (surcharge có thể âm), tối thiểu 0. Ghế cũ không có khu (chỉ còn trong test tự tạo ghế) giữ cách tính cũ `TicketTiers::seatPrice()`.

### Sáu mẫu bố trí (`rooms.layout_preset`) — nội dung chốt

| preset | Dùng cho | Khu (code · tên · kind · level · phụ thu) | Hàng ghế |
|---|---|---|---|
| `mega_concert` | Concert sân vận động | GA-L · Fanzone đứng trái · standing · 1 · −20.000 (40 chỗ); SVIP · SVIP sát sân khấu · seated · 1 · +150.000; GA-R · Fanzone đứng phải · standing · 1 · −20.000 (40 chỗ); VIP · VIP mặt sàn · seated · 2 · +80.000; KD-C · Khán đài C (cánh trái) · seated · 3 · 0; KD-A · Khán đài A (trung tâm) · seated · 3 · +40.000; KD-D · Khán đài D (cánh phải) · seated · 3 · 0; SKY · Skybox VIP · seated · 4 · +300.000 | SVIP-A, SVIP-B: 12 ghế; FL-1..FL-4: 7 ghế + lối đi + 7 ghế; C1, C2: 10; A1..A3: 4 + lối đi + 8 + lối đi + 4; D1, D2: 10; SB: 8 |
| `theater_hall` | Nhà hát, giao hưởng, nhạc kịch, fan meeting | STALLS · Tầng trệt (Stalls) · seated · 1 · +80.000; CIRCLE · Tầng lửng (Dress Circle) · seated · 2 · +40.000; GALLERY · Ban công (Gallery) · seated · 3 · 0 | ST-A..ST-E: 8 + lối đi + 8; DC-A..DC-C: 7 + lối đi + 7; GL-A, GL-B: 18 |
| `convention_center` | Hội thảo, diễn đàn | KEYNOTE · Hàng ghế VIP Keynote · seated · 1 · +100.000; BL-L · Khối trái · seated · 2 · 0; BL-C · Khối giữa · seated · 2 · +20.000; BL-R · Khối phải · seated · 2 · 0 | KN-A, KN-B: 12; L1..L5: 6; C1..C5: 10; R1..R5: 6 |
| `banquet_hall` | Workshop, gala, masterclass | VIPT · Bàn VIP gần sân khấu · table · 1 · +120.000; STDT · Bàn tiêu chuẩn · table · 2 · 0 | B01..B04: 8 ghế; B05..B12: 6 ghế |
| `open_space` | Triển lãm, festival ngoài trời | VIPX · Vé VIP / Fast-track · standing · 1 · +150.000 (50 chỗ); GEN · Vé vào cửa phổ thông · standing · 1 · 0 (300 chỗ) | không có |
| `custom_grid` | Admin tự vẽ | VIP · Ghế VIP · seated · 1 · +80.000; STD · Ghế tiêu chuẩn · seated · 2 · 0 | `rows` hàng (A, B, …) × `cols` ghế; 2 hàng đầu thuộc VIP |

Màu, cổng, mô tả, quyền lợi từng khu: người làm tự viết (tiếng Việt, ngắn), màu hex khác nhau trong cùng mẫu.

### Cách vẽ sơ đồ cho khách

Sân khấu ở trên cùng (kiểu vẽ theo preset: sân khấu chữ T cho `mega_concert`, sân khấu nhà hát cho `theater_hall`, bục + màn chiếu cho `convention_center`, sân khấu nhỏ cho `banquet_hall`, cổng vào cho `open_space`, thanh "SÂN KHẤU" cho `custom_grid`). Dưới đó, khu xếp theo `level` tăng dần; các khu cùng `level` nằm cạnh nhau theo `sort_order` (trái → phải), xuống dòng trên màn hình hẹp. Khu `seated`: mỗi hàng là một CSS grid, ghế đặt ở cột `pos_col` nên lối đi thành khoảng trống. Khu `table`: mỗi bàn là hình tròn, ghế xếp quanh. Khu `standing`: một ô ghi "còn N chỗ" + nút −/+.

## Global Constraints

- Nhánh làm việc: `feature/hoan-thien`, rẽ từ `feature/update_UI`. Mỗi task 1 commit. Không push/mở PR khi user chưa bảo.
- Không sửa migration đã có — chỉ thêm migration mới.
- Roles chỉ `admin`, `user`; mọi route quản trị nằm dưới `admin.*` + `role:admin`.
- Test bằng Pest; `php artisan test` xanh trước mỗi commit.
- Sau task có sửa `resources/js` hoặc `resources/css`: chạy `npm run build`.
- Chữ hiển thị: tiếng Việt. Tối đa 8 vé / lượt đặt (`SeatHoldService::MAX_SEATS`), giữ chỗ 10 phút — giữ nguyên.
- Không đụng tới chức năng đặt lại mật khẩu (user bỏ qua Plan 7 Task 2, 2026-10-26).
- Bỏ vé "Combo nhóm 2 người" (`combo_pass`): thay bằng khu do admin đặt; ghi lại trong `docs/architecture.md`.

## Review Focus

- Hai khách cùng bấm "giữ" cùng một ghế hoặc cùng mua vé khu đứng còn 1 chỗ → chỉ 1 người thành công (Task 4: test khu đứng còn 1 chỗ).
- Gửi `zones[id]` của khu thuộc **địa điểm khác** hoặc khu ghế có số khi sự kiện có sơ đồ → bị từ chối, không giữ gì (Task 4).
- Admin dán layout có hai hàng trùng nhãn, ô trỏ tới khu không tồn tại, hoặc JSON hỏng → báo lỗi, sơ đồ cũ còn nguyên, không lỗi 500 (Task 5).
- Admin sửa giá khu của địa điểm **đã có suất diễn** → giá mới áp dụng cho lượt giữ sau, ghế và vé đã bán không đổi (Task 5).
- Sự kiện không có sơ đồ đặt ở địa điểm có khu `table` → khách vẫn mua được theo số lượng, server tự gán ghế quanh bàn (Task 4).

---

### Task 1: Chặn chuyển trạng thái đơn vé sai (Plan 7 Task 1)

Làm **nguyên văn Plan 7 Task 1** (`docs/superpowers/plans/2026-10-05-p7-hardening-before-merge.md`): 4 test mới trong `tests/Feature/AdminBookingManagementTest.php`, thay `Admin\BookingController@updateStatus` bằng bảng chuyển trạng thái `TRANSITIONS`, khóa ghế bằng `lockForUpdate`, hủy thì xóa `price_override`.

- [ ] **Step 1:** Thêm 4 test của Plan 7 Task 1 Step 1.
- [ ] **Step 2:** `php artisan test --filter=AdminBookingManagementTest` → 4 test mới FAIL.
- [ ] **Step 3:** Sửa controller theo Plan 7 Task 1 Step 3.
- [ ] **Step 4:** `php artisan test` → toàn bộ PASS.
- [ ] **Step 5:** Commit `fix(admin): block booking status transitions that could double-sell seats`.

Plan 7 Task 2 (mật khẩu) bỏ theo yêu cầu user; Plan 7 Task 3 (validate ma trận ghế) được thay bằng Task 5 của plan này.

---

### Task 2: Bảng khu vực `seat_zones` và giá theo khu

**Files:**
- Create: `database/migrations/2026_10_26_000001_create_seat_zones_table.php`
- Create: `app/Models/SeatZone.php`
- Modify: `app/Models/Room.php` (thêm `zones()`), `app/Models/Seat.php` (fillable `zone_id`, `pos_col`; `zone()`; `isVip()`), `app/Models/ShowtimeSeat.php` (`getEffectivePriceAttribute`)
- Test: `tests/Feature/SeatZoneTest.php`

**Interfaces:**
- Produces:
  - Bảng `seat_zones`: `id`, `room_id` (FK `rooms`, cascadeOnDelete), `code` string(12), `name` string(120), `kind` string(20), `level` unsignedTinyInteger default 1, `sort_order` unsignedSmallInteger default 0, `surcharge` integer default 0, `color` string(7) default `#3A5A40`, `gate` string(80) nullable, `description` string(500) nullable, `perks` json nullable, `capacity` unsignedSmallInteger nullable (chỉ khu standing), timestamps; unique(`room_id`, `code`).
  - Cột mới ở `seats`: `zone_id` foreignId nullable constrained `seat_zones` (KHÔNG cascade — xóa khu phải xóa ghế trước), `pos_col` unsignedSmallInteger nullable.
  - `SeatZone::KINDS = ['seated', 'table', 'standing']`; casts `perks` array; `room(): BelongsTo`, `seats(): HasMany`; `priceFor(int $basePrice): int` = `max(0, $basePrice + $this->surcharge)`; `isStanding(): bool`; `isPickable(): bool` (seated hoặc table).
  - `Room::zones(): HasMany` sắp theo `level`, `sort_order`.
  - `Seat::zone(): BelongsTo`. `Seat::isVip()`: nếu có khu → `surcharge > 0`; nếu không → giữ danh sách type cũ.
  - `ShowtimeSeat::effective_price`: `price_override` → `seat->zone->priceFor(base)` → `TicketTiers::seatPrice()` (ghế không có khu).
- Migration backfill: với mỗi `(room_id, seats.type)` đang có, tạo 1 khu `code` = type viết hoa cắt 12 ký tự, tên/phụ thu/màu lấy theo bảng cũ của `TicketTiers::for(0, true)` (`standing_pit` → kind `standing`, `capacity` = số ghế), rồi gán `zone_id`, `pos_col` = `seat_number`. Viết map tên/phụ thu ngay trong migration (không gọi class app).

- [ ] **Step 1: Viết test thất bại** — `tests/Feature/SeatZoneTest.php`:

```php
test('zone price is base price plus surcharge and never negative', function () {
    $zone = new SeatZone(['surcharge' => 80000]);
    expect($zone->priceFor(200000))->toBe(280000)
        ->and((new SeatZone(['surcharge' => -300000]))->priceFor(200000))->toBe(0);
});

test('a seat in a zone is priced from its zone', function () {
    $showtime = Showtime::factory()->create(['base_price' => 200000]);
    $zone = SeatZone::create(['room_id' => $showtime->room_id, 'code' => 'VIP', 'name' => 'VIP', 'kind' => 'seated', 'surcharge' => 50000]);
    $seat = Seat::create(['room_id' => $showtime->room_id, 'zone_id' => $zone->id, 'row_label' => 'A', 'seat_number' => 1, 'pos_col' => 1, 'type' => 'VIP']);
    $showtimeSeat = ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'available']);

    expect($showtimeSeat->effective_price)->toBe(250000)
        ->and($seat->isVip())->toBeTrue();
});

test('room zones are ordered by level then sort order', function () {
    $room = Room::factory()->create();
    SeatZone::create(['room_id' => $room->id, 'code' => 'B', 'name' => 'B', 'kind' => 'seated', 'level' => 2]);
    SeatZone::create(['room_id' => $room->id, 'code' => 'A2', 'name' => 'A2', 'kind' => 'seated', 'level' => 1, 'sort_order' => 1]);
    SeatZone::create(['room_id' => $room->id, 'code' => 'A1', 'name' => 'A1', 'kind' => 'seated', 'level' => 1, 'sort_order' => 0]);

    expect($room->zones()->pluck('code')->all())->toBe(['A1', 'A2', 'B']);
});
```

- [ ] **Step 2:** `php artisan test --filter=SeatZoneTest` → FAIL (class `SeatZone` không tồn tại).
- [ ] **Step 3:** Tạo migration (kèm backfill), model, quan hệ và sửa `effective_price` như Interfaces.
- [ ] **Step 4:** `php artisan test` → PASS toàn bộ (`SeatPricingTest` cũ vẫn xanh nhờ nhánh fallback).
- [ ] **Step 5:** Commit `feat(venue): add seat zones with per-zone pricing`.

---

### Task 3: Sáu mẫu bố trí và bộ sinh ghế theo khu

**Files:**
- Create: `app/Support/LayoutTemplates.php`
- Modify: `app/Services/RoomSeatGenerator.php` (viết lại), `app/Models/Room.php` (`getPresetLabelAttribute`, `getSeatsSummaryAttribute`)
- Test: `tests/Feature/LayoutTemplatesTest.php`; sửa `tests/Feature/AdminRoomSafetyTest.php` (test "mega concert preset includes a hidden standing row" → kiểm tra 2 khu standing GA-L, GA-R mỗi khu 40 ghế)

**Interfaces:**
- Consumes: `SeatZone`, `Seat.zone_id`, `Seat.pos_col` (Task 2).
- Produces:
  - Dạng **layout** dùng chung toàn plan (PHP array, cũng là JSON admin gửi lên):
    ```
    ['zones' => [['code','name','kind','level','surcharge','color','gate','description','perks','capacity'], ...],
     'rows'  => [['label' => 'FL-1', 'cells' => [['zone' => 'VIP'], ['aisle' => true], ...]], ...]]
    ```
    Thứ tự `zones` = `sort_order`. Ô `blocked` (ghế kỹ thuật) được lưu như `aisle`.
  - `LayoutTemplates::PRESETS = ['mega_concert','theater_hall','convention_center','banquet_hall','open_space','custom_grid']`
  - `LayoutTemplates::get(string $preset, array $options = []): array` — trả layout theo bảng ở mục Thiết kế; `custom_grid` đọc `$options['rows']` (kẹp 2..26, mặc định 10) và `$options['cols']` (kẹp 4..40, mặc định 14).
  - `LayoutTemplates::all(): array` — `[preset => ['label', 'subtitle', 'icon', 'layout']]` cho trình dựng admin (custom_grid mặc định 10×14).
  - `LayoutTemplates::fromRoom(Room $room): array` — đọc ngược khu + ghế hiện có của phòng thành layout (ô trống giữa các `pos_col` thành `aisle`).
  - `RoomSeatGenerator::apply(Room $room, array $layout): int` — xóa ghế rồi xóa khu cũ của phòng, tạo khu, tạo ghế (số ghế đếm 1..n trong từng hàng, bỏ qua lối đi; `pos_col` = vị trí ô, tính từ 1; `type` = mã khu), tạo `capacity` ghế ẩn cho mỗi khu standing (`row_label` = mã khu). Trả về tổng số ghế. CHỈ gọi khi phòng chưa có suất diễn.
  - `RoomSeatGenerator::generate(Room $room, string $preset, array $options = []): int` = `apply($room, LayoutTemplates::get($preset, $options))`. Bỏ `generateFromMatrix()`, `STANDING_ROW`, `STANDING_CAPACITY`, `RoomSeatGenerator::PRESETS` (mọi chỗ dùng chuyển sang `LayoutTemplates::PRESETS`).
  - `Room::seats_summary`: `['total' => int, 'zones' => [['code','name','color','kind','count'], ...]]`. `preset_label` có thêm `banquet_hall` (🍽️ Bàn tiệc & Workshop) và `open_space` (🎪 Không gian mở).

- [ ] **Step 1: Viết test thất bại** — `tests/Feature/LayoutTemplatesTest.php`:

```php
test('every preset produces zones and only references its own zones', function (string $preset) {
    $layout = LayoutTemplates::get($preset);
    $codes = collect($layout['zones'])->pluck('code');

    expect($codes)->not->toBeEmpty()->and($codes->duplicates())->toBeEmpty();
    foreach ($layout['rows'] as $row) {
        foreach ($row['cells'] as $cell) {
            expect(isset($cell['aisle']) || $codes->contains($cell['zone']))->toBeTrue();
        }
    }
})->with(LayoutTemplates::PRESETS);

test('theater rows leave a centre aisle gap', function () {
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'theater_hall');

    $row = $room->seats()->where('row_label', 'ST-A')->orderBy('pos_col')->get();
    expect($row)->toHaveCount(16)
        ->and($row->pluck('pos_col')->contains(9))->toBeFalse()
        ->and($row->last()->seat_number)->toBe(16)
        ->and($row->last()->pos_col)->toBe(17);
});

test('banquet tables become rows of chairs in table zones', function () {
    $room = Room::factory()->create();
    app(RoomSeatGenerator::class)->generate($room, 'banquet_hall');

    expect($room->zones()->pluck('kind')->unique()->all())->toBe(['table'])
        ->and($room->seats()->where('row_label', 'B01')->count())->toBe(8)
        ->and($room->seats()->where('row_label', 'B12')->count())->toBe(6);
});

test('open space has only standing zones with hidden seats', function () {
    $room = Room::factory()->create();
    $total = app(RoomSeatGenerator::class)->generate($room, 'open_space');

    expect($total)->toBe(350)
        ->and($room->seats()->whereNotNull('pos_col')->count())->toBe(0)
        ->and($room->zones()->where('code', 'GEN')->first()->seats()->count())->toBe(300);
});

test('a room layout can be read back and regenerated identically', function () {
    $room = Room::factory()->create();
    $generator = app(RoomSeatGenerator::class);
    $generator->generate($room, 'mega_concert');
    $before = $room->seats()->orderBy('id')->get(['row_label', 'seat_number', 'pos_col', 'type'])->toArray();

    $copy = Room::factory()->create();
    $generator->apply($copy, LayoutTemplates::fromRoom($room));

    expect($copy->seats()->orderBy('id')->get(['row_label', 'seat_number', 'pos_col', 'type'])->toArray())->toBe($before);
});
```

Thêm vào cuối file: `custom_grid` 6×10 → 60 ghế, 20 ghế thuộc khu `VIP`.

- [ ] **Step 2:** `php artisan test --filter=LayoutTemplatesTest` → FAIL.
- [ ] **Step 3:** Viết `LayoutTemplates`, viết lại `RoomSeatGenerator`, sửa `Room`; đổi `AdminRoomSafetyTest` như ghi ở Files. `RoomController` tạm thời gọi `generate()` (Task 5 viết lại hẳn).
- [ ] **Step 4:** `php artisan test` → PASS.
- [ ] **Step 5:** Commit `feat(venue): add six zone-based layout templates and generator`.

---

### Task 4: Giữ chỗ theo khu (thay hạng vé cố định)

**Files:**
- Create: `app/Support/SeatMap.php` (thay `app/Support/SeatMapPresenter.php` — xóa file cũ)
- Modify: `app/Services/SeatHoldService.php`, `app/Http/Requests/HoldSeatsRequest.php`, `app/Http/Controllers/SeatHoldController.php`, `app/Http/Controllers/ShowtimeController.php`, `app/Http/Controllers/EventController.php`, `app/Http/Controllers/CartController.php`, `app/Support/TicketTiers.php`
- Test: sửa `tests/Feature/SeatHoldServiceTest.php`, `tests/Feature/SeatHoldHttpTest.php`, `tests/Unit/TicketTiersTest.php`; tạo `tests/Feature/SeatMapDataTest.php`

**Interfaces:**
- Consumes: `SeatZone::priceFor()`, `isStanding()`, `isPickable()` (Task 2); `RoomSeatGenerator::generate()` (Task 3, dùng trong test).
- Produces:
  - `SeatHoldService::hold(User $user, Showtime $showtime, array $showtimeSeatIds, array $zoneQuantities = []): Collection` — `$zoneQuantities` là `[zone_id => số vé]`. Quy tắc:
    - Ghế chọn tay: sự kiện phải `is_seated`; ghế thuộc khu `standing` → lỗi `Khu đứng bán theo số lượng, không chọn theo ghế.`
    - Theo số lượng: khu phải thuộc phòng của suất diễn, nếu không → `Khu vực không hợp lệ.`; sự kiện `is_seated` mà khu không phải standing → `Khu này chọn ghế trên sơ đồ.`
    - Gán tự động: `showtime_seats` holdable có `seats.zone_id` = khu, sắp theo `showtime_seats.id`, `limit`, `lockForUpdate`. Thiếu chỗ → `Khu "{tên}" chỉ còn {n} chỗ.`
    - Giá chốt vào `price_override` = `zone->priceFor(base_price)` (ghế không khu: `TicketTiers::seatPrice`).
    - Các quy tắc cũ (8 vé, 1 suất / user, suất đã bắt đầu, sự kiện chưa mở bán, tất cả hoặc không) giữ nguyên.
  - `HoldSeatsRequest`: bỏ `tiers`; thêm `zones` (`nullable|array|max:12`), `zones.*` (`integer|min:0|max:8`); `zoneQuantities(): array<int,int>` (bỏ phần tử 0).
  - `SeatMap::forShowtime(Showtime $showtime): array` →
    ```
    ['preset' => string, 'is_seated' => bool,
     'levels' => [level => [zoneArray, ...]],          // theo level tăng dần, trong level theo sort_order
     'available_total' => int]
    zoneArray = ['id','code','name','kind','color','gate','description','perks','price','available','total',
                 'max_col' => int, 'rows' => [['label', 'seats' => [['id','number','col','code','status']]]]]
    ```
    `id` của ghế = id `showtime_seats`; `status` = `publicStatus()`; khu standing có `rows` rỗng. Một truy vấn cho ghế (eager `seat.zone`), không N+1.
  - `SeatMap::zoneCards(Showtime $showtime): list<zoneArray-không-rows>` — cho thẻ giá ở trang chi tiết sự kiện.
  - `ShowtimeController@seats` truyền `seatMap` (= `SeatMap::forShowtime`) thay cho `seats` + `ticketTiers`. `EventController@show` truyền `zoneCards` của suất sắp tới đầu tiên (rỗng nếu không có suất) thay cho `ticketTiers`.
  - `CartController`: tên hạng của ghế = `seat->zone->name`, fallback tên cũ.
  - `TicketTiers`: xóa `PASS_TIERS`, nhánh non-seated của `for()`, `isPassTier`, `passSeatTypes`, `seatsPerUnit`, `pricePerSeat`; giữ `for(int $basePrice)` (chỉ bảng ghế cũ), `normalizeSeatType`, `seatPrice` — ghi docblock "chỉ dùng cho ghế cũ không có khu".

- [ ] **Step 1: Viết test thất bại.** Trong `SeatHoldServiceTest.php`, thay 4 test pass/standing cũ (`non seated events sell passes…`, `non seated events reject hand picked seats and unknown tiers`, `a pass tier fails…`, `standing tickets are assigned…`) bằng:

```php
test('non seated events sell any zone by quantity and the server picks front seats', function () {
    // phòng convention_center, sự kiện is_seated=false
    $held = $service->hold($user, $showtime, [], [$keynote->id => 2]);
    expect($held->pluck('seat.row_label')->unique()->all())->toBe(['KN-A'])
        ->and($held->first()->price_override)->toBe($showtime->base_price + 100000);
});

test('seated events sell standing zones by quantity but not numbered zones', function () {
    // phòng mega_concert, is_seated=true
    expect($service->hold($user, $showtime, [], [$gaLeft->id => 3]))->toHaveCount(3);
    expect(fn () => $service->hold($user, $showtime, [], [$vip->id => 1]))
        ->toThrow(SeatHoldException::class, 'Khu này chọn ghế trên sơ đồ.');
});

test('standing seats cannot be hand picked', function () { /* lấy 1 ghế GA-L → 'Khu đứng bán theo số lượng' */ });

test('a zone of another room is rejected', function () { /* zone phòng khác → 'Khu vực không hợp lệ.' */ });

test('the last standing place goes to one customer only', function () {
    // khu standing capacity còn 1: user A giữ 1 thành công, user B giữ 1 → 'chỉ còn 0 chỗ'
});

test('table zones can be sold by quantity for non seated events', function () {
    // phòng banquet_hall, is_seated=false, [$stdt->id => 4] → 4 ghế cùng bàn B05
});
```

`SeatHoldHttpTest`: test `standing tickets can be held by quantity` gửi `['zones' => [$gaLeft->id => 2]]`, assert 200. `TicketTiersTest`: xóa 3 test pass/combo, giữ test giá ghế cũ. `SeatMapDataTest.php`:

```php
test('seat map groups zones by level with sold and held states', function () {
    // mega_concert; bán 1 ghế SVIP, giữ còn hạn 1 ghế VIP
    $map = SeatMap::forShowtime($showtime);
    expect(collect($map['levels'][1])->pluck('code')->all())->toBe(['GA-L', 'SVIP', 'GA-R'])
        ->and(collect($map['levels'][3])->pluck('code')->all())->toBe(['KD-C', 'KD-A', 'KD-D']);
    // ghế đã bán có status 'booked', ghế giữ 'held'; available của SVIP giảm 1; GA-L rows rỗng, available 40
});

test('aisles show up as gaps in seat columns', function () {
    // theater_hall: hàng ST-A có max_col 17, không ghế nào col 9
});
```

- [ ] **Step 2:** `php artisan test --filter="SeatHold|SeatMapData|TicketTiers"` → FAIL.
- [ ] **Step 3:** Cài đặt theo Interfaces. Xóa `SeatMapPresenter.php`. Trang `showtimes/seats` và `events/show` tạm dùng biến mới (giao diện mới ở Task 6) — chỉ cần không lỗi.
- [ ] **Step 4:** `php artisan test` → PASS (cho phép sửa `SeatMapPageTest`, `EventBookingFlowTest` chỗ nào gửi `tiers` sang `zones`).
- [ ] **Step 5:** Commit `feat(booking): hold seats and standing places per zone`.

---

### Task 5: Trình dựng sơ đồ admin theo khu + kiểm tra dữ liệu (thay Plan 7 Task 3)

**Files:**
- Create: `app/Http/Requests/RoomLayoutRequest.php`
- Modify: `app/Http/Controllers/Admin/RoomController.php`, `resources/views/admin/rooms/builder.blade.php`, `resources/views/admin/rooms/index.blade.php` (modal xem sơ đồ: màu theo khu), `resources/js/app.js` (`adminSeatMapBuilder`)
- Test: sửa `tests/Feature/AdminRoomBuilderTest.php`; tạo `tests/Feature/AdminRoomLayoutValidationTest.php`

**Interfaces:**
- Consumes: `LayoutTemplates::all()`, `get()`, `fromRoom()`, `RoomSeatGenerator::apply()` (Task 3).
- Produces:
  - Form gửi: `name`, `address`, `capacity` (giữ rule cũ), `layout_preset` (`in:` `LayoutTemplates::PRESETS`), `layout` (`required|string|max:200000`, JSON).
  - `RoomLayoutRequest::layout(): array` — layout đã kiểm tra. Lỗi gắn vào key `layout`, thông báo tiếng Việt chỉ rõ chỗ sai. Luật:
    - JSON hợp lệ, có `zones` (1..20 phần tử) và `rows` (0..60).
    - Khu: `code` khớp `/^[A-Z0-9-]{1,12}$/`, không trùng; `name` 1..120; `kind` thuộc `SeatZone::KINDS`; `level` 1..6; `surcharge` số nguyên −1.000.000..10.000.000; `color` `/^#[0-9A-Fa-f]{6}$/`; `gate` ≤ 80; `description` ≤ 500; `perks` ≤ 6 chuỗi ≤ 80; khu standing có `capacity` 1..2000.
    - Hàng: `label` khớp `/^[A-Za-z0-9-]{1,12}$/`, không trùng (không phân biệt hoa thường) và không trùng mã khu standing; 1..40 ô; mỗi ô là `aisle` hoặc `zone` trỏ tới khu `seated`/`table`.
    - Tổng ghế (ô ghế + capacity khu đứng) 1..5000.
  - `store`: tạo phòng + `apply()`. `update`: phòng **chưa** có suất diễn → `apply()`; **đã có** suất diễn → chỉ cập nhật thông tin phòng và các trường `name`, `surcharge`, `color`, `gate`, `description`, `perks` của khu trùng `code`; không thêm/xóa khu hay ghế; flash `warning` như cũ.
  - `duplicate`: phòng mới + `apply($newRoom, LayoutTemplates::fromRoom($room))`.
  - `builder`/`create` truyền cho view: `templates` (`LayoutTemplates::all()`) và `initialLayout` (`fromRoom($room)` khi sửa, layout mẫu `mega_concert` khi tạo).
  - JS `adminSeatMapBuilder(config)`: state `preset`, `zones`, `rows`, `selectedTool` (mã khu | `aisle`); `selectPreset(p)` nạp `config.templates[p].layout` (sao chép sâu); `addZone(kind)`, `removeZone(code)` (ô của khu bị xóa thành `aisle`); `paintCell(r, c)`, `paintRow(r)`, `addRow()`, `removeRow(r)`, `addCell(r)`, `removeCell(r)`; `zoneColor(code)`; getter `layoutJson` cho input ẩn `layout`; getter `stats` (số ghế từng khu + tổng gồm khu đứng). Xóa `buildDefaultMatrix`, `generatedTiers`, `presetsMeta`, `calculatedTotalSeats` (dữ liệu nay đến từ server). Bảng "hạng vé tự sinh" bên trái thay bằng **bảng khu sửa được** (tên, loại, tầng/level, phụ thu, màu, sức chứa nếu là khu đứng, cổng, mô tả).

- [ ] **Step 1: Viết test thất bại** — `AdminRoomLayoutValidationTest.php` (dùng admin như `AdminRoomBuilderTest`):

```php
test('duplicate row labels are rejected and the old seats survive', function () {
    // phòng đã generate theater_hall, gửi update layout có 2 hàng 'A'
    $this->actingAs($admin)->put(route('admin.rooms.update', $room), $payload)->assertSessionHasErrors('layout');
    expect($room->seats()->count())->toBe($seatCountBefore);
});

test('broken json is rejected without a server error', function () {
    $this->actingAs($admin)->post(route('admin.rooms.store'), [...$base, 'layout' => '{oops'])
        ->assertSessionHasErrors('layout');
    expect(Room::count())->toBe(0);
});

test('a cell pointing to an unknown or standing zone is rejected', function () { /* ... */ });

test('standing zone capacity becomes hidden seats', function () {
    // layout 1 khu standing capacity 120, rows rỗng → 120 ghế pos_col null
});

test('editing zones of a room with showtimes changes prices but not seats', function () {
    // phòng có suất diễn; đổi surcharge khu STALLS 80000 → 50000, thêm 1 hàng mới vào layout
    // → seats()->count() không đổi, zone STALLS surcharge = 50000, session có 'warning'
});

test('duplicating a room copies zones and seats', function () { /* số khu, số ghế, mã khu bằng phòng gốc */ });
```

`AdminRoomBuilderTest.php`: các test tạo/sửa phòng gửi `layout` = `json_encode(LayoutTemplates::get(...))`; test custom grid gửi `LayoutTemplates::get('custom_grid', ['rows' => 6, 'cols' => 10])` → 60 ghế; test mega concert kiểm tra có khu `SVIP`, `VIP`, `SKY` thay cho type cũ; trang create `assertSee` tên 6 mẫu.

- [ ] **Step 2:** `php artisan test --filter=AdminRoom` → FAIL.
- [ ] **Step 3:** Cài đặt request, controller, view, JS theo Interfaces. Modal ở `rooms/index`: mỗi ghế lấy `color` của khu; `RoomController@index` eager load `zones` và `seats:id,room_id,zone_id,row_label,seat_number,pos_col` (bớt cột, giải quyết ghi nhận "nạp toàn bộ ghế" ở roadmap mục 6).
- [ ] **Step 4:** `npm run build` thành công; `php artisan test` → PASS.
- [ ] **Step 5:** Mở `/admin/rooms/create` trên trình duyệt (tài khoản admin): chọn lần lượt 6 mẫu, đổi giá 1 khu, vẽ lối đi, lưu → trang danh sách hiện phòng mới, modal sơ đồ đúng màu khu. Không có lỗi console.
- [ ] **Step 6:** Commit `feat(admin): zone-based room builder with layout validation`.

---

### Task 6: Trang chọn chỗ cho khách — một trình vẽ chung theo khu

**Files:**
- Modify (viết lại): `resources/views/components/seat-map.blade.php`
- Create: `resources/views/components/seat-map/stage.blade.php`, `zone-seated.blade.php`, `zone-table.blade.php`, `zone-standing.blade.php`, `zone-cards.blade.php` (thẻ khu + bộ đếm số lượng cho sự kiện không sơ đồ)
- Modify: `resources/views/showtimes/seats.blade.php`, `resources/views/events/show.blade.php` (khối hạng vé dùng `zoneCards`), `resources/js/app.js` (`seatBookingManager`)
- Test: sửa `tests/Feature/SeatMapPageTest.php`

**Interfaces:**
- Consumes: `SeatMap::forShowtime()` / `zoneCards()` (Task 4); endpoint hold nhận `seat_ids`, `zones` (Task 4).
- Produces:
  - `<x-seat-map :showtime :movie :seat-map />`. Giữ nguyên: khung tối kiểu arena, thanh header (tên địa điểm, sức chứa), zoom, "Live Inspector" khi rê chuột, ngăn kéo vé đang chọn, toast, polling 15 giây.
  - Lọc khu: hàng chip theo khu (thay tab khu cũ); chọn chip → khu khác mờ đi (`activeZoneFilter` = mã khu | `all`).
  - Ghế: màu nền = màu khu (inline style), đã bán xám mờ, đang giữ viền vàng nhấp nháy, đang chọn phóng to + viền trắng. Bàn: hình tròn ghi nhãn bàn, ghế xếp quanh theo góc chia đều (tính vị trí trong Blade), có nút "Chọn cả bàn" (chọn mọi ghế còn trống của bàn, không vượt 8 vé).
  - JS `seatBookingManager(config)`: `config.zones` = `{zoneId: {name, price, kind}}`; state `selectedSeats`, `zoneQuantities` (`{zoneId: n}`); hàm `toggleSeat(seat)` nhận object `{id, code, zoneId, zoneName, price, status}`, `toggleTable(seats)`, `incrementZone(id)`, `decrementZone(id)`, `zoneCount(id)`; `confirmHold()` gửi `{seat_ids, zones}`. Bỏ `standingCount`, `tierQuantities`, `tierMeta`, `incrementTier`/`decrementTier`/`addStandingTicket`/`removeStandingTicket`.
  - Sự kiện `is_seated = false`: không vẽ sơ đồ, hiện `zone-cards` (tên, giá, mô tả, quyền lợi, "còn N chỗ", −/+).

- [ ] **Step 1: Viết test thất bại** — trong `SeatMapPageTest.php` thay 2 test hiển thị cũ bằng:

```php
test('stadium page shows standing pits beside the numbered zones', function () {
    $this->get(route('showtimes.seats', $stadiumShowtime))->assertOk()
        ->assertSeeInOrder(['Fanzone đứng trái', 'SVIP sát sân khấu', 'Fanzone đứng phải'])
        ->assertSee('còn 40 chỗ');
});

test('theater page shows stalls circle and gallery', function () {
    $this->get(route('showtimes.seats', $theaterShowtime))->assertOk()
        ->assertSeeInOrder(['Tầng trệt (Stalls)', 'Tầng lửng (Dress Circle)', 'Ban công (Gallery)']);
});

test('banquet page draws tables with a whole table button', function () {
    $this->get(route('showtimes.seats', $banquetShowtime))->assertOk()->assertSee('B01')->assertSee('Chọn cả bàn');
});

test('non seated events show zone cards with quantity steppers', function () {
    $this->get(route('showtimes.seats', $openSpaceShowtime))->assertOk()
        ->assertSee('Vé vào cửa phổ thông')->assertSee('còn 300 chỗ')->assertDontSee('Chọn cả bàn');
});
```

- [ ] **Step 2:** `php artisan test --filter=SeatMapPageTest` → FAIL.
- [ ] **Step 3:** Viết lại component + partials + JS theo Interfaces.
- [ ] **Step 4:** `npm run build`; `php artisan test` → PASS.
- [ ] **Step 5:** Kiểm tra trên trình duyệt (sau Task 7 có dữ liệu demo — nếu chưa, làm bước này cuối Task 7): đăng nhập `user@ticketbox.vn`, với mỗi kiểu (concert, nhà hát, hội thảo, workshop bàn, triển lãm) chọn vé → "Xác nhận giữ chỗ" → giỏ vé hiện đúng tên khu và giá. Thử màn hình rộng 375px: không cuộn ngang trang (sơ đồ được phép cuộn ngang trong khung riêng).
- [ ] **Step 6:** Commit `feat(booking): render every venue from its zones on one seat map`.

---

### Task 7: Dữ liệu demo — mỗi sự kiện một kiểu bố trí hợp lý

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php` (`VENUES`, `SCHEDULE`, vòng tạo phòng), `database/seeders/data/events.php` (cờ `is_seated` của fan meeting và masterclass)
- Test: sửa `tests/Feature/DatabaseSeederTest.php`

**Interfaces:**
- Consumes: `RoomSeatGenerator::generate()` (Task 3).
- Produces — sự kiện → địa điểm → mẫu:

| Sự kiện (slug) | Địa điểm | preset | is_seated |
|---|---|---|---|
| live-concert-anh-trai-vuot-ngan-chong-gai-2026 | SVĐ QK7 (có sẵn) | mega_concert | true |
| hoa-nhac-giao-huong-saigon-philharmonic | Nhà Hát Thành Phố (có sẵn) | theater_hall | true |
| nhac-kich-les-miserables-vn | Nhà Hát Thành Phố | theater_hall | true |
| fan-meeting-running-man-vn-2026 | Nhà Hát Hòa Bình (có sẵn) | theater_hall | **true** (đổi từ false) |
| vietnam-tech-summit-ai-expo-2026 | SECC Hall A (có sẵn) | convention_center | false |
| masterclass-sang-tao-phim-ai-2026 | **Mới:** SECC Phòng Workshop B2, cùng địa chỉ SECC | banquet_hall | **true** (đổi từ false) |
| trien-lam-nghe-thuat-van-gogh-impressionism | **Mới:** Không gian Nghệ thuật The Factory, 15 Trần Ngọc Diện, Thảo Điền, TP Thủ Đức (10.8015, 106.7350) | open_space | false |
| saigon-autumn-music-light-festival-2026 | **Mới:** Công viên Bờ Sông Sài Gòn, Tôn Đức Thắng, Bến Nghé, Quận 1 (10.7745, 106.7065) | open_space | false |

  Tọa độ hai địa điểm mới là gần đúng (dữ liệu demo). Giờ suất giữ như `SCHEDULE` cũ; suất nào đổi phòng thì kiểm tra không chồng giờ trong cùng phòng. Đơn mẫu `TBX-89214` (giao hưởng) và `TBX-77102` (nhạc kịch) giữ nguyên.

- [ ] **Step 1: Viết test thất bại** — trong `DatabaseSeederTest.php` thay test `the concert runs in the stadium room with a standing row` bằng:

```php
test('each demo event runs in a venue with a fitting layout', function () {
    $this->seed();
    $preset = fn (string $slug) => Event::where('slug', $slug)->first()->showtimes()->first()->room->layout_preset;

    expect($preset('live-concert-anh-trai-vuot-ngan-chong-gai-2026'))->toBe('mega_concert')
        ->and($preset('masterclass-sang-tao-phim-ai-2026'))->toBe('banquet_hall')
        ->and($preset('trien-lam-nghe-thuat-van-gogh-impressionism'))->toBe('open_space')
        ->and($preset('saigon-autumn-music-light-festival-2026'))->toBe('open_space')
        ->and(Event::where('slug', 'fan-meeting-running-man-vn-2026')->first()->is_seated)->toBeTrue();
});

test('every seat of every demo room belongs to a zone of that room', function () {
    $this->seed();
    expect(Seat::whereNull('zone_id')->count())->toBe(0)
        ->and(Seat::join('seat_zones', 'seat_zones.id', '=', 'seats.zone_id')
            ->whereColumn('seat_zones.room_id', '!=', 'seats.room_id')->count())->toBe(0);
});
```

- [ ] **Step 2:** `php artisan test --filter=DatabaseSeederTest` → FAIL.
- [ ] **Step 3:** Sửa seeder + dữ liệu sự kiện.
- [ ] **Step 4:** `php artisan test` → PASS.
- [ ] **Step 5:** Commit `feat(seed): give each demo event a venue layout that fits it`.

---

### Task 8: Tài liệu

**Files:**
- Modify: `docs/architecture.md` (ERD thêm `seat_zones`; mục "Sơ đồ chỗ ngồi theo khu": 3 kiểu khu, 6 mẫu, cách tính giá, bỏ `combo_pass`, `TicketTiers` chỉ còn cho ghế cũ), `docs/superpowers/plans/2026-09-21-roadmap-hoan-thien.md` (mục 7: Plan 8 xong, thứ tự tiếp theo), `docs/superpowers/plans/2026-10-05-p7-hardening-before-merge.md` (ghi chú đầu file: Task 1 làm trong Plan 8 Task 1, Task 2 bỏ theo user, Task 3 thay bằng Plan 8 Task 5)

- [ ] **Step 1:** Cập nhật 3 file.
- [ ] **Step 2:** `php artisan test` → PASS; dán kết quả vào commit message.
- [ ] **Step 3:** Commit `docs: record zone-based seating design`.
