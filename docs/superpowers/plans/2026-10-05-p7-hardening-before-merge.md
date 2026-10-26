# Plan 7 — Vá lỗi nhánh `feature/update_UI` trước khi merge vào `main`

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sửa 3 lỗi tìm thấy khi audit nhánh `feature/update_UI` (2026-10-05) để nhánh này đủ an toàn merge vào `main`.

**Architecture:** Sửa tại chỗ trong controller/service hiện có, không thêm bảng, không thêm route. Mỗi lỗi = 1 task = 1 commit, có test Pest tái hiện lỗi trước khi sửa.

**Tech Stack:** Laravel 11, Pest 4, spatie/laravel-permission.

**Spec:** `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md` (mục 5: chống đặt trùng ghế)
**Lộ trình:** `docs/superpowers/plans/2026-09-21-roadmap-hoan-thien.md` (mục 6, cập nhật 2026-10-05)

## Global Constraints

- Nhánh: `feature/p7-hardening`, rẽ từ `origin/feature/update_UI` (KHÔNG rẽ từ `main` — `main` chưa có code Plan 1–3).
- PR: base `main`. PR này mang theo toàn bộ commit của `feature/update_UI` + 3 commit sửa lỗi.
- Không sửa migration đã có. Plan này không cần migration mới.
- Không đổi giao diện ngoài những dòng ghi trong task.
- `php artisan test` xanh trước mỗi commit; dán output vào mô tả PR.

## Review Focus

- Admin bấm "xác nhận" một đơn đã hủy mà ghế đã bán cho người khác → phải bị từ chối, không có 2 đơn cùng sở hữu 1 ghế (Task 1).
- Admin đổi trạng thái lặp lại cùng một giá trị (cancelled → cancelled) → không lỗi, không thay đổi ghế (Task 1).
- Mật khẩu tạm sau khi reset không đoán được và khác nhau giữa hai lần reset (Task 2).
- Ma trận ghế có 2 ô trùng (hàng, số ghế) → báo lỗi validate, sơ đồ cũ còn nguyên, không lỗi 500 (Task 3).
- Ma trận ghế chứa JSON hỏng hoặc quá lớn → báo lỗi validate (Task 3).

---

### Task 1: Chặn chuyển trạng thái đơn vé sai gây đặt trùng ghế

**Lỗi:** `Admin\BookingController@updateStatus` (`app/Http/Controllers/Admin/BookingController.php:55-97`) nhận mọi trạng thái từ request. Kịch bản: đơn A bị hủy → ghế về `available` → khách B mua đúng ghế đó → admin gửi `PATCH status=confirmed` cho đơn A → ghế bị ghi lại `booked`, **hai đơn cùng sở hữu một ghế**. Giao diện chỉ ẩn nút, server không chặn. Ngoài ra khi hủy không xóa `price_override`.

**Quy tắc mới:**
- Chỉ cho phép: `pending → confirmed`, `pending → cancelled`, `confirmed → cancelled`.
- `cancelled` là trạng thái cuối. Gửi trạng thái trùng trạng thái hiện tại → báo "không có thay đổi", không đụng ghế.
- `pending → confirmed`: khóa các `showtime_seats` của đơn (`lockForUpdate`), từ chối nếu có ghế nào thuộc một đơn **khác** chưa hủy, hoặc đang được user **khác** giữ còn hạn.

**Files:**
- Modify: `app/Http/Controllers/Admin/BookingController.php:55-97`
- Test: `tests/Feature/AdminBookingManagementTest.php` (thêm test cuối file, dùng lại `beforeEach` có sẵn)

**Interfaces:**
- Consumes: `ShowtimeSeat`, `BookingItem` (quan hệ `bookingItems()` trên `ShowtimeSeat`, `booking()` trên `BookingItem`).
- Produces: không có API mới; route và tên route giữ nguyên.

- [ ] **Step 1: Viết test thất bại**

Thêm vào cuối `tests/Feature/AdminBookingManagementTest.php`:

```php
test('a cancelled booking cannot be confirmed again once its seat is resold', function () {
    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $this->booking), [
        'status' => 'cancelled',
    ]);

    $buyer = User::factory()->create();
    $buyer->assignRole('user');
    $resale = Booking::create(['user_id' => $buyer->id, 'total_price' => 300000, 'status' => 'confirmed']);
    BookingItem::create(['booking_id' => $resale->id, 'showtime_seat_id' => $this->stSeat1->id, 'price' => 300000]);
    $this->stSeat1->update(['status' => 'booked']);

    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $this->booking), [
        'status' => 'confirmed',
    ])->assertRedirect()->assertSessionHas('error');

    expect($this->booking->fresh()->status)->toBe('cancelled');
});

test('confirming a pending booking is refused when another user holds one of its seats', function () {
    $this->booking->update(['status' => 'pending']);
    $other = User::factory()->create();
    $this->stSeat1->update(['status' => 'held', 'held_by_user_id' => $other->id, 'held_until' => now()->addMinutes(5)]);

    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $this->booking), [
        'status' => 'confirmed',
    ])->assertSessionHas('error');

    expect($this->booking->fresh()->status)->toBe('pending');
    expect($this->stSeat1->fresh()->held_by_user_id)->toBe($other->id);
});

test('setting the same status again changes nothing', function () {
    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $this->booking), ['status' => 'cancelled']);
    $this->stSeat1->update(['status' => 'held', 'held_by_user_id' => $this->admin->id, 'held_until' => now()->addMinutes(5)]);

    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $this->booking), ['status' => 'cancelled'])
        ->assertSessionHas('error');

    expect($this->stSeat1->fresh()->status)->toBe('held');
});

test('cancelling clears the price override of released seats', function () {
    $this->stSeat1->update(['price_override' => 999000]);

    $this->actingAs($this->admin)->patch(route('admin.bookings.update-status', $this->booking), ['status' => 'cancelled']);

    expect($this->stSeat1->fresh()->price_override)->toBeNull();
});
```

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run: `php artisan test --filter=AdminBookingManagementTest`
Expected: 4 test mới FAIL (không có `error` trong session / `price_override` vẫn 999000). Các test cũ vẫn PASS.

- [ ] **Step 3: Sửa controller**

Thay toàn bộ phương thức `updateStatus` trong `app/Http/Controllers/Admin/BookingController.php`:

```php
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['cancelled'],
        'cancelled' => [],
    ];

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,cancelled,pending',
        ]);
        $newStatus = $validated['status'];

        if (! in_array($newStatus, self::TRANSITIONS[$booking->status] ?? [], true)) {
            return back()->with('error', "Không thể chuyển đơn [{$booking->booking_code}] từ trạng thái \"{$booking->status}\" sang \"{$newStatus}\".");
        }

        $conflict = DB::transaction(function () use ($booking, $newStatus) {
            $seats = ShowtimeSeat::whereIn('id', $booking->items()->pluck('showtime_seat_id'))
                ->lockForUpdate()
                ->get();

            if ($newStatus === 'confirmed') {
                $takenByOtherBooking = BookingItem::whereIn('showtime_seat_id', $seats->pluck('id'))
                    ->where('booking_id', '!=', $booking->id)
                    ->whereHas('booking', fn ($query) => $query->where('status', '!=', 'cancelled'))
                    ->exists();
                $heldByOtherUser = $seats->contains(fn (ShowtimeSeat $seat) => $seat->status === 'held'
                    && $seat->held_by_user_id !== null
                    && $seat->held_by_user_id !== $booking->user_id
                    && $seat->held_until?->isFuture());

                if ($takenByOtherBooking || $heldByOtherUser) {
                    return true;
                }
            }

            $booking->update(['status' => $newStatus]);

            $seatState = $newStatus === 'cancelled'
                ? ['status' => 'available', 'held_by_user_id' => null, 'held_until' => null, 'price_override' => null]
                : ['status' => 'booked', 'held_by_user_id' => null, 'held_until' => null];
            ShowtimeSeat::whereIn('id', $seats->pluck('id'))->update($seatState);

            return false;
        });

        if ($conflict) {
            return back()->with('error', "Không thể xác nhận đơn [{$booking->booking_code}]: có ghế đã thuộc về đơn khác hoặc đang được người khác giữ.");
        }

        $message = match ($newStatus) {
            'cancelled' => "Đã hủy đơn vé [{$booking->booking_code}] và giải phóng toàn bộ ghế liên quan.",
            default => "Đã xác nhận thanh toán đơn vé [{$booking->booking_code}].",
        };

        return back()->with('success', $message);
    }
```

Thêm `use App\Models\BookingItem;` và `use App\Models\ShowtimeSeat;` vào đầu file nếu chưa có. `BookingItem::booking()` đã có sẵn (`app/Models/BookingItem.php:23`).

- [ ] **Step 4: Chạy test, xác nhận qua**

Run: `php artisan test --filter=AdminBookingManagementTest`
Expected: PASS toàn bộ, gồm test cũ "admin can confirm a pending booking" (ghế 2 của fixture chỉ thuộc chính đơn này nên không bị coi là xung đột).

- [ ] **Step 5: Chạy toàn bộ test rồi commit**

```bash
php artisan test
git add app/Http/Controllers/Admin/BookingController.php tests/Feature/AdminBookingManagementTest.php
git commit -m "fix(admin): guard booking status transitions against double-selling seats"
```

---

### Task 2: Mật khẩu tạm khi admin reset phải ngẫu nhiên

**Lỗi:** `Admin\UserController@resetPassword` (`app/Http/Controllers/Admin/UserController.php:151-159`) đặt mật khẩu `TicketBox@<năm>` cho mọi tài khoản. Ai biết quy tắc này đều đăng nhập được vào tài khoản vừa bị reset (kể cả tài khoản admin khác).

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php:151-159`
- Modify test: `tests/Feature/AdminUserManagementTest.php:106-117`, `tests/Feature/AdminRoleAndCrudVerificationTest.php:518-533`

**Interfaces:**
- Produces: flash session key `temp_password` (chuỗi 12 ký tự) bên cạnh `success`. View hiện chỉ đọc `success`, câu thông báo vẫn chứa mật khẩu nên không cần sửa view.

- [ ] **Step 1: Sửa 2 test cũ thành test cho hành vi mới**

Trong `tests/Feature/AdminUserManagementTest.php`, thay test `admin can reset password of a user to default temporary password` bằng:

```php
test('admin reset sets a random temporary password that differs on each reset', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $targetUser = User::factory()->create(['password' => 'old_secret_pwd']);

    $first = $this->actingAs($admin)->post(route('admin.users.reset-password', $targetUser))
        ->assertRedirect()->assertSessionHas('temp_password')->getSession()->get('temp_password');
    expect(Illuminate\Support\Facades\Hash::check($first, $targetUser->fresh()->password))->toBeTrue();
    expect(Illuminate\Support\Facades\Hash::check('TicketBox@'.date('Y'), $targetUser->fresh()->password))->toBeFalse();

    $second = $this->actingAs($admin)->post(route('admin.users.reset-password', $targetUser))
        ->getSession()->get('temp_password');
    expect($second)->not->toBe($first)->and(strlen($second))->toBe(12);
});
```

Trong `tests/Feature/AdminRoleAndCrudVerificationTest.php`, trong test `admin can reset user password and user can authenticate with the new temporary password`, thay 2 dòng:

```php
        $this->actingAs($this->admin)->post(route('admin.users.reset-password', $user));

        $tempPassword = 'TicketBox@'.date('Y');
```

bằng:

```php
        $tempPassword = $this->actingAs($this->admin)->post(route('admin.users.reset-password', $user))
            ->getSession()->get('temp_password');
```

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run: `php artisan test --filter="reset"`
Expected: FAIL — session không có `temp_password`.

- [ ] **Step 3: Sửa controller**

```php
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $tempPassword = Str::password(12, symbols: false);
        $user->password = $tempPassword;
        $user->save();

        return back()
            ->with('temp_password', $tempPassword)
            ->with('success', "Đã đặt lại mật khẩu cho tài khoản [{$user->name}] thành: {$tempPassword} (chỉ hiển thị một lần, hãy gửi cho người dùng).");
    }
```

Thêm `use Illuminate\Support\Str;` ở đầu file.

- [ ] **Step 4: Chạy test, xác nhận qua**

Run: `php artisan test --filter="reset"`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
php artisan test
git add app/Http/Controllers/Admin/UserController.php tests/Feature/AdminUserManagementTest.php tests/Feature/AdminRoleAndCrudVerificationTest.php
git commit -m "fix(admin): generate a random temporary password on reset"
```

---

### Task 3: Kiểm tra ma trận ghế tùy chỉnh trước khi xóa sơ đồ cũ

**Lỗi:** `Admin\RoomController@store/update` nhận `custom_layout_matrix` là chuỗi JSON không kiểm tra. `RoomSeatGenerator::generateFromMatrix` (`app/Services/RoomSeatGenerator.php:51`) **xóa ghế cũ trước**, rồi insert. Nếu ma trận có 2 ô trùng (hàng, số) thì vi phạm unique `seats(room_id,row_label,seat_number)` → lỗi 500 và phòng **mất hết ghế** (không có transaction). Ma trận không giới hạn kích thước.

**Files:**
- Create: `app/Http/Requests/Admin/RoomRequest.php`
- Modify: `app/Http/Controllers/Admin/RoomController.php` (`store`, `update` — bỏ 2 khối `$request->validate` + `json_decode` trùng lặp, dùng `RoomRequest`; bọc phần sinh ghế trong `DB::transaction`)
- Test: `tests/Feature/AdminRoomBuilderTest.php` (thêm test cuối file)

**Interfaces:**
- Produces: `RoomRequest::matrix(): ?array` — ma trận đã decode và đã kiểm tra, hoặc `null` nếu không gửi. `RoomRequest::seatConfig(): array` — mảng `base_price, rows, cols, vip_ratio, svip_ratio` (+ `matrix` nếu có), đúng như code hiện tại đang dựng.

- [ ] **Step 1: Viết test thất bại**

Thêm vào cuối `tests/Feature/AdminRoomBuilderTest.php` (dùng `createAdmin()` từ `tests/Pest.php`):

```php
function roomPayload(array $matrix): array
{
    return [
        'name' => 'Phòng Ma Trận',
        'address' => 'Q1',
        'capacity' => 100,
        'layout_preset' => 'theater_hall',
        'custom_layout_matrix' => json_encode($matrix),
    ];
}

test('a matrix with duplicate seat numbers is rejected and keeps the old seats', function () {
    $admin = createAdmin();
    $room = \App\Models\Room::create(['name' => 'Cũ', 'capacity' => 100, 'layout_preset' => 'theater_hall']);
    \App\Models\Seat::create(['room_id' => $room->id, 'row_label' => 'A', 'seat_number' => 1, 'type' => 'cat1_stand']);

    $this->actingAs($admin)->put(route('admin.rooms.update', $room), roomPayload([
        ['row' => 'A', 'seats' => [['number' => 1, 'type' => 'vip_gold'], ['number' => 1, 'type' => 'vip_gold']]],
    ]))->assertSessionHasErrors('custom_layout_matrix');

    expect($room->seats()->count())->toBe(1);
});

test('a broken or oversized matrix is rejected', function () {
    $admin = createAdmin();

    $this->actingAs($admin)->post(route('admin.rooms.store'), array_merge(roomPayload([]), [
        'custom_layout_matrix' => '{not json',
    ]))->assertSessionHasErrors('custom_layout_matrix');

    $huge = [['row' => 'A', 'seats' => array_map(fn ($n) => ['number' => $n, 'type' => 'cat1_stand'], range(1, 2001))]];
    $this->actingAs($admin)->post(route('admin.rooms.store'), roomPayload($huge))
        ->assertSessionHasErrors('custom_layout_matrix');

    expect(\App\Models\Room::count())->toBe(0);
});

test('a valid matrix still creates the room seats', function () {
    $admin = createAdmin();

    $this->actingAs($admin)->post(route('admin.rooms.store'), roomPayload([
        ['row' => 'A', 'seats' => [['number' => 1, 'type' => 'vip_gold'], ['number' => 2, 'type' => 'aisle'], ['number' => 3, 'type' => 'vip_gold']]],
    ]))->assertRedirect(route('admin.rooms.index'));

    expect(\App\Models\Seat::where('row_label', 'A')->count())->toBe(2);
});
```

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run: `php artisan test --filter=AdminRoomBuilderTest`
Expected: test trùng ghế FAIL với `QueryException` (UNIQUE constraint); test JSON hỏng FAIL vì phòng vẫn được tạo.

- [ ] **Step 3: Tạo `app/Http/Requests/Admin/RoomRequest.php`**

```php
<?php

namespace App\Http\Requests\Admin;

use App\Services\RoomSeatGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RoomRequest extends FormRequest
{
    public const MAX_MATRIX_SEATS = 2000;

    private ?array $decodedMatrix = null;

    public function authorize(): bool
    {
        return true; // route đã có middleware role:admin
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'capacity' => 'required|integer|min:10|max:50000',
            'layout_preset' => 'required|string|in:'.implode(',', RoomSeatGenerator::PRESETS),
            'base_price' => 'nullable|numeric|min:10000',
            'rows' => 'nullable|integer|min:2|max:26',
            'cols' => 'nullable|integer|min:4|max:40',
            'vip_ratio' => 'nullable|integer|min:0|max:100',
            'svip_ratio' => 'nullable|integer|min:0|max:100',
            'custom_layout_matrix' => 'nullable|string',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $raw = $this->input('custom_layout_matrix');
            if ($raw === null || $raw === '') {
                return;
            }

            $matrix = json_decode($raw, true);
            if (! is_array($matrix) || ! array_is_list($matrix)) {
                $validator->errors()->add('custom_layout_matrix', 'Sơ đồ ghế không hợp lệ.');

                return;
            }

            $codes = [];
            foreach ($matrix as $row) {
                $label = trim((string) ($row['row'] ?? ''));
                if (mb_strlen($label) > 10) {
                    $validator->errors()->add('custom_layout_matrix', "Tên hàng \"{$label}\" quá dài (tối đa 10 ký tự).");

                    return;
                }
                foreach ((array) ($row['seats'] ?? []) as $seat) {
                    if (! empty($seat['is_aisle']) || ($seat['type'] ?? '') === 'aisle') {
                        continue;
                    }
                    $code = $label.'-'.(int) ($seat['number'] ?? 1);
                    if (isset($codes[$code])) {
                        $validator->errors()->add('custom_layout_matrix', "Ghế {$code} bị trùng trong sơ đồ.");

                        return;
                    }
                    $codes[$code] = true;
                }
            }

            if (count($codes) > self::MAX_MATRIX_SEATS) {
                $validator->errors()->add('custom_layout_matrix', 'Sơ đồ tối đa '.self::MAX_MATRIX_SEATS.' ghế.');

                return;
            }

            $this->decodedMatrix = $matrix === [] ? null : $matrix;
        }];
    }

    public function matrix(): ?array
    {
        return $this->decodedMatrix;
    }

    public function seatConfig(): array
    {
        $config = [
            'base_price' => (float) ($this->validated('base_price') ?? 250000),
            'rows' => (int) ($this->validated('rows') ?? 10),
            'cols' => (int) ($this->validated('cols') ?? 14),
            'vip_ratio' => (int) ($this->validated('vip_ratio') ?? 30),
            'svip_ratio' => (int) ($this->validated('svip_ratio') ?? 15),
        ];
        if ($this->matrix() !== null) {
            $config['matrix'] = $this->matrix();
        }

        return $config;
    }
}
```

- [ ] **Step 4: Dùng `RoomRequest` trong `RoomController`**

Trong `app/Http/Controllers/Admin/RoomController.php`: thêm `use App\Http\Requests\Admin\RoomRequest;` và `use Illuminate\Support\Facades\DB;`. Thay `store` và `update` bằng:

```php
    public function store(RoomRequest $request, RoomSeatGenerator $generator): RedirectResponse
    {
        $room = DB::transaction(function () use ($request, $generator) {
            $room = Room::create([
                'name' => $request->validated('name'),
                'address' => $request->validated('address') ?? 'Trung tâm Tổ chức Sự kiện',
                'capacity' => (int) $request->validated('capacity'),
                'layout_preset' => $request->validated('layout_preset'),
                'seat_config' => $request->seatConfig(),
            ]);
            $this->generateSeats($room, $request, $generator);

            return $room;
        });

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã tạo khán phòng [{$room->name}] và lưu sơ đồ tùy chỉnh thành công!");
    }

    public function update(RoomRequest $request, Room $room, RoomSeatGenerator $generator): RedirectResponse
    {
        $info = [
            'name' => $request->validated('name'),
            'address' => $request->validated('address') ?? $room->address,
            'capacity' => (int) $request->validated('capacity'),
        ];

        if ($room->showtimes()->exists()) {
            // Đã có suất diễn: KHÔNG sinh lại ghế (sẽ cascade xóa vé đã bán).
            $room->update($info);

            return redirect()->route('admin.rooms.index')
                ->with('warning', "Đã cập nhật thông tin [{$room->name}]. Khán phòng đã có suất diễn nên sơ đồ ghế được giữ nguyên. Hãy dùng nút Nhân Bản nếu muốn tạo phiên bản sơ đồ mới!");
        }

        DB::transaction(function () use ($request, $room, $generator, $info) {
            $room->update($info + [
                'layout_preset' => $request->validated('layout_preset'),
                'seat_config' => $request->seatConfig(),
            ]);
            $this->generateSeats($room, $request, $generator);
        });

        return redirect()->route('admin.rooms.index')
            ->with('success', "Đã cập nhật khán phòng [{$room->name}] và tái tạo sơ đồ ghế thành công!");
    }

    private function generateSeats(Room $room, RoomRequest $request, RoomSeatGenerator $generator): void
    {
        if ($request->matrix() !== null) {
            $generator->generateFromMatrix($room, $request->matrix(), $request->validated('layout_preset'));
        } else {
            $generator->generate($room, $request->validated('layout_preset'), $request->seatConfig());
        }
    }
```

Đồng thời bọc thân `duplicate()` trong `DB::transaction(function () use ($room) { ... })` (trả `$newRoom` ra ngoài) để không còn phòng bản sao thiếu ghế khi insert lỗi.

- [ ] **Step 5: Chạy test, xác nhận qua**

Run: `php artisan test --filter=AdminRoomBuilderTest`
Expected: PASS toàn bộ (test cũ + 3 test mới).

- [ ] **Step 6: Commit**

```bash
php artisan test
git add app/Http/Requests/Admin/RoomRequest.php app/Http/Controllers/Admin/RoomController.php tests/Feature/AdminRoomBuilderTest.php
git commit -m "fix(admin): validate custom seat matrix before replacing room seats"
```

---

### Task 4: Gộp commit lạc + cập nhật tài liệu + mở PR

Commit `948f33d` (bnglananh, "fix(ui): update home category tabs, hide guest cart badge, and polish auth text") nằm trên `origin/feature/ui-core-function-ticketbox`, **không** có trong `feature/update_UI`. Nhánh `update_UI` đã tự sửa phần lớn (ẩn badge giỏ, tab danh mục) nên rất có thể commit này xung đột.

- [ ] **Step 1: Thử cherry-pick**

```bash
git cherry-pick 948f33d
```

Nếu xung đột ở `resources/views/home.blade.php`, `layouts/site.blade.php`, `auth/login.blade.php`, `auth/register.blade.php`: **giữ phiên bản của `update_UI`** (`git checkout --ours <file>`), chỉ lấy thêm những chỉnh sửa chữ (text) không có ở bản `update_UI`. Nếu sau khi xử lý không còn thay đổi nào: `git cherry-pick --abort` và ghi vào mô tả PR "948f33d đã được bao phủ bởi update_UI".

- [ ] **Step 2: Cập nhật `docs/architecture.md`**

Thêm vào mục quản trị (hoặc cuối file) 3 dòng:

```markdown
- Đơn vé: chỉ cho phép `pending→confirmed`, `pending→cancelled`, `confirmed→cancelled`; `cancelled` là trạng thái cuối. Xác nhận bị từ chối nếu ghế đã thuộc đơn khác hoặc đang được người khác giữ.
- Reset mật khẩu bởi admin: sinh mật khẩu ngẫu nhiên 12 ký tự, hiển thị một lần trong flash message.
- Sơ đồ ghế tùy chỉnh: `Admin\RoomRequest` kiểm tra JSON, trùng (hàng, số), tối đa 2000 ghế; xóa + sinh lại ghế chạy trong transaction.
```

- [ ] **Step 3: Build + test + push + PR**

```bash
npm run build
php artisan test
git add docs/architecture.md
git commit -m "docs: record booking status rules, password reset and seat matrix validation"
git push -u origin feature/p7-hardening
gh pr create --base main --title "Merge update_UI (Plan 1-3 + UI) and hardening fixes" --body "<tóm tắt 3 lỗi + output php artisan test>"
```
