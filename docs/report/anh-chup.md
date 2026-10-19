# Checklist ảnh chụp màn hình

## Chuẩn bị

1. Dữ liệu sạch: `php artisan migrate:fresh --seed` (**xóa toàn bộ dữ liệu cũ** trong database đang cấu hình ở `.env`). Suất diễn được tạo tính từ ngày chạy seed nên luôn là suất sắp tới.
2. `php artisan storage:link` (một lần), `npm run build`, `php artisan serve --port=8080`.
3. Tài khoản (mật khẩu `password`): `admin@ticketbox.vn` (quản trị), `user@ticketbox.vn` (thành viên). Ảnh cần "người thứ hai" thì đăng ký thêm một tài khoản mới ở `/register` (ví dụ `demo2@example.com`).
4. Lưu ảnh PNG vào `docs/report/screenshots/` đúng tên dưới đây. Mục có dấu (M): chụp thêm bản mobile 375px (DevTools → Toggle device toolbar), đặt tên thêm hậu tố `-mobile` (ví dụ `01-trang-chu-mobile.png`).

## Sự kiện dùng để chụp (theo dữ liệu seed)

| Mục đích | Sự kiện | Khán phòng |
|---|---|---|
| Sơ đồ khu (sân vận động) | Live Concert: Anh Trai Vượt Ngàn Chông Gai 2026 (suất ngày mai) | SVĐ Quân Khu 7 — theo khu: các khu ghế SVIP/VIP/khán đài + Khu đứng GA |
| Sơ đồ ghế (nhà hát) | Đêm Hòa Nhạc Giao Hưởng: The Sound of Saigon Philharmonic | Nhà Hát Thành Phố — theo ghế |
| Chọn hạng vé + số lượng | Fan Meeting 2026: Gặp Gỡ & Ký Tặng Dàn Cast Running Man VN | Nhà Hát Hòa Bình — sự kiện không chọn ghế |
| Vé vào cửa theo số lượng | Vietnam Tech Summit & Global AI Expo 2026 | SECC Hall A — một khu vé vào cửa |

## Danh sách ảnh

| # | Tên file | Trang / thao tác | Tài khoản |
|---|---|---|---|
| 1 | `01-trang-chu.png` (M) | `/` | Khách |
| 2 | `02-danh-sach-tim-kiem.png` | `/events?q=concert`, thấy kết quả + phân trang/bộ lọc danh mục | Khách |
| 3 | `03-goi-y-tim-kiem.png` | Mở hộp tìm kiếm ở header, gõ "hòa" → danh sách gợi ý | Khách |
| 4 | `04-chi-tiet-su-kien.png` (M) | Trang concert Anh Trai Vượt Ngàn Chông Gai: lineup, lịch trình, suất diễn, bản đồ (xem ghi chú thời tiết bên dưới) | Khách |
| 5 | `05-dang-ky.png` | `/register` | Khách |
| 6 | `06-so-do-khu.png` (M) | Sơ đồ khu suất concert: các khu kèm giá và số chỗ còn | user@ticketbox.vn |
| 7 | `07-so-do-khu-chon-ghe.png` | Bấm "Chọn ghế khu này" ở khu SVIP, chọn 2 ghế; đồng thời tăng Khu đứng GA lên 1 vé | user@ticketbox.vn |
| 8 | `08-so-do-nha-hat.png` | Sơ đồ ghế suất hòa nhạc, đang chọn 3 ghế, rê chuột lên một ghế để hiện thông tin | user@ticketbox.vn |
| 9 | `09-chon-hang-ve.png` | Suất Fan Meeting, chọn "Combo Nhóm 2 Người" × 1 | user@ticketbox.vn |
| 10 | `10-bao-ghe-da-giu.png` | Cửa sổ thứ hai cố giữ ghế mà cửa sổ thứ nhất vừa giữ → toast "Ghế … vừa có người giữ hoặc đã bán" | tài khoản mới đăng ký |
| 11 | `11-gio-ve.png` (M) | `/cart` có đồng hồ đếm ngược và nút "Bỏ vé" | user@ticketbox.vn |
| 12 | `12-ma-giam-gia.png` | Ở giỏ vé nhập `TICKETBOX2026`, bấm "Áp dụng" → hiện số tiền giảm (giỏ cần tạm tính ≥ 200.000₫) | user@ticketbox.vn |
| 13 | `13-thong-tin-nguoi-nhan.png` | Ô họ tên, số điện thoại, email người nhận vé + chọn phương thức | user@ticketbox.vn |
| 14 | `14-thanh-toan-qr.png` | Bước thanh toán VietQR, thấy nhãn "Mã QR minh họa – không chuyển tiền" | user@ticketbox.vn |
| 15 | `15-ve-dien-tu.png` | Màn hình vé điện tử sau thanh toán (QR mã đặt vé, nút tải ảnh vé) | user@ticketbox.vn |
| 16 | `16-lich-su.png` | `/bookings` | user@ticketbox.vn |
| 17 | `17-binh-luan.png` | Gửi bình luận trên trang chi tiết, bình luận hiện ngay không tải lại trang | user@ticketbox.vn |
| 18 | `18-ho-so-avatar.png` | `/profile` sau khi đổi avatar | user@ticketbox.vn |
| 19 | `19-chatbot.png` | Mở chatbot, bấm một câu hỏi gợi ý | Khách |
| 20 | `20-admin-dashboard.png` | `/admin` | admin@ticketbox.vn |
| 21 | `21-admin-su-kien-form.png` | Form thêm/sửa sự kiện (upload poster) | admin@ticketbox.vn |
| 22 | `22-admin-suat-dien.png` | Danh sách suất diễn + form; thử tạo suất trùng giờ cùng phòng để thấy lỗi | admin@ticketbox.vn |
| 23 | `23-admin-khan-phong-ghe.png` | `/admin/rooms/create?mode=seats` — trình thiết kế ma trận ghế | admin@ticketbox.vn |
| 24 | `24-admin-khan-phong-khu.png` | `/admin/rooms/create?mode=zones` — trình thiết kế sơ đồ khu | admin@ticketbox.vn |
| 25 | `25-admin-don-dat-ve.png` | `/admin/bookings`, mở chi tiết một đơn (nút xác nhận/hủy) | admin@ticketbox.vn |
| 26 | `26-admin-ma-giam-gia.png` | `/admin/discounts` | admin@ticketbox.vn |
| 27 | `27-admin-nguoi-dung.png` | `/admin/users`, đang khóa tài khoản mới đăng ký | admin@ticketbox.vn |
| 28 | `28-tai-khoan-bi-khoa.png` | Cửa sổ của tài khoản vừa bị khóa bấm sang trang khác → về trang đăng nhập kèm thông báo bị khóa | tài khoản mới đăng ký |
| 29 | `29-admin-bao-cao.png` | `/admin/reports` và bản in `/admin/reports/pdf` | admin@ticketbox.vn |
| 30 | `30-validation.png` | Form sự kiện bỏ trống tên → thông báo lỗi tiếng Việt | admin@ticketbox.vn |
| 31 | `31-test-pass.png` | Terminal `php artisan test` toàn xanh (363 passed) | — |

## Ghi chú

- **Bản đồ:** khán phòng có tọa độ nên luôn hiện bản đồ Google Maps; không có `GOOGLE_MAPS_EMBED_KEY` vẫn hiện bằng link nhúng công khai.
- **Thời tiết:** chỉ hiện khi (1) `.env` có `OPENWEATHER_API_KEY`, (2) địa điểm ngoài trời (sân vận động), (3) suất diễn trong 5 ngày tới. Concert Anh Trai Vượt Ngàn Chông Gai có suất ngày mai tại SVĐ Quân Khu 7 nên đủ điều kiện. Thiếu key thì khối thời tiết **ẩn hẳn** — vẫn chụp được, chỉ không có ô thời tiết.
- **Chatbot:** không có `GEMINI_API_KEY` thì bot dùng bộ câu trả lời có sẵn (FAQ); có key thì trả lời bằng Gemini theo dữ liệu sự kiện thật.
- Ảnh 10 và 28 cần hai trình duyệt (ví dụ Chrome thường + Chrome ẩn danh) để hai phiên đăng nhập không dùng chung cookie.
