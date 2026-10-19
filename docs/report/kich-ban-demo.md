# Kịch bản thuyết trình (~10 phút)

## Chuẩn bị trước giờ trình bày

1. `php artisan migrate:fresh --seed` — dữ liệu sạch, suất diễn tính từ ngày hôm nay (**xóa dữ liệu cũ** trong database ở `.env`). Nếu nhóm dùng file dump SQL thì import lại rồi chạy `php artisan migrate`.
2. `php artisan storage:link` (nếu chưa làm), `npm run build`.
3. Mở 2 cửa sổ terminal: `php artisan serve --port=8080` và `php artisan schedule:work` (nhả ghế hết hạn mỗi phút).
4. Mở sẵn 2 trình duyệt (mật khẩu demo `password`):
   - **Trình duyệt 1** (Chrome thường): đăng nhập `user@ticketbox.vn`.
   - **Trình duyệt 2** (Chrome ẩn danh): để ở trang `/register` — sẽ đăng ký tài khoản mới ngay khi demo (ví dụ `demo2@example.com`), vừa khoe chức năng đăng ký vừa có người dùng thứ hai.
   - **Trình duyệt 3** (Edge hoặc Firefox): đăng nhập `admin@ticketbox.vn` cho phần quản trị. Không đăng nhập admin trong trình duyệt 1/2 vì sẽ thay phiên đăng nhập đang có.
5. Kiểm tra `.env`:
   - `OPENWEATHER_API_KEY` — có thì trang concert Anh Trai Vượt Ngàn Chông Gai (suất ngày mai, sân vận động ngoài trời) hiện dự báo thời tiết; không có thì khối thời tiết ẩn hẳn, bản đồ vẫn hiện.
   - `GEMINI_API_KEY` — có thì chatbot trả lời bằng Gemini; không có thì bot dùng bộ câu trả lời có sẵn (FAQ).
6. Mở sẵn `docs/report/erd.md` và `docs/report/chuc-nang.md` (VS Code preview) để chiếu.

## Trình tự

| Phút | Người nói | Nội dung |
|---|---|---|
| 0–1 | … | Giới thiệu đề tài "Website quản lý đặt vé sự kiện", công nghệ: Laravel 11, PHP 8.3, MySQL 8.4, Tailwind CSS + Alpine.js, Pest. Hai vai trò `admin` / `user`. |
| 1–2 | … | ERD (`erd.md`): `showtime_seats` vừa là trạng thái ghế theo suất vừa là giỏ hàng; khán phòng theo khu có `room_zones` + `zone_tickets` cho khu đứng/vé vào cửa bán theo số lượng. |
| 2–4 | … | **Luồng khách (trình duyệt 2, chưa đăng nhập):** trang chủ → hộp tìm kiếm gõ "hòa" thấy gợi ý tức thì → trang concert Anh Trai Vượt Ngàn Chông Gai (lineup, lịch trình, bản đồ, thời tiết) → mở sơ đồ khu: bấm khu SVIP để xem ghế, Khu đứng GA chọn số lượng. Bấm giữ khi chưa đăng nhập → bị chuyển tới đăng nhập → **đăng ký tài khoản mới** ngay. |
| 4–6 | … | **Demo chống đặt trùng ghế:** cả 2 trình duyệt mở cùng suất "Đêm Hòa Nhạc Giao Hưởng" (sơ đồ ghế nhà hát). Trình duyệt 2 chọn một ghế nhưng **chưa** bấm giữ. Trình duyệt 1 chọn đúng ghế đó (+ 1 ghế bên cạnh), bấm "Xác Nhận Giữ Chỗ" → vào giỏ. Trình duyệt 2 bấm "Xác Nhận Giữ Chỗ" → bị từ chối ngay: "Ghế … vừa có người giữ hoặc đã bán", ghế chuyển xám, không ghế nào được giữ (tất cả hoặc không). Nếu chờ ≤ 15 giây không bấm, polling cũng tự làm xám ghế và báo "vừa được người khác giữ". Giải thích `lockForUpdate` trong `SeatHoldService::hold()`. |
| 6–7 | … | **Thanh toán (trình duyệt 1):** giỏ vé có đồng hồ 10 phút → nhập mã `TICKETBOX2026`, bấm "Áp dụng" (giảm 20%, tối đa 100.000₫) → điền họ tên + số điện thoại người nhận → chọn MoMo → màn hình QR ghi rõ "minh họa – không chuyển tiền" → "Xác nhận đã hoàn tất thanh toán" → vé điện tử, tải ảnh PNG → `/bookings`. |
| 7–9 | … | **Admin:** tạo sự kiện có poster → tạo suất diễn (thử chồng giờ cùng phòng để thấy lỗi tiếng Việt) → giới thiệu trình thiết kế khán phòng theo khu → `/admin/bookings` thấy đơn vừa đặt (giá đã trừ mã giảm giá) → dashboard doanh thu, báo cáo bản in → `/admin/users` khóa tài khoản vừa đăng ký → trình duyệt 2 bấm sang trang khác là bị đăng xuất kèm thông báo bị khóa. |
| 9–10 | … | Chạy `php artisan test` (363 test pass), tổng kết theo `chuc-nang.md`. |

Dự phòng nếu thiếu thời gian: bỏ phần trình thiết kế khán phòng và báo cáo bản in ở phút 7–9.

## Câu hỏi hay gặp

- *Hai người bấm cùng một ghế cùng lúc thì sao?* — Giữ ghế chạy trong transaction, các dòng `showtime_seats` được khóa bằng `SELECT … FOR UPDATE` **trước** khi kiểm tra còn trống. Request đến sau phải chờ khóa, đọc lại thấy ghế đã `held` nên bị từ chối toàn bộ. Xem `app/Services/SeatHoldService.php`.
- *Khu đứng không có ghế thì chống bán quá sức chứa thế nào?* — Khóa dòng `room_zones` của khu, đếm vé `booked` + vé `held` còn hạn trong `zone_tickets`, vượt sức chứa thì báo "Khu … chỉ còn N vé".
- *Không thanh toán thì ghế bị giữ mãi?* — Không. Giữ tối đa 10 phút; quá hạn là ghế được coi là trống ngay (scope `holdable` so `held_until` với giờ hiện tại), lệnh `seats:release-expired` dọn dữ liệu mỗi phút. Thêm vé vào giỏ không gia hạn vé cũ (một đồng hồ chung cho cả giỏ).
- *Một người giữ nhiều suất để "xí chỗ" được không?* — Mỗi tài khoản chỉ giữ vé của một suất tại một thời điểm (giữ suất mới thì vé ở suất cũ được nhả) và tối đa 8 chỗ; endpoint giữ chỗ bị giới hạn 30 lần/phút.
- *Sao không có bảng giỏ hàng?* — Ghế đang giữ đã chứa đủ "ai giữ, tới khi nào, giá bao nhiêu"; thêm bảng giỏ sẽ có 2 nguồn dữ liệu có thể lệch nhau. Xem `erd.md`.
- *Mã giảm giá có bị dùng quá số lượt khi nhiều người thanh toán cùng lúc?* — Lúc thanh toán, dòng mã được khóa (`lockForUpdate`), kiểm tra lại điều kiện rồi mới tăng `used_count`. Tiền giảm luôn tính lại ở server, không tin số từ trình duyệt. Admin hủy đơn thì trả lại 1 lượt.
- *Thanh toán có thật không?* — Không. Đây là thanh toán giả lập theo phạm vi đề tài: QR chỉ là hình minh họa, form thẻ không thu thông tin thẻ; nhưng đơn, vé, mã đặt vé được tạo thật trong database.
- *Khóa tài khoản có tác dụng ngay không?* — Có. Middleware `EnsureUserIsActive` chạy trên mọi request web: tài khoản bị khóa bị đăng xuất ở request kế tiếp, đăng nhập lại cũng bị chặn.
- *Sao cập nhật trạng thái ghế bằng polling 15 giây mà không realtime?* — Theo spec đề tài (AJAX polling, không dùng WebSocket); tính đúng đắn vẫn do khóa ở server đảm bảo, polling chỉ để giao diện cập nhật sớm.
- *Admin sửa sơ đồ khán phòng đã có suất diễn thì sao?* — Không cho sinh lại ghế/khu (tránh mất vé đã bán), chỉ sửa tên/địa chỉ; muốn sơ đồ mới thì dùng "Nhân bản".
- *Không có API key thì sao?* — Bản đồ dùng link nhúng công khai; thời tiết ẩn; chatbot dùng bộ câu trả lời có sẵn. Các chức năng đặt vé không phụ thuộc API ngoài.
