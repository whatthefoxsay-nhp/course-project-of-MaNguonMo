# Mô tả chức năng (đối chiếu tiêu chí chấm)

Mỗi dòng ghi file code tương ứng (đường dẫn tính từ thư mục gốc dự án) để người chấm mở kiểm tra.
Nền tảng: Laravel 11 · PHP 8.3 · MySQL 8.4 · Tailwind CSS + Alpine.js · Pest. Múi giờ `Asia/Ho_Chi_Minh`, ngôn ngữ `vi`.

## 1. Hoàn thiện chức năng chính (40%)

| Yêu cầu | Đã làm | Code |
|---|---|---|
| CRUD | Danh mục (kèm xóa nhiều), sự kiện (upload poster), khán phòng (sơ đồ theo ghế hoặc theo khu, nhân bản), suất diễn (tự sinh trạng thái ghế), mã giảm giá (bật/tắt) | `app/Http/Controllers/Admin/CategoryController.php`, `app/Http/Controllers/Admin/EventController.php`, `app/Http/Controllers/Admin/RoomController.php`, `app/Http/Controllers/Admin/ShowtimeController.php`, `app/Http/Controllers/Admin/DiscountController.php` |
| Auth | Đăng ký, đăng nhập, quên/đặt lại mật khẩu, đổi mật khẩu (Breeze); phân quyền `admin`/`user` bằng spatie/laravel-permission; khóa tài khoản có hiệu lực ngay ở request kế tiếp; admin đặt lại mật khẩu ngẫu nhiên | `routes/auth.php`, `app/Http/Middleware/EnsureUserIsActive.php`, `app/Http/Requests/Auth/LoginRequest.php`, `app/Http/Controllers/Admin/UserController.php` |
| Validation | Form Request có thông báo tiếng Việt cho sự kiện, suất diễn (chặn chồng giờ cùng phòng), danh mục, giữ ghế, thanh toán (họ tên, số điện thoại, mã giảm giá), bình luận, hồ sơ/avatar; file ngôn ngữ `lang/vi` | `app/Http/Requests/Admin/EventRequest.php`, `app/Http/Requests/Admin/ShowtimeRequest.php`, `app/Http/Requests/Admin/CategoryRequest.php`, `app/Http/Requests/HoldSeatsRequest.php`, `app/Http/Requests/CheckoutRequest.php`, `app/Http/Requests/StoreCommentRequest.php`, `app/Http/Requests/ProfileUpdateRequest.php`, `lang/vi/validation.php` |
| Pagination | Danh sách sự kiện public 9/trang; các bảng admin 10/trang (danh mục chọn 10/20/50); bình luận phân trang theo con trỏ 10/lần; giữ tham số lọc khi chuyển trang (`withQueryString`) | `app/Http/Controllers/EventController.php`, `app/Http/Controllers/Admin/*Controller.php`, `app/Http/Controllers/CommentController.php`, `resources/views/vendor/pagination/custom.blade.php` |
| Search | Tìm theo tiêu đề/mô tả + lọc danh mục; gợi ý tìm kiếm tức thì (≥ 2 ký tự, tối đa 6 kết quả); admin tìm/lọc sự kiện, đơn, người dùng, danh mục | `app/Http/Controllers/EventController.php`, `app/Http/Controllers/SearchSuggestionController.php` |
| Đặt vé | Giữ ghế 10 phút (tối đa 8 chỗ, chống đặt trùng), bán theo khu + số lượng cho khu đứng/vé vào cửa, hạng vé theo số lượng cho sự kiện không chọn ghế, giỏ vé có đồng hồ, mã giảm giá, thanh toán giả lập, vé điện tử PNG, lịch sử đơn | `app/Services/SeatHoldService.php`, `app/Services/CheckoutService.php`, `app/Services/DiscountService.php`, `app/Http/Controllers/CartController.php`, `app/Http/Controllers/BookingController.php` |
| Quản lý đơn | Admin xem, xác nhận/hủy đơn; hủy thì nhả ghế và trả lượt mã giảm giá | `app/Http/Controllers/Admin/BookingController.php` |

## 2. Áp dụng công nghệ Laravel (25%)

| Công nghệ | Cách dùng | Code |
|---|---|---|
| Eloquent ORM | 13 model với quan hệ 1-n/n-1; scope (`published`, `holdable`, `heldBy`, `active`, `forShowtimes`); accessor (`poster_url`, `base_price`, `full_code`, `avatar_url`, `session_label`…); cast JSON/boolean/datetime/integer | `app/Models/*` |
| Migration | 28 migration: khóa ngoại, unique kép (ví dụ `showtime_seats(showtime_id, seat_id)`), cột JSON, các migration bổ sung khu (`room_zones`, `zone_tickets`), người nhận vé và mã giảm giá cho `bookings` | `database/migrations/*` |
| Seeder / Factory | Dữ liệu demo: 2 tài khoản, 8 danh mục, 9 sự kiện, 5 khán phòng (sân vận động theo khu, nhà hát theo ghế, trung tâm hội nghị vé vào cửa), 13 suất tính từ ngày chạy seed, 3 mã giảm giá, đơn và bình luận mẫu; factory cho test | `database/seeders/DatabaseSeeder.php`, `database/seeders/RoleSeeder.php`, `database/seeders/data/events.php`, `database/factories/*` |
| Blade | Layout dạng component (`x-site-layout`, `x-admin-layout`, `x-guest-layout`), component `x-seat-map` (nhúng `components/seat-map-zoned` khi phòng theo khu), `x-event-card`, `x-admin.field`, `x-ai-chatbot`; truyền dữ liệu sang JS bằng `@js` | `resources/views/**`, `app/View/Components/*` |
| Routing | Route model binding theo slug (`{event:slug}`), resource route, nhóm `admin.*` có prefix | `routes/web.php` |
| Middleware | `auth`, `role:admin` (spatie), `throttle` cho các endpoint AJAX, `EnsureUserIsActive` gắn vào nhóm `web` | `routes/web.php`, `bootstrap/app.php` |
| Policy | Chỉ chủ bình luận hoặc admin được xóa | `app/Policies/CommentPolicy.php` |
| Transaction + Lock | `DB::transaction` + `lockForUpdate()` chống đặt trùng ghế, vượt sức chứa khu, vượt lượt mã giảm giá | `app/Services/SeatHoldService.php`, `app/Services/CheckoutService.php`, `app/Http/Controllers/Admin/BookingController.php` |
| Service class | Logic nghiệp vụ tách khỏi controller, inject qua service container | `app/Services/*` |
| Scheduler + Artisan command | `seats:release-expired` chạy mỗi phút | `app/Console/Commands/ReleaseExpiredSeatHolds.php`, `routes/console.php` |
| Storage | Poster sự kiện, avatar trên disk `public` (`php artisan storage:link`) | `app/Http/Controllers/Admin/EventController.php`, `app/Http/Controllers/ProfileController.php` |
| HTTP Client + Cache | Gọi OpenWeatherMap (timeout 5 giây), cache 30 phút, lỗi thì nghỉ 5 phút; gọi Gemini cho chatbot | `app/Services/WeatherService.php`, `app/Services/GeminiChatService.php` |
| Pest | **363 test / 1612 assertion, tất cả pass** (`php artisan test`, SQLite in-memory) | `tests/Feature/*`, `tests/Unit/*` |

## 3. Thiết kế & trải nghiệm (15%)

- **Bảng màu** (khai báo trong `tailwind.config.js`, giải thích thêm ở `docs/architecture.md`): đen `#000000` cho tiêu đề, sidebar admin, nút chính; vàng cổ `#D4AF37` cho hạng VIP, giá vé; hồng taupe `#C08497` cho nút CTA; xanh rêu `#3A5A40` cho trạng thái thành công/hoạt động; đỏ `#CC0000` cho lỗi/tài khoản bị khóa; nền trắng và be `#F5F5DC`.
- **Responsive:** giao diện dùng các breakpoint Tailwind (`sm:`, `lg:`); khi chụp ảnh kiểm tra ở 375px / 768px / 1440px (xem `anh-chup.md`).
- **Sơ đồ chọn chỗ tương tác:** sơ đồ ghế nhà hát (phóng to/thu nhỏ, lọc theo khu, xem thông tin ghế khi rê chuột) và sơ đồ khu sân vận động (bấm khu để mở sơ đồ ghế của khu, khu đứng chọn số lượng, hiện số chỗ còn) — `resources/views/components/seat-map.blade.php`, `resources/views/components/seat-map-zoned.blade.php`.
- **Phản hồi tức thì:** đồng hồ đếm ngược giữ vé ở giỏ, thông báo toast khi giữ ghế/áp mã/thanh toán, ghế người khác vừa giữ tự chuyển xám.
- **Minh bạch về thanh toán giả lập:** mã QR ghi rõ "Mã QR minh họa – không chuyển tiền", form thẻ ghi rõ không thu thông tin thẻ.
- Ảnh minh họa: `docs/report/screenshots/` (danh sách trong `docs/report/anh-chup.md`).

## 4. Báo cáo & thuyết trình (10%)

| Tài liệu | Nội dung |
|---|---|
| `docs/report/erd.md` | Sơ đồ quan hệ thực thể + giải thích bảng |
| `docs/report/use-cases.md` | Tác nhân, sơ đồ use case, đặc tả use case chính |
| `docs/report/chuc-nang.md` | File này — đối chiếu chức năng với tiêu chí chấm |
| `docs/report/anh-chup.md` | Checklist ảnh chụp màn hình |
| `docs/report/kich-ban-demo.md` | Kịch bản thuyết trình ~10 phút + câu hỏi hay gặp |
| `README.md` | Hướng dẫn cài đặt và chạy từ máy trắng |

## 5. Sáng tạo & mở rộng (10%)

| Tính năng | Code |
|---|---|
| AJAX giữ ghế + polling trạng thái ghế/số chỗ còn của khu mỗi 15 giây (chỉ khi tab đang hiển thị) | `resources/js/app.js` (`seatBookingManager`), `app/Http/Controllers/ShowtimeController.php` (`seatStatus`) |
| AJAX bình luận (tải thêm/gửi/xóa), gợi ý tìm kiếm, bỏ vé khỏi giỏ, áp mã giảm giá, thanh toán, khóa/mở khóa user | `resources/js/app.js`, `resources/views/admin/users/index.blade.php` |
| Mô hình khán phòng theo khu: khu ghế ngồi, bàn, khu đứng, vé vào cửa; khu đứng bán theo số lượng có kiểm soát sức chứa | `app/Models/RoomZone.php`, `app/Models/ZoneTicket.php`, `app/Services/ZoneLayoutGenerator.php`, `app/Support/VenuePresets.php` |
| Trình thiết kế khán phòng: ma trận ghế và sơ đồ khu trên canvas, nhân bản phòng | `app/Http/Controllers/Admin/RoomController.php`, `resources/views/admin/rooms/builder.blade.php`, `resources/views/admin/rooms/zoned-builder.blade.php` |
| Mã giảm giá (phần trăm/cố định, đơn tối thiểu, giảm tối đa, số lượt, thời hạn) | `app/Models/Discount.php`, `app/Services/DiscountService.php`, `app/Http/Controllers/Admin/DiscountController.php` |
| Google Maps nhúng (có/không có key) + dự báo OpenWeatherMap cho địa điểm ngoài trời | `app/Support/MapEmbed.php`, `app/Services/WeatherService.php` |
| Vé điện tử có QR mã đặt vé, tải ảnh PNG (vẽ bằng canvas) | `resources/js/app.js` (`drawAndDownloadTicket`), `resources/views/bookings/history.blade.php` |
| Dashboard doanh thu, tỉ lệ lấp đầy, báo cáo có bản in (in/lưu PDF từ trình duyệt) | `app/Http/Controllers/Admin/DashboardController.php`, `app/Http/Controllers/Admin/ReportController.php`, `app/Services/SalesReportService.php`, `resources/views/admin/reports/pdf.blade.php` |
| Trợ lý chatbot dùng Gemini với dữ liệu sự kiện thật (tùy chọn `GEMINI_API_KEY`) | `app/Http/Controllers/ChatbotController.php`, `app/Services/GeminiChatService.php`, `resources/views/components/ai-chatbot.blade.php` |
