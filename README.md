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

## Ảnh trên Google Drive

Poster sự kiện và ảnh đại diện mới được lưu bắt buộc lên Google Drive; nếu Drive chưa cấu hình hoặc upload lỗi, form sẽ báo lỗi thay vì lưu ảnh local. Ảnh Drive được trả qua route cùng domain để trình duyệt hiển thị được.

Điền các biến `GOOGLE_DRIVE_ENABLED`, `GOOGLE_DRIVE_FOLDER_ID`, `GOOGLE_DRIVE_OAUTH_CLIENT_ID`, `GOOGLE_DRIVE_OAUTH_CLIENT_SECRET` và `GOOGLE_DRIVE_OAUTH_REFRESH_TOKEN` trong `.env`. OAuth token cần scope `https://www.googleapis.com/auth/drive`; chia sẻ thư mục Drive với từng tester bằng quyền Editor, rồi mỗi tester tạo OAuth refresh token riêng. Không commit `.env`.

Poster demo trong seeder trỏ tới các file Drive đã chuẩn bị sẵn. Với database cũ, kiểm tra rồi chuyển poster/avatars local hoặc từ Picsum bằng:

```bash
php artisan images:migrate-to-drive --dry-run
php artisan images:migrate-to-drive
```

Lệnh giữ bản gốc local làm backup; database chỉ đổi sang URL Drive sau khi upload và đọc lại ảnh thành công.

## Khóa API (tùy chọn)
- `OPENWEATHER_API_KEY` — dự báo thời tiết trên trang sự kiện (đăng ký miễn phí tại openweathermap.org; key mới có thể mất tới ~1 giờ mới hoạt động). Bỏ trống thì trang hiện "Chưa có dự báo".
- `GOOGLE_MAPS_EMBED_KEY` — bản đồ qua Maps Embed API; bỏ trống vẫn hiện bản đồ bằng link nhúng công khai.
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` — bật đăng nhập/đăng ký Google. Đây là cấu hình tùy chọn; thiếu credentials thì nút Google ẩn và đăng nhập bằng email/mật khẩu vẫn dùng được. Redirect URI cần khớp URI đã khai báo trong Google Cloud Console; giá trị mẫu là `${APP_URL}/auth/google/callback`.

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
