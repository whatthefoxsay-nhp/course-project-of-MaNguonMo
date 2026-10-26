# TicketBox — Website quản lý đặt vé sự kiện

Đồ án môn Mã nguồn mở. Laravel 11 · PHP 8.3 · MySQL 8.4 · Tailwind CSS + Alpine.js · Pest.

## Tính năng chính
- **Khách:** tìm kiếm/lọc sự kiện, gợi ý tìm kiếm khi gõ, chi tiết sự kiện có bản đồ Google Maps và dự báo thời tiết tại giờ diễn, sơ đồ chỗ ngồi tương tác.
- **Thành viên:** giữ chỗ 10 phút (chống đặt trùng), chọn ghế / chọn cả bàn / chọn số lượng vé khu đứng tùy loại sự kiện, giỏ vé, thanh toán giả lập (VietQR/MoMo/ZaloPay/Thẻ), vé điện tử, lịch sử, bình luận, hồ sơ + ảnh đại diện.
- **Quản trị:** danh mục, sự kiện (upload poster), địa điểm với trình dựng sơ đồ theo khu (6 mẫu: sân vận động, nhà hát, hội trường, bàn tiệc, không gian mở, lưới tùy chỉnh), suất diễn, đơn đặt vé, mã giảm giá, người dùng (khóa/mở, đổi vai trò), dashboard, báo cáo.

## Cài đặt (Windows + Laragon)
```bash
git clone git@github.com:whatthefoxsay-nhp/course-project-of-MaNguonMo.git DoAnMNM
cd DoAnMNM
composer install --no-security-blocking
npm install
cp .env.example .env
php artisan key:generate
```

Sửa `.env` để dùng MySQL của Laragon (tạo database `doanmnm_ticket` trong HeidiSQL trước):
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=doanmnm_ticket
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve --port=8080
```
Mở http://127.0.0.1:8080

Tùy chọn: chạy `php artisan schedule:work` ở cửa sổ khác để dọn chỗ giữ quá hạn mỗi phút
(không chạy cũng không sao: chỗ giữ quá hạn vẫn được coi là trống).

Muốn làm lại dữ liệu demo từ đầu: `php artisan migrate:fresh --seed` (xóa toàn bộ dữ liệu cũ).

## Khóa API (tùy chọn)
- `OPENWEATHER_API_KEY` — dự báo thời tiết trên trang sự kiện (đăng ký miễn phí tại openweathermap.org; key mới có thể mất tới ~1 giờ mới hoạt động). Bỏ trống thì trang hiện "Chưa có dự báo".
- `GOOGLE_MAPS_EMBED_KEY` — bản đồ qua Maps Embed API; bỏ trống vẫn hiện bản đồ bằng link nhúng công khai.

Sau khi sửa `.env`: `php artisan config:clear`. Không commit key lên git.

## Tài khoản demo (mật khẩu `password`)
| Vai trò | Email |
|---|---|
| Quản trị | admin@ticketbox.vn |
| Thành viên | user@ticketbox.vn |

## Dữ liệu demo
8 sự kiện ở 8 địa điểm, mỗi loại sự kiện một kiểu bố trí chỗ ngồi:

| Sự kiện | Kiểu bố trí |
|---|---|
| Concert Anh Trai Vượt Ngàn Chông Gai | Sân vận động: 2 khu đứng, SVIP, VIP mặt sàn, khán đài, Skybox |
| Hòa nhạc giao hưởng, nhạc kịch Les Misérables, Fan meeting | Nhà hát: tầng trệt, tầng lửng, ban công |
| Vietnam Tech Summit | Hội trường (mua theo khu, không chọn ghế) |
| Masterclass AI | Bàn tiệc / workshop (chọn ghế quanh bàn) |
| Triển lãm Van Gogh, Festival | Không gian mở (vé vào cửa theo sức chứa) |

## Kiểm thử
```bash
php artisan test
```
Test không gọi API thật (`Http::fake`, key được để trống trong `phpunit.xml`).

## Tài liệu
- **Cho thành viên nhóm — cách chạy sau khi pull, lỗi tồn đọng cần nhận:** `docs/HUONG-DAN-NHOM.md`
- Đặc tả: `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md`
- Kiến trúc & quyết định: `docs/architecture.md`
- Báo cáo: `docs/report/` (ERD, use case, chức năng, checklist ảnh chụp, kịch bản demo)

## Thành viên
| Họ tên | Vai trò |
|---|---|
| … | … |
