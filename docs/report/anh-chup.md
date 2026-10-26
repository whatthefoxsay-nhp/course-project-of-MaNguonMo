# Checklist ảnh chụp màn hình

Chuẩn bị: `php artisan migrate:fresh --seed`, `npm run build`, `php artisan serve --port=8080`. Lưu ảnh PNG vào
`docs/report/screenshots/` đúng tên dưới đây. Chụp cả bản desktop (1440px) và mobile (375px, DevTools) cho mục có dấu (M).

Tài khoản demo (mật khẩu `password`): `admin@ticketbox.vn`, `user@ticketbox.vn`. Tài khoản thứ hai cho mục 9 và 21: tự đăng ký ở cửa sổ ẩn danh.

| # | Tên file | Trang / thao tác | Tài khoản |
|---|---|---|---|
| 1 | `01-trang-chu.png` (M) | `/` | Khách |
| 2 | `02-danh-sach-tim-kiem.png` | `/events?q=concert` | Khách |
| 3 | `03-goi-y-tim-kiem.png` | Mở ô tìm kiếm trên menu, gõ "con" | Khách |
| 4 | `04-chi-tiet-su-kien.png` | Trang concert Anh Trai, cuộn tới khối bản đồ + thời tiết | Khách |
| 5 | `05-so-do-san-van-dong.png` | Sơ đồ suất concert: 2 khu đứng hai bên, SVIP, VIP, khán đài; đang chọn 2 ghế + 2 vé khu đứng | user@ticketbox.vn |
| 6 | `06-so-do-nha-hat.png` | Sơ đồ suất hòa nhạc (tầng trệt, tầng lửng, ban công, lối đi giữa) | user@ticketbox.vn |
| 7 | `07-so-do-ban-tiec.png` | Sơ đồ suất Masterclass AI, vừa bấm "Chọn cả bàn" | user@ticketbox.vn |
| 8 | `08-chon-khu-so-luong.png` | Suất triển lãm Van Gogh hoặc Tech Summit: chọn khu + số lượng | user@ticketbox.vn |
| 9 | `09-bao-ghe-da-giu.png` | Cửa sổ thứ 2 cố giữ ghế người khác đang giữ | tài khoản thứ hai |
| 10 | `10-gio-ve.png` (M) | `/cart` có đồng hồ đếm ngược | user@ticketbox.vn |
| 11 | `11-thanh-toan-qr.png` | Bước thanh toán VietQR | user@ticketbox.vn |
| 12 | `12-ve-dien-tu.png` | Màn hình E-Ticket sau thanh toán | user@ticketbox.vn |
| 13 | `13-lich-su.png` | `/bookings` | user@ticketbox.vn |
| 14 | `14-binh-luan.png` | Gửi bình luận không tải lại trang | user@ticketbox.vn |
| 15 | `15-ho-so-avatar.png` | `/profile` sau khi tải ảnh đại diện | user@ticketbox.vn |
| 16 | `16-admin-dashboard.png` | `/admin` | admin@ticketbox.vn |
| 17 | `17-admin-su-kien-form.png` | Form thêm/sửa sự kiện | admin@ticketbox.vn |
| 18 | `18-admin-suat-dien.png` | Danh sách suất diễn + form | admin@ticketbox.vn |
| 19 | `19-admin-trinh-dung-so-do.png` | `/admin/rooms/create`: chọn mẫu, bảng khu & giá, bản vẽ có lối đi | admin@ticketbox.vn |
| 20 | `20-admin-don-dat-ve.png` | `/admin/bookings` | admin@ticketbox.vn |
| 21 | `21-admin-nguoi-dung.png` | `/admin/users`, đang khóa tài khoản thứ hai | admin@ticketbox.vn |
| 22 | `22-validation.png` | Form sự kiện bỏ trống → thông báo lỗi tiếng Việt | admin@ticketbox.vn |
| 23 | `23-test-pass.png` | Terminal `php artisan test` toàn xanh | — |
