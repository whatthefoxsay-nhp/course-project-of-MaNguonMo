# Báo cáo hoàn thiện dự án — 19/10/2026

Tổng kết đợt audit và hoàn thiện "Website Quản lý đặt vé" (môn Mã nguồn mở). Toàn bộ thay đổi đã được merge vào `main` (commit `af25d01`).

**Kết quả cuối:**
- **Test:** `php artisan test` → **363 passed (1612 assertions)**.
- **Build:** `npm run build` chạy được.
- **So với `main` cũ (`dc550bf`):** 238 file thay đổi, +20.628 / −5.230 dòng (bao gồm cả các nhánh nhóm đã làm trước đó nhưng chưa merge).

---

## 1. Audit ban đầu

Trước khi sửa, 4 agent audit song song 4 mảng: spec/plan, backend, frontend, test/tài liệu/git. Kết quả chính:

| Hạng mục | Tình trạng lúc audit |
|---|---|
| Plan 1–3 (CRUD admin, catalog đọc DB, engine đặt vé) | Đã xong, test pass |
| Plan 4 (bình luận, gợi ý tìm kiếm, avatar) | Chưa làm, bình luận trên trang là dữ liệu giả |
| Plan 5 (bản đồ, thời tiết) | Chưa làm, thời tiết cố định "28°C" |
| Plan 6 (báo cáo, README) | Chưa làm, `docs/report/` trống, README mặc định của Laravel |
| Nhánh git | `main` chậm 41 commit; `feature/fix-so-do-ghe` đi trước thêm 32 commit |
| Lỗi nghiêm trọng | Admin đổi trạng thái đơn có thể ghi đè ghế người khác đã mua; xóa tài khoản làm mất đơn đã thanh toán; VietQR sinh mã quét được tới tài khoản bịa; dùng thông tin công ty Ticketbox thật |

---

## 2. Môi trường trên máy

- **MySQL:** cổng 3306 trước đó bị MySQL (MariaDB 10.4) của XAMPP chiếm. Mình đã dừng service đó và chạy MySQL 8.4 của Laragon.
- **Database:** tạo `doanmnm_ticket` và import `database/doanmnm_ticket.sql`, sau đó chạy thêm 2 migration mới (thông tin người mua, mã giảm giá).
- **`.env`:** `DB_CONNECTION=mysql`, `APP_LOCALE=vi`, `APP_TIMEZONE=Asia/Ho_Chi_Minh`. Đã chạy `php artisan storage:link`.
- **File `database/doanmnm_ticket.sql` không commit:** file có email người dùng thật và đã cũ so với schema mới.

---

## 3. Những gì đã làm

### 3.1 Gộp nhánh
- `feature/update_UI` (P1–P3 + giao diện) và `feature/fix-so-do-ghe` (P7: sơ đồ khán phòng theo khu, chatbot Gemini).
- Conflict duy nhất ở `layouts/admin.blade.php`: giữ bản sidebar mới hơn (17/10). Hai bên có cùng các link route.

### 3.2 Năm nhánh làm song song (mỗi nhánh một worktree riêng, mình merge và xử lý conflict)

**A1 — Toàn vẹn dữ liệu & hiệu năng backend**
- **Đổi trạng thái đơn (admin):** chạy trong transaction có khóa dòng. Khi hủy chỉ nhả ghế/vé còn thuộc đơn đó. Khi xác nhận lại sẽ từ chối nếu ghế đã thuộc người khác. Bỏ trạng thái `pending`.
- **Chặn xóa khi còn đơn:**
  - khách tự xóa tài khoản;
  - admin xóa sự kiện hoặc suất diễn có booking item.
- **Suất diễn đã bán vé:** không cho đổi sự kiện hoặc khán phòng. Suất đã bắt đầu vẫn sửa được thông tin khác.
- **Khán phòng:** tạo/sửa chạy trong transaction; sơ đồ ghế được kiểm tra trước khi lưu (nhập sai trả lỗi 422, không còn 500, không để lại phòng 0 ghế).
- **Reset mật khẩu:** sinh mật khẩu ngẫu nhiên và đăng xuất user khỏi các phiên khác.
- **Giới hạn 8 vé:** an toàn khi một user gửi nhiều request cùng lúc.
- **Dashboard/báo cáo:** dùng truy vấn tổng hợp (`SalesReportService`) thay vì nạp toàn bộ ghế vào bộ nhớ.
- **Dọn code:** bỏ model `Movie` và `MovieController` không còn dùng.

**A2 — Thanh toán, giỏ vé, lịch sử**
- **Thông tin người nhận vé** (tên, SĐT, email) được lưu vào đơn và hiện ở lịch sử, trang chi tiết đơn của admin và vé PNG.
- **Mã giảm giá áp dụng thật:**
  - nút "Áp dụng" trong giỏ;
  - server tự kiểm tra và tính tiền, khóa dòng mã khi thanh toán;
  - tăng `used_count` khi dùng.
- **QR thanh toán chỉ còn là hình minh họa** (không quét chuyển tiền được), bỏ form thu số thẻ/CVV.
- **Kiểm tra lại khi thanh toán:** suất diễn chưa bắt đầu, sự kiện còn mở bán.
- **Đồng hồ giữ vé:** dừng sau khi thanh toán thành công; giỏ trống thì không hiện.
- **Lịch sử vé:** hiện đúng trạng thái; vé đã hủy không có QR và không tải được.
- **Ảnh vé PNG:** gộp 2 đoạn code vẽ vé trùng nhau làm một, bỏ dữ liệu giả.

**B — Plan 4**
- **Bình luận AJAX:** xem, đăng, xóa không tải lại trang; chỉ chủ bình luận hoặc admin được xóa. Huy hiệu "4.9/5" giả được thay bằng số bình luận thật.
- **Gợi ý tìm kiếm tức thì** trong ô tìm kiếm; phím Esc đóng hộp tìm kiếm.
- **Avatar:** upload ảnh đại diện; chữ cái viết tắt hiển thị đúng với tiếng Việt (ví dụ "Đ").

**C — Plan 5 + tiếng Việt**
- **Bản đồ:** Google Maps nhúng kèm nút "Chỉ đường"; chạy được cả khi không có API key.
- **Thời tiết:** dự báo OpenWeatherMap chỉ hiện cho địa điểm ngoài trời (`Room::isOutdoor()`), suất diễn trong 5 ngày tới. Không có key hoặc gọi API lỗi thì ẩn hẳn khối thời tiết.
- **Tọa độ:** admin nhập tọa độ khán phòng; phòng mới không còn tự điền sẵn thông tin SVĐ Quân Khu 7.
- **Tiếng Việt:** `lang/vi/validation.php`, `passwords.php`, `pagination.php`, `vi.json`; dịch trang xác thực email và xác nhận mật khẩu.

**D — Dọn giao diện**
- **Thay dữ liệu giả bằng dữ liệu thật:** chuông thông báo hiện suất diễn sắp tới của user; thống kê trang chủ và thẻ sự kiện lấy từ DB.
- **Thông tin công ty Ticketbox thật:** bỏ hotline, tên miền và tên công ty; footer ghi rõ là đồ án môn học.
- **Sửa XSS:**
  - giá trị `old('email')` ở trang login;
  - dữ liệu ghế trên sơ đồ ghế;
  - chatbot;
  - tab danh mục ở trang chủ.
- **Nút đăng nhập nhanh** (tài khoản demo) chỉ hiện ở môi trường local.
- **Sơ đồ ghế trên mobile** cuộn ngang được.
- **Class Tailwind không tồn tại** đã được sửa; bỏ `x-collapse` khi chưa cài plugin.
- **Xóa code không dùng:** views `movies/*`, phần còn lại của Breeze, các component không dùng, `skill_agent.txt`, `ExampleTest`.

### 3.3 Review trước khi merge vào `main`

**`code-review`:** ra 9 điểm, đã sửa 6 điểm có ảnh hưởng thật:

| Vấn đề | Cách sửa |
|---|---|
| Giữ thêm vé thì gia hạn mọi vé cũ, nên có thể giữ ghế vô thời hạn | Vé giữ sau dùng chung hạn của vé giữ sớm nhất, không gia hạn |
| Doanh thu theo sự kiện/danh mục chưa trừ giảm giá, lệch "Tổng doanh thu" | Phân bổ giảm giá theo tỉ lệ giá vé |
| Admin hủy đơn có mã giảm giá không trả lại lượt dùng | Hủy thì trả lượt; xác nhận lại thì kiểm tra còn lượt |
| Gửi bình luận dạng mảng gây lỗi 500 | Trả 422 |
| Xóa phiên đăng nhập luôn chạy kể cả khi không dùng session DB | Chỉ xóa khi driver session là `database` |
| Quy tắc mã giảm giá bị viết ở 2 nơi | Gộp vào model `Discount`, `DiscountException` tách riêng |

**`security-review`:** tìm ra 1 lỗi **reflected XSS ở `/admin/categories?search=`**.
- **Hậu quả:** chỉ cần dụ admin bấm một link là chạy được JavaScript trong phiên của admin, đủ để tự nâng tài khoản lên quyền admin.
- **Đã sửa:** dùng `@js`, kèm test hồi quy.

### 3.4 Sửa thêm sau review
- **Link dùng route cũ:** toàn bộ chuyển từ `movies.*` sang `events.*`.
- **`addslashes`:** các chỗ còn lại đổi sang `@js`.
- **Form tạo user** không còn điền sẵn mật khẩu đoán được `TicketBox@năm`.
- **Chatbot không có `GEMINI_API_KEY`:** dùng bộ câu trả lời có sẵn thay vì báo lỗi cấu hình.

### 3.5 Tài liệu (Plan 6)
- **`docs/report/`:**
  - `erd.md`: ERD Mermaid theo schema thật, có bảng khu và vé theo khu;
  - `use-cases.md`;
  - `chuc-nang.md`: đối chiếu tiêu chí chấm, có đường dẫn file;
  - `anh-chup.md`: checklist ảnh chụp màn hình;
  - `kich-ban-demo.md`;
  - thư mục `screenshots/`.
- **`README.md`** viết mới: cài đặt, tài khoản demo, API key, cách chạy test.
- **`docs/architecture.md`** cập nhật theo code hiện tại.

---

## 4. Kiểm tra đã chạy

| Kiểm tra | Kết quả |
|---|---|
| `php artisan test` (SQLite in-memory) | 363 passed, 1612 assertions |
| `npm run build` | OK |
| Gọi HTTP thật trên MySQL | Trang chủ, danh sách, chi tiết, sơ đồ ghế, gợi ý tìm kiếm: 200 |
| | User A giữ 2 ghế: 200; user B giữ trùng ghế: **422** |
| | Thanh toán thiếu thông tin người nhận: 422; đủ thông tin: tạo đơn, đơn hiện trong lịch sử |
| | Đăng bình luận: 201, nội dung được hiển thị dạng text (không chạy HTML) |
| | Admin vào `/admin/*`: 200; user thường: **403** |

Dữ liệu tạo ra khi chạy thử (1 đơn, 1 bình luận) đã được xóa khỏi DB.

**Chưa kiểm tra:** chưa mở trình duyệt xem giao diện, kể cả sơ đồ ghế trên điện thoại.

---

## 5. Nhánh Git

| Nhánh | Xử lý |
|---|---|
| `main` | Đã merge toàn bộ (`af25d01`) |
| `feature/hoan-thien-du-an` | Nhánh tổng hợp của đợt này, giữ lại để tham khảo |
| `feature/update_UI`, `feature/fix-so-do-ghe` | Giữ lại vì mới có commit gần đây; đã nằm trọn trong `main`, nhóm muốn xóa lúc nào cũng được |
| `feature/booking-engine`, `feature/user-account-management`, `feature/fix-bug`, `fix-bug` | **Đã xóa** (đã merge, cũ) |
| `feature/ui-core-function-ticketbox` | **Đã xóa**. Commit chưa gộp duy nhất được giữ ở tag `archive/ui-core-function-ticketbox` |

Không tạo được Pull Request vì token GitHub trên máy chỉ có quyền push. Nội dung mô tả nằm trong commit merge `af25d01`.

---

## 6. Việc còn lại cho nhóm

**Mỗi máy sau khi pull `main`:**

```bash
composer install --no-security-blocking
php artisan migrate          # 2 migration mới cho bảng bookings
php artisan storage:link
npm install && npm run build
```

Trong `.env`, sửa `APP_LOCALE=vi` và `APP_TIMEZONE=Asia/Ho_Chi_Minh`. Thêm `OPENWEATHER_API_KEY` và `GEMINI_API_KEY` nếu có; không có thì app vẫn chạy.

**Cho báo cáo:**
- Chụp ảnh màn hình theo `docs/report/anh-chup.md`.
- Điền tên thành viên vào `README.md` và `docs/report/kich-ban-demo.md`.
- Mở các sơ đồ Mermaid bằng VS Code hoặc mermaid.live để kiểm tra (chưa render thử).

**Chưa làm (ngoài phạm vi hoặc ưu tiên thấp):**
- **Test đặt trùng ghế đồng thời trên MySQL:** test đang chạy SQLite, không kiểm được khóa dòng thật. Đã ghi trong tài liệu và kịch bản demo.
- **Màu sắc:** trang khách và trang admin chưa dùng chung bảng màu.
- **Ảnh QR** vẫn lấy từ dịch vụ ngoài `api.qrserver.com`.
- **Trường "áp dụng cho"** của mã giảm giá chỉ là chữ mô tả, chưa có tác dụng.
- **Khán phòng dạng khu đã có suất diễn** không sửa được tọa độ từ form.
