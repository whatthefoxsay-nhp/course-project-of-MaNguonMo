# TÀI LIỆU ĐẶC TẢ LUỒNG NGHIỆP VỤ CÁC USE CASE (UC_001 - UC_023)
**Dự án:** Nền tảng Đặt vé Sự kiện & Quản lý Khán phòng (TicketBox Platform)  
**Môn học:** Mã nguồn mở (LTMNM)  
**Ngày cập nhật:** 09/11/2026  
**Nhánh:** `feature/ticketing-experience-updates`

---

## MỤC LỤC TỔNG QUAN

| Mã UC | Tên Use Case | Phân hệ | Tác nhân chính | Trạng thái kỹ thuật |
|:---:|:---|:---:|:---|:---:|
| [UC_001](#uc_001--quản-lý-tài-khoản) | Quản lý tài khoản | Quản trị (Admin) | Quản trị viên (Admin) | Đã hoàn thiện |
| [UC_002](#uc_002--quản-lý-danh-mục) | Quản lý danh mục | Quản trị (Admin) | Quản trị viên (Admin) | Đã hoàn thiện |
| [UC_003](#uc_003--quản-lý-địa-điểm) | Quản lý địa điểm | Quản trị (Admin) | Quản trị viên (Admin) | Đã hoàn thiện |
| [UC_004](#uc_004--quản-lý-sự-kiện) | Quản lý sự kiện | Quản trị (Admin) | Quản trị viên (Admin) | Đã hoàn thiện |
| [UC_005](#uc_005--quản-lý-sơ-đồ-ghế) | Quản lý sơ đồ ghế | Quản trị (Admin) | Quản trị viên (Admin) | Đã hoàn thiện |
| [UC_006](#uc_006--quản-lý-loại-vé) | Quản lý loại vé (Seat Zones) | Quản trị (Admin) | Quản trị viên (Admin) | Đã hoàn thiện |
| [UC_007](#uc_007--quản-lý-đơn-hàng) | Quản lý đơn hàng | Quản trị (Admin) | Quản trị viên (Admin) | Đã hoàn thiện |
| [UC_008](#uc_008--quản-lý-mã-giảm) | Quản lý mã giảm giá | Quản trị (Admin) | Quản trị viên (Admin) | Đã hoàn thiện |
| [UC_009](#uc_009--quản-lý-đánh-giá) | Quản lý đánh giá & phản hồi | Quản trị (Admin) | Quản trị viên (Admin) | Đã hoàn thiện |
| [UC_010](#uc_010--quản-lý-thông-báo) | Quản lý thông báo hệ thống | Quản trị (Admin) | Quản trị viên (Admin) | Đã hoàn thiện |
| [UC_011](#uc_011--thống-kê-báo-cáo-dashboard) | Thống kê báo cáo Dashboard | Quản trị (Admin) | Quản trị viên (Admin) | Đã hoàn thiện |
| [UC_012](#uc_012--đăng-ký-tài-khoản) | Đăng ký tài khoản | Người dùng (Client) | Khách vãng lai (Guest) | Đã hoàn thiện |
| [UC_013](#uc_013--tra-cứu-sự-kiện) | Tra cứu sự kiện | Người dùng (Client) | Khách / Thành viên | Đã hoàn thiện |
| [UC_014](#uc_014--xem-chi-tiết-sự-kiện) | Xem chi tiết sự kiện | Người dùng (Client) | Khách / Thành viên | Đã hoàn thiện |
| [UC_015](#uc_015--chọn-vé--chọn-chỗ) | Chọn vé / Chọn chỗ | Người dùng (Client) | Thành viên (User) | Đã hoàn thiện |
| [UC_016](#uc_016--đặt-vé) | Đặt vé & Giữ chỗ | Người dùng (Client) | Thành viên (User) | Đã hoàn thiện |
| [UC_017](#uc_017--lịch-sử-mua-vé) | Lịch sử mua vé | Người dùng (Client) | Thành viên (User) | Đã hoàn thiện |
| [UC_018](#uc_018--xem-vé-điện-tử) | Xem vé điện tử (E-Ticket) | Người dùng (Client) | Thành viên (User) | Đã hoàn thiện |
| [UC_019](#uc_019--thanh-toán-online) | Thanh toán online giả lập | Người dùng (Client) | Thành viên (User) | Đã hoàn thiện |
| [UC_020](#uc_020--quản-lý-hồ-sơ-cá-nhân) | Quản lý hồ sơ cá nhân | Người dùng (Client) | Thành viên (User) | Đã hoàn thiện |
| [UC_021](#uc_021--đánh-giá) | Đánh giá & Bình luận | Người dùng (Client) | Thành viên (User) | Đã hoàn thiện |
| [UC_022](#uc_022--lưu-sự-kiện-vào-yêu-thích) | Lưu sự kiện vào yêu thích | Người dùng (Client) | Thành viên (User) | Thiết kế nghiệp vụ |
| [UC_023](#uc_023--hỏi-đáp-chatbot-ai) | Hỏi đáp trợ lý ảo Chatbot AI | Người dùng (Client) | Khách / Thành viên | Đã hoàn thiện |

---

## CHI TIẾT ĐẶC TẢ TỪNG USE CASE

---

### UC_001 — Quản lý tài khoản
- **Tác nhân:** Quản trị viên (Admin).
- **Mục tiêu:** Quản trị danh sách người dùng toàn hệ thống, cấp quyền, khóa/mở khóa tài khoản khi có vi phạm.
- **Tiền điều kiện:** Đăng nhập với tài khoản có vai trò `admin`.
- **Luồng nghiệp vụ chính:**
  1. Admin truy cập menu **Quản trị người dùng** (`/admin/users`).
  2. Hệ thống tải danh sách người dùng kèm phân trang (10 người dùng/trang), hiển thị họ tên, email, số điện thoại, vai trò (`admin` / `user`), trạng thái (`Đang hoạt động` / `Đã khóa`), ngày tạo.
  3. **Tìm kiếm / Lọc:** Admin có thể lọc theo trạng thái tài khoản hoặc tìm theo từ khóa (tên, email).
  4. **Tạo tài khoản:** Admin bấm "Thêm người dùng", nhập thông tin (tên, email, số điện thoại, vai trò). Hệ thống tự sinh mật khẩu tạm thời ngẫu nhiên 12 ký tự, mã hóa lưu vào DB và trả về thông báo kèm mật khẩu tạm trên màn hình.
  5. **Đổi vai trò:** Admin có thể đổi quyền giữa `admin` và `user`. Hệ thống cập nhật bảng phân quyền Spatie.
  6. **Khóa / Mở khóa tài khoản:** Admin bấm nút chuyển trạng thái tài khoản (AJAX). Hệ thống cập nhật trường `is_active` ngay lập tức.
- **Luồng ngoại lệ / Ràng buộc an toàn:**
  - *Chặn tự khóa / tự hạ quyền:* Admin không thể tự khóa tài khoản của chính mình hoặc tự xóa quyền Admin của mình.
  - *Hiệu lực tức thì:* Khi một tài khoản bị khóa, Middleware `EnsureUserIsActive` sẽ chặn ngay ở request tiếp theo, tự động đăng xuất người dùng đó và báo lỗi nếu họ cố đăng nhập lại.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/Admin/UserController.php`
  - Middleware: `app/Http/Middleware/EnsureUserIsActive.php`
  - View: `resources/views/admin/users/index.blade.php`

---

### UC_002 — Quản lý danh mục
- **Tác nhân:** Quản trị viên (Admin).
- **Mục tiêu:** Phân loại các sự kiện (Âm nhạc, Hội thảo, Thể thao, Kịch nghệ...) giúp người dùng dễ dàng tìm kiếm.
- **Tiền điều kiện:** Đăng nhập tài khoản `admin`.
- **Luồng nghiệp vụ chính:**
  1. Admin vào trang **Danh mục** (`/admin/categories`). Hệ thống hiển thị danh sách các danh mục hiện có cùng số lượng sự kiện thuộc danh mục đó.
  2. **Thêm danh mục:** Admin nhập tên danh mục và mô tả ngắn. Hệ thống tự động chuyển tên thành `slug` chuẩn URL (ví dụ: "Đại Nhạc Hội" → `dai-nhac-hoi`), kiểm tra tính duy nhất và lưu vào DB.
  3. **Chỉnh sửa danh mục:** Admin cập nhật tên hoặc mô tả, hệ thống kiểm tra và cập nhật lại dữ liệu.
  4. **Xóa danh mục:** Admin bấm xóa danh mục. Hệ thống xác nhận và thực hiện xóa mềm/xóa hẳn nếu đủ điều kiện.
- **Luồng ngoại lệ / Ràng buộc:**
  - *Ràng buộc toàn vẹn:* Nếu danh mục đang chứa sự kiện (`events_count > 0`), hệ thống từ chối xóa và hiển thị thông báo: "Không thể xóa danh mục đang có sự kiện".
  - Tên danh mục trùng lặp sẽ bị Form Request từ chối và báo lỗi tiếng Việt.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/Admin/CategoryController.php`
  - Requests: `app/Http/Requests/Admin/CategoryRequest.php`
  - View: `resources/views/admin/categories/index.blade.php`, `form.blade.php`

---

### UC_003 — Quản lý địa điểm
- **Tác nhân:** Quản trị viên (Admin).
- **Mục tiêu:** Quản lý các phòng chiếu, nhà thi đấu, sân vận động, trung tâm hội nghị nơi diễn ra sự kiện.
- **Tiền điều kiện:** Đăng nhập tài khoản `admin`.
- **Luồng nghiệp vụ chính:**
  1. Admin mở mục **Địa điểm / Khán phòng** (`/admin/rooms`).
  2. Hệ thống hiển thị bảng danh sách các địa điểm kèm sức chứa tối đa, mẫu sơ đồ đang áp dụng và số suất diễn liên quan.
  3. **Thêm địa điểm:** Admin nhập tên khán phòng (VD: "SVĐ Quân Khu 7 - Sân Trung Tâm"), vị trí/tòa nhà, chọn một trong các mẫu sơ đồ khởi tạo (Preset Layout). Hệ thống tạo bản ghi phòng.
  4. **Nhân bản địa điểm (Duplicate):** Khi cần tổ chức tại một địa điểm có cấu trúc ghế tương tự, Admin chỉ cần bấm "Nhân bản". Hệ thống sao chép toàn bộ cấu hình phòng, các khu vực ghế và tọa độ sang một phòng mới với hậu tố "(Bản sao)" giúp tiết kiệm thời gian vẽ sơ đồ.
  5. **Xóa địa điểm:** Admin chỉ có thể xóa địa điểm chưa từng mở bán vé.
- **Luồng ngoại lệ / Ràng buộc:**
  - Không được xóa địa điểm nếu đã có suất diễn gắn liền đã bán vé.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/Admin/RoomController.php`
  - Model: `app/Models/Room.php`
  - View: `resources/views/admin/rooms/index.blade.php`, `form.blade.php`

---

### UC_004 — Quản lý sự kiện
- **Tác nhân:** Quản trị viên (Admin).
- **Mục tiêu:** Khởi tạo và biên tập nội dung sự kiện, ca sĩ biểu diễn, thời gian, hình ảnh poster.
- **Tiền điều kiện:** Đăng nhập tài khoản `admin`.
- **Luồng nghiệp vụ chính:**
  1. Admin vào danh sách sự kiện (`/admin/events`), có thể lọc theo danh mục hoặc trạng thái (`draft` - Bản nháp, `published` - Đang mở bán, `archived` - Đã lưu trữ).
  2. **Tạo sự kiện mới:**
     - Nhập thông tin cơ bản: Tên sự kiện, danh mục, thời lượng (phút), chế độ có chọn chỗ ngồi hay không (`is_seated`).
     - Tải ảnh poster (jpg/png/webp ≤ 2MB): Hệ thống hỗ trợ lưu poster lên **Google Drive** (nếu cấu hình dịch vụ) hoặc lưu tại thư mục public cục bộ.
     - Nhập thông tin chi tiết (Lưu dạng cấu trúc JSON): Địa chỉ diễn ra, tọa độ bản đồ, nghệ sĩ / diễn giả (Lineup), lịch trình chi tiết (Timeline).
  3. **Cập nhật sự kiện:** Admin chỉnh sửa thông tin, đổi trạng thái mở bán khi sự kiện đã sẵn sàng.
  4. **Xóa sự kiện:** Nếu sự kiện chưa phát sinh vé bán, Admin có thể xóa.
- **Luồng ngoại lệ / Ràng buộc:**
  - Không cho phép xóa sự kiện đã bán vé thành công. Hệ thống gợi ý chuyển trạng thái sang `archived` (Lưu trữ) để đảm bảo toàn vẹn dữ liệu đơn hàng và lịch sử người dùng.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/Admin/EventController.php`, `EventPosterController.php`
  - Request: `app/Http/Requests/Admin/EventRequest.php`
  - Model: `app/Models/Event.php`
  - View: `resources/views/admin/events/index.blade.php`, `form.blade.php`

---

### UC_005 — Quản lý sơ đồ ghế
- **Tác nhân:** Quản trị viên (Admin).
- **Mục tiêu:** Thiết kế ma trận chỗ ngồi trực quan cho từng khán phòng theo các loại hình sân khấu thực tế.
- **Tiền điều kiện:** Đăng nhập tài khoản `admin`, đã tạo địa điểm (Room).
- **Luồng nghiệp vụ chính:**
  1. Admin bấm vào nút **"Dựng sơ đồ (Builder)"** tại một địa điểm (`/admin/rooms/{id}/builder`).
  2. **Chọn mẫu bố trí sẵn (6 Layout Presets):**
     - Sân vận động lớn (Stadium - có khán đài A/B/C/D, fanzone).
     - Nhà hát kịch nghệ (Theater - xếp vòm, tầng lửng, ban công).
     - Hội trường âm nhạc (Auditorium - dốc bục).
     - Tiệc cưới & Bàn tròn Gala (Banquet - các bàn tiệc tròn 8-10 ghế).
     - Không gian mở (Open Space - khu đứng tự do GA).
     - Lưới tùy chỉnh (Custom Grid - tự do thêm bớt hàng cột).
  3. **Tương tác kéo thả / vẽ sơ đồ:** Admin chỉnh số hàng, số cột, click chuột để biến ô thành: Ghế ngồi, Bàn tiệc, Lối đi trống (Aisle), hoặc Sân khấu (Stage).
  4. **Lưu sơ đồ:** Hệ thống kiểm tra tính hợp lệ và tự động sinh bản ghi trong bảng `seats` với đầy đủ mã hàng (`row_label`), số ghế (`seat_number`), tọa độ (`pos_col`) và liên kết khu vực (`zone_id`).
- **Luồng ngoại lệ / Ràng buộc:**
  - Giới hạn sức chứa an toàn tối đa 5.000 chỗ/khán phòng để đảm bảo hiệu năng tải trang web.
  - Địa điểm đã phát sinh suất diễn và bán vé thì không cho phép thay đổi cấu trúc vị trí các ghế cũ.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/Admin/RoomController.php@builder`
  - Support: `app/Support/LayoutTemplates.php`, `app/Services/RoomSeatGenerator.php`
  - View: `resources/views/admin/rooms/builder.blade.php`

---

### UC_006 — Quản lý loại vé (Seat Zones & Ticket Tiers)
- **Tác nhân:** Quản trị viên (Admin).
- **Mục tiêu:** Định nghĩa các phân hạng vé (SVIP, VIP, Standard, Fanzone, Skybox...) kèm chính sách phụ thu trên từng khu vực của địa điểm.
- **Tiền điều kiện:** Đăng nhập tài khoản `admin`.
- **Luồng nghiệp vụ chính:**
  1. Trong giao diện thiết lập địa điểm và sơ đồ, Admin tạo các **Khu vực ghế (Seat Zones)**.
  2. Với mỗi khu, Admin thiết lập:
     - Tên loại vé: VD "SVIP Kim Cương", "VIP Sàn", "Khán Đài A", "Khu Đứng GA Fanzone".
     - Mã khu (Code): VD `SVIP`, `VIP`, `CAT1`, `GA`.
     - Loại khu (`kind`): Khu ghế ngồi (`seated`), khu bàn tiệc tròn (`table`), hoặc khu đứng tự do (`standing`).
     - Màu sắc hiển thị nhận diện trên sơ đồ (Mã HEX, VD `#D4AF37` cho VIP, `#3A5A40` cho Standard).
     - Cổng soát vé khuyến nghị (Gate): VD "Cổng 1 - Tầng Trệt".
     - Mức phụ thu (`surcharge`): Số tiền cộng thêm vào giá cơ bản của suất diễn (VD: Suất diễn giá sàn 300.000đ, vé VIP phụ thu +200.000đ → Giá vé VIP = 500.000đ).
  3. Hệ thống lưu cấu trúc vào bảng `seat_zones`. Khi suất diễn diễn ra, giá của từng loại vé sẽ được tính tự động chính xác cho khách hàng.
- **File mã nguồn liên quan:**
  - Model: `app/Models/SeatZone.php`, `Seat.php`
  - Migration: `*_create_seat_zones_table.php`
  - Logic tính giá: `app/Models/Seat.php` (`effectivePrice()`), `app/Support/TicketTiers.php`

---

### UC_007 — Quản lý đơn hàng
- **Tác nhân:** Quản trị viên (Admin).
- **Mục tiêu:** Kiểm soát tất cả các giao dịch đặt vé của khách hàng, xác nhận thanh toán thủ công hoặc hủy đơn vi phạm.
- **Tiền điều kiện:** Đăng nhập tài khoản `admin`.
- **Luồng nghiệp vụ chính:**
  1. Admin truy cập **Đơn đặt vé** (`/admin/bookings`).
  2. Bảng hiển thị danh sách đơn hàng: Mã đơn (`TBX-XXXXXXXX`), người mua, sự kiện, suất diễn, số lượng vé, tổng tiền, phương thức thanh toán, trạng thái (`pending`, `confirmed`, `cancelled`).
  3. **Xem chi tiết:** Bấm vào đơn để xem thông tin người mua, danh sách từng vé, ghế ngồi đã chọn, mã giảm giá đã áp dụng.
  4. **Xác nhận đơn (Confirm):** Dành cho các đơn đang chờ thanh toán (`pending`). Khi tiền đã vào tài khoản, Admin bấm xác nhận. Hệ thống đổi trạng thái đơn sang `confirmed`, khóa ghế chính thức (`booked`).
  5. **Hủy đơn (Cancel):** Admin bấm hủy đơn. Hệ thống chuyển trạng thái sang `cancelled`, giải phóng toàn bộ ghế trong đơn về trạng thái trống (`available`) để người khác có thể đặt mua.
- **Luồng ngoại lệ / Ràng buộc:**
  - *Chống bán trùng ghế:* Nếu đơn hàng đã bị hủy, Admin không thể xác nhận lại nếu bất kỳ chiếc ghế nào trong đơn đó đã được bán cho một khách hàng khác.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/Admin/BookingController.php`
  - Model: `app/Models/Booking.php`, `BookingItem.php`
  - View: `resources/views/admin/bookings/index.blade.php`, `show.blade.php`

---

### UC_008 — Quản lý mã giảm giá
- **Tác nhân:** Quản trị viên (Admin).
- **Mục tiêu:** Tạo và phát hành các voucher khuyến mãi kích cầu mua vé.
- **Tiền điều kiện:** Đăng nhập tài khoản `admin`.
- **Luồng nghiệp vụ chính:**
  1. Admin mở mục **Mã giảm giá** (`/admin/discounts`).
  2. **Tạo mã mới:** Admin bấm "Thêm mã giảm giá" và điền:
     - Mã voucher (Code): Tự động viết hoa không dấu (VD: `CHAOHANHTRINH`, `SUMMER2026`).
     - Kiểu giảm: Theo phần trăm (%) hoặc số tiền cố định (VNĐ).
     - Giá trị giảm: VD 15% hoặc 50.000 VNĐ.
     - Giới hạn giảm tối đa (với %): Tránh trường hợp đơn quá lớn giảm vượt ngân sách.
     - Giá trị đơn hàng tối thiểu để áp dụng.
     - Ngày hết hạn & Số lượt sử dụng tối đa.
  3. **Bật / Tắt nhanh:** Có nút toggle trạng thái kích hoạt ngay trên danh sách mà không cần xóa mã.
- **Luồng ngoại lệ / Ràng buộc:**
  - Hệ thống kiểm tra trùng lặp mã code và chặn giá trị % vượt quá 100%.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/Admin/DiscountController.php`
  - Model: `app/Models/Discount.php`
  - View: `resources/views/admin/discounts/index.blade.php`

---

### UC_009 — Quản lý đánh giá & phản hồi
- **Tác nhân:** Quản trị viên (Admin).
- **Mục tiêu:** Giám sát nhận xét, xếp hạng sao của khán giả, phản hồi thắc mắc và kiểm duyệt nội dung độc hại.
- **Tiền điều kiện:** Đăng nhập tài khoản `admin`.
- **Luồng nghiệp vụ chính:**
  1. Admin vào trang **Quản lý đánh giá** (`/admin/reviews`).
  2. Màn hình thống kê: Tổng số lượt đánh giá, số lượt có chấm điểm sao, điểm đánh giá trung bình toàn hệ thống (VD: 4.8 / 5.0 ⭐).
  3. **Lọc và tìm kiếm:** Admin lọc đánh giá theo số sao (1 đến 5 sao), tìm kiếm theo tên sự kiện, nội dung đánh giá hoặc người bình luận.
  4. **Phản hồi đánh giá (Admin Reply):** Admin nhập nội dung phản hồi chính thức cho bình luận của khách hàng. Hệ thống lưu `admin_reply`, thời gian phản hồi và tự động bắn một thông báo chuông (`CommentReplyNotification`) đến tài khoản của khách hàng đó.
  5. **Xóa đánh giá vi phạm:** Admin có quyền xóa bỏ các bình luận thô tục, spam, sai lệch thông tin.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/Admin/ReviewController.php`
  - Notification: `app/Notifications/CommentReplyNotification.php`
  - View: `resources/views/admin/reviews/index.blade.php`

---

### UC_010 — Quản lý thông báo hệ thống
- **Tác nhân:** Quản trị viên (Admin).
- **Mục tiêu:** Gửi thông báo đại trà (Broadcast Announcement) đến toàn thể người dùng đang hoạt động để thông báo bảo trì, ưu đãi sốc hoặc mở bán concert lớn.
- **Tiền điều kiện:** Đăng nhập tài khoản `admin`.
- **Luồng nghiệp vụ chính:**
  1. Admin vào mục **Thông báo** (`/admin/notifications`).
  2. Bảng hiển thị lịch sử các bản tin hệ thống đã phát hành gần đây kèm thời gian và tiêu đề.
  3. **Soạn thông báo mới:** Admin nhập Tiêu đề thông báo và Nội dung chi tiết.
  4. **Phát tán (Broadcast):** Bấm "Gửi thông báo". Hệ thống sử dụng cơ chế xử lý theo đợt (`chunkById(200)`) để chia nhỏ danh sách người dùng `user` đang hoạt động, gửi đối tượng `SystemAnnouncement` lưu vào bảng `notifications`.
  5. Hệ thống báo cáo tổng số khách hàng đã nhận được thông báo thành công.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/Admin/AnnouncementController.php`
  - Notification: `app/Notifications/SystemAnnouncement.php`
  - View: `resources/views/admin/notifications/index.blade.php`

---

### UC_011 — Thống kê báo cáo Dashboard
- **Tác nhân:** Quản trị viên (Admin).
- **Mục tiêu:** Cung cấp bức tranh toàn cảnh về hiệu quả kinh doanh, doanh thu thực và số lượng vé bán ra.
- **Tiền điều kiện:** Đăng nhập tài khoản `admin`.
- **Luồng nghiệp vụ chính:**
  1. Khi Admin đăng nhập, hệ thống điều hướng vào **Dashboard** (`/admin`).
  2. Màn hình trực quan hiển thị các thẻ số liệu thực (Real data từ DB, cam kết không dùng số liệu ảo):
     - Tổng doanh thu thực tế từ các đơn thành công (`confirmed`).
     - Tổng số vé đã bán ra.
     - Số đơn hàng mới đang chờ xử lý.
     - Tỷ lệ lấp đầy ghế bình quân của các sự kiện.
     - Danh sách 5 sự kiện có doanh thu cao nhất.
  3. **Báo cáo & Xuất file PDF (`/admin/reports` & `/admin/reports/pdf`):** Admin xem bảng chi tiết doanh thu theo khoảng thời gian, xem biểu đồ doanh số và bấm nút **"Xuất báo cáo PDF"**. Thư viện `barryvdh/laravel-dompdf` sẽ biên dịch thành file tài liệu PDF tiếng Việt có đầy đủ bảng biểu số liệu để in ấn hoặc báo cáo cấp trên.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/Admin/DashboardController.php`, `ReportController.php`
  - View: `resources/views/admin/dashboard.blade.php`, `reports/index.blade.php`, `reports/pdf.blade.php`

---

### UC_012 — Đăng ký tài khoản
- **Tác nhân:** Khách vãng lai (Guest).
- **Mục tiêu:** Tạo tài khoản định danh để tham gia mua vé, giữ chỗ và nhận vé điện tử.
- **Tiền điều kiện:** Người dùng chưa đăng nhập.
- **Luồng nghiệp vụ chính:**
  1. Khách bấm nút "Đăng ký" trên thanh điều hướng (`/register`).
  2. **Cách 1 - Đăng ký biểu mẫu truyền thống:**
     - Nhập họ tên, email, số điện thoại, mật khẩu và xác nhận mật khẩu.
     - Hệ thống kiểm tra: Email chưa từng đăng ký, định dạng số điện thoại Việt Nam hợp lệ, mật khẩu tối thiểu 8 ký tự.
     - Tạo tài khoản với trạng thái `is_active = true`, tự động gán vai trò `user` qua Spatie Permission.
     - Hệ thống tự động đăng nhập người dùng và chuyển về trang chủ kèm thông báo chào mừng.
  3. **Cách 2 - Đăng ký nhanh qua Google OAuth:**
     - Khách bấm nút "Đăng ký bằng tài khoản Google".
     - Hệ thống chuyển hướng sang Google xác thực. Nếu email chưa có trong hệ thống, hệ thống tự động tạo tài khoản với `google_id`, lấy avatar và họ tên từ Google.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/Auth/RegisteredUserController.php`
  - OAuth: `app/Http/Controllers/Auth/GoogleAuthController.php`
  - View: `resources/views/auth/register.blade.php`

---

### UC_013 — Tra cứu sự kiện
- **Tác nhân:** Khách vãng lai, Thành viên.
- **Mục tiêu:** Dễ dàng tìm kiếm show diễn, nghệ sĩ, thể loại sự kiện mình quan tâm.
- **Luồng nghiệp vụ chính:**
  1. **Tìm kiếm tức thì (AJAX Live Search):** Người dùng gõ từ khóa vào ô tìm kiếm trên thanh menu Header. Sau 0.25 giây (debounce chống spam), hệ thống gửi request đến `GET /search/suggestions` và trả về ngay hộp gợi ý tối đa 6 sự kiện khớp từ khóa kèm poster, ngày diễn và giá vé thấp nhất. Bấm vào sự kiện để chuyển thẳng đến trang chi tiết.
  2. **Tìm kiếm & Lọc nâng cao tại Catalog (`/events`):**
     - Người dùng nhập từ khóa tìm kiếm theo tên hoặc mô tả sự kiện.
     - Chọn lọc theo Danh mục sự kiện (Âm nhạc, Thể thao, Hội thảo...).
     - Hệ thống lọc các sự kiện đang ở trạng thái `published` (Đang mở bán) và phân trang 9 sự kiện/trang.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/EventController.php@index`, `SearchSuggestionController.php`
  - View: `resources/views/events/index.blade.php`, `components/search-modal.blade.php`

---

### UC_014 — Xem chi tiết sự kiện
- **Tác nhân:** Khách vãng lai, Thành viên.
- **Mục tiêu:** Cung cấp đầy đủ thông tin về sự kiện để khách hàng đưa ra quyết định mua vé.
- **Luồng nghiệp vụ chính:**
  1. Khách hàng click vào một sự kiện để mở trang chi tiết (`/events/{slug}`).
  2. **Nội dung hiển thị phong phú:**
     - Poster kích thước lớn chất lượng cao.
     - Tên sự kiện, thời lượng, danh mục, bảng giá vé tối thiểu.
     - Mô tả nội dung chương trình.
     - Danh sách nghệ sĩ tham gia biểu diễn (Lineup).
     - Lịch trình sự kiện (Timeline).
     - Danh sách các suất diễn (`showtimes`) kèm ngày giờ bắt đầu và địa điểm tổ chức.
  3. **Tích hợp dịch vụ bên ngoài (External APIs):**
     - **Bản đồ Google Maps:** Nhúng iframe bản đồ trực tiếp chỉ đường đến địa điểm tổ chức.
     - **Dự báo thời tiết OpenWeatherMap:** Hệ thống tự động gọi API OpenWeatherMap lấy nhiệt độ, thời tiết (nắng/mưa), độ ẩm tại thời điểm diễn ra sự kiện (có cache 30 phút).
  4. **Khu vực Đánh giá & Bình luận:** Hiển thị số sao trung bình và các nhận xét của khán giả đã mua vé trước đó.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/EventController.php@show`
  - Service: `app/Services/WeatherService.php`, `app/Support/MapEmbed.php`
  - View: `resources/views/events/show.blade.php`

---

### UC_015 — Chọn vé / Chọn chỗ
- **Tác nhân:** Thành viên đã đăng nhập.
- **Mục tiêu:** Lựa chọn vị trí ghế ngồi hoặc số lượng vé phù hợp trên sơ đồ khán phòng.
- **Tiền điều kiện:** Khách hàng đã chọn suất diễn của sự kiện.
- **Luồng nghiệp vụ chính:**
  1. Người dùng bấm "Mua vé ngay" tại suất diễn mong muốn (`/showtimes/{id}/seats`).
  2. **Đối với sự kiện có chọn chỗ (Seated Event):**
     - Màn hình hiển thị sơ đồ khán phòng trực quan (SVG/Canvas).
     - Ghế được tô màu theo Khu vực (Zone): VIP (Vàng), Tiêu chuẩn (Xanh rêu), Ghế đôi, Bàn tiệc tròn.
     - Trạng thái từng ghế: Trống (chọn được), Đang có người giữ (màu vàng nhạt/vô hiệu hóa), Đã bán (màu xám tối).
     - Tương tác: Người dùng có thể zoom phóng to/thu nhỏ, rê chuột xem tooltip (số ghế, loại vé, giá tiền), click để chọn ghế (tối đa 8 ghế).
  3. **Đối với sự kiện khu đứng (GA Standing) hoặc không chọn chỗ:**
     - Hiển thị bảng danh sách các khu vực vé kèm số lượng còn lại.
     - Người dùng bấm nút tăng giảm số lượng vé cần mua cho từng khu.
  4. Thanh tóm tắt đơn vé bên dưới hiển thị tổng số ghế đã chọn và tổng tiền tạm tính.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/ShowtimeController.php@seats`, `@seatStatus`
  - Support: `app/Support/SeatMap.php`
  - View: `resources/views/showtimes/seats.blade.php`, `components/seat-map.blade.php`

---

### UC_016 — Đặt vé & Giữ chỗ
- **Tác nhân:** Thành viên đã đăng nhập.
- **Mục tiêu:** Khóa ghế an toàn trong 10 phút để người dùng yên tâm thanh toán mà không bị người khác nẫng tay trên.
- **Tiền điều kiện:** Đã đăng nhập tài khoản hợp lệ, chưa hết hạn suất diễn.
- **Luồng nghiệp vụ chính:**
  1. Sau khi chọn ghế xong ở UC_015, người dùng bấm nút **"Xác Nhận Giữ Chỗ"**.
  2. Trình duyệt gửi AJAX request `POST /showtimes/{id}/holds` chứa danh sách mã ghế / số lượng vé khu đứng.
  3. **Cơ chế Khóa giao dịch (Database Transaction & Pessimistic Lock):**
     - Hệ thống khóa tạm thời các bản ghi ghế tương ứng để kiểm tra trạng thái.
     - Đảm bảo tất cả các ghế đều đang ở trạng thái `available` (hoặc suất giữ cũ đã hết hạn).
     - Chuyển trạng thái ghế sang `held`, gán `held_by_user_id = Auth::id()`, đặt thời hạn giữ chỗ `held_until = now() + 10 phút`.
  4. Hệ thống chuyển hướng người dùng vào trang **Giỏ vé** (`/cart`).
  5. **Tại giỏ vé:** Đồng hồ đếm ngược 10 phút đếm lùi thời gian thực. Người dùng có thể xóa bớt vé khỏi giỏ hoặc nhập mã giảm giá (`POST /cart/discount`).
- **Luồng ngoại lệ / Ràng buộc:**
  - *Xung đột giữ chỗ (Conflict Race Condition):* Nếu ghế vừa bị người khác giữ trước trong tích tắc, hệ thống hủy thao tác và báo lỗi: "Ghế [Tên ghế] vừa có người khác giữ, vui lòng chọn ghế khác".
  - *Tự động nhả chỗ hết hạn:* Sau 10 phút nếu người dùng không thanh toán, Lệnh Artisan `php artisan seats:release-holds` (chạy ngầm mỗi phút qua Scheduler) sẽ tự động đưa ghế về lại trạng thái `available`.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/SeatHoldController.php`, `CartController.php`
  - Service: `app/Services/SeatHoldService.php`
  - Command: `app/Console/Commands/ReleaseExpiredSeatHolds.php`
  - View: `resources/views/cart/index.blade.php`

---

### UC_017 — Lịch sử mua vé
- **Tác nhân:** Thành viên đã đăng nhập.
- **Mục tiêu:** Theo dõi và quản lý toàn bộ các giao dịch đặt vé đã từng thực hiện trên nền tảng.
- **Tiền điều kiện:** Đã đăng nhập tài khoản.
- **Luồng nghiệp vụ chính:**
  1. Người dùng mở menu tài khoản và chọn **"Lịch sử đặt vé"** (`/bookings/history`).
  2. Hệ thống truy vấn danh sách các đơn hàng của riêng người dùng đó (`auth()->user()->bookings()`).
  3. Bảng danh sách đơn vé hiển thị:
     - Mã đơn hàng (`TBX-XXXXXXXX`).
     - Tên sự kiện & ảnh poster thu nhỏ.
     - Suất diễn, thời gian tổ chức, địa điểm.
     - Danh sách ghế / loại vé đã mua.
     - Tổng tiền đã thanh toán, phương thức thanh toán.
     - Trạng thái đơn: Đã xác nhận (`confirmed`), Chờ thanh toán (`pending`), hoặc Đã hủy (`cancelled`).
  4. Người dùng có thể bấm vào nút **"Xem vé điện tử"** để xuất trình vé vào cổng.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/BookingController.php@history`
  - View: `resources/views/bookings/history.blade.php`

---

### UC_018 — Xem vé điện tử (E-Ticket)
- **Tác nhân:** Thành viên sở hữu vé.
- **Mục tiêu:** Cung cấp vé vào cổng chính thức dưới định dạng điện tử hiện đại, phục vụ check-in sự kiện.
- **Tiền điều kiện:** Đơn hàng đã ở trạng thái `confirmed`.
- **Luồng nghiệp vụ chính:**
  1. Người dùng bấm xem vé từ Lịch sử mua vé hoặc sau khi thanh toán thành công (`/bookings/{id}/ticket`).
  2. **Giao diện Vé điện tử phong cách TicketBox:**
     - Thiết kế thẻ vé sang trọng, có thông tin sự kiện, thời gian, cửa vào (Gate), khu vực (Zone) và số ghế ngồi cụ thể.
     - **Mã QR Code bảo mật:** Sinh mã QR chứa chuỗi mã hóa xác thực duy nhất của vé để nhân viên sự kiện quét tại cửa vào.
  3. **Các tính năng đi kèm:**
     - **Tải file PDF vé (`/bookings/{id}/ticket/pdf`):** Tải về máy file PDF định dạng vé tiêu chuẩn để lưu trữ hoặc in ra giấy.
     - **Gửi vé qua Gmail:** Tự động gửi email thông báo kèm vé đính kèm vào hòm thư người mua.
     - **Lưu trữ đám mây Google Drive:** Hỗ trợ đồng bộ bản sao vé điện tử lên Google Drive cá nhân của người dùng.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/BookingController.php@ticket`, `@ticketPdf`
  - Service: `app/Services/TicketPdfService.php`
  - View: `resources/views/bookings/ticket.blade.php`, `tickets/pdf.blade.php`

---

### UC_019 — Thanh toán online giả lập
- **Tác nhân:** Thành viên sở hữu giỏ vé.
- **Mục tiêu:** Hoàn tất thanh toán tiền vé qua các cổng thanh toán điện tử phổ biến tại Việt Nam.
- **Tiền điều kiện:** Giỏ vé đang có ghế giữ chỗ còn hiệu lực thời gian.
- **Luồng nghiệp vụ chính:**
  1. Tại trang giỏ vé (`/cart`), người dùng xác nhận thông tin người nhận vé (Tên, Email, Số điện thoại).
  2. **Chọn phương thức thanh toán:**
     - **VietQR:** Hiển thị mã QR ngân hàng chuẩn NAPAS 24/7 (kèm số tài khoản và nội dung chuyển khoản tự động).
     - **Ví điện tử MoMo.**
     - **Ví điện tử ZaloPay.**
     - **Thẻ ngân hàng nội địa / Quốc tế (Visa, Mastercard).**
  3. Người dùng bấm nút **"Xác Nhận & Thanh Toán"** (`POST /checkout`).
  4. **Quy trình xử lý giao dịch tại Backend:**
     - Kiểm tra giỏ hàng còn hạn (nếu hết hạn, hủy giao dịch và báo lỗi).
     - Tính tổng tiền (áp dụng giảm giá voucher nếu có).
     - Tạo bản ghi đơn hàng trong bảng `bookings` với trạng thái `confirmed`.
     - Chuyển trạng thái toàn bộ ghế giữ thành `booked` (đã bán chính thức).
     - Gửi email xác nhận kèm vé điện tử qua Gmail.
  5. Chuyển hướng người dùng sang trang hiển thị Vé điện tử thành công (UC_018).
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/CheckoutController.php`
  - Service: `app/Services/CheckoutService.php`
  - Request: `app/Http/Requests/CheckoutRequest.php`

---

### UC_020 — Quản lý hồ sơ cá nhân
- **Tác nhân:** Thành viên đã đăng nhập.
- **Mục tiêu:** Cập nhật thông tin định danh cá nhân, ảnh đại diện và quản lý bảo mật tài khoản.
- **Tiền điều kiện:** Đã đăng nhập tài khoản.
- **Luồng nghiệp vụ chính:**
  1. Người dùng bấm vào avatar trên Header và chọn **"Hồ sơ cá nhân"** (`/profile`).
  2. **Cập nhật thông tin cơ bản:** Sửa Họ tên, Số điện thoại liên hệ. Bấm "Lưu thay đổi".
  3. **Tải lên ảnh đại diện (Avatar):** Chọn ảnh đại diện từ thiết bị (jpg/png ≤ 1MB). Hệ thống lưu file ảnh vào thư mục storage và hiển thị ngay trên thanh điều hướng.
  4. **Đổi mật khẩu:** Nhập mật khẩu hiện tại, mật khẩu mới và xác nhận mật khẩu mới. Hệ thống kiểm tra mật khẩu cũ chính xác và cập nhật mật khẩu mới mã hóa Bcrypt.
  5. **Xóa tài khoản:** Nếu không còn nhu cầu sử dụng, người dùng nhập mật khẩu xác nhận để xóa vĩnh viễn tài khoản của mình.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/ProfileController.php`, `UserAvatarController.php`
  - Request: `app/Http/Requests/ProfileUpdateRequest.php`
  - View: `resources/views/profile/edit.blade.php`

---

### UC_021 — Đánh giá & Bình luận sự kiện
- **Tác nhân:** Thành viên đã đăng nhập.
- **Mục tiêu:** Khán giả chia sẻ cảm nhận, chấm điểm số sao về trải nghiệm tham gia sự kiện.
- **Tiền điều kiện:** Đã đăng nhập tài khoản.
- **Luồng nghiệp vụ chính:**
  1. Người dùng mở trang chi tiết sự kiện (`/events/{slug}`).
  2. Kéo xuống mục **"Đánh Giá & Cảm Nhận Khán Giả"**.
  3. **Chấm sao & Nhập nội dung:**
     - Chọn số sao từ 1 đến 5 sao (⭐).
     - Nhập nội dung cảm nhận (từ 3 đến 1000 ký tự).
     - Bấm nút "Gửi nhận xét".
  4. Hệ thống kiểm tra hợp lệ, lưu vào bảng `comments` và phản hồi AJAX trả về ngay lập tức để chèn nhận xét mới lên đầu danh sách mà không cần tải lại toàn bộ trang web.
  5. **Xóa bình luận:** Tác giả của bình luận có nút "Xóa" để gỡ bỏ bình luận của chính mình khi muốn.
  6. **Xem phản hồi từ Ban tổ chức:** Nếu Admin đã gửi phản hồi, nội dung phản hồi của Admin sẽ hiển thị lồng phía dưới bình luận của khách hàng.
- **File mã nguồn liên quan:**
  - Controller: `app/Http/Controllers/CommentController.php`
  - Request: `app/Http/Requests/StoreCommentRequest.php`
  - Policy: `app/Policies/CommentPolicy.php`
  - Model: `app/Models/Comment.php`

---

### UC_022 — Lưu sự kiện vào yêu thích (Wishlist)
- **Tác nhân:** Thành viên đã đăng nhập.
- **Mục tiêu:** Đánh dấu theo dõi các sự kiện mong muốn để dễ dàng tra cứu lại hoặc nhận thông báo mở bán vé sớm.
- **Đặc tả luồng nghiệp vụ thiết kế:**
  1. Tại danh sách sự kiện (`/events`) hoặc trang chi tiết sự kiện (`/events/{slug}`), trên mỗi thẻ sự kiện có biểu tượng **Trái tim (Yêu thích)**.
  2. Người dùng click vào biểu tượng Trái tim:
     - Nếu chưa đăng nhập: Hệ thống hiển thị hộp thoại gợi ý đăng nhập để lưu sự kiện.
     - Nếu đã đăng nhập: Hệ thống gửi request AJAX thêm sự kiện vào danh sách yêu thích của người dùng (`favorites`), biểu tượng trái tim chuyển sang màu đỏ rực.
  3. Click lần thứ hai vào trái tim sẽ bỏ sự kiện khỏi danh sách yêu thích.
  4. **Trang xem lại sự kiện yêu thích:** Người dùng vào mục "Sự kiện yêu thích" trong trang cá nhân để xem nhanh toàn bộ các sự kiện đã lưu.
- **Hiện trạng kỹ thuật trong dự án:**
  - Đây là use case nằm trong định hướng mở rộng của hệ thống.
  - Hiện tại giao diện đã chuẩn bị phong cách và nút bấm, tầng cơ sở dữ liệu sẽ mở rộng bảng liên kết `event_user_favorites` khi kích hoạt chính thức.

---

### UC_023 — Hỏi đáp trợ lý ảo Chatbot AI
- **Tác nhân:** Khách vãng lai, Thành viên.
- **Mục tiêu:** Cung cấp kênh trợ lý ảo tự động 24/7 giải đáp thắc mắc về lịch diễn, giá vé, chính sách đổi trả và hướng dẫn đặt chỗ.
- **Tiền điều kiện:** Không có (hoạt động trực tiếp trên giao diện mọi trang).
- **Luồng nghiệp vụ chính:**
  1. Khi người dùng truy cập website, ở góc dưới bên phải màn hình luôn hiển thị biểu tượng **TicketBot AI**. Sau 1.8 giây, một bóng chào mừng thân thiện tự động xuất hiện.
  2. Người dùng click mở hộp thoại Chatbot AI.
  3. **Hỏi đáp qua các nút gợi ý nhanh (Quick Chips):**
     - Người dùng bấm vào các câu hỏi gợi ý sẵn:
       - *🔥 Concert hot nhất tuần này?*
       - *🎟️ Quy trình giữ chỗ 10 phút như thế nào?*
       - *💳 Hướng dẫn thanh toán VietQR & MoMo?*
       - *📱 Làm sao để tải vé điện tử E-Ticket QR Code?*
       - *🏟️ Sơ đồ khán đài SVĐ Quân Khu 7?*
       - *🔄 Chính sách hoàn hủy đổi vé?*
     - Chatbot AI lập tức đưa ra câu trả lời chi tiết, chuẩn xác, định dạng danh sách gạch đầu dòng rõ ràng, kèm thẻ sự kiện (Event Card) bấm vào là tới thẳng trang chọn vé.
  4. **Chat tự do qua khung nhập:** Người dùng nhập câu hỏi bất kỳ vào ô chat. Bot phân tích từ khóa nghiệp vụ và đưa ra câu trả lời tương ứng.
- **File mã nguồn liên quan:**
  - Component: `resources/views/components/ai-chatbot.blade.php`
  - Tích hợp layout: `resources/views/layouts/site.blade.php`

---

## TỔNG KẾT BẢO ĐẢM KIẾN TRÚC & MÃ NGUỒN
- Toàn bộ 23 Use Case trên được xây dựng bám sát theo các quy chuẩn:
  1. **MVC Architecture:** Tách biệt rõ ràng Model, Controller và Blade Views.
  2. **Security & Authorization:** Sử dụng Laravel Form Request, Spatie Permission (`admin` / `user`), CSRF protection, Middleware lọc truy cập.
  3. **High Concurrency & Transaction:** Cơ chế giữ chỗ chống bán trùng ghế (`DB::transaction`, `lockForUpdate()`), tự động hủy chỗ bằng Laravel Scheduler.
  4. **Testing Coverage:** Đảm bảo toàn bộ các luồng qua bộ kiểm thử Pest (`tests/Feature/**`).

