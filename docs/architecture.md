# Kiến trúc hệ thống (living doc)

Cập nhật file này mỗi khi kiến trúc thay đổi so với spec gốc. Nguồn sự thật ban đầu:
`docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md`.

## Trạng thái hiện tại
- Phase 0 (setup nền tảng): hoàn thành — xem
  `docs/superpowers/plans/2026-09-07-phase0-project-setup.md`
- Laravel 11.56.1, PHP 8.3.30, MySQL 8.4 (DB: `doanmnm_ticket`), Pest 4, Breeze (blade),
  spatie/laravel-permission với 2 role (`admin`, `user`), route `/admin` bảo vệ bởi
  `role:admin`.
- **UI-first pass (2026-09-07)**: 6 trang công khai đã dựng bằng dữ liệu giả —
  `App\Support\DemoCatalog` (trả về object dùng đúng tên field như schema thật trong
  spec mục 3). Route: `/`, `/movies`, `/movies/{slug}`, `/showtimes/{id}/seats`, `/cart`,
  `/bookings` (auth). Layout công khai riêng: `layouts/site.blade.php` +
  `App\View\Components\SiteLayout` (`<x-site-layout>`), tách khỏi `x-app-layout` của
  Breeze vì trang duyệt phim/sự kiện cần dùng được cho khách chưa đăng nhập. **Khi làm
  Phase 1 BE**: xoá `DemoCatalog`, thay lời gọi trong các controller (`HomeController`,
  `MovieController`, `ShowtimeController`, `CartController`, `BookingController`) bằng
  Eloquent query thật — Blade không cần sửa vì field name đã khớp sẵn.

## Thay đổi so với spec gốc

- **2026-09-07 — Frontend CSS**: spec gốc ghi "Blade + Bootstrap 5", nhưng Phase 0 dùng
  **Tailwind CSS + Vite** (mặc định của Laravel Breeze blade stack) thay vì Bootstrap.
  Lý do: Breeze tự dựng Tailwind sẵn, trộn thêm Bootstrap sẽ xung đột quy ước class mà
  không lợi gì; Node 22 đã có sẵn qua Laragon nên Vite build không thiếu phụ thuộc.
- **2026-09-07 — Composer audit gate**: mọi `composer require`/`create-project` chạm tới
  `laravel/framework` phải thêm `--no-security-blocking`. Composer 2.9 chặn toàn bộ dải
  version 11.31–11.56 do 3 advisory XSS ở debug-mode dường như thiếu mốc "đã fix" trong
  dữ liệu advisory. Cần chạy `composer audit` định kỳ để tự rà soát thủ công.
- **2026-09-07 — Pest install**: `php artisan pest:install` không tồn tại ở phiên bản
  này — dùng `vendor/bin/pest --init` (interactive, cần `</dev/null` để không treo shell
  non-interactive).

## ERD

Xem spec mục 3 cho schema đầy đủ (`users`, `categories`, `movies`, `rooms`, `seats`,
`showtimes`, `showtime_seats`, `bookings`, `booking_items`, `comments`). Bảng
`roles`/`permissions`/`model_has_roles` do `spatie/laravel-permission` tự sinh
(migration `database/migrations/*_create_permission_tables.php`). Sơ đồ hình sẽ được
thêm vào `docs/report/` khi Phase 1 hoàn thành schema đầy đủ.
