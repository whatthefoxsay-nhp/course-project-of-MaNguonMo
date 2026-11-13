# TỔNG HỢP DANH MỤC TEST CASE THEO USE CASE (UC_001 - UC_023)
**Dự án:** Nền tảng Đặt vé Sự kiện & Quản lý Khán phòng (TicketBox Platform)  
**Framework kiểm thử tự động:** Pest PHP (PHP 8.3 / Laravel 11)  
**Lệnh thực thi toàn bộ test:** `php artisan test`  
**Ngày cập nhật:** 13/11/2026  

---

## CẤU TRÚC THƯ MỤC KIỂM THỬ (TEST DIRECTORY STRUCTURE)

Hệ thống kiểm thử tự động được tái cấu trúc thành các nhóm thư mục chuyên biệt, trực quan và dễ theo dõi:

```text
tests/
├── Feature/
│   ├── Admin/         # Toàn bộ kiểm thử phân hệ Quản trị viên (UC_001 - UC_011)
│   ├── Client/        # Toàn bộ kiểm thử phân hệ Khách hàng / Người dùng (UC_012 - UC_023)
│   │   └── Auth/      # Bộ kịch bản xác thực, đăng nhập, bảo mật mật khẩu
│   ├── Services/      # Kiểm thử các dịch vụ tích hợp API (Google Drive, Gmail, Thời tiết...)
│   └── System/        # Kiểm thử hạ tầng hệ thống (Database Seeder, Preset Layouts...)
├── Unit/              # Kiểm thử đơn vị các logic tính toán phụ thu, chiết khấu
├── Pest.php           # Cấu hình kiểm thử Pest & Custom Helpers
├── TestCase.php       # Base TestCase mở rộng của Laravel
└── TestCase.md        # Toàn bộ tài liệu chi tiết test case theo Use Case
```

### Các lệnh chạy kiểm thử theo phân hệ:
- **Chạy toàn bộ test:** `php artisan test`
- **Chạy riêng phân hệ Quản trị (Admin):** `php artisan test tests/Feature/Admin`
- **Chạy riêng phân hệ Khách hàng (Client):** `php artisan test tests/Feature/Client`
- **Chạy riêng dịch vụ API bên ngoài (Services):** `php artisan test tests/Feature/Services`
- **Chạy kiểm thử hạ tầng (System):** `php artisan test tests/Feature/System`

---

## 1. BẢNG TỔNG HỢP TIẾN ĐỘ KIỂM THỬ THEO USE CASE

| Mã UC | Tên Use Case | Phân hệ | File Test chính liên quan | Số Test Case | Trạng thái |
|:---:|:---|:---:|:---|:---:|:---:|
| **UC_001** | Quản lý tài khoản | Admin | `tests/Feature/Admin/AdminUserManagementTest.php`, `LockedUserTest.php`, `RoleSeederTest.php` | 18 | Đã tự động hóa |
| **UC_002** | Quản lý danh mục | Admin | `tests/Feature/Admin/AdminCategoryCrudTest.php`, `AdminRoleAndCrudVerificationTest.php` | 17 | Đã tự động hóa |
| **UC_003** | Quản lý địa điểm | Admin | `tests/Feature/Admin/AdminRoomUseCaseTest.php`, `AdminRoomBuilderTest.php`, `AdminRoomSafetyTest.php` | 17 | Đã tự động hóa |
| **UC_004** | Quản lý sự kiện | Admin | `tests/Feature/Admin/AdminEventUseCaseTest.php`, `AdminEventCrudTest.php` | 24 | Đã tự động hóa |
| **UC_005** | Quản lý sơ đồ ghế | Admin | `tests/Feature/Admin/AdminSeatMapUseCaseTest.php`, `AdminRoomLayoutValidationTest.php` | 17 | Đã tự động hóa |
| **UC_006** | Quản lý loại vé (Seat Zones) | Admin | `tests/Feature/Admin/AdminTicketTiersUseCaseTest.php`, `tests/Feature/Client/SeatZoneTest.php` | 19 | Đã tự động hóa |
| **UC_007** | Quản lý đơn hàng | Admin | `tests/Feature/Admin/AdminBookingUseCaseTest.php`, `AdminBookingManagementTest.php` | 16 | Đã tự động hóa |
| **UC_008** | Quản lý mã giảm giá | Admin | `tests/Feature/Admin/AdminDiscountUseCaseTest.php`, `AdminDiscountCrudTest.php` | 19 | Đã tự động hóa |
| **UC_009** | Quản lý đánh giá | Admin | `tests/Feature/Admin/AdminReviewUseCaseTest.php`, `tests/Feature/Client/CommentApiTest.php` | 19 | Đã tự động hóa |
| **UC_010** | Quản lý thông báo | Admin | `tests/Feature/Admin/AdminNotificationUseCaseTest.php` | 15 | Đã tự động hóa |
| **UC_011** | Thống kê Dashboard | Admin | `tests/Feature/Admin/AdminDashboardRealDataTest.php` | 3 | Đã tự động hóa |
| **UC_012** | Đăng ký tài khoản | Client | `tests/Feature/Client/UserRegistrationUseCaseTest.php`, `Auth/RegistrationTest.php`, `GoogleLoginTest.php` | 17 | Đã tự động hóa |
| **UC_013** | Tra cứu sự kiện | Client | `tests/Feature/Client/EventSearchUseCaseTest.php`, `SearchSuggestionTest.php`, `PublicCatalogTest.php` | 16 | Đã tự động hóa |
| **UC_014** | Xem chi tiết sự kiện | Client | `tests/Feature/Client/EventDetailUseCaseTest.php`, `PublicCatalogTest.php`, `EventVenueInfoTest.php` | 14 | Đã tự động hóa |
| **UC_015** | Chọn vé / Chọn chỗ | Client | `tests/Feature/Client/SeatSelectionUseCaseTest.php`, `SeatMapPageTest.php`, `SeatMapDataTest.php` | 13 | Đã tự động hóa |
| **UC_016** | Đặt vé & Giữ chỗ | Client | `tests/Feature/Client/SeatHoldBookingUseCaseTest.php`, `SeatHoldServiceTest.php`, `SeatHoldHttpTest.php` | 17 | Đã tự động hóa |
| **UC_017** | Lịch sử mua vé | Client | `tests/Feature/Client/BookingHistoryUseCaseTest.php`, `CartAndHistoryTest.php` | 11 | Đã tự động hóa |
| **UC_018** | Xem vé điện tử | Client | `tests/Feature/Client/TicketDetailsUseCaseTest.php`, `TicketPdfServiceTest.php`, `BookingConfirmationMailTest.php` | 12 | Đã tự động hóa |
| **UC_019** | Thanh toán online (Payment & Checkout) | Client | `tests/Feature/Client/CheckoutPaymentUseCaseTest.php`, `CheckoutTest.php` | 18 | Đã tự động hóa |
| **UC_020** | Quản lý hồ sơ cá nhân | Client | `tests/Feature/Client/UserProfileUseCaseTest.php`, `ProfileTest.php`, `ProfileAvatarTest.php` | 18 | Đã tự động hóa |
| **UC_021** | Đánh giá & Bình luận sự kiện | Client | `tests/Feature/Client/EventCommentReviewUseCaseTest.php`, `CommentApiTest.php` | 15 | Đã tự động hóa |
| **UC_022** | Lưu sự kiện yêu thích | Client | `tests/Feature/Client/EventFavoriteUseCaseTest.php` | 14 | Đã tự động hóa |
| **UC_023** | Chatbot AI hỗ trợ | Client | `tests/Feature/Client/AiChatbotAssistantUseCaseTest.php` | 16 | Đã tự động hóa |

---

## 2. CHI TIẾT CÁC TEST CASE CHO TỪNG USE CASE

---

### UC_001: Quản lý tài khoản
- **Files:** `tests/Feature/Admin/AdminUserManagementTest.php`, `tests/Feature/Admin/LockedUserTest.php`, `tests/Feature/Admin/AdminAccessTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC001_01` | Admin xem danh sách người dùng kèm phân trang | Positive | Trả về view `admin.users.index`, phân trang 10 user/trang, đủ thông tin tên, email, vai trò, trạng thái. |
| `TC_UC001_02` | Tìm kiếm người dùng theo từ khóa (tên, email, SĐT) | Positive | Trả về đúng các user có thông tin khớp từ khóa tìm kiếm. |
| `TC_UC001_03` | Lọc người dùng theo vai trò (`admin` / `user`) | Positive | Danh sách chỉ chứa các tài khoản mang vai trò được chọn. |
| `TC_UC001_04` | Lọc người dùng theo trạng thái (`active` / `locked`) | Positive | Danh sách lọc chính xác tài khoản đang hoạt động hoặc đã bị khóa. |
| `TC_UC001_05` | Admin tạo tài khoản mới với mật khẩu tự sinh | Positive | Tạo thành công tài khoản, mật khẩu tạm 12 ký tự ngẫu nhiên được mã hóa và hiển thị cho Admin. |
| `TC_UC001_06` | Tạo tài khoản với email trùng lặp | Negative/Val | Bị từ chối và hiển thị thông báo lỗi trùng email tiếng Việt. |
| `TC_UC001_07` | Admin khóa tài khoản người dùng qua AJAX | Security | Cập nhật `is_active = false`, trả về JSON thông báo thành công. |
| `TC_UC001_08` | Tài khoản bị khóa cố truy cập web request | Security | Bị Middleware `EnsureUserIsActive` đăng xuất ngay lập tức và chuyển hướng về trang Login. |
| `TC_UC001_09` | Tài khoản bị khóa gửi AJAX request | Security | Trả về mã lỗi HTTP 403 Forbidden. |
| `TC_UC001_10` | Admin tự khóa tài khoản của chính mình | Security/Self | Bị chặn và trả về lỗi 422: "Bạn không thể tự khóa tài khoản của chính mình". |
| `TC_UC001_11` | Admin nâng quyền User lên Admin | Positive | Cập nhật vai trò thành `admin`, user có toàn quyền truy cập `/admin/*`. |
| `TC_UC001_12` | Admin hạ quyền Admin khác xuống User | Positive | Gỡ quyền `admin`, gắn quyền `user`, tài khoản bị chặn khỏi `/admin/*`. |
| `TC_UC001_13` | Admin tự hạ quyền của chính mình | Security/Self | Bị từ chối với thông báo: "Bạn không thể tự thay đổi vai trò của chính mình". |
| `TC_UC001_14` | Admin đặt lại mật khẩu cho người dùng | Positive | Mật khẩu đổi về mặc định `TicketBox@2026`, user đăng nhập thành công với mật khẩu này. |
| `TC_UC001_15` | User thường cố truy cập route `/admin/users` | Security | Trả về HTTP 403 hoặc chuyển hướng về trang chủ. |

---

### UC_002: Quản lý danh mục
- **Files:** `tests/Feature/Admin/AdminCategoryCrudTest.php`, `tests/Feature/Admin/AdminRoleAndCrudVerificationTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC002_01` | Admin xem danh sách danh mục | Positive | Hiển thị bảng danh mục kèm thống kê số sự kiện liên kết (`withCount('events')`). |
| `TC_UC002_02` | Admin tạo danh mục mới với slug tự động | Positive | Tự động sinh slug không dấu từ tên (VD: "Đại Nhạc Hội" $\rightarrow$ `dai-nhac-hoi`). |
| `TC_UC002_03` | Admin tạo danh mục với custom slug | Positive | Lưu chính xác custom slug do admin nhập. |
| `TC_UC002_04` | Tạo danh mục có tên trùng lặp | Negative/Val | Bị Form Request chặn và báo lỗi tên danh mục đã tồn tại. |
| `TC_UC002_05` | Admin cập nhật tên và mô tả danh mục | Positive | Dữ liệu cập nhật thành công trong Database. |
| `TC_UC002_06` | Admin xóa danh mục rỗng (0 sự kiện) | Positive | Xóa thành công bản ghi khỏi Database. |
| `TC_UC002_07` | Admin xóa danh mục đang có sự kiện | Integrity | Bị từ chối xóa, thông báo lỗi: "Không thể xóa danh mục đang có sự kiện". |
| `TC_UC002_08` | Xóa hàng loạt danh mục hợp lệ | Positive | Xóa đồng thời các danh mục rỗng được chọn. |
| `TC_UC002_09` | Xóa hàng loạt danh mục có chứa danh mục có sự kiện | Integrity | Chỉ xóa các danh mục rỗng, bảo vệ nguyên vẹn các danh mục có sự kiện. |
| `TC_UC002_10` | Xóa hàng loạt khi danh sách ID gửi lên rỗng | Negative | Báo lỗi yêu cầu chọn ít nhất một danh mục. |
| `TC_UC002_11` | User thường gửi request xóa/sửa danh mục | Security | Trả về mã lỗi HTTP 403 Forbidden. |
| `TC_UC002_12` | Thêm danh mục thành công | Notification/Pos | Hiển thị Banner xanh và Toast góc phải: "Đã tạo danh mục [Tên]". |
| `TC_UC002_13` | Thêm danh mục thất bại (trùng tên/để trống) | Notification/Neg | Hiển thị Banner đỏ cảnh báo, Toast báo lỗi, giữ modal mở và báo lỗi đỏ dưới ô nhập. |
| `TC_UC002_14` | Sửa danh mục thành công | Notification/Pos | Hiển thị Banner xanh và Toast thông báo: "Đã cập nhật danh mục [Tên]". |
| `TC_UC002_15` | Sửa danh mục thất bại (trùng tên danh mục khác) | Notification/Neg | Hiển thị Banner đỏ, Toast báo lỗi và thông báo lỗi dưới ô nhập. |
| `TC_UC002_16` | Xóa danh mục thành công (0 sự kiện) | Notification/Pos | Hiển thị Banner xanh và Toast thông báo: "Đã xóa danh mục." |
| `TC_UC002_17` | Xóa danh mục thất bại (còn sự kiện liên kết) | Notification/Neg | Hiển thị Modal cảnh báo chặn xóa, Banner đỏ và Toast: "Danh mục [...] vẫn còn sự kiện, không thể xóa." |

---

### UC_003: Quản lý địa điểm
- **Files:** `tests/Feature/Admin/AdminRoomUseCaseTest.php`, `tests/Feature/Admin/AdminRoomBuilderTest.php`, `tests/Feature/Admin/AdminRoomSafetyTest.php`, `tests/Feature/Admin/AdminRoomCoordinatesTest.php`, `tests/Feature/Admin/AdminRoomLayoutValidationTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC003_001` | Hiển thị danh sách địa điểm tại /admin/rooms | Positive/UI | Hiển thị đầy đủ cột: Tên khán phòng, vị trí/tòa nhà, sức chứa tối đa, nhãn mẫu sơ đồ áp dụng và số suất diễn liên quan. |
| `TC_UC003_002` | Tạo mới địa điểm thành công với trường hợp lệ | Positive | Tạo thành công phòng, tự động sinh ma trận ghế và lưu bản ghi phòng vào DB. |
| `TC_UC003_003` | Tạo mới địa điểm với từng mẫu sơ đồ khởi tạo (Preset) | Positive | Khởi tạo thành công với 6 preset (`mega_concert`, `theater_hall`, `convention_center`, `banquet_hall`, `open_space`, `custom_grid`) cùng cấu hình ghế mặc định tương ứng. |
| `TC_UC003_004` | Cập nhật thông tin địa điểm khi chưa phát sinh vé bán | Positive | Đổi tên, vị trí, sức chứa thành công trong DB. |
| `TC_UC003_005` | Xóa địa điểm thành công khi chưa có suất diễn nào | Positive | Xóa thành công bản ghi phòng, các khu vực ghế (`seat_zones`) và toàn bộ ghế liên kết. |
| `TC_UC003_006` | Xóa địa điểm khi có suất diễn nhưng chưa bán vé nào | Positive | Cho phép xóa sạch phòng và các suất diễn rỗng (không có vé đã bán/giữ chỗ). |
| `TC_UC003_007` | Nhân bản địa điểm — kiểm tra hậu tố "(Bản sao)" | Positive | Tạo bản ghi Room mới có tên kèm hậu tố `(Bản Sao)`. |
| `TC_UC003_008` | Nhân bản địa điểm — sao chép toàn bộ cấu hình ghế | Positive | Sao chép đầy đủ zones, số ghế và tọa độ sang `room_id` mới mà không làm thay đổi dữ liệu phòng gốc. |
| `TC_UC003_009` | Nhân bản nhiều lần từ cùng một địa điểm gốc | Positive/Edge | Tự động sinh tên phân biệt `(Bản Sao)`, `(Bản Sao 2)`, `(Bản Sao 3)` không bị trùng lặp hay lỗi DB. |
| `TC_UC003_010` | Bỏ trống các trường bắt buộc khi tạo/sửa | Negative/Val | Bị chặn và báo lỗi validation các trường `name`, `address`, `capacity`, `layout_preset`, `layout`. |
| `TC_UC003_011` | Tên khán phòng vượt quá 255 ký tự hoặc chỉ chứa khoảng trắng | Negative/Val | Bị validation chặn, báo lỗi trường tên khán phòng. |
| `TC_UC003_012` | Giá trị Preset Layout không hợp lệ | Negative/Val | Bị validation chặn khi gửi preset không nằm trong danh mục cho phép. |
| `TC_UC003_013` | Kiểm tra chống lỗi XSS mã độc `<script>` | Security | Tên và địa chỉ chứa thẻ `<script>` được HTML-escape an toàn khi hiển thị ra giao diện web. |
| `TC_UC003_014` | Chặn xóa địa điểm khi đã có vé bán ra (confirmed) | Integrity | Bị từ chối xóa, trả về flash message lỗi và giữ nguyên bản ghi trong DB. |
| `TC_UC003_015` | Chặn xóa địa điểm khi đang có ghế giữ chỗ (held) | Integrity | Bị từ chối xóa để đảm bảo luồng thanh toán / đặt vé không bị đứt gãy. |
| `TC_UC003_016` | Khách vãng lai (Guest) truy cập các route quản lý phòng | Security | Tất cả request GET/POST/PUT/DELETE chuyển hướng về trang `/login`. |
| `TC_UC003_017` | Tài khoản User thường truy cập hoặc thao tác địa điểm | Security | Trả về mã lỗi HTTP 403 Forbidden cho mọi thao tác xem, tạo, sửa, nhân bản, xóa. |

---

### UC_004: Quản lý sự kiện
- **Files:** `tests/Feature/Admin/AdminEventUseCaseTest.php`, `tests/Feature/Admin/AdminEventCrudTest.php`, `tests/Feature/Services/GoogleDriveServiceTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC004_001` | Hiển thị danh sách sự kiện tại `/admin/events` | Positive/UI | Hiển thị đầy đủ thông tin: tên sự kiện, danh mục và các nhãn trạng thái (`draft`, `published`, `archived`). |
| `TC_UC004_002` | Lọc danh sách sự kiện theo danh mục | Positive | Trả về danh sách sự kiện chính xác theo `category_id` đã chọn, không hiển thị sự kiện danh mục khác. |
| `TC_UC004_003` | Lọc danh sách sự kiện theo trạng thái | Positive | Trả về danh sách sự kiện khớp trạng thái (`draft`, `published`, hoặc `archived`). |
| `TC_UC004_004` | Kết hợp đồng thời bộ lọc Danh mục và Trạng thái | Positive | Lọc đồng thời theo cả 2 tiêu chí, chỉ trả về các sự kiện thỏa mãn cả danh mục và trạng thái. |
| `TC_UC004_005` | Tạo mới sự kiện có chọn chỗ (`is_seated = true`) | Positive | Lưu bản ghi sự kiện thành công với cờ `is_seated = 1`. |
| `TC_UC004_006` | Tạo mới sự kiện không chọn chỗ (`is_seated = false`) | Positive | Lưu bản ghi sự kiện vé tự do/khu đứng với cờ `is_seated = 0`. |
| `TC_UC004_007` | Upload poster lưu trữ cục bộ (`public` storage) | Positive/Storage | File ảnh được lưu an toàn trong thư mục `posters/` trên disk `public` khi Google Drive tắt. |
| `TC_UC004_008` | Upload poster đẩy lên Google Drive | Positive/API | Gọi service đẩy ảnh lên Drive thành công và lưu URL Google Drive vào DB. |
| `TC_UC004_009` | Lưu trữ dữ liệu cấu trúc JSON (Lineup, Timeline) | Positive | Dữ liệu dàn nghệ sĩ Lineup và lịch trình Timeline dạng text được parse và lưu thành mảng JSON chuẩn trong DB. |
| `TC_UC004_010` | Cập nhật thông tin cơ bản và chi tiết JSON | Positive | Đổi tên sự kiện, thời lượng và dữ liệu lineup/timeline cập nhật chính xác vào DB. |
| `TC_UC004_011` | Chuyển trạng thái từ draft sang published | Lifecycle | Sự kiện chuyển sang mở bán công khai và ngay lập tức hiển thị trên trang danh mục `/events` của khách hàng. |
| `TC_UC004_012` | Cập nhật thay thế ảnh poster mới | Lifecycle/Storage | Ảnh poster mới được lưu vào storage và file poster cũ được dọn dẹp sạch sẽ khỏi hệ thống. |
| `TC_UC004_013` | Chuyển trạng thái sự kiện sang archived (Lưu trữ) | Lifecycle | Sự kiện ẩn khỏi danh mục mở bán công khai nhưng toàn bộ lịch sử đơn hàng và vé cũ vẫn toàn vẹn. |
| `TC_UC004_014` | Bỏ trống các trường bắt buộc khi tạo/sửa | Negative/Val | Bị validation chặn, báo lỗi các trường `title`, `category_id`, `duration_minutes`. |
| `TC_UC004_015` | Validation `category_id` không tồn tại | Negative/Val | Bị chặn khi gửi ID danh mục giả mạo không tồn tại trong bảng `categories`. |
| `TC_UC004_016` | Validation trường thời lượng (`duration_minutes`) | Negative/Val | Bị chặn khi nhập thời lượng là số âm (-10), bằng 0, hoặc chuỗi ký tự không phải số. |
| `TC_UC004_017` | Validation file poster sai định dạng | Negative/Val | Bị chặn khi upload file `.pdf`, `.exe`, `.svg` (chỉ chấp nhận `.jpg`, `.jpeg`, `.png`, `.webp`). |
| `TC_UC004_018` | Validation biên dung lượng poster (2048 KB vs 2049 KB) | Boundary | Thành công ở mốc tối đa 2048 KB (2MB) và bị từ chối khi vượt quá mốc (2049 KB). |
| `TC_UC004_019` | Chống tấn công XSS trong mô tả và lineup | Security | Nội dung chứa thẻ `<script>` độc hại được HTML-escape an toàn khi render ra giao diện web. |
| `TC_UC004_020` | Xóa sự kiện thành công khi chưa bán vé | Positive | Xóa sạch bản ghi sự kiện và file poster liên quan khỏi DB và storage. |
| `TC_UC004_021` | Chặn xóa sự kiện đã phát sinh vé bán thành công | Integrity | Hệ thống từ chối xóa, giữ nguyên dữ liệu và hiển thị thông báo gợi ý chuyển trạng thái sang `archived`. |
| `TC_UC004_022` | Chặn xóa sự kiện đang có ghế giữ chỗ (`held`) | Integrity | Hệ thống từ chối xóa để đảm bảo luồng giao dịch thanh toán của khách hàng không bị gián đoạn. |
| `TC_UC004_023` | Khách vãng lai truy cập route quản lý sự kiện | Security | Mọi request GET/POST/PUT/DELETE chuyển hướng về trang đăng nhập `/login`. |
| `TC_UC004_024` | User thường truy cập route quản lý sự kiện | Security | Trả về mã lỗi HTTP 403 Forbidden cho mọi hành động xem, tạo, sửa, xóa sự kiện. |

---

### UC_005: Quản lý sơ đồ ghế
- **Files:** `tests/Feature/Admin/AdminSeatMapUseCaseTest.php`, `tests/Feature/Admin/AdminRoomLayoutValidationTest.php`, `tests/Feature/System/LayoutTemplatesTest.php`, `tests/Feature/Admin/AdminRoomBuilderTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC005_001` | Truy cập giao diện dựng sơ đồ tại `/admin/rooms/{id}/builder` | Positive/UI | Truy cập thành công, hiển thị đầy đủ công cụ chọn mẫu bố trí, bản vẽ sơ đồ trực quan và thông tin phòng. |
| `TC_UC005_002` | Áp dụng từng mẫu trong 6 Layout Presets | Positive | Nạp chuẩn xác ma trận và danh sách khu vực mặc định theo `LayoutTemplates` cho cả 6 mẫu. |
| `TC_UC005_003` | Kiểm tra mẫu Banquet (Bàn tiệc gala) | Positive | Sinh đúng cấu trúc các bàn tiệc `kind = 'table'`, các bàn B01-B04 gồm 8 ghế và B05-B12 gồm 6 ghế mỗi bàn. |
| `TC_UC005_004` | Kiểm tra mẫu Open Space (Không gian mở) | Positive | Sinh đúng các khu vực đứng tự do (GA) với `pos_col = null`, không gán tọa độ ghế cứng. |
| `TC_UC005_005` | Lưu sơ đồ lưới tùy chỉnh (Custom Grid) | Positive | Lưu thành công sơ đồ kết hợp đa dạng các loại ô: Ghế ngồi (Seat), Bàn tiệc (Table) và Lối đi trống (Aisle). |
| `TC_UC005_006` | Sinh dữ liệu ghế chuẩn xác vào bảng `seats` | Positive | `RoomSeatGenerator` sinh chính xác mã hàng (`row_label`), số ghế (`seat_number`), cột (`pos_col`) và `zone_id`. |
| `TC_UC005_007` | Bỏ qua các ô Lối đi trống (Aisle) | Positive | Các ô `aisle = true` không sinh bản ghi ghế trong bảng `seats`, cột ghế được đánh số cách quãng chuẩn xác. |
| `TC_UC005_008` | Cập nhật lại sơ đồ khi chưa có suất diễn bán vé | Positive | Tái sinh toàn bộ ghế và khu vực đồng bộ theo cấu hình layout mới. |
| `TC_UC005_009` | Kiểm tra biên sức chứa: đúng 5.000 chỗ | Boundary | Lưu thành công sơ đồ đạt ngưỡng tối đa 5.000 chỗ (`total_seats == 5000`). |
| `TC_UC005_010` | Kiểm tra vượt biên sức chứa: 5.001 chỗ | Boundary | Bị chặn và trả về lỗi validation: tổng số chỗ phải từ 1 đến 5.000. |
| `TC_UC005_011` | Lưu sơ đồ thất bại khi ma trận có 0 ghế bán vé | Negative/Val | Bị chặn và báo lỗi khi sơ đồ chỉ toàn lối đi trống hoặc không có bất kỳ ghế/sức chứa nào. |
| `TC_UC005_012` | Gửi payload lưới bị lỗi định dạng | Negative/Val | Bị validation chặn khi nhãn hàng trống hoặc trỏ vào `zone_id` không tồn tại. |
| `TC_UC005_013` | Chặn thay đổi vị trí ghế khi đã có vé bán (`confirmed`) | Integrity | Hệ thống bảo toàn 100% bản ghi ghế cũ, chỉ cho phép sửa thông tin hiển thị và phụ thu của khu vực kèm cảnh báo `warning`. |
| `TC_UC005_014` | Chỉnh sửa sơ đồ khi phòng đã có suất diễn chưa bán vé | Integrity | Vị trí ghế cũ được giữ nguyên an toàn để bảo đảm tính nhất quán dữ liệu suất diễn. |
| `TC_UC005_015` | Chặn thay đổi cấu trúc ghế khi có ghế đang giữ chỗ (`held`) | Integrity | Toàn bộ bản ghi ghế được bảo vệ nguyên vẹn để không làm hỏng phiên thanh toán của khách. |
| `TC_UC005_016` | Khách vãng lai truy cập Room Builder hoặc lưu sơ đồ | Security | Mọi request GET builder hoặc PUT update chuyển hướng về `/login`. |
| `TC_UC005_017` | User thường truy cập Room Builder hoặc lưu sơ đồ | Security | Trả về mã lỗi HTTP 403 Forbidden. |

---

### UC_006: Quản lý loại vé (Seat Zones & Ticket Tiers)
- **Files:** `tests/Feature/Admin/AdminTicketTiersUseCaseTest.php`, `tests/Feature/Client/SeatZoneTest.php`, `tests/Feature/Client/SeatPricingTest.php`, `tests/Unit/TicketTiersTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC006_001` | Tạo mới khu vực ghế ngồi (`kind = 'seated'`) | Positive | Lưu thành công mã khu `SVIP`, màu HEX `#D4AF37`, cổng soát vé Gate và phụ thu `surcharge` vào DB. |
| `TC_UC006_002` | Tạo mới khu vực bàn tiệc tròn (`kind = 'table'`) | Positive | Lưu đúng thuộc tính `kind = 'table'`, hỗ trợ chọn bàn tiệc (`isPickable() = true`). |
| `TC_UC006_003` | Tạo mới khu vực đứng tự do (`kind = 'standing'`) | Positive | Lưu đúng thuộc tính `kind = 'standing'`, ghi nhận sức chứa khu đứng (`capacity = 500`). |
| `TC_UC006_004` | Tạo loại vé tiêu chuẩn có phụ thu bằng 0 (`surcharge = 0`) | Positive/Boundary | Hệ thống chấp nhận mức phụ thu 0đ hợp lệ. |
| `TC_UC006_005` | Cập nhật thông tin khu vực vé (tên, HEX, cổng, phụ thu) | Positive | Cập nhật thành công và đồng bộ dữ liệu khu vực trong database. |
| `TC_UC006_006` | Xóa khu vực vé khi chưa gán cho ghế nào và chưa bán vé | Positive | Xóa thành công bản ghi `seat_zones` mà không ảnh hưởng toàn vẹn dữ liệu. |
| `TC_UC006_007` | Tính giá vé tự động với khu vực có phụ thu dương | Positive/Logic | Suất diễn giá sàn 300.000đ + phụ thu 200.000đ $\rightarrow$ `effectivePrice()` = 500.000đ. |
| `TC_UC006_008` | Tính giá vé tự động với khu vực không phụ thu | Positive/Logic | Suất diễn giá sàn 300.000đ + phụ thu 0đ $\rightarrow$ `effectivePrice()` = 300.000đ. |
| `TC_UC006_009` | Tính giá vé nhiều khu vực khác nhau trong cùng suất diễn | Positive/Logic | Mỗi ghế trả về chính xác tổng giá sàn cộng phụ thu riêng của từng khu (SVIP: 600k, VIP: 450k, CAT1: 350k, GA: 300k). |
| `TC_UC006_010` | Bỏ trống các trường bắt buộc (`name`, `code`, `kind`) | Negative/Val | Bị validation chặn và trả về thông báo lỗi tương ứng. |
| `TC_UC006_011` | Validation trùng lặp mã khu (`code`) trong cùng địa điểm | Negative/Val | Chặn tạo 2 khu có cùng `code` trong cùng 1 phòng; cho phép trùng `code` ở các phòng khác nhau. |
| `TC_UC006_012` | Validation giá trị `kind` không hợp lệ | Negative/Val | Bị chặn khi gửi giá trị `kind` ngoài tập hợp cho phép (`seated`, `standing`, `table`). |
| `TC_UC006_013` | Validation định dạng mã màu HEX (`#RRGGBB`) | Negative/Val | Chấp nhận mã HEX 6 ký tự chuẩn (`#D4AF37`), từ chối mã không hợp lệ (`red`, `#GGGGGG`, XSS payload). |
| `TC_UC006_014` | Validation mức phụ thu không hợp lệ | Negative/Val | Chặn khi nhập phụ thu chuỗi chữ, vượt ngưỡng âm cho phép (< -1.000.000đ), hoặc vượt ngưỡng tối đa (> 10.000.000đ). |
| `TC_UC006_015` | Cổng soát vé (`gate`) cho phép để trống | Boundary | Cột `gate` là nullable, cho phép để trống (null) khi tạo khu vực vé. |
| `TC_UC006_016` | Bảo toàn cấu trúc `kind` và ghế khi phòng đã phát sinh vé bán | Integrity | Chặn thay đổi `kind` và không làm mất các ghế liên kết khi phòng đã bán vé; bảo toàn toàn vẹn dữ liệu. |
| `TC_UC006_017` | Thay đổi phụ thu không ảnh hưởng giá các đơn hàng đã thanh toán | Financial Integrity | Giá tiền trong `booking_items` đã mua được bảo toàn nguyên vẹn (`snapshot pricing`), không bị hồi tố sai lệch doanh thu. |
| `TC_UC006_018` | Khách vãng lai truy cập route quản lý sơ đồ/khu vực vé | Security | Chuyển hướng người dùng chưa đăng nhập về trang `/login`. |
| `TC_UC006_019` | User thường truy cập route quản lý sơ đồ/khu vực vé | Security | Trả về mã lỗi HTTP 403 Forbidden. |

---

### UC_007: Quản lý đơn hàng
- **Files:** `tests/Feature/Admin/AdminBookingUseCaseTest.php`, `tests/Feature/Admin/AdminBookingManagementTest.php`, `tests/Feature/Admin/AdminRoleAndCrudVerificationTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC007_001` | Hiển thị danh sách đơn đặt vé tại `/admin/bookings` | Positive/UI | Hiển thị đầy đủ thông tin: Mã đơn (`TBX-XXXXXXXX`), người mua, sự kiện, khán phòng, suất diễn, số lượng vé, tổng tiền, phương thức thanh toán và trạng thái (`pending`, `confirmed`, `cancelled`). |
| `TC_UC007_002` | Tìm kiếm và lọc danh sách đơn hàng | Positive | Lọc chuẩn xác theo mã đơn (`TBX-XXXXXXXX`), theo tên/email người mua, hoặc theo từng trạng thái (`pending`, `confirmed`, `cancelled`). |
| `TC_UC007_003` | Xem chi tiết đơn hàng (`/admin/bookings/{id}`) | Positive/UI | Hiển thị chi tiết khách hàng, sự kiện, thời gian suất diễn, danh sách vé (`booking_items`), vị trí từng ghế (hàng, số), hạng ghế, đơn giá, tổng tiền và phương thức thanh toán. |
| `TC_UC007_004` | Truy cập chi tiết đơn hàng với id không tồn tại | Negative | Trả về mã lỗi HTTP 404 Not Found. |
| `TC_UC007_005` | Xác nhận đơn hàng đang ở trạng thái `pending` | Positive/Lifecycle | Cột `status` trong bảng `bookings` cập nhật thành `confirmed`, hiển thị thông báo thành công. |
| `TC_UC007_006` | Kiểm tra trạng thái ghế khi xác nhận đơn `pending` | Integrity | Toàn bộ các ghế thuộc đơn hàng chuyển sang trạng thái khóa chính thức (`booked`), xóa bỏ thông tin giữ chỗ tạm thời (`held_by_user_id` và `held_until` về `null`). |
| `TC_UC007_007` | Xác nhận đơn hàng đã ở trạng thái `confirmed` (Idempotent) | Positive/Idempotency | Hệ thống không xử lý trùng lặp, an toàn bảo toàn trạng thái `confirmed` và trạng thái ghế `booked`. |
| `TC_UC007_008` | Hủy đơn hàng đang ở trạng thái `pending` | Positive/Lifecycle | Đơn hàng chuyển sang `cancelled`, toàn bộ ghế trong đơn lập tức được giải phóng về trạng thái trống (`available`). |
| `TC_UC007_009` | Hủy đơn hàng đang ở trạng thái `confirmed` | Positive/Lifecycle | Đơn hàng chuyển sang `cancelled`, toàn bộ ghế đã bán được giải phóng về `available` để mở bán lại cho khách khác. |
| `TC_UC007_010` | Đơn hàng nhiều vé ($\ge 2$ ghế) khi bị hủy | Integrity/Batch | Đảm bảo 100% các ghế thuộc đơn đều được giải phóng đồng loạt về `available` (không bị sót ghế nào). |
| `TC_UC007_011` | Đặt mua lại ghế vừa được giải phóng từ đơn bị hủy | Concurrency | Sau khi đơn bị hủy và ghế trả về `available`, người dùng khác có thể thực hiện giữ chỗ (`held`) và thanh toán đặt mua lại thành công. |
| `TC_UC007_012` | Chặn xác nhận lại đơn hàng đã hủy (`cancelled`) | Integrity/Workflow | Hệ thống từ chối chuyển trạng thái từ `cancelled` sang `confirmed`, giữ nguyên trạng thái `cancelled` an toàn. |
| `TC_UC007_013` | Chặn xác nhận lại đơn đã hủy khi ghế đã bán cho người khác | Concurrency/Anti-Double | Từ chối thao tác, báo lỗi chống bán trùng vé và giữ nguyên trạng thái `cancelled`. |
| `TC_UC007_014` | Chặn xác nhận lại đơn khi ghế đang được khách khác giữ chỗ | Concurrency/Anti-Conflict | Từ chối thao tác, báo lỗi xung đột giữ chỗ và bảo vệ toàn vẹn phiên giữ chỗ của khách hàng khác. |
| `TC_UC007_015` | Khách vãng lai truy cập route quản lý đơn hàng | Security | Mọi request GET danh sách, GET chi tiết, PATCH cập nhật trạng thái đều chuyển hướng về `/login`. |
| `TC_UC007_016` | User thường truy cập route quản lý đơn hàng | Security | Trả về mã lỗi HTTP 403 Forbidden cho mọi request xem hoặc thao tác đơn hàng. |

---

### UC_008: Quản lý mã giảm giá (Discounts & Vouchers)
- **Files:** `tests/Feature/Admin/AdminDiscountUseCaseTest.php`, `tests/Feature/Admin/AdminDiscountCrudTest.php`, `tests/Feature/Admin/AdminRoleAndCrudVerificationTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC008_001` | Hiển thị danh sách mã giảm giá tại `/admin/discounts` | Positive/UI | Hiển thị bảng danh sách voucher kèm đầy đủ cột: Mã voucher, kiểu giảm, giá trị giảm, mức giảm tối đa, đơn tối thiểu, hạn sử dụng, số lượt dùng tối đa và trạng thái kích hoạt. |
| `TC_UC008_002` | Tạo mã giảm giá theo phần trăm (%) thành công | Positive | Lưu bản ghi voucher thành công với đầy đủ các trường: `discount_type = 'percentage'`, `max_discount_amount`, `min_order_value`, `max_uses`, `start_date`, `end_date`. |
| `TC_UC008_003` | Tạo mã giảm giá theo số tiền cố định (VNĐ) | Positive | Tạo thành công voucher giảm tiền mặt cố định (VD: 50.000 VNĐ) vào database. |
| `TC_UC008_004` | Chuẩn hóa mã voucher đầu vào (chữ hoa & xóa khoảng trắng) | Positive/Sanitization | Mã nhập chữ thường hoặc có khoảng trắng (VD: `  summer2026  `) được tự động chuẩn hóa thành chữ in hoa `SUMMER2026` trước khi lưu vào DB. |
| `TC_UC008_005` | Tạo mã giảm giá không giới hạn điều kiện tùy chọn | Boundary | Chấp nhận để trống các điều kiện tùy chọn (đơn tối thiểu, mức giảm tối đa, lượt dùng) và nạp giá trị mặc định hợp lệ. |
| `TC_UC008_006` | Cập nhật thông tin mã giảm giá hiện có | Positive | Đổi giá trị giảm, gia hạn ngày hết hạn, tăng lượt dùng tối đa thành công mà không bị lỗi trùng mã code hiện tại. |
| `TC_UC008_007` | Toggle chuyển trạng thái từ Kích hoạt sang Tạm tắt | Positive/Lifecycle | Cột `is_active` đổi sang `false`, mã bị vô hiệu hóa ngay lập tức và không thể áp dụng khi tính tiền giỏ hàng. |
| `TC_UC008_008` | Toggle kích hoạt lại mã giảm giá đang tạm tắt | Positive/Lifecycle | Cột `is_active` đổi sang `true`, mã hợp lệ trở lại và có thể áp dụng thành công cho đơn hàng. |
| `TC_UC008_009` | Xóa mã giảm giá chưa từng sử dụng trong đơn hàng nào | Positive | Xóa sạch bản ghi voucher khỏi database mà không có bất kỳ xung đột nào. |
| `TC_UC008_010` | Xóa mã giảm giá đã có lịch sử áp dụng trên đơn hàng cũ | Financial Integrity | Xóa thành công bản ghi discount mà không làm mất dữ liệu đơn hàng hay gây lỗi khóa ngoại trên bảng `bookings`. |
| `TC_UC008_011` | Bỏ trống các trường bắt buộc (`code`, `discount_type`, `discount_value`) | Negative/Val | Bị Form Request / Controller validation chặn và báo lỗi tương ứng. |
| `TC_UC008_012` | Validation trùng lặp mã voucher (kể cả khác hoa/thường) | Negative/Val | Bị từ chối khi nhập mã code đã tồn tại trong DB (kể cả khi nhập `summer2026` đối chiếu với `SUMMER2026`). |
| `TC_UC008_013` | Validation biên giá trị giảm theo phần trăm (1% đến 100%) | Boundary | Chấp nhận các mốc biên hợp lệ 1% và 100%; từ chối các giá trị vượt biên (0%, -10%, 101%). |
| `TC_UC008_014` | Validation số tiền cố định, mức giảm tối đa và đơn tối thiểu | Negative/Val | Từ chối giá trị âm, bằng 0 (đối với số tiền giảm) hoặc chuỗi ký tự không phải số. |
| `TC_UC008_015` | Validation số lượt sử dụng tối đa (`max_uses`) | Negative/Val | Từ chối số âm, bằng 0 hoặc số thập phân (`10.5`). |
| `TC_UC008_016` | Validation ngày hết hạn (`end_date`) trong quá khứ | Negative/Val | Bị từ chối khi chọn ngày hết hạn nhỏ hơn ngày hiện tại lúc tạo mới voucher. |
| `TC_UC008_017` | Validation định dạng mã voucher (chống ký tự lạ & XSS) | Security/Val | Từ chối mã chứa dấu tiếng Việt (`GIẢM50%`), ký tự đặc biệt hoặc payload độc hại (`<script>`). |
| `TC_UC008_018` | Khách vãng lai truy cập route quản lý mã giảm giá | Security | Mọi request GET danh sách, POST tạo mới, PATCH toggle, DELETE xóa đều chuyển hướng về `/login`. |
| `TC_UC008_019` | User thường truy cập route quản lý mã giảm giá | Security | Trả về mã lỗi HTTP 403 Forbidden cho mọi request xem, tạo, cập nhật hoặc xóa mã. |

---

### UC_009: Quản lý đánh giá (Reviews & Ratings Management)
- **Files:** `tests/Feature/Admin/AdminReviewUseCaseTest.php`, `tests/Feature/Client/CommentApiTest.php`, `tests/Feature/Client/EventCommentsPageTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC009_001` | Hiển thị trang quản lý đánh giá tại `/admin/reviews` | Positive/UI | Hiển thị đầy đủ danh sách nhận xét, sự kiện liên quan, tác giả, số sao và 3 thẻ KPI: Tổng bình luận, Số lượt chấm sao, Điểm đánh giá trung bình toàn hệ thống. |
| `TC_UC009_002` | Tính toán điểm trung bình an toàn khi hệ thống có 0 đánh giá | Boundary | Không phát sinh lỗi chia cho 0 (`DivisionByZeroError`), hiển thị điểm trung bình mặc định `0.0 / 5`. |
| `TC_UC009_003` | Lọc danh sách đánh giá theo mức số sao (1 sao đến 5 sao) | Positive | Trả về chính xác các bình luận khớp đúng mức sao được chọn từ dropdown filter. |
| `TC_UC009_004` | Tìm kiếm đánh giá theo tên sự kiện | Positive | Lọc và trả về danh sách các bình luận thuộc sự kiện có tiêu đề khớp từ khóa tìm kiếm. |
| `TC_UC009_005` | Tìm kiếm đánh giá theo nội dung hoặc tên/email người đánh giá | Positive | Tìm kiếm linh hoạt theo nội dung bình luận, tên tác giả hoặc địa chỉ email tác giả. |
| `TC_UC009_006` | Kết hợp đồng thời bộ lọc số sao và từ khóa tìm kiếm | Positive | Lọc chính xác các đánh giá thỏa mãn cả điều kiện mức sao và từ khóa tìm kiếm. |
| `TC_UC009_007` | Admin gửi phản hồi chính thức cho đánh giá | Positive/Lifecycle | Bản ghi trong bảng `comments` lưu chính xác nội dung vào `admin_reply` và cập nhật `admin_replied_at`. |
| `TC_UC009_008` | Kích hoạt bắn thông báo (`CommentReplyNotification`) | Event/Notification | Hệ thống tự động gửi notification đến đúng tài khoản người dùng là tác giả của đánh giá được phản hồi. |
| `TC_UC009_009` | Kiểm tra cấu trúc payload của `CommentReplyNotification` | Integrity | Bản ghi trong bảng `notifications` chứa đầy đủ `event_id`, `event_slug`, trích dẫn nội dung phản hồi `reply`, và link điều hướng `url = /events/{slug}`. |
| `TC_UC009_010` | Admin cập nhật/chỉnh sửa nội dung phản hồi đã gửi | Positive | Ghi đè thành công nội dung phản hồi mới và làm mới timestamp `admin_replied_at`. |
| `TC_UC009_011` | Hiển thị phản hồi của Admin trên trang chi tiết sự kiện | Positive/UI | Khách hàng truy cập trang `/events/{slug}` nhìn thấy badge "Phản hồi từ TicketBox" và nội dung phản hồi của Admin. |
| `TC_UC009_012` | Admin xóa đánh giá vi phạm (spam/thô tục) | Positive/Moderation | Bản ghi bị xóa khỏi bảng `comments` trong DB và không còn hiển thị trên trang chi tiết sự kiện. |
| `TC_UC009_013` | Tự động tính toán lại điểm đánh giá trung bình sau khi xóa | Financial Integrity | Điểm trung bình của sự kiện và hệ thống lập tức cập nhật lại chính xác sau khi một đánh giá 1 sao hoặc 5 sao bị xóa. |
| `TC_UC009_014` | Gửi request phản hồi hoặc xóa với ID không tồn tại | Negative | Trả về mã lỗi HTTP 404 Not Found. |
| `TC_UC009_015` | Gửi phản hồi rỗng hoặc chỉ chứa khoảng trắng | Negative/Val | Bị validation chặn, không lưu DB và không kích hoạt bắn thông báo. |
| `TC_UC009_016` | Validation biên độ dài nội dung phản hồi (tối đa 1000 ký tự) | Boundary | Chấp nhận nội dung tối đa 1000 ký tự; từ chối khi vượt biên (1001 ký tự). |
| `TC_UC009_017` | Chống tấn công XSS trong nội dung bình luận và phản hồi | Security | Các đoạn mã độc `<script>alert(1)</script>` được escape an toàn khi render ra HTML, ngăn chặn thực thi mã script. |
| `TC_UC009_018` | Khách vãng lai truy cập route quản lý đánh giá | Security | Mọi request GET danh sách, PATCH phản hồi, DELETE xóa đều chuyển hướng về `/login`. |
| `TC_UC009_019` | User thường truy cập route quản lý hoặc mạo danh Admin | Security | Trả về mã lỗi HTTP 403 Forbidden khi user cố truy cập `/admin/reviews`, gửi phản hồi dưới danh nghĩa admin, hoặc xóa bình luận của người khác. |

---

### UC_010: Quản lý thông báo (System Notifications & Broadcasts)
- **Files:** `tests/Feature/Admin/AdminNotificationUseCaseTest.php`, `tests/Feature/Client/EventBookingFlowTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC010_001` | Hiển thị trang quản lý thông báo tại `/admin/notifications` | Positive/UI | Hiển thị bảng lịch sử các bản tin hệ thống đã phát hành gần đây kèm đầy đủ tiêu đề, nội dung tóm tắt và thời gian phát hành. |
| `TC_UC010_002` | Sắp xếp lịch sử thông báo theo thứ tự mới nhất | Positive | Bản tin mới phát hành nhất hiển thị ở đầu danh sách theo thứ tự giảm dần thời gian `created_at`. |
| `TC_UC010_003` | Hiển thị trạng thái rỗng khi chưa có thông báo nào | Boundary/UI | Hiển thị thông báo an toàn "Chưa gửi thông báo nào." khi bảng `notifications` trống. |
| `TC_UC010_004` | Gửi thông báo hợp lệ và báo cáo số người nhận | Positive/Broadcast | Kích hoạt gửi `SystemAnnouncement` qua `Notification::fake()` và phản hồi thông báo thành công ghi nhận đúng số tài khoản khách hàng đã nhận. |
| `TC_UC010_005` | Lưu dữ liệu thực tế vào bảng `notifications` trong DB | Integrity | Mỗi khách hàng nhận đúng 1 bản ghi với `type = SystemAnnouncement` và payload JSON chứa chính xác `title`, `message`, `kind = 'announcement'`, `url = '/'`. |
| `TC_UC010_006` | Loại trừ các tài khoản đã bị khóa (`is_active = false`) | Security/Filter | Chỉ gửi thông báo đến các tài khoản `is_active = true`; các tài khoản bị khóa không nhận được thông báo. |
| `TC_UC010_007` | Loại trừ các tài khoản Admin khỏi danh sách nhận | Filter | Chỉ gửi đến người dùng có vai trò `user`; tài khoản Admin không bị gửi trùng lặp thông báo của khách hàng. |
| `TC_UC010_008` | Xử lý theo đợt (`chunkById(200)`) với tập dữ liệu lớn (> 200 users) | Performance/Batch | Tạo 250 người dùng hoạt động; hệ thống chia batch xử lý thành công, không bỏ sót ai và báo cáo chính xác 250 người nhận. |
| `TC_UC010_009` | Xử lý an toàn khi hệ thống có 0 người dùng hoạt động | Boundary | Xử lý an toàn, không ném lỗi ngoại lệ, thông báo số người nhận bằng 0. |
| `TC_UC010_010` | Bỏ trống Tiêu đề hoặc Nội dung chi tiết | Negative/Val | Bị validation chặn và không phát tán bất kỳ thông báo nào. |
| `TC_UC010_011` | Tiêu đề hoặc Nội dung chỉ chứa toàn khoảng trắng (`"   "`) | Negative/Val | Bị tự động trim và validation chặn với lỗi `required`. |
| `TC_UC010_012` | Validation biên độ dài tiêu đề (tối đa 255 ký tự) | Boundary | Chấp nhận tiêu đề 255 ký tự; từ chối khi vượt biên (256 ký tự). |
| `TC_UC010_013` | Chống tấn công XSS trong tiêu đề và nội dung thông báo | Security | Các đoạn mã độc `<script>alert('XSS')</script>` được HTML-escape an toàn trên giao diện Admin và hộp chuông thông báo phía người dùng. |
| `TC_UC010_014` | Khách vãng lai truy cập route quản lý thông báo | Security | Mọi request GET danh sách hoặc POST gửi thông báo đều chuyển hướng về `/login`. |
| `TC_UC010_015` | User thường truy cập route quản lý hoặc cố gửi thông báo | Security | Trả về mã lỗi HTTP 403 Forbidden và ngăn chặn hoàn toàn việc phát tán thông báo trái phép. |

---

### UC_011: Thống kê báo cáo Dashboard
- **Files:** `tests/Feature/Admin/AdminDashboardRealDataTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC011_01` | Dashboard khi database chưa có đơn hàng nào | Positive/RealData | Hiển thị doanh thu 0 VNĐ (cam kết không dùng số liệu ảo). |
| `TC_UC011_02` | Dashboard khi có các đơn hàng đã xác nhận | Positive/RealData | Tính toán chính xác tổng doanh thu thực tế từ bảng `bookings`. |
| `TC_UC011_03` | Báo cáo chi tiết và xuất file PDF báo cáo | Positive | Route `/admin/reports` và `/admin/reports/pdf` biên dịch số liệu thật thành file PDF in ấn chuẩn. |

---

### UC_012: Đăng ký tài khoản
- **Files:** `tests/Feature/Client/UserRegistrationUseCaseTest.php`, `tests/Feature/Client/Auth/RegistrationTest.php`, `tests/Feature/Client/GoogleLoginTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC012_001` | Hiển thị trang đăng ký tại GET `/register` với đầy đủ các trường nhập liệu và nút đăng ký qua Google | Positive | Trả về HTTP 200, hiển thị form đăng ký đầy đủ các trường `name`, `email`, `phone`, `password`, `password_confirmation` và nút `Tiếp tục với Google`. |
| `TC_UC012_002` | Đăng ký tài khoản mới thành công qua POST `/register` với dữ liệu hợp lệ | Positive | Tạo bản ghi mới trong bảng `users` với `is_active = true`, mật khẩu được hash an toàn (Bcrypt/Argon2) không lưu plain-text. |
| `TC_UC012_003` | Tự động gán phân quyền Spatie sau đăng ký | Security | Tài khoản mới được tự động gán vai trò `user` (`$user->hasRole('user') === true`) và không có vai trò `admin`. |
| `TC_UC012_004` | Tự động đăng nhập và điều hướng sau khi đăng ký | Positive | Khởi tạo phiên đăng nhập ngay (`Auth::check() === true`), chuyển hướng về trang chủ (`route('home')`) kèm thông báo chào mừng trong flash session. |
| `TC_UC012_005` | Điều hướng sang màn hình xác thực Google khi truy cập route redirect | Positive/OAuth | Trả về redirect (HTTP 302) chuyển hướng sang URL đăng nhập của Google OAuth (`accounts.google.com`). |
| `TC_UC012_006` | Đăng ký nhanh qua Google OAuth thành công với email mới | Positive/OAuth | Tạo tài khoản mới lưu đầy đủ `google_id`, tên, `avatar`, `is_active = true`, gán vai trò `user` và tự động đăng nhập. |
| `TC_UC012_007` | Xử lý callback Google OAuth với email đã tồn tại trong DB | Positive/OAuth | Hệ thống liên kết `google_id` vào tài khoản hiện có mà không sinh bản ghi trùng email, tự động đăng nhập. |
| `TC_UC012_008` | Xử lý ngoại lệ khi người dùng hủy cấp quyền Google hoặc lỗi callback | Negative/OAuth | Bắt ngoại lệ an toàn, chuyển hướng về `/login` kèm thông báo lỗi tiếng Việt thân thiện, không ném exception 500. |
| `TC_UC012_009` | Đăng ký thất bại khi bỏ trống các trường bắt buộc | Negative/Val | Trả về lỗi validation session `name`, `email`, `password`, không tạo tài khoản và giữ trạng thái Guest. |
| `TC_UC012_010` | Validation định dạng email không đúng chuẩn | Negative/Val | Từ chối các chuỗi email sai chuẩn (`user@`, `user.com`, `user@.com`, `@domain.com`) và trả về lỗi trường `email`. |
| `TC_UC012_011` | Validation trùng lặp email không phân biệt chữ hoa/thường | Negative/Val | Từ chối khi email đã tồn tại trong DB (kể cả khác chữ hoa/thường như `User@Gmail.com` vs `user@gmail.com`). |
| `TC_UC012_012` | Validation định dạng số điện thoại di động Việt Nam | Validation | Chấp nhận số điện thoại hợp lệ 10 chữ số (đầu số 03, 05, 07, 08, 09); từ chối số sai đầu số, chứa chữ cái hoặc sai độ dài (9 hoặc 11 số). |
| `TC_UC012_013` | Kiểm tra giá trị biên độ dài mật khẩu (7 ký tự vs 8 ký tự) | Boundary | Thất bại khi mật khẩu có 7 ký tự (length < 8) và thành công ở mốc biên 8 ký tự (length == 8). |
| `TC_UC012_014` | Validation xác nhận mật khẩu không trùng khớp | Negative/Val | Trả về lỗi validation `password` khi `password_confirmation` khác với `password`. |
| `TC_UC012_015` | Kiểm tra chống mã độc XSS trong trường họ tên | Security | Chuỗi script `<script>alert('XSS')</script>` được escape/mã hóa HTML an toàn, không thực thi mã độc. |
| `TC_UC012_016` | Người dùng đã đăng nhập truy cập route đăng ký bị chặn bởi Guest Middleware | Security | Người dùng hoặc Admin đã đăng nhập khi truy cập GET/POST `/register` bị Middleware `guest` chặn và tự động chuyển hướng. |
| `TC_UC012_017` | Tài khoản đang bị khóa (`is_active = false`) cố gắng đăng nhập/đăng ký qua Google OAuth | Security | Hệ thống từ chối đăng nhập, chuyển hướng về `/login` kèm thông báo tài khoản đã bị khóa. |

---

### UC_013: Tra cứu sự kiện
- **Files:** `tests/Feature/Client/EventSearchUseCaseTest.php`, `tests/Feature/Client/SearchSuggestionTest.php`, `tests/Feature/Client/PublicCatalogTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC013_001` | Gửi request GET `/search/suggestions` với từ khóa hợp lệ khớp tên sự kiện | Positive/LiveSearch | Trả về HTTP 200, JSON chứa thông tin poster, ngày diễn, giá vé thấp nhất và đường dẫn chi tiết sự kiện. |
| `TC_UC013_002` | Giới hạn tối đa kết quả gợi ý Live Search | Boundary | Khi có nhiều hơn 6 sự kiện khớp từ khóa, API chỉ trả về tối đa 6 sự kiện (`count <= 6`). |
| `TC_UC013_003` | Lọc trạng thái hiển thị trên Live Search | Security/Filter | Các sự kiện ở trạng thái `draft` (bản nháp) hoặc `archived` (lưu trữ) tuyệt đối không xuất hiện trong gợi ý. |
| `TC_UC013_004` | Gửi từ khóa rỗng hoặc chỉ chứa khoảng trắng đến `/search/suggestions` | Edge/Validation | Trả về HTTP 200 kèm mảng rỗng `{"results": []}` an toàn, không lỗi server. |
| `TC_UC013_005` | Gửi từ khóa không khớp với bất kỳ sự kiện nào | Negative | Trả về HTTP 200 kèm mảng kết quả gợi ý rỗng. |
| `TC_UC013_006` | Truy cập trang `/events` hiển thị danh sách sự kiện mở bán (`published`) | Positive | Render giao diện catalog sự kiện thành công (HTTP 200), hiển thị đầy đủ tiêu đề các sự kiện đang mở bán. |
| `TC_UC013_007` | Phân trang danh sách sự kiện mặc định 9 sự kiện/trang | Pagination | Trang 1 trả về 9 sự kiện và trang 2 (`?page=2`) trả về các sự kiện còn lại. |
| `TC_UC013_008` | Đảm bảo tính toàn vẹn hiển thị công khai trên `/events` | Security | Các sự kiện ở trạng thái `draft` và `archived` tuyệt đối không hiển thị trên danh mục công khai. |
| `TC_UC013_009` | Tìm kiếm theo từ khóa khớp với tên sự kiện (`title`) | Search | Trả về đúng các sự kiện published có chứa từ khóa (không phân biệt chữ hoa/thường). |
| `TC_UC013_010` | Tìm kiếm theo từ khóa nằm trong mô tả sự kiện (`description`) | Search | Trả về đúng các sự kiện published khớp nội dung mô tả chi tiết chương trình. |
| `TC_UC013_011` | Lọc danh sách theo Danh mục sự kiện (`category_id` / `category`) | Filter | Chỉ trả về các sự kiện published thuộc đúng danh mục đã chọn. |
| `TC_UC013_012` | Kết hợp đồng thời cả từ khóa tìm kiếm và bộ lọc danh mục | Combination | Kết quả trả về thỏa mãn đồng thời cả hai điều kiện tìm kiếm và danh mục. |
| `TC_UC013_013` | Tìm kiếm / lọc với điều kiện không có kết quả phù hợp | Empty State | Trả về HTTP 200 và hiển thị thông báo trạng thái trống ("Không tìm thấy sự kiện phù hợp"). |
| `TC_UC013_014` | Chống tấn công SQL Injection trong từ khóa và tham số danh mục | Security/SQLi | Truyền chuỗi SQLi độc hại (`' OR 1=1 --`, `1; DROP TABLE events`) vào `q` hoặc `category_id` không gây lỗi SQL 500, truy vấn tham số hóa an toàn. |
| `TC_UC013_015` | Chống tấn công XSS (Cross-Site Scripting) trong ô tìm kiếm | Security/XSS | Chuỗi mã độc `<script>alert('XSS')</script>` được escape an toàn khi render lại trên giao diện hoặc JSON response. |
| `TC_UC013_016` | Quyền truy cập công khai không giới hạn tác nhân | Authorization | Cả Khách vãng lai (Guest), Thành viên (User) và Quản trị viên (Admin) đều tra cứu và xem danh mục bình thường. |

---

### UC_014: Xem chi tiết sự kiện
- **Files:** `tests/Feature/Client/EventDetailUseCaseTest.php` (**14 tests**)

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC014_001` | Truy cập trang chi tiết sự kiện `published` thành công | Positive | Trả về HTTP 200, hiển thị đầy đủ poster, tên sự kiện, thời lượng, danh mục, bảng giá tối thiểu và mô tả. |
| `TC_UC014_002` | Hiển thị dữ liệu chi tiết JSON Lineup và Timeline | Positive | Danh sách nghệ sĩ/diễn giả tham gia biểu diễn (Lineup) và lịch trình sự kiện (Timeline) render đầy đủ, đúng thứ tự. |
| `TC_UC014_003` | Hiển thị danh sách suất diễn (showtimes) kèm nút đặt vé | Positive | Từng suất diễn hiển thị đúng ngày giờ bắt đầu, địa điểm tổ chức (Room) và nút chọn vé liên kết `route('showtimes.seats', $id)`. |
| `TC_UC014_004` | Hiển thị khu vực Đánh giá & Bình luận kèm phản hồi Admin | Positive | Điểm sao trung bình tính chính xác, danh sách nhận xét hiển thị đúng kèm phản hồi ban quản trị (`admin_reply`). |
| `TC_UC014_005` | Nhúng iframe Google Maps địa điểm biểu diễn | Positive/Integration | Hiển thị iframe Google Maps với tọa độ (latitude, longitude) hoặc địa chỉ khán phòng/địa điểm. |
| `TC_UC014_006` | Tích hợp API thời tiết thực tế OpenWeatherMap | Positive/Integration | Hiển thị đầy đủ nhiệt độ thực tế (°C), mô tả thời tiết (nắng/mưa) và độ ẩm tại giờ diễn. |
| `TC_UC014_007` | Cơ chế bộ nhớ đệm (Cache) dự báo thời tiết 30 phút | Performance/Cache | Hai request liên tiếp trong vòng 30 phút chỉ gọi OpenWeatherMap API 1 lần (`Http::assertSentCount(1)`). |
| `TC_UC014_008` | Làm mới dự báo thời tiết khi Cache hết hạn sau 30 phút | Performance/Cache | Sau khi thời gian trôi qua hơn 30 phút, request tiếp theo gọi lại API thời tiết mới (`Http::assertSentCount(2)`). |
| `TC_UC014_009` | Xử lý ngoại lệ khi API thời tiết bị lỗi 500 hoặc Timeout | Resilience/Negative | Trang chi tiết vẫn trả về HTTP 200, hiển thị fallback an toàn ("Chưa có dự báo"), không gián đoạn người dùng. |
| `TC_UC014_010` | Truy cập sự kiện với slug không tồn tại trong hệ thống | Negative | Hệ thống xử lý an toàn và trả về mã lỗi HTTP 404 Not Found. |
| `TC_UC014_011` | Chặn truy cập sự kiện ở trạng thái `draft` hoặc `archived` | Security/Authorization | Khách vãng lai và Thành viên thường truy cập slug sự kiện nháp/lưu trữ đều nhận mã lỗi HTTP 404 Not Found. |
| `TC_UC014_012` | Render an toàn khi sự kiện chưa có suất diễn và JSON rỗng | Edge Case | Trang vẫn render HTTP 200 an toàn, hiển thị thông báo "Hiện chưa có suất diễn mở bán cho sự kiện này. Vui lòng quay lại sau!", không lỗi PHP key. |
| `TC_UC014_013` | Render an toàn khi sự kiện chưa có bình luận hay đánh giá | Edge Case | Điểm đánh giá hiển thị 0.0, 0 lượt đánh giá, hiển thị placeholder "Chưa có bình luận nào. Hãy là người đầu tiên!", không chia cho 0. |
| `TC_UC014_014` | Phân quyền truy cập công khai trang chi tiết sự kiện | Authorization | Khách vãng lai (Guest), Thành viên (User) và Quản trị viên (Admin) đều truy cập xem chi tiết bình thường. |

---

### UC_015: Chọn vé / Chọn chỗ
- **Files:** `tests/Feature/Client/SeatSelectionUseCaseTest.php` (**13 tests**), `tests/Feature/Client/SeatMapPageTest.php` (8 tests), `tests/Feature/Client/SeatMapDataTest.php` (3 tests)

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC015_001` | Thành viên đăng nhập truy cập trang chọn vé thành công | Positive | Trả về HTTP 200, tải đúng dữ liệu khán phòng (Room) và sự kiện (Event). |
| `TC_UC015_002` | Cấu trúc dữ liệu sơ đồ chọn chỗ (is_seated = true) | Positive | Dữ liệu sơ đồ trả về đầy đủ danh sách ghế, tọa độ (hàng, số ghế, cột), phân khu màu sắc và giá vé đã cộng phụ thu chính xác. |
| `TC_UC015_003` | Cấu trúc dữ liệu sự kiện khu đứng (is_seated = false / GA) | Positive | Trả về danh sách khu vực vé (seat_zones) kèm số lượng vé còn lại khả dụng chính xác cho từng khu. |
| `TC_UC015_004` | API kiểm tra trạng thái ghế (seatStatus) đa trạng thái | Positive/API | Trả về chính xác các nhóm: Ghế trống (available), Ghế đang giữ còn hạn (held) và Ghế đã bán (booked). |
| `TC_UC015_005` | Xử lý ghế giữ chỗ quá hạn (held nhưng held_until <= now()) | Positive/Logic | API `seatStatus` và Model nhận diện ghế đã hết hạn là khả dụng (available) để người khác chọn tiếp. |
| `TC_UC015_006` | Tính toán số lượng vé còn lại tại khu đứng (GA Standing) | Positive/Logic | Số vé khả dụng bằng tổng sức chứa trừ đi số vé đã bán (booked) và số vé đang bị giữ còn hiệu lực (held). |
| `TC_UC015_007` | Giới hạn chọn tối đa 8 ghế/lần đặt vé | Boundary/Validation | Chấp nhận payload hợp lệ từ 1 đến 8 ghế (HTTP 200) và từ chối/báo lỗi 422 khi gửi từ 9 ghế trở lên. |
| `TC_UC015_008` | Tính tổng tiền tạm tính khi chọn nhiều ghế các phân hạng khác nhau | Positive/Logic | Tính đúng tổng tiền tạm tính bằng tổng giá vé từng ghế theo phân khu tương ứng (VD: 2 VIP + 1 Standard). |
| `TC_UC015_009` | Kiểm tra giới hạn số lượng vé chọn ở khu đứng (GA Standing) | Validation/Business | Chặn số lượng âm (<= 0), không vượt quá số vé còn lại của khu và tuân thủ giới hạn tối đa 8 vé/lần đặt. |
| `TC_UC015_010` | Chặn khách vãng lai (Guest) chưa đăng nhập vào trang chọn vé | Security/Auth | Middleware auth chặn lại và tự động chuyển hướng sang trang đăng nhập `/login`. |
| `TC_UC015_011` | Truy cập trang chọn vé với ID suất diễn không tồn tại | Negative | Hệ thống trả về mã lỗi HTTP 404 Not Found. |
| `TC_UC015_012` | Chặn truy cập suất diễn thuộc sự kiện bản nháp hoặc lưu trữ | Security/Business | Suất diễn thuộc sự kiện `draft` hoặc `archived` bị chặn và trả về HTTP 404 Not Found. |
| `TC_UC015_013` | Chặn chọn vé đối với suất diễn đã kết thúc trong quá khứ | Business/Redirect | Suất diễn có `start_time < now()` bị chặn, tự động chuyển hướng về trang sự kiện kèm thông báo lỗi hết hạn mở bán. |

---

### UC_016: Đặt vé & Giữ chỗ
- **Files:** `tests/Feature/Client/SeatHoldBookingUseCaseTest.php` (**17 tests**), `tests/Feature/Client/SeatHoldServiceTest.php` (18 tests), `tests/Feature/Client/SeatHoldHttpTest.php` (9 tests)

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC016_001` | Giữ chỗ thành công sự kiện có chọn ghế (1 - 8 ghế) | Positive | Trả về JSON thành công kèm thời hạn giữ (`held_until`) và chuyển hướng `/cart`. |
| `TC_UC016_002` | Cập nhật dữ liệu DB sau khi giữ chỗ (SeatHoldService) | Positive/DB | Ghế chuyển trạng thái sang `held`, gán đúng `held_by_user_id = Auth::id()` và `held_until = now() + 10 phút`. |
| `TC_UC016_003` | Giữ vé thành công cho sự kiện khu đứng tự do (GA Standing) | Positive | Trừ tạm thời đúng số lượng vé khả dụng của khu vực tương ứng trên sơ đồ. |
| `TC_UC016_004` | Giữ lại ghế đã từng bị người khác giữ nhưng quá hạn (held_until < now()) | Positive/Logic | Cho phép ghi đè an toàn và gán suất giữ chỗ mới cho người dùng hiện tại. |
| `TC_UC016_005` | Truy cập `/cart` sau khi giữ chỗ thành công | Positive | Hiển thị đúng danh sách ghế đang giữ của user hiện tại, đơn giá từng ghế, tổng tiền tạm tính và thời gian đếm ngược. |
| `TC_UC016_006` | Xóa bớt một ghế khỏi giỏ vé (/cart) | Positive | Ghế bị xóa lập tức trở về trạng thái `available` (`held_by_user_id = null`, `held_until = null`) và tổng tiền giỏ vé được tính lại. |
| `TC_UC016_007` | Áp dụng mã giảm giá hợp lệ theo phần trăm (%) có giới hạn trần | Positive/Promo | Trừ chính xác mức chiết khấu phần trăm (không vượt mức trần tối đa) và tính đúng tổng thanh toán. |
| `TC_UC016_008` | Áp dụng mã giảm giá hợp lệ theo số tiền cố định (VNĐ) | Positive/Promo | Trừ chính xác số tiền khuyến mãi vào tổng giỏ vé (`subtotal - fixed_amount`). |
| `TC_UC016_009` | Từ chối áp dụng mã giảm giá không hợp lệ | Validation/Promo | Chặn và báo lỗi 422: mã không tồn tại, mã tắt (`is_active = false`), mã hết hạn, mã hết lượt dùng, hoặc đơn chưa đạt tối thiểu. |
| `TC_UC016_010` | Xử lý xung đột giữ chỗ (Race Condition với ghế held còn hạn) | Concurrency | Hệ thống hủy giao dịch, báo lỗi chỉ rõ tên ghế bị chiếm và không giữ dở dang các ghế còn lại (Rollback). |
| `TC_UC016_011` | Xử lý xung đột với ghế đã bán (booked) | Integrity/Transaction | Giao dịch giữ ghế bị rollback toàn bộ qua Database Transaction & `lockForUpdate()`, không giữ ghế dở dang. |
| `TC_UC016_012` | Lệnh Artisan tự động nhả chỗ hết hạn (`php artisan seats:release-holds`) | Automation/Scheduler | Sau 11 phút, các ghế quá hạn tự động chuyển về `available`, trong khi các ghế còn hạn vẫn giữ nguyên. |
| `TC_UC016_013` | Kiểm tra giới hạn biên: payload rỗng hoặc vượt quá 8 ghế (gửi 9 ghế) | Boundary/Validation | Hệ thống từ chối với lỗi validation HTTP 422. |
| `TC_UC016_014` | Chặn tấn công IDOR / Payload Tampering mã ghế của suất diễn khác | Security/IDOR | Hệ thống phát hiện ghế không thuộc suất diễn đang thao tác và từ chối xử lý (422). |
| `TC_UC016_015` | Chặn giữ chỗ cho suất diễn đã kết thúc hoặc sự kiện chưa mở bán | Business/Security | Từ chối giữ chỗ đối với suất diễn có `start_time < now()` hoặc sự kiện `draft`/`archived`. |
| `TC_UC016_016` | Chặn khách vãng lai (Guest chưa đăng nhập) thực hiện thao tác giỏ vé | Security/Auth | Chặn giữ chỗ (401), chặn xem giỏ vé (redirect `/login`) và chặn áp dụng mã giảm giá (401). |
| `TC_UC016_017` | Đảm bảo tính cô lập giỏ vé tuyệt đối giữa các người dùng | Security/Isolation | User A chỉ xem và thao tác được ghế của mình; cố xóa ghế của User B bị chặn 422 và dữ liệu User B không bị ảnh hưởng. |

---

### UC_017: Lịch sử mua vé
- **Files:** `tests/Feature/Client/BookingHistoryUseCaseTest.php` (**11 tests**), `tests/Feature/Client/CartAndHistoryTest.php` (3 tests), `tests/Feature/Client/UserRoleAndFeaturesVerificationTest.php` (1 test)

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC017_001` | Thành viên truy cập `/bookings/history` xem đầy đủ chi tiết đơn vé | Positive | Trả về HTTP 200, hiển thị mã đơn (TBX-...), tên sự kiện, poster, suất diễn, địa điểm, ghế, tổng tiền và phương thức thanh toán. |
| `TC_UC017_002` | Hiển thị chính xác 3 trạng thái đơn hàng | Positive | Giao diện hiển thị rõ ràng 3 trạng thái: "Đã Xác Nhận", "Chờ Thanh Toán" và "Đã Hủy" tương ứng từng đơn hàng. |
| `TC_UC017_003` | Nút "Xem vé điện tử" hiển thị và hoạt động với đơn `confirmed` | Positive | Nút xuất hiện và liên kết tới `/bookings/{id}/ticket` (HTTP 200, hiển thị vé điện tử kèm mã QR check-in). |
| `TC_UC017_004` | Ẩn hoặc vô hiệu hóa nút "Xem vé điện tử" với đơn `pending` hoặc `cancelled` | Security/Business | Không hiển thị mã QR và nút xem vé điện tử đối với đơn chưa thanh toán hoặc đã hủy; chặn truy cập trực tiếp route ticket (404). |
| `TC_UC017_005` | Thứ tự sắp xếp đơn mới nhất ở đầu và phân trang | Positive/Paging | Sắp xếp theo thứ tự mới nhất trước (`latest('id')`) và cơ chế phân trang hoạt động chính xác khi có nhiều đơn hàng. |
| `TC_UC017_006` | Trạng thái rỗng an toàn khi tài khoản chưa từng đặt vé | Edge Case | Hiển thị giao diện Empty State an toàn: "Bạn chưa có đơn đặt vé nào", không văng lỗi null pointer. |
| `TC_UC017_007` | Lịch sử mua vé lưu giữ nguyên vẹn với sự kiện `archived` hoặc đã qua | Business/History | Đơn hàng thuộc sự kiện đã lưu trữ hoặc diễn ra trong quá khứ vẫn hiển thị đầy đủ thông tin sự kiện, suất diễn và vé. |
| `TC_UC017_008` | Đơn hàng gồm nhiều vé (booking_items ≥ 2 ghế hoặc GA) | Positive | Liệt kê đầy đủ danh sách mã ghế/loại vé và tổng tiền thanh toán khớp chính xác. |
| `TC_UC017_009` | Tính cô lập dữ liệu tuyệt đối giữa các người dùng | Security/Isolation | User A chỉ xem được đơn hàng của mình, không thấy đơn của User B; User A truy cập vé của User B bị chặn 403 Forbidden. |
| `TC_UC017_010` | Khách vãng lai (Guest chưa đăng nhập) truy cập `/bookings/history` | Security/Auth | Middleware auth chặn lại và tự động chuyển hướng sang trang đăng nhập `/login`. |
| `TC_UC017_011` | Tài khoản bị Admin khóa (`is_active = false`) truy cập lịch sử vé | Security/Active | Middleware `EnsureUserIsActive` chặn lại, tự động đăng xuất và điều hướng về `/login`. |

---

### UC_018: Xem vé điện tử (E-Ticket)
- **Files:** `tests/Feature/Client/TicketDetailsUseCaseTest.php` (**12 tests**), `tests/Feature/Client/TicketPdfServiceTest.php` (1 test), `tests/Feature/Client/BookingConfirmationMailTest.php` (1 test), `tests/Feature/Services/GmailApiTransportTest.php` (2 tests)

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC018_001` | Chủ sở hữu đơn `confirmed` truy cập `GET /bookings/{id}/ticket` | Positive | Trả về HTTP 200, hiển thị đầy đủ sự kiện, thời gian, cửa vào (Gate), khu vực (Zone) và vị trí ghế ngồi cụ thể. |
| `TC_UC018_002` | Hiển thị đơn hàng gồm nhiều vé (booking_items ≥ 2 ghế hoặc vé GA) | Positive/Multi | Giao diện render đầy đủ thông tin chi tiết và mã định danh riêng biệt cho từng vé (`TBX-...-01`, `TBX-...-02`). |
| `TC_UC018_003` | Sinh mã QR Code bảo mật phục vụ quét check-in | Positive/Security | Từng vé render thẻ hình ảnh/SVG mã QR chứa chuỗi xác thực duy nhất tại cổng soát vé. |
| `TC_UC018_004` | Tải file PDF vé điện tử qua `GET /bookings/{id}/ticket/pdf` | Positive/PDF | Trả về HTTP 200, Content-Type: `application/pdf`, tên file tải về hợp lệ và nội dung PDF chuẩn. |
| `TC_UC018_005` | Gửi vé điện tử qua Gmail (`Mail::fake()`) | Positive/Mail | Hệ thống đẩy email xác nhận vào hàng đợi đến đúng địa chỉ email của người mua kèm file vé đính kèm. |
| `TC_UC018_006` | Đồng bộ bản sao vé điện tử lên Google Drive cá nhân | Positive/Drive | Service tải file vé PDF lên Google Drive thành công và trả về đường dẫn xem file trên Drive. |
| `TC_UC018_007` | Xử lý ngoại lệ an toàn khi Google Drive hoặc SMTP gặp lỗi/timeout | Resilience | Bắt ngoại lệ an toàn, trả về lỗi HTTP 500 kèm thông báo thân thiện và không gây crash trang xem vé chính. |
| `TC_UC018_008` | Chặn xem vé và tải PDF đối với đơn hàng đang chờ thanh toán (`pending`) | Security/Business | Hệ thống từ chối cấp vé điện tử và trả về HTTP 403 Forbidden. |
| `TC_UC018_009` | Chặn xem vé và tải PDF đối với đơn hàng đã bị hủy (`cancelled`) | Security/Business | Vé điện tử bị vô hiệu hóa, không xuất trình mã QR và từ chối tải PDF (HTTP 403 Forbidden). |
| `TC_UC018_010` | Chống lỗ hổng IDOR xem hoặc tải vé của tài khoản khác | Security/IDOR | User A cố tình truy cập hoặc tải vé của User B bị hệ thống chặn với mã lỗi HTTP 403 Forbidden. |
| `TC_UC018_011` | Khách vãng lai (Guest chưa đăng nhập) truy cập vé điện tử | Security/Auth | Bị Middleware auth chặn lại và tự động chuyển hướng sang trang đăng nhập `/login`. |
| `TC_UC018_012` | Truy cập vé điện tử với ID đơn hàng không tồn tại | Negative | Hệ thống xử lý an toàn và trả về mã lỗi HTTP 404 Not Found. |

---

### UC_019: Thanh toán online (Online Payment & Checkout)
- **Files:** `tests/Feature/Client/CheckoutPaymentUseCaseTest.php`, `tests/Feature/Client/CheckoutTest.php`, `tests/Feature/Client/EventBookingFlowTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC019_001` | Thanh toán thành công với phương thức VietQR (mã QR chuẩn NAPAS 24/7) | Positive | Tạo bản ghi đơn hàng mới trong bảng `bookings` với trạng thái `confirmed`, sinh mã `booking_code` chuẩn, lưu thông tin người nhận vé và chuyển hướng sang trang Vé điện tử (`/bookings/{id}/ticket`). |
| `TC_UC019_002` | Thanh toán thành công với phương thức Ví điện tử MoMo | Positive | Ghi nhận chính xác `payment_method = 'momo'`, trạng thái `confirmed` và hoàn tất giao dịch. |
| `TC_UC019_003` | Thanh toán thành công với phương thức Ví điện tử ZaloPay | Positive | Ghi nhận chính xác `payment_method = 'zalopay'`, trạng thái `confirmed` và hoàn tất giao dịch. |
| `TC_UC019_004` | Thanh toán thành công với phương thức Thẻ ngân hàng nội địa / Quốc tế (`card`) | Positive | Ghi nhận chính xác `payment_method = 'card'`, trạng thái `confirmed` và hoàn tất giao dịch. |
| `TC_UC019_005` | Cập nhật trạng thái ghế và chi tiết đơn hàng (`CheckoutService`) | Positive/State | Toàn bộ ghế đang giữ (`held`) chuyển sang `booked`, giải phóng giữ chỗ tạm (`held_until = null`, `held_by_user_id = null`), sinh đầy đủ bản ghi trong `booking_items` với đúng giá vé. |
| `TC_UC019_006` | Gửi email xác nhận kèm vé điện tử (`Mail::fake()`) | Positive/Mail | Gửi email xác nhận đặt vé thành công đến đúng địa chỉ email người nhận vé (`BookingConfirmationMail`). |
| `TC_UC019_007` | Thanh toán giỏ vé không áp dụng mã giảm giá | Positive/Calculation | `total_price` lưu trong bảng `bookings` khớp chính xác 100% với tổng giá trị các ghế, `discount_id = null`, `discount_amount = 0`. |
| `TC_UC019_008` | Thanh toán giỏ vé có áp dụng mã giảm giá (voucher % có trần hoặc số tiền cố định) | Positive/Promotion | Tính đúng số tiền thực thu cuối cùng sau giảm giá, liên kết mã voucher vào đơn hàng và tự động tăng `used_count` của mã giảm giá thêm 1. |
| `TC_UC019_009` | Thanh toán thất bại khi thời gian giữ chỗ 10 phút đã hết hạn (`held_until < now()`) | Negative/Timeout | Tua thời gian 11 phút (`travel(11)->minutes()`), hệ thống chặn thanh toán, hủy giao dịch an toàn (HTTP 422), không tạo đơn hàng rác trong DB. |
| `TC_UC019_010` | Giao dịch bị gián đoạn giữa chừng (1 ghế còn hạn, 1 ghế đã hết hạn hoặc bị giải phóng) | Security/Atomicity | `DB::transaction` tự động rollback toàn bộ, không tạo đơn hàng bị thiếu vé, giữ nguyên trạng thái an toàn cho các ghế còn lại. |
| `TC_UC019_011` | Chống gửi trùng lặp giao dịch (Double Submit / Idempotency) | Security/Idempotency | Gửi 2 request thanh toán liên tiếp cho cùng một giỏ vé, chỉ tạo duy nhất 1 đơn hàng `confirmed`, request thứ hai bị từ chối do giỏ vé đã xử lý. |
| `TC_UC019_012` | Gửi thanh toán khi giỏ vé đang trống | Negative/Boundary | Hệ thống từ chối thanh toán với mã lỗi HTTP 422 và thông báo giỏ vé trống/hết hạn. |
| `TC_UC019_013` | Validation thông tin người nhận vé (bỏ trống Họ tên, Email hoặc Số điện thoại) | Negative/Val | Trả về HTTP 422 với các thông báo lỗi trường bắt buộc tiếng Việt (`delivery_name`, `delivery_email`, `delivery_phone`). |
| `TC_UC019_014` | Validation định dạng Email và Số điện thoại người nhận vé | Negative/Val | Từ chối email sai chuẩn RFC hoặc số điện thoại không đúng định dạng mạng viễn thông Việt Nam (HTTP 422). |
| `TC_UC019_015` | Validation phương thức thanh toán (bỏ trống hoặc gửi giá trị không hỗ trợ) | Negative/Val | Từ chối các phương thức thanh toán không nằm trong danh mục hỗ trợ (HTTP 422). |
| `TC_UC019_016` | Khách vãng lai (Guest chưa đăng nhập) gửi yêu cầu thanh toán | Security/Auth | Bị Middleware auth chặn lại và trả về HTTP 401 Unauthorized (hoặc chuyển hướng sang `/login`). |
| `TC_UC019_017` | Tài khoản đã bị khóa (`is_active = false`) cố gắng thanh toán | Security/Lock | Bị Middleware `EnsureUserIsActive` chặn lại (HTTP 403 Forbidden) và hủy phiên đăng nhập ngay lập tức. |
| `TC_UC019_018` | Đảm bảo tính cô lập giỏ vé khi thanh toán (Cart Isolation / Anti-Stealing) | Security/Isolation | User A thanh toán chỉ xử lý các ghế do chính User A giữ trong giỏ, tuyệt đối không chạm tới hoặc thanh toán ghế của User B. |

---

### UC_020: Quản lý hồ sơ cá nhân (User Profile Management)
- **Files:** `tests/Feature/Client/UserProfileUseCaseTest.php`, `tests/Feature/Client/ProfileTest.php`, `tests/Feature/Client/ProfileAvatarTest.php`, `tests/Feature/Client/Auth/PasswordUpdateTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC020_001` | Truy cập GET `/profile` với tài khoản thành viên đã đăng nhập | Positive/UI | Trả về HTTP 200, hiển thị chính xác họ tên, email, số điện thoại và đường dẫn ảnh đại diện của người dùng. |
| `TC_UC020_002` | Cập nhật họ tên và số điện thoại hợp lệ thành công | Positive | Lưu dữ liệu mới vào DB, chuyển hướng về `profile.edit` và hiển thị flash message `profile-updated`. |
| `TC_UC020_003` | Validation thông tin cơ bản (bỏ trống tên, tên > 255 ký tự, SĐT sai định dạng VN) | Negative/Val | Bị validation chặn lại (HTTP 302 về trang kèm error bag), từ chối SĐT chứa chữ, thiếu số hoặc thừa số. |
| `TC_UC020_004` | Chống tấn công XSS khi nhập chuỗi mã độc `<script>` vào trường họ tên | Security/XSS | Dữ liệu được HTML entity escape an toàn khi render trên view hồ sơ và header navigation bar. |
| `TC_UC020_005` | Upload ảnh đại diện hợp lệ định dạng `.jpg` hoặc `.png` (`Storage::fake('public')`) | Positive/File | Lưu file ảnh vào thư mục disk `avatars`, cập nhật cột `avatar` trong bảng `users` và render public URL. |
| `TC_UC020_006` | Cập nhật thay thế ảnh đại diện mới khi người dùng đã có ảnh cũ | Positive/Cleanup | Lưu thành công ảnh mới và dọn dẹp xóa bỏ file ảnh cũ khỏi bộ nhớ disk để tránh lãng phí dung lượng. |
| `TC_UC020_007` | Validation biên dung lượng file ảnh đại diện (1.024 KB vs 1.025 KB) | Boundary/File | Thành công ở mốc biên đúng 1.024 KB (1MB) và thất bại có lỗi validation khi file vượt ngưỡng 1.025 KB (> 1MB). |
| `TC_UC020_008` | Validation định dạng file ảnh đại diện (từ chối `.pdf`, `.exe`, `.svg`, `.txt`) | Security/File | Hệ thống từ chối mọi file không phải định dạng hình ảnh cho phép (`jpg, jpeg, png, webp`). |
| `TC_UC020_009` | Gửi request upload ảnh đại diện nhưng không đính kèm file (`avatar = null`) | Negative/Val | Hệ thống chặn và trả về lỗi validation bắt buộc chọn tệp tin hình ảnh. |
| `TC_UC020_010` | Đổi mật khẩu thành công khi nhập đúng mật khẩu hiện tại và mật khẩu mới hợp lệ | Positive/Security | Cập nhật mật khẩu mới mã hóa Bcrypt, `Hash::check()` trả về `true` với mật khẩu mới và `false` với mật khẩu cũ. |
| `TC_UC020_011` | Đổi mật khẩu thất bại khi nhập sai mật khẩu hiện tại | Security | Báo lỗi validation tại trường `current_password`, không thay đổi mật khẩu cũ trong DB. |
| `TC_UC020_012` | Validation biên độ dài mật khẩu mới (7 ký tự vs 8 ký tự) | Boundary/Security | Thất bại khi mật khẩu mới có 7 ký tự (`length < 8`) và thành công ở mốc biên 8 ký tự (`length == 8`). |
| `TC_UC020_013` | Validation xác nhận mật khẩu mới (không trùng khớp hoặc bỏ trống) | Negative/Val | Hệ thống bắt lỗi trường xác nhận mật khẩu và không cho phép lưu mật khẩu rỗng. |
| `TC_UC020_014` | Xóa tài khoản thành công khi nhập đúng mật khẩu xác nhận | Positive/Lifecycle | Hủy phiên đăng nhập (`Auth::check() === false`), chuyển hướng về trang chủ và xóa vĩnh viễn user khỏi bảng `users`. |
| `TC_UC020_015` | Xóa tài khoản thất bại khi nhập sai mật khẩu xác nhận hoặc bỏ trống | Security | Báo lỗi validation `userDeletion.password`, giữ nguyên phiên đăng nhập và không xóa dữ liệu người dùng. |
| `TC_UC020_016` | Toàn vẹn dữ liệu liên quan (`bookings`, `comments`) khi người dùng xóa tài khoản | Integrity/FK | Xử lý khóa ngoại cascade an toàn theo DB schema, xóa sạch bản ghi liên kết mà không gây lỗi SQL 500. |
| `TC_UC020_017` | Khách vãng lai (Guest chưa đăng nhập) truy cập hoặc thao tác trên `/profile` | Security/Auth | Bị Middleware `auth` chặn lại và tự động chuyển hướng về trang đăng nhập `/login`. |
| `TC_UC020_018` | Tài khoản đã bị khóa (`is_active = false`) cố gắng thao tác trên `/profile` | Security/Lock | Bị Middleware `EnsureUserIsActive` chặn lại, tự động đăng xuất và chuyển hướng về `/login`. |

---

### UC_021: Đánh giá & Bình luận sự kiện (Event Comments & Reviews)
- **Files:** `tests/Feature/Client/EventCommentReviewUseCaseTest.php`, `tests/Feature/Client/CommentApiTest.php`, `tests/Feature/Client/EventCommentsPageTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC021_001` | Thành viên đã đăng nhập gửi nhận xét hợp lệ kèm số sao (1-5★) và nội dung (3-1.000 ký tự) qua AJAX | Positive/AJAX | Trả về HTTP 201 Created JSON chứa thông tin bình luận, user, số sao và lưu bản ghi vào bảng `comments` đúng `user_id`, `event_id`. |
| `TC_UC021_002` | Gửi nhận xét hợp lệ với các mức sao biên: mức thấp nhất (1 sao) và cao nhất (5 sao) | Boundary/Data | Giá trị số sao lưu chính xác vào DB và cập nhật lại điểm đánh giá trung bình `rating_average`, `rating_count` của sự kiện. |
| `TC_UC021_003` | Thứ tự hiển thị danh sách bình luận trên trang chi tiết sự kiện (`/events/{slug}`) | Positive/Order | Bình luận mới gửi được sắp xếp lên đầu danh sách theo thứ tự thời gian mới nhất trước (`latest` order). |
| `TC_UC021_004` | Hiển thị phản hồi từ Ban tổ chức (Admin reply) lồng dưới bình luận của khách hàng | Positive/UI | Khi bình luận có `admin_reply`, nội dung phản hồi chính thức của BTC được render chuẩn xác lồng dưới nhận xét của người dùng. |
| `TC_UC021_005` | Tác giả của bình luận gửi request xóa bình luận của chính mình | Positive/Auth | `CommentPolicy` cho phép thực thi, xóa vĩnh viễn bình luận khỏi DB và tự động tính lại điểm đánh giá trung bình của sự kiện. |
| `TC_UC021_006` | Chống xóa bình luận của người khác (User A cố xóa bình luận của User B) | Security/Policy | Bị `CommentPolicy` chặn với mã lỗi HTTP 403 Forbidden và giữ nguyên bản ghi trong database. |
| `TC_UC021_007` | Gửi request xóa bình luận với ID không tồn tại trong hệ thống | Negative | Hệ thống xử lý an toàn và trả về mã lỗi HTTP 404 Not Found. |
| `TC_UC021_008` | Gửi đánh giá thất bại khi bỏ trống nội dung, chỉ nhập khoảng trắng ("   "), hoặc bỏ trống số sao | Negative/Val | Trả về HTTP 422 Unprocessable Entity kèm thông báo lỗi validation tiếng Việt tương ứng cho `content` hoặc `rating`. |
| `TC_UC021_009` | Validation biên độ dài tối thiểu của nội dung bình luận (2 ký tự vs 3 ký tự) | Boundary/Val | Thất bại khi nhập 2 ký tự (`length < 3`) và thành công tạo bình luận ở mốc biên 3 ký tự (`length == 3`). |
| `TC_UC021_010` | Validation biên độ dài tối đa của nội dung bình luận (1.000 ký tự vs 1.001 ký tự) | Boundary/Val | Thành công tạo nhận xét ở mốc biên đúng 1.000 ký tự và thất bại khi nội dung vượt ngưỡng 1.001 ký tự. |
| `TC_UC021_011` | Validation giá trị số sao ngoài phạm vi cho phép (0, -1, 6, 4.5, chuỗi không phải số) | Negative/Val | Bị chặn bởi validation rule `integer|between:1,5` và trả về mã lỗi HTTP 422. |
| `TC_UC021_012` | Gửi đánh giá vào sự kiện không tồn tại hoặc sự kiện chưa công bố (`draft`, `archived`) | Business/Guard | Hệ thống từ chối lưu bình luận và trả về mã lỗi HTTP 404 Not Found an toàn. |
| `TC_UC021_013` | Chống tấn công XSS (Cross-Site Scripting) khi chèn mã độc `<script>` vào nội dung nhận xét | Security/XSS | Chuỗi script được escape an toàn khi render trên trang chi tiết sự kiện và trang quản trị đánh giá Admin. |
| `TC_UC021_014` | Khách vãng lai (Guest chưa đăng nhập) gửi request tạo mới hoặc xóa bình luận | Security/Auth | Bị Middleware `auth` chặn lại và trả về HTTP 401 Unauthorized (khi gọi qua AJAX/JSON). |
| `TC_UC021_015` | Tài khoản đã bị Admin khóa (`is_active = false`) cố gắng gửi bình luận | Security/Lock | Bị Middleware `EnsureUserIsActive` chặn lại (HTTP 403 Forbidden) và tự động đăng xuất tài khoản. |

---

### UC_022: Lưu sự kiện vào yêu thích (Wishlist / Favorite Events)
- **Files:** `tests/Feature/Client/EventFavoriteUseCaseTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC022_001` | Thành viên đã đăng nhập gửi request AJAX thêm một sự kiện đang mở bán (`published`) vào danh sách yêu thích | Positive/AJAX | Trả về HTTP 200 JSON với `favorited = true`, `favorites_count = 1` và tạo đúng 1 bản ghi trong bảng liên kết `event_user_favorites (user_id, event_id)`. |
| `TC_UC022_002` | Kiểm tra cơ chế bật/tắt (Toggle) khi click lần thứ hai cho sự kiện đã lưu trước đó | Positive/Toggle | Trả về HTTP 200 JSON với `favorited = false`, `favorites_count = 0` và xóa bản ghi tương ứng khỏi bảng `event_user_favorites`. |
| `TC_UC022_003` | Kiểm tra trạng thái hiển thị biểu tượng Trái tim khi tải lại trang (`/events` và `/events/{slug}`) | Positive/UI | Sự kiện đã lưu của user hiện tại hiển thị trạng thái đã kích hoạt (`text-crimson`), sự kiện chưa lưu giữ trạng thái mặc định. |
| `TC_UC022_004` | Kiểm tra chống trùng lặp bản ghi yêu thích (Unique constraint / Idempotency) | Security/Idempotency | Gửi yêu cầu lặp lại hoặc ghi đè không gây lỗi hệ thống 500 và chỉ duy trì duy nhất 1 bản ghi trong cơ sở dữ liệu (`unique(user_id, event_id)`). |
| `TC_UC022_005` | Truy cập trang "Sự kiện yêu thích" (`/favorites` hoặc `/profile/favorites`) với tài khoản đã lưu sự kiện | Positive/View | Trả về HTTP 200, hiển thị đầy đủ thông tin: tên sự kiện, danh mục, liên kết chi tiết và nút thao tác săn vé nhanh. |
| `TC_UC022_006` | Bỏ lưu trực tiếp tại trang danh sách yêu thích qua request `DELETE /favorites/{id}` | Positive/Delete | Bản ghi bị xóa khỏi `event_user_favorites`, trả về HTTP 200 JSON và sự kiện biến mất khỏi danh sách hiển thị khi tải lại. |
| `TC_UC022_007` | Truy cập trang yêu thích khi chưa lưu sự kiện nào (`favorites_count == 0`) | Positive/Empty | Trả về HTTP 200 và hiển thị giao diện rỗng (Empty State) với thông điệp thân thiện và nút điều hướng khám phá sự kiện. |
| `TC_UC022_008` | Sự kiện đã lưu bị Admin chuyển trạng thái sang `archived` hoặc `draft` | Resilience/UI | Không gây lỗi sập giao diện trang yêu thích, hiển thị đúng thông tin sự kiện kèm nhãn cảnh báo trạng thái tương ứng ("Ngừng mở bán" / "Bản nháp"). |
| `TC_UC022_009` | Gửi request thêm hoặc bỏ yêu thích với `event_id` không tồn tại trong hệ thống | Negative/404 | Hệ thống xử lý an toàn và trả về mã lỗi HTTP 404 Not Found. |
| `TC_UC022_010` | Gửi request thêm yêu thích đối với sự kiện đang ở trạng thái bản nháp (`draft`) | Business/Guard | Hệ thống chặn lưu và trả về mã lỗi HTTP 422 Unprocessable Entity, không ghi bản ghi vào database. |
| `TC_UC022_011` | Khách vãng lai (Guest chưa đăng nhập) bấm Trái tim qua AJAX | Security/Auth | Hệ thống chặn thao tác, trả về HTTP 401 Unauthorized và tuyệt đối không ghi dữ liệu vào bảng `event_user_favorites`. |
| `TC_UC022_012` | Khách vãng lai truy cập trực tiếp URL trang yêu thích (`/favorites`) | Security/Route | Middleware `auth` can thiệp và chuyển hướng người dùng về trang đăng nhập (`/login`). |
| `TC_UC022_013` | Kiểm tra tính cô lập danh sách yêu thích giữa các tài khoản | Security/Isolation | Người dùng A chỉ nhìn thấy các sự kiện do chính mình yêu thích, hoàn toàn không nhìn thấy hoặc bị rò rỉ dữ liệu của Người dùng B. |
| `TC_UC022_014` | Tài khoản người dùng đã bị khóa (`is_active = false`) cố gắng truy cập trang hoặc thao tác yêu thích | Security/Lock | Middleware `EnsureUserIsActive` chặn truy cập, tự động đăng xuất tài khoản và chuyển hướng về trang `/login`. |

---

### UC_023: Hỏi đáp trợ lý ảo Chatbot AI (TicketBot AI)
- **Files:** `tests/Feature/Client/AiChatbotAssistantUseCaseTest.php`, `tests/Feature/Client/EventBookingFlowTest.php`

| Mã Test Case | Tên kịch bản kiểm thử | Phân loại | Kết quả mong đợi |
|:---|:---|:---:|:---|
| `TC_UC023_001` | Kiểm tra render thành phần Chatbot trên giao diện toàn cục (`/`, `/events`, `/events/{slug}`) | Positive/UI | Nút biểu tượng TicketBot AI và khối component `ai-chatbot` được nhúng đầy đủ trên DOM với `data-component="ai-chatbot"`. |
| `TC_UC023_002` | Kiểm tra hiển thị bóng chào mừng tự động sau 1.8 giây | Positive/Timer | Sau khoảng thời gian trễ 1.8 giây (`1800ms`) kể từ khi tải trang, bóng thông báo chào mừng tự động chuyển sang hiển thị. |
| `TC_UC023_003` | Kiểm tra thao tác mở và đóng hộp thoại Chatbot và hiển thị 6 quick chips | Positive/UI | Click mở khung chat hiển thị đầy đủ 6 nút gợi ý nhanh, ô nhập liệu và nút đóng/thu gọn. |
| `TC_UC023_004` | Click nút gợi ý "🔥 Concert hot nhất tuần này?" phản hồi chính xác sự kiện published | Positive/Data | Chatbot phản hồi chính xác sự kiện đang mở bán (`published`) kèm thẻ sự kiện (Event Card) chứa đường dẫn hợp lệ tới `/events/{slug}`. |
| `TC_UC023_005` | Kiểm tra dữ liệu thẻ sự kiện (Event Card) khi hệ thống chưa có sự kiện published nào | Empty/Resilience | Chatbot trả về thông báo trạng thái trống an toàn ("chưa có sự kiện mở bán nào mới"), không lỗi giao diện hay liên kết hỏng. |
| `TC_UC023_006` | Click nút gợi ý "🎟️ Quy trình giữ chỗ 10 phút như thế nào?" | Business/Help | Phản hồi định dạng danh sách có cấu trúc, nêu chính xác cơ chế khóa ghế 10 phút và tự động nhả ghế khi hết hạn. |
| `TC_UC023_007` | Click nút gợi ý "💳 Hướng dẫn thanh toán VietQR & MoMo?" | Business/Help | Phản hồi liệt kê đầy đủ 4 phương thức thanh toán được hỗ trợ (VietQR, MoMo, ZaloPay, thẻ ngân hàng). |
| `TC_UC023_008` | Click nút gợi ý "📱 Làm sao để tải vé điện tử E-Ticket QR Code?" | Business/Help | Phản hồi hướng dẫn đúng đường dẫn lịch sử mua vé (`/bookings/history`), xem mã QR và tải file PDF vé điện tử. |
| `TC_UC023_009` | Click nút gợi ý "🏟️ Sơ đồ SVĐ Quân Khu 7" và "🔄 Chính sách hoàn hủy đổi vé" | Business/Help | Từng nút trả về đúng nội dung phân khu sân khấu và chính sách không hoàn tiền / chuyển nhượng vé trước 48h. |
| `TC_UC023_010` | Nhập câu hỏi tự do chứa từ khóa nghiệp vụ hợp lệ ("giá vé", "thanh toán", "mã giảm giá", "đặt vé", "qr code") | NLP/Keyword | Bot phân tích từ khóa và phản hồi chính xác chủ đề tương ứng khớp với câu hỏi người dùng. |
| `TC_UC023_011` | Phân tích từ khóa không phân biệt chữ hoa/thường và dấu câu | NLP/Normalization | Chuỗi câu hỏi được chuẩn hóa (`.toLowerCase().trim()`), nhận diện đúng dù viết hoa hoặc thừa khoảng trắng ("VIETQR", "   giữ chỗ   "). |
| `TC_UC023_012` | Nhập câu hỏi ngoài phạm vi từ khóa nghiệp vụ (Fallback Response) | NLP/Fallback | Bot trả về câu phản hồi mặc định thân thiện kèm gợi ý các chủ đề hỗ trợ, không phát sinh lỗi. |
| `TC_UC023_013` | Gửi tin nhắn rỗng ("") hoặc chỉ chứa khoảng trắng ("   ") | Negative/Val | Hệ thống chặn gửi (nút gửi bị vô hiệu hóa), không chèn bong bóng chat rỗng vào lịch sử hội thoại. |
| `TC_UC023_014` | Kiểm tra chống lỗi XSS khi người dùng nhập chuỗi mã độc (`<script>`, `<img>`) | Security/XSS | Nội dung tin nhắn người dùng được render an toàn dưới dạng văn bản thuần túy (`x-text`), mã JavaScript không được thực thi. |
| `TC_UC023_015` | Nhập chuỗi văn bản cực dài (> 1.000 ký tự) vào khung chat | Resilience/UI | Giao diện bong bóng chat tự động ngắt dòng (`break-words`), không làm vỡ khung hội thoại hoặc đứng trình duyệt. |
| `TC_UC023_016` | Quyền truy cập không yêu cầu đăng nhập | Security/Access | Cả Khách vãng lai (Guest) và Thành viên đã đăng nhập (`user`, `admin`) đều sử dụng được đầy đủ toàn bộ widget và tính năng chatbot. |

---

## 3. HƯỚNG DẪN CẬP NHẬT KHI THÊM TEST CASE MỚI

Khi bạn yêu cầu viết thêm hoặc bổ sung các ca kiểm thử mới (ví dụ viết thêm test cho `ReviewController` hay `AnnouncementController`), bạn có thể bổ sung dòng mới vào bảng theo mẫu sau:

```markdown
| TC_UCxxx_yy | [Tên kịch bản kiểm thử] | [Positive/Negative/Security] | [Kết quả mong đợi] |
```
Sau đó chỉ cần chạy lệnh kiểm tra:
```bash
php artisan test --filter=[TênFileHoacTenTest]
```




---

## 4. MA TRẬN KIỂM THỬ VALIDATION & RÀNG BUỘC NGHIỆP VỤ CỐT LÕI (VALIDATION MATRIX)

File kiểm thử tự động: `tests/Feature/System/ValidationAndBusinessRulesMatrixTest.php`  
Nhóm chạy nhanh: `php artisan test --group=validation`

| Mã Test Case | Tên Kịch Bản Kiểm Thử | Phân Loại | Kết Quả Mong Đợi (HTTP & DB Integrity) |
| :--- | :--- | :---: | :--- |
| `TC_UC006_001` | Tạo suất diễn có `start_time` trong quá khứ hoặc `end_time <= start_time` | Validation/Time | HTTP 302 kèm lỗi validation `start_time`/`end_time`. DB không lưu bản ghi (`assertDatabaseMissing`). |
| `TC_UC006_002` | Tạo suất diễn trùng khung giờ trong cùng một khán phòng | Business/Time | Chặn trùng giờ chiếu (`after:validator`), báo lỗi khán phòng đã có suất diễn khác. DB không lưu bản ghi trùng. |
| `TC_UC010_001` | Tạo mã giảm giá có ngày hết hạn (`end_date`) trong quá khứ | Validation/Time | HTTP 302 kèm lỗi `end_date: after_or_equal:today`. DB không lưu mã giảm giá hết hạn. |
| `TC_UC016_001` | Khách hàng cố giữ chỗ / đặt vé cho suất diễn đã qua giờ bắt đầu (`isPast()`) | Business/Time | Bị chặn bởi `SeatHoldException` ("Suất diễn đã bắt đầu, không thể đặt vé"), ghế giữ nguyên trạng thái `available`. |
| `TC_UC016_002` | Thời gian giữ chỗ (`held_until`) khóa chính xác 10 phút | Business/Time | Cột `held_until` trong bảng `showtime_seats` được set đúng `now() + 10 phút`. |
| `TC_UC005_001` | Sơ đồ khán phòng vượt biên sức chứa tối đa 5.000 chỗ/phòng | Boundary/Format | HTTP 302 kèm lỗi `layout`. DB không tạo mới khán phòng. |
| `TC_UC015_001` | Khách hàng giữ vượt quá biên 8 ghế trong một lượt đặt vé | Boundary/Seats | Ném `SeatHoldException` ("Mỗi lượt đặt tối đa 8 chỗ"), không cho phép giữ từ 9 ghế trở lên. |
| `TC_UC004_001` | Kiểm tra biên dung lượng file poster sự kiện (2.048 KB vs 2.049 KB) | Boundary/Upload | File 2.049 KB bị từ chối với lỗi `poster.max`. File đúng biên 2.048 KB upload thành công. |
| `TC_UC020_001` | Kiểm tra biên dung lượng file avatar tài khoản (1.024 KB vs 1.025 KB) | Boundary/Upload | File 1.025 KB bị từ chối với lỗi `avatar.max`. Cột `avatar` của user giữ nguyên giá trị cũ. |
| `TC_UC010_002` | Mã giảm giá phần trăm vượt biên 100% và chuẩn hóa in hoa | Boundary/Format | Giảm 101% bị chặn bởi rule closure. Mã chữ thường tự động chuẩn hóa sang chữ IN HOA không dấu. |
| `TC_UC021_001` | Đánh giá bình luận biên số sao (1 đến 5) và độ dài nội dung (3 đến 1.000 ký tự) | Boundary/Format | Sao = 6 và nội dung 2 ký tự bị từ chối HTTP 422. Nội dung 3 ký tự và sao = 1 được chấp nhận HTTP 201. |
| `TC_UC013_001` | Đăng ký tài khoản yêu cầu số điện thoại di động chuẩn Việt Nam 10 chữ số | Boundary/Format | Số điện thoại sai đầu số hoặc thiếu chữ số bị từ chối với lỗi `phone`. DB không tạo tài khoản. |
| `TC_UC002_001` | Chặn xóa danh mục sự kiện khi đang có sự kiện liên kết | Integrity/FK | Redirect back kèm thông báo lỗi flash `error`. Danh mục không bị xóa khỏi cơ sở dữ liệu. |
| `TC_UC003_001` | Chặn xóa khán phòng khi có suất diễn phát sinh vé đã bán (`booked`) | Integrity/State | Bị chặn, redirect back kèm thông báo lỗi. Khán phòng và sơ đồ ghế được bảo toàn toàn vẹn. |
| `TC_UC007_001` | Chặn xác nhận lại đơn đã hủy nếu ghế đã thuộc đơn khác hoặc bị giữ | Integrity/State | DB Transaction kiểm tra xung đột ghế, chặn khôi phục đơn, giữ nguyên trạng thái `cancelled`. |
| `TC_UC018_001` | Chặn xem và tải vé điện tử PDF nếu đơn hàng chưa ở trạng thái `confirmed` | Integrity/State | Hệ thống chặn bằng mã lỗi HTTP 403 Forbidden đối với cả route view HTML và route tải PDF. |
| `TC_UC001_001` | Chặn Quản trị viên tự khóa tài khoản hoặc tự hạ quyền chính mình | Security/RBAC | Request AJAX trả về HTTP 422 JSON cảnh báo, tài khoản admin giữ nguyên quyền và trạng thái hoạt động. |
| `TC_UC001_002` | Middleware EnsureUserIsActive tự động logout tài khoản bị khóa | Security/Auth | Người dùng có `is_active = false` gửi request bị logout ngay lập tức và chuyển hướng về `/login`. |
| `TC_UC016_003` | Chống tranh chấp giữ chỗ đồng thời (Race Condition) bằng Pessimistic Lock | Security/Lock | User A giữ ghế thành công, User B đồng thời giữ cùng ghế đó bị chặn ngay với thông báo ghế đã có người giữ. |
| `TC_UC018_002` | Chống lỗi IDOR: User A không thể xem hoặc tải vé điện tử của User B | Security/IDOR | User A truy cập `/bookings/{id_user_b}/ticket` hoặc ticket PDF bị chặn với mã lỗi HTTP 403 Forbidden. |
| `TC_UC021_002` | Chống lỗi IDOR: User A không thể xóa bình luận của User B | Security/IDOR | `CommentPolicy` từ chối thực thi khi không phải tác giả, trả về HTTP 403 JSON và giữ nguyên bản ghi. |
