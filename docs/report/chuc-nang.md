# Mô tả chức năng (đối chiếu tiêu chí chấm)

Mỗi dòng ghi file code để người chấm mở ra kiểm tra. Số liệu test lấy từ `php artisan test` ngày 26/10/2026.

## 1. Hoàn thiện chức năng chính (40%)

| Yêu cầu | Đã làm | Code |
|---|---|---|
| CRUD | Danh mục, sự kiện (kèm upload poster), địa điểm (sơ đồ theo khu, nhân bản), suất diễn, mã giảm giá, người dùng | `app/Http/Controllers/Admin/{Category,Event,Room,Showtime,Discount,User}Controller.php` |
| Auth | Đăng ký, đăng nhập, quên mật khẩu (Breeze); phân quyền `admin` / `user` (spatie); khóa tài khoản có hiệu lực ngay | `routes/auth.php`, `app/Http/Middleware/EnsureUserIsActive.php` |
| Validation | Form Request cho các form chính, thông báo tiếng Việt; chặn suất chồng giờ; kiểm tra sơ đồ địa điểm (mã khu, nhãn hàng, sức chứa); bình luận 3–1000 ký tự; ảnh ≤ 1–2MB | `app/Http/Requests/**` |
| Pagination | Danh sách sự kiện public (9/trang), các bảng admin (10/trang) | `EventController@index`, `Admin/*Controller@index` |
| Search | Tìm theo tên/mô tả + lọc danh mục; gợi ý tìm kiếm tức thì khi gõ | `EventController@index`, `SearchSuggestionController.php` |
| Đặt vé | Sơ đồ theo khu (ghế, bàn, khu đứng), giữ chỗ 10 phút chống trùng, giỏ vé, thanh toán giả lập, lịch sử, vé điện tử PNG | `app/Services/{SeatHoldService,CheckoutService}.php`, `app/Support/SeatMap.php` |
| Quản lý đơn | Xem, lọc, hủy đơn (trả ghế); chặn xác nhận lại đơn đã hủy khi ghế đã bán cho người khác | `app/Http/Controllers/Admin/BookingController.php` |

## 2. Áp dụng công nghệ Laravel (25%)

| Công nghệ | Cách dùng | Code |
|---|---|---|
| Eloquent ORM | 13 model, quan hệ 1-n (`Room → SeatZone → Seat → ShowtimeSeat`, `Event → Showtime`, …), scope (`published`, `holdable`, `heldBy`), accessor (`poster_url`, `base_price`, `avatar_url`, chi tiết JSON), cast JSON/boolean/datetime | `app/Models/*` |
| Migration | 21 migration, khóa ngoại (cascade / null on delete), unique kép, index `(status, held_until)`, migration chuyển dữ liệu ghế cũ sang khu | `database/migrations/*` |
| Seeder / Factory | Dữ liệu demo 8 sự kiện, 8 địa điểm, mỗi sự kiện một kiểu bố trí; factory cho test | `database/seeders/*`, `database/factories/*` |
| Blade | Layout, component (`x-seat-map` + 7 component con theo kiểu khu, `x-event-card`, `x-admin.field`) | `resources/views/**` |
| Middleware | `auth`, `role:admin` (spatie), `throttle` (giữ chỗ, thanh toán, bình luận, gợi ý), `EnsureUserIsActive` | `routes/web.php`, `bootstrap/app.php` |
| Policy | Quyền xóa bình luận | `app/Policies/CommentPolicy.php` |
| Transaction + Lock | Chống đặt trùng ghế / chỗ đứng; chặn đổi trạng thái đơn gây bán trùng | `app/Services/SeatHoldService.php`, `Admin/BookingController.php` |
| Scheduler + Artisan command | Nhả chỗ hết hạn mỗi phút | `app/Console/Commands/ReleaseExpiredSeatHolds.php`, `routes/console.php` |
| Storage | Poster, avatar trên disk `public` | `Admin/EventController.php`, `ProfileController.php` |
| HTTP Client + Cache | Gọi OpenWeatherMap, timeout 5 giây, cache 30 phút | `app/Services/WeatherService.php` |
| Pest | 278 test / 1792 assertion, không gọi mạng thật (`Http::fake`) | `tests/**` |

## 3. Thiết kế & trải nghiệm (15%)

- **Bảng màu** (xem `docs/architecture.md` mục 3): nền trắng ngà, beige, đen obsidian, vàng antique gold cho VIP/giá, hồng rose taupe cho nút hành động.
- **Sơ đồ chỗ ngồi theo loại sự kiện**: sân khấu vẽ theo kiểu địa điểm (sân khấu chữ T, sân khấu nhà hát, bục diễn giả, cổng vào…); khu xếp theo tầng, cùng tầng nằm cạnh nhau; ghế tô màu theo khu, lối đi là khoảng trống; bàn tiệc vẽ hình tròn có ghế quanh; khu đứng có ô chọn số lượng.
- **Tương tác**: zoom, lọc theo khu, bảng thông tin ghế khi rê chuột, ngăn kéo "vé đang chọn" luôn hiện ở dưới, toast thông báo, đồng hồ giữ vé 10 phút.
- **Responsive**: thử ở 375px / 768px / 1440px; sơ đồ lớn cuộn ngang trong khung riêng.
- Ảnh minh họa: `docs/report/screenshots/` (danh sách ở `anh-chup.md`).

## 4. Báo cáo & thuyết trình (10%)

- `erd.md` — sơ đồ quan hệ thực thể + giải thích thiết kế
- `use-cases.md` — tác nhân, sơ đồ use case, đặc tả 8 use case chính
- `chuc-nang.md` — file này
- `anh-chup.md` — checklist ảnh chụp màn hình
- `kich-ban-demo.md` — kịch bản thuyết trình ~10 phút + câu hỏi hay gặp

## 5. Sáng tạo & mở rộng (10%)

| Tính năng | Code |
|---|---|
| 6 mẫu bố trí theo loại sự kiện (sân vận động, nhà hát, hội trường, bàn tiệc, không gian mở, lưới tùy chỉnh) + trình dựng sơ đồ cho admin | `app/Support/LayoutTemplates.php`, `resources/views/admin/rooms/builder.blade.php` |
| AJAX giữ chỗ + polling trạng thái ghế 15 giây | `resources/js/app.js` (`seatBookingManager`) |
| AJAX bình luận, gợi ý tìm kiếm, bỏ vé khỏi giỏ, khóa user | `resources/js/app.js` (`eventComments`, `searchModal`, …) |
| Google Maps nhúng + dự báo thời tiết OpenWeatherMap tại giờ diễn | `app/Support/MapEmbed.php`, `app/Services/WeatherService.php` |
| Dashboard doanh thu, báo cáo in PDF | `Admin/DashboardController.php`, `Admin/ReportController.php` |
| Trợ lý chatbot (câu trả lời có sẵn) | `resources/views/components/ai-chatbot.blade.php` |
