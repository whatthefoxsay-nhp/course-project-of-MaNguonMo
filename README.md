# TicketBox — Website quản lý đặt vé sự kiện

Đồ án môn Mã nguồn mở. Laravel 11 · PHP 8.3 · MySQL 8.4 · Tailwind CSS + Alpine.js · Pest.

Repo: https://github.com/whatthefoxsay-nhp/course-project-of-MaNguonMo

## Tính năng chính

- **Khách:** tìm kiếm/lọc sự kiện theo danh mục, gợi ý tìm kiếm tức thì, chi tiết sự kiện có bản đồ Google Maps và dự báo thời tiết (địa điểm ngoài trời), xem sơ đồ ghế/sơ đồ khu, đọc bình luận, hỏi chatbot.
- **Thành viên:** giữ ghế 10 phút có chống đặt trùng (tối đa 8 chỗ); sân vận động bán theo khu (khu ghế ngồi chọn ghế, khu đứng/vé vào cửa chọn số lượng), nhà hát chọn từng ghế, sự kiện không chọn ghế mua theo hạng vé; giỏ vé có đồng hồ đếm ngược; mã giảm giá; thanh toán **giả lập** (VietQR / MoMo / ZaloPay / Thẻ — QR chỉ là hình minh họa, không thu thông tin thẻ) với thông tin người nhận vé; vé điện tử tải PNG; lịch sử đơn; bình luận; hồ sơ + avatar.
- **Quản trị:** danh mục, sự kiện (upload poster), khán phòng (thiết kế sơ đồ ghế hoặc sơ đồ khu, nhân bản), suất diễn (chặn chồng giờ), đơn đặt vé (xác nhận/hủy, hủy thì nhả ghế), mã giảm giá, người dùng (khóa/mở, đổi vai trò, đặt lại mật khẩu), dashboard doanh thu, báo cáo có bản in.

## Yêu cầu

- PHP 8.3, Composer, Node.js 18+ và npm, MySQL 8.4 — trên Windows dùng **Laragon** là đủ.
- Nếu shell chưa nhận `php`/`composer` của Laragon: xem mục "Tool paths" trong `docs/superpowers/plans/2026-09-07-phase0-project-setup.md`.

## Cài đặt (Windows + Laragon)

```bash
git clone https://github.com/whatthefoxsay-nhp/course-project-of-MaNguonMo.git
cd course-project-of-MaNguonMo
composer install --no-security-blocking
npm install
cp .env.example .env          # PowerShell: Copy-Item .env.example .env
php artisan key:generate
```

`.env.example` đã đặt sẵn `DB_DATABASE=doanmnm_ticket`, `DB_USERNAME=root`, `DB_PASSWORD=` (mặc định của Laragon), `APP_TIMEZONE=Asia/Ho_Chi_Minh`, `APP_LOCALE=vi`. Sửa lại nếu máy bạn khác.

### Tạo dữ liệu — chọn một trong hai cách

**Cách 1 — migrate + seed (khuyến nghị):** tạo database rỗng `doanmnm_ticket` (utf8mb4) trong HeidiSQL/phpMyAdmin của Laragon, rồi:

```bash
php artisan migrate --seed
```

**Cách 2 — import file dump SQL** (nếu nhóm gửi kèm file `.sql`; file dump **không** được commit vào repo): import file vào database `doanmnm_ticket` bằng HeidiSQL hoặc phpMyAdmin, sau đó chạy:

```bash
php artisan migrate
```

để bổ sung các bảng/cột mới hơn bản dump (nếu có).

Muốn làm sạch dữ liệu về trạng thái demo: `php artisan migrate:fresh --seed` (**xóa toàn bộ dữ liệu**).

### Chạy

```bash
php artisan storage:link      # bắt buộc để hiện poster/avatar đã upload
npm run build                 # hoặc npm run dev khi đang sửa giao diện
php artisan serve --port=8080
```

Mở http://127.0.0.1:8080

Nên chạy thêm ở một cửa sổ khác để nhả ghế giữ quá hạn mỗi phút (lệnh `seats:release-expired`):

```bash
php artisan schedule:work
```

Không chạy lệnh này thì ghế quá hạn vẫn được coi là trống khi người khác đặt, chỉ là dữ liệu chưa được dọn.

## Khóa API (tùy chọn)

| Biến `.env` | Tác dụng | Bỏ trống thì |
|---|---|---|
| `OPENWEATHER_API_KEY` | Dự báo thời tiết trên trang sự kiện ở địa điểm ngoài trời, cho suất trong 5 ngày tới (đăng ký miễn phí tại openweathermap.org) | Ẩn khối thời tiết |
| `GOOGLE_MAPS_EMBED_KEY` | Bản đồ qua Google Maps Embed API | Vẫn hiện bản đồ bằng link nhúng công khai |
| `GEMINI_API_KEY` (+ `GEMINI_MODEL`, mặc định `gemini-2.5-flash`) | Chatbot trả lời bằng Gemini dựa trên dữ liệu sự kiện | Chatbot dùng bộ câu trả lời có sẵn (FAQ) trên trình duyệt |

Sau khi sửa `.env`: `php artisan config:clear`.

## Tài khoản demo (mật khẩu `password`)

| Vai trò | Email |
|---|---|
| Quản trị | admin@ticketbox.vn |
| Thành viên | user@ticketbox.vn |

Cần thêm người dùng thì đăng ký ở `/register` hoặc tạo trong `/admin/users`.
Dữ liệu seed có sẵn 3 mã giảm giá: `TICKETBOX2026`, `VIPCONCERT30`, `EARLYBIRD50K`.

## Kiểm thử

```bash
php artisan test
```

Test chạy trên SQLite in-memory (cấu hình trong `phpunit.xml`), không đụng tới database MySQL. Kết quả hiện tại: 363 test pass (1612 assertion).

## Tài liệu

- Đặc tả: `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md`
- Kiến trúc & quyết định: `docs/architecture.md`
- Báo cáo: `docs/report/` — `erd.md` (ERD), `use-cases.md` (use case), `chuc-nang.md` (đối chiếu tiêu chí chấm), `anh-chup.md` (checklist ảnh), `kich-ban-demo.md` (kịch bản demo)

## Thành viên

| Họ tên | Vai trò |
|---|---|
| … | … |
