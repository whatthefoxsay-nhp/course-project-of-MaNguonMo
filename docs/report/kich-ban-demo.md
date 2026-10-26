# Kịch bản thuyết trình (~10 phút)

## Chuẩn bị trước giờ trình bày
1. `php artisan migrate:fresh --seed` (dữ liệu sạch, suất diễn tính từ ngày hôm nay).
2. `npm run build`, `php artisan serve --port=8080`, `php artisan schedule:work` (cửa sổ riêng).
3. Mở sẵn 2 trình duyệt: Chrome (`user@ticketbox.vn`, mật khẩu `password`) và Chrome ẩn danh (đăng ký trước một tài khoản thứ hai).
4. Kiểm tra `.env` có `OPENWEATHER_API_KEY` (nếu không có, khối thời tiết hiện "Chưa có dự báo" — vẫn demo được). Bản đồ không cần key.

## Trình tự
| Phút | Người nói | Nội dung |
|---|---|---|
| 0–1 | … | Giới thiệu đề tài, công nghệ (Laravel 11, MySQL, Tailwind + Alpine, Pest) |
| 1–2 | … | ERD (`erd.md`): khu vực → ghế → trạng thái ghế theo suất; vì sao `showtime_seats` là giỏ hàng |
| 2–4 | … | Luồng khách: gõ "con" thấy gợi ý → chi tiết concert (bản đồ, thời tiết thật) → sơ đồ sân vận động. Mở nhanh sơ đồ nhà hát và bàn tiệc workshop để cho thấy mỗi loại sự kiện một kiểu bố trí |
| 4–6 | … | **Demo chống đặt trùng:** cả 2 trình duyệt mở cùng suất concert; trình duyệt 1 giữ ghế SVIP-A-05; trình duyệt 2 bấm SVIP-A-05 → sau ≤ 15 giây ghế chuyển "đang giữ", bấm giữ thì bị báo "vừa có người giữ"; giải thích `lockForUpdate` |
| 6–7 | … | Giỏ vé, đồng hồ 10 phút, bỏ 1 vé, thanh toán MoMo → E-Ticket → lịch sử; gửi một bình luận không tải lại trang |
| 7–9 | … | Admin: tạo địa điểm mới từ mẫu "Bàn tiệc", đổi giá một khu → tạo suất (thử trùng giờ để thấy lỗi) → thấy đơn vừa đặt + doanh thu dashboard → khóa tài khoản thứ hai (bị đăng xuất ngay) |
| 9–10 | … | Chạy `php artisan test`, tổng kết theo `chuc-nang.md` |

## Câu hỏi hay gặp
- *Hai người bấm cùng một ghế cùng lúc thì sao?* — Transaction + `SELECT … FOR UPDATE`: request sau chờ request trước xong, đọc lại thấy ghế đã `held` nên bị từ chối. Xem `SeatHoldService::hold()`.
- *Vé đứng không có ghế thì chống bán quá sức chứa thế nào?* — Mỗi chỗ đứng là một ghế ẩn; mua vé đứng = server khóa và gán N ghế ẩn còn trống, hết ghế ẩn là hết chỗ.
- *Mỗi sự kiện bố trí khác nhau thì làm sao?* — Địa điểm chia thành khu (ghế đánh số / bàn / khu đứng). Có 6 mẫu sẵn; admin chỉnh tên, giá, màu từng khu và tô lại ô trong trình dựng sơ đồ.
- *Không thanh toán thì ghế bị giữ mãi?* — Không. Giữ 10 phút; quá hạn ghế được coi là trống ngay, lệnh `seats:release-expired` dọn dữ liệu mỗi phút.
- *Sao không có bảng giỏ hàng?* — Xem `erd.md` mục "Vì sao không có bảng cart_items".
- *Thanh toán có thật không?* — Giả lập theo phạm vi đề tài (spec mục 5); đơn vẫn được tạo thật trong DB.
- *API thời tiết chết thì trang có lỗi không?* — Không. `WeatherService` bắt lỗi, trả `null`, trang hiện "Chưa có dự báo".
