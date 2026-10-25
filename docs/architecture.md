# Kiến trúc hệ thống (Living Architecture Document)

Cập nhật file này mỗi khi kiến trúc hoặc yêu cầu dự án thay đổi so với spec gốc. Nguồn sự thật ban đầu:
`docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md`. Sơ đồ ERD chi tiết: `docs/report/erd.md`.

---

## 1. Trạng thái hiện tại
- **Nền tảng**: Laravel 11.56.1, PHP 8.3 (Laragon), Pest 4, Laravel Breeze (Blade stack), Tailwind CSS 3 + Alpine.js 3, Vite 6.
- **Cơ sở dữ liệu**: chạy ứng dụng trên **MySQL 8.4** (database `doanmnm_ticket`, cấu hình trong `.env`); bộ test chạy trên
  **SQLite in-memory** (`phpunit.xml`: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`). Schema gồm **28 file migration**
  trong `database/migrations/` (13 bảng nghiệp vụ + 5 bảng phân quyền spatie + bảng hạ tầng của Laravel).
- **Múi giờ & ngôn ngữ**: `Asia/Ho_Chi_Minh`, locale `vi` (fallback `en`, Faker `vi_VN`) — xem `config/app.php`.
- **Phân quyền & Bảo mật**: `spatie/laravel-permission` với 2 role (`admin`, `user`); mọi route quản trị nằm dưới
  `admin.*` với middleware `auth` + `role:admin` (`routes/web.php`). Tài khoản bị khóa (`users.is_active = false`) không
  đăng nhập được (`App\Http\Requests\Auth\LoginRequest`) và bị đăng xuất nếu đang có phiên (`App\Http\Middleware\EnsureUserIsActive`).
- **Định hướng sản phẩm**: Chuyên biệt hóa **100% cho Nền tảng Đặt vé Sự Kiện (Event Ticketing)** — Live Concert,
  Hòa Nhạc Giao Hưởng, Hội Thảo & Diễn Đàn Doanh Nghiệp (Summit/Expo), Triển Lãm Nghệ Thuật & Nhạc Kịch.
- **Tiến độ hoàn thành** (chi tiết từng hạng mục ở các mục 6–10):
  - **Database & Models (Phase 1)**: 13 bảng nghiệp vụ (`users`, `categories`, `events`, `rooms`, `room_zones`, `seats`,
    `showtimes`, `showtime_seats`, `zone_tickets`, `discounts`, `bookings`, `booking_items`, `comments`) và 13 Eloquent Model
    trong `app/Models/`.
  - **Admin CRUD (Plan 1)**: danh mục, sự kiện (upload poster), suất diễn (chặn trùng giờ, tự sinh ghế theo suất), khán phòng
    (builder ma trận ghế + sơ đồ theo khu, nhân bản), quản lý đơn vé, mã giảm giá, người dùng, Dashboard và Báo cáo doanh thu.
  - **Public Catalog (Plan 2)**: trang chủ, danh sách, chi tiết sự kiện và sơ đồ ghế đọc trực tiếp từ DB (đã bỏ `DemoCatalog`).
  - **Engine đặt vé (Plan 3)**: giữ ghế AJAX chống đặt trùng, giỏ vé, thanh toán giả lập kèm thông tin người nhận và mã giảm giá,
    lịch sử đơn.
  - **Tương tác AJAX (Plan 4)**: bình luận, gợi ý tìm kiếm, cập nhật SĐT + ảnh đại diện (mục 6).
  - **API ngoài (Plan 5)**: Google Maps + dự báo thời tiết OpenWeatherMap cho địa điểm ngoài trời (mục 10).
  - **Sơ đồ khán phòng theo khu (P7)**: `room_zones` + `zone_tickets`, bán vé đứng/vé vào cửa theo số lượng (mục 7).
  - **Chatbot AI**: Gemini, có câu trả lời dự phòng khi không gọi được API (mục 10).
  - **Giao diện**: Luxury Light Theme cho trang khách, tông Ink & Antique Gold cho khu quản trị (mục 3).

---

## 2. Các thay đổi & Cập nhật so với Spec ban đầu

| Ngày | Hạng mục | Chi tiết thay đổi | Lý do kỹ thuật & Yêu cầu |
| :--- | :--- | :--- | :--- |
| **2026-09-07** | **Frontend CSS** | Dùng **Tailwind CSS + Vite** thay vì Bootstrap 5 | Tránh xung đột với Laravel Breeze Blade stack, tận dụng tối đa hệ sinh thái utility-first. |
| **2026-09-18** | **Định hướng dịch vụ** | Chuyển toàn bộ sang **Vé Sự Kiện (Events / Concerts / Summits / Exhibitions)** | Chuẩn hóa thông điệp sản phẩm theo đúng đề bài đặt vé sự kiện chuyên nghiệp, loại bỏ thuật ngữ phim chiếu rạp. |
| **2026-09-18** | **Giao diện & Màu sắc** | Áp dụng **Luxury Light Theme** (Nền trắng #FFFFFF & Beige #F5F5DC, Text đen #000000) | Tối ưu độ tương phản, chữ và các thành phần nổi bật, loại bỏ hiện tượng chìm màu trên dark theme. |
| **2026-09-18** | **Component Layouts** | Layout dạng class component: `app/View/Components/{SiteLayout,AdminLayout,GuestLayout}.php` render `resources/views/layouts/{site,admin,guest}.blade.php`; view dùng `<x-site-layout>` / `<x-admin-layout>` | Bộ proxy `<x-layouts.*>` thử nghiệm ban đầu đã bỏ, hiện chỉ còn một cú pháp duy nhất. |
| **2026-09-20** | **Database Schema & Models** | Đổi bảng `movies` thành `events`, khóa ngoại `movie_id` → `event_id` trên `showtimes` và `comments`, tạo Model `Event` và Controller `EventController` | Chuẩn hóa tầng dữ liệu hướng sự kiện (Event-Centric). Còn lại vài alias tương thích ngược: route `movies.index`/`movies.show` và quan hệ `Showtime::movie()`. |
| **2026-09-21** | **Chi tiết sự kiện** | Thêm cột `events.is_seated` (bool) và `events.details` (JSON: địa điểm, lineup, lịch trình, BTC, quy định) | Giữ nguyên giao diện chi tiết sự kiện giàu thông tin của nhóm mà không tạo thêm 5–6 bảng phụ. |
| **2026-09-21** | **An toàn dữ liệu** | Không sinh lại ghế / không xóa khán phòng khi đã có suất diễn; không xóa danh mục còn sự kiện; không xóa sự kiện/suất diễn đã bán vé | Các FK đang `cascade` — xóa nhầm sẽ mất vé đã bán. Chi tiết mục 9. |
| **2026-09-21** | **Engine đặt vé** | Giữ ghế = `showtime_seats.status=held` (+`held_by_user_id`, `held_until` 10 phút, `price_override` = giá chốt); khóa dòng `lockForUpdate()` trong transaction, tất cả-hoặc-không; mỗi user chỉ giữ ghế của 1 suất; tối đa 8 chỗ; lệnh `seats:release-expired` chạy mỗi phút | Đúng spec mục 3, không có bảng giỏ hàng riêng. |
| **2026-09-21** | **Thanh toán** | Thanh toán giả lập: chọn VietQR/MoMo/ZaloPay/Thẻ, server tạo booking `confirmed`, lưu `bookings.payment_method` | Spec mục 5: không tích hợp cổng thật. |
| **2026-09-21** | **Đồng bộ ghế** | Polling `GET /showtimes/{id}/seat-status` mỗi 15 giây (tạm dừng khi tab bị ẩn) | Spec mục 5: không dùng WebSocket. |
| **2026-09-23** | **Quản trị hệ thống** | Dashboard & Báo cáo chạy trên số liệu thật; chi tiết đơn vé & giải phóng ghế khi hủy đơn; bảng & Model `Discount`; đổi vai trò Admin/User và đặt lại mật khẩu | Phân hệ Admin vận hành 100% trên dữ liệu thật, không còn mock/alert giả lập. |
| **2026-09-23** | **Public Catalog DB (Plan 2)** | Trang chủ, danh sách sự kiện, chi tiết sự kiện và sơ đồ ghế đọc trực tiếp từ DB; thống nhất định giá qua `App\Support\TicketTiers`; sơ đồ ghế động qua `App\Support\SeatMapPresenter`; hỗ trợ cả sân vận động và khán phòng nhà hát | Loại bỏ hoàn toàn phụ thuộc vào `DemoCatalog`. |
| **2026-10-11** | **Sơ đồ khán phòng theo khu (P7)** | Mỗi phòng có các khu `seated`, `table`, `standing` hoặc `admission`; khu có ghế dùng ghế thật, khu bán theo số lượng dùng `zone_tickets` thay ghế GA ảo. Sức chứa phòng được tính từ các khu và ghế legacy. | Phản ánh đúng mô hình sân khấu, bàn nhóm, khu đứng và vé vào cửa; không tạo tồn kho ghế giả cho khu bán số lượng. |
| **2026-10-11** | **Đồng bộ Sức chứa & Sơ đồ** | Admin builder tính sức chứa từ khu; seeder khởi tạo khu theo mục đích. Lệnh `rooms:migrate-zones --dry-run` báo cáo chuyển đổi phòng cũ trước khi áp dụng. | Giữ tồn kho vé, sơ đồ và sức chứa nhất quán; chuyển dữ liệu thật chỉ sau khi rà soát báo cáo dry-run. |
| **2026-10-19** | **API ngoài (Plan 5)** | Google Maps (`App\Support\MapEmbed`) + dự báo OpenWeatherMap (`App\Services\WeatherService`); thiếu key / API lỗi → ẩn khối thời tiết, bản đồ dùng link nhúng công khai. Admin nhập vĩ độ/kinh độ trong form khán phòng. | Trang không phụ thuộc vào việc API ngoài sống hay chết. Chi tiết mục 10. |
| **2026-10-19** | **Phạm vi thời tiết** | Chỉ hiện dự báo cho địa điểm **ngoài trời** — suy ra từ `rooms.layout_preset` (`Room::isOutdoor()`). | Spec ghi "sự kiện ngoài trời"; chưa có cờ trong nhà/ngoài trời riêng nên dùng mẫu bố cục thay vì thêm cột mới. |
| **2026-10-19** | **Tiếng Việt** | Ngôn ngữ mặc định `vi` (fallback `en`, `APP_FAKER_LOCALE=vi_VN`); bổ sung `lang/vi/{auth,validation,passwords,pagination}.php` và `lang/vi.json` cho chuỗi của framework/Breeze; `phpunit.xml` chạy test với `APP_LOCALE=vi` | Thông báo kiểm tra dữ liệu không còn trộn tiếng Anh với tên trường tiếng Việt. |
| **2026-10-19** | **Hoàn thiện thanh toán** | Thông tin người nhận vé (`bookings.buyer_name/buyer_phone/buyer_email`), mã giảm giá (`bookings.discount_id/discount_amount`, `total_price` là số tiền sau giảm), QR minh họa thay QR thanh toán thật | Khép kín luồng mua vé mà vẫn không giả làm cổng thanh toán thật. Chi tiết mục 8. |

---

## 3. Bảng Màu & Phân Bổ Token (Design Tokens)

Font chung (Google Fonts, nạp trong `layouts/site`, `layouts/admin`, `layouts/guest`): **Plus Jakarta Sans** (`font-sans`),
**Space Grotesk** (`font-display`, số liệu/tiêu đề), **Playfair Display** (`font-serif`). Token khai báo ở 2 nơi:
biến CSS trong `:root` của `resources/css/app.css` và bảng màu mở rộng trong `tailwind.config.js`
(`obsidian`, `ink`, `warm-gray`, `beige`, `rose`, `gold`, `sage`, `crimson`).

### 3.1. Trang khách (Luxury Light Theme — `resources/views/layouts/site.blade.php`)
- **`#FFFFFF` / `#FAF9F6` (Canvas)**: nền trang, card dữ liệu, modal, input (`.glass-card`, `.glass-panel`, `.glass-input`).
- **`#F5F5DC` (Warm Beige)**: khung toolbar bộ lọc, thẻ ưu đãi (`.glass-beige`).
- **`#000000` (Obsidian)**: tiêu đề đậm, nút chính (`.btn-dark`), footer.
- **`#C08497` (Rose Taupe)**: CTA "Đặt vé", viền hover của card, nút gửi chatbot.
- **`#D4AF37` (Antique Gold)**: viền focus input, huy hiệu VIP, giá vé.
- **`#3A5A40` (Forest Sage)**: trạng thái thành công / "Hoạt động".
- **Crimson** (`#B91C1C` trong token, một vài chỗ còn `#CC0000`): lỗi, "Đã bị khóa".

### 3.2. Khu quản trị (Ink & Antique Gold — `resources/views/layouts/admin.blade.php`)
- **Sidebar** nền mực `#141210` (header/footer `#0E0D0C`), chữ `#F5F5F4`, mục đang chọn viền trái vàng `#D4AF37`.
- **Canvas** `#FAF9F6`, card trắng, toolbar `#F5F1E8`, chữ chính `#1C1917`, chữ phụ `#57534E`.
- Component dùng chung (lớp `@layer components` cuối `resources/css/app.css`): `.admin-card`, `.kpi-card`,
  `.kpi-card-hero` (nền mực, viền vàng), `.admin-input`, `.admin-btn-primary` (mực + viền vàng), `.admin-btn-secondary`,
  `.admin-btn-rose`.
- **Màu ngữ nghĩa** dùng thang màu Tailwind: `.badge-semantic-sage` = **emerald** (thành công / đang hoạt động / đã xác nhận),
  `.badge-semantic-gold` = amber (cảnh báo), `.badge-semantic-crimson` = rose (lỗi / đã hủy / bị khóa), `.badge-semantic-neutral` = stone.

---

## 4. Kiểm thử & Độ bao phủ (Test Suite)
- Toàn bộ tính năng được kiểm thử bằng Pest (`php artisan test`, SQLite in-memory, `RefreshDatabase` bật trong `tests/Pest.php`):
  **363 tests / 1612 assertions (Pass 100%)** ở lần chạy gần nhất.
- Feature test trong `tests/Feature/` (Unit: `TicketTiersTest`, `ZoneCanvasTest`), nhóm chính:
  - **Admin**: truy cập & phân quyền, Dashboard số liệu thật, báo cáo doanh thu, quản lý đơn & toàn vẹn khi đổi trạng thái
    (`AdminBookingStatusIntegrityTest`), chặn xóa (`AdminDeleteGuardsTest`), CRUD danh mục/sự kiện/suất diễn/mã giảm giá,
    builder & kiểm tra sơ đồ khán phòng, tọa độ, quản lý người dùng (khóa, đổi role, đặt lại mật khẩu).
  - **Đặt vé**: `SeatHoldServiceTest`, `SeatHoldHttpTest`, `ZoneTicketHoldTest`, `CheckoutTest`, `CheckoutCompletionTest`,
    `DiscountValidityTest`, `DiscountLifecycleTest`, `CartAndHistoryTest`, `SeatPricingTest`, `EventBookingFlowTest`.
  - **Mô hình khu (P7)**: `RoomZoneModelTest`, `ZoneLayoutGeneratorTest`, `ZonedSeatMapTest`, `MigrateRoomsToZonesTest`, `VenuePresetsTest`.
  - **Trang khách & tích hợp**: Public Catalog, sơ đồ ghế, nhãn suất diễn (`SessionLabelQueryTest`), bình luận, gợi ý tìm kiếm,
    avatar, Maps/thời tiết (`ExternalApiServicesTest`), chatbot (`ChatbotTest`, `ChatbotEscapingTest`), tiếng Việt, seeder.

---

## 5. Chạy lịch dọn ghế hết hạn

`routes/console.php` lên lịch `seats:release-expired` (`app/Console/Commands/ReleaseExpiredSeatHolds.php`) **mỗi phút**.
Môi trường dev: `php artisan schedule:work` (hoặc chạy tay `php artisan seats:release-expired`). Lệnh trả `showtime_seats`
hết hạn về `available` và xóa `zone_tickets` hết hạn (trừ vé gắn với đơn đã hủy — giữ lại để tra cứu).
Kể cả không chạy lịch, chỗ giữ quá hạn vẫn được coi là trống khi người khác giữ — lịch chỉ để dọn dữ liệu và hiển thị đúng.

---

## 6. Tương tác AJAX (Plan 4)

Tất cả endpoint nằm trong `routes/web.php` (session + CSRF qua header `X-CSRF-TOKEN`, helper `jsonHeaders()` trong `resources/js/app.js`).

| Tính năng | Route | Bảo vệ | Ghi chú |
| :--- | :--- | :--- | :--- |
| Danh sách bình luận | `GET /events/{event:slug}/comments` → `comments.index` | public, `throttle:60,1` | Cursor pagination theo `id` giảm dần, 10/trang: `{data, total, next_page_url}`. Sự kiện chưa `published` → 404; cursor bị sửa → 400. |
| Gửi bình luận | `POST /events/{event:slug}/comments` → `comments.store` | `auth`, `throttle:10,1` | `StoreCommentRequest`: trim, 3–1000 ký tự. 201 → `{comment}`. |
| Xóa bình luận | `DELETE /comments/{comment}` → `comments.destroy` | `auth`, `throttle:30,1`, `CommentPolicy@delete` | Chủ bình luận hoặc admin; người khác → 403. |
| Gợi ý tìm kiếm | `GET /search/suggestions?q=` → `search.suggestions` | public, `throttle:60,1` | `q` ≥ 2 ký tự: tối đa 6 sự kiện `published` khớp tiêu đề (escape `%`/`_`). `q` ngắn: `results: []` + `popular` (6 sự kiện mới). `q` > 100 ký tự → 422. |
| Ảnh đại diện | `PATCH /profile` → `profile.update` (multipart) | `auth` | jpg/png/webp ≤ 2MB, lưu disk `public` thư mục `avatars/`, xóa file cũ sau khi lưu. |

- **Bình luận** (`events/show.blade.php`, Alpine `eventComments`): trang đầu được nhúng sẵn bằng `@js(...)` (cùng dữ liệu
  `Comment::toPublicArray()` với API), nút "Xem thêm" gọi `comments.index`. Nội dung người dùng luôn render bằng `x-text`
  (không `x-html`). Khách thấy lời mời đăng nhập thay cho ô nhập. Huy hiệu ở đầu trang hiển thị số bình luận thật
  (schema không có cột điểm đánh giá nên đã bỏ "4.9/5" giả).
- **Tìm kiếm** (`layouts/site.blade.php`, Alpine `searchModal`): debounce 250ms, huỷ request cũ bằng `AbortController`,
  trạng thái đang tải / không có kết quả / lỗi; phím `Esc` đóng modal; Enter vẫn gửi form tới `events.index?q=`.
- **Avatar**: `User::avatar_url` (URL disk public, URL ngoài nếu cột chứa http(s), hoặc `null`) và `User::initials`
  (tối đa 2 chữ cái đầu, dùng `mb_substr`/`mb_strtoupper` cho tên tiếng Việt). Không gọi dịch vụ avatar bên ngoài.
  Cần chạy `php artisan storage:link` một lần trên mỗi máy để `/storage/avatars/...` hiển thị được.

---

## 7. Khán phòng: ma trận ghế và sơ đồ theo khu (P7)

`rooms.diagram_mode` chọn 1 trong 2 cách dựng phòng; `admin.rooms.create` hiện trang chọn kiểu (`admin/rooms/choose-layout.blade.php`).

- **Ma trận ghế** (`diagram_mode = seats`): builder `resources/views/admin/rooms/builder.blade.php` cho vẽ từng hàng/ghế/lối đi
  theo mẫu (`layout_preset`) hoặc ma trận tùy chỉnh; server kiểm tra ma trận (`Admin\RoomController::validatedSeatLayout`) rồi sinh ghế
  bằng `App\Services\RoomSeatGenerator::generateFromMatrix()` trong 1 transaction.
- **Sơ đồ theo khu** (`diagram_mode = zones`): builder `resources/views/admin/rooms/zoned-builder.blade.php` (kéo thả khối khu trên canvas,
  vị trí lưu ở `room_zones.meta`, chuẩn hóa bởi `App\Support\ZoneCanvas`; mẫu khu sẵn trong `App\Support\VenuePresets`).
  `App\Services\ZoneLayoutGenerator::generate()` tạo lại các khu và ghế thật cho khu `seated`/`table`; từ chối khi phòng đã có suất diễn.
  - Model: `App\Models\RoomZone` (`kind` = `seated|table|standing|admission`, `sellsByQuantity()`, `hasSeats()`; lưu/xóa khu
    tự gọi `Room::recalculateCapacity()`), `App\Models\ZoneTicket`, `Seat.zone_id`.
  - Khu `standing`/`admission` bán theo số lượng: mỗi vé giữ/bán là 1 dòng `zone_tickets`; `SeatHoldService` khóa dòng
    `room_zones` (`lockForUpdate()`) trước khi đếm số vé đang chiếm chỗ để không bán vượt `capacity`.
  - Sơ đồ phía khách: `resources/views/components/seat-map-zoned.blade.php` (dữ liệu từ `SeatMapPresenter`).
- **Nhân bản khán phòng**: `POST /admin/rooms/{room}/duplicate` (`RoomController::duplicate`) chép phòng, các khu và toàn bộ ghế
  sang phòng mới để sửa sơ đồ mà không đụng phòng đã có suất diễn.
- **Chuyển phòng cũ sang khu**: `php artisan rooms:migrate-zones --dry-run` (`app/Console/Commands/MigrateRoomsToZones.php`) gom ghế
  legacy thành khu theo tiền tố hàng + loại ghế và xóa ghế GA ảo chưa từng bán/giữ; `--dry-run` chỉ in báo cáo rồi rollback.
- **Nhãn suất diễn**: `Showtime::session_label` / `session_short_label` — sự kiện chỉ có 1 suất hiện "Suất Diễn Duy Nhất";
  nhiều suất thì đánh số theo danh mục (concert: "Đêm 1/2", triển lãm: "Ngày 1/2", sân khấu kịch: "Suất 1/2", còn lại "Suất Diễn N").
  `Showtime::primeSessionLabels()` nạp sẵn danh sách suất để tránh N+1.

---

## 8. Giỏ vé, thanh toán giả lập & mã giảm giá

- **Giữ chỗ** (`App\Services\SeatHoldService`): 10 phút, tối đa 8 chỗ, 1 suất/người; khóa dòng `users` để 2 request song song
  của cùng một người không cùng vượt giới hạn. **Một đồng hồ chung cho giỏ**: vé giữ thêm dùng `held_until` của vé giữ sớm nhất
  (không gia hạn) nên cả giỏ hết hạn cùng lúc.
- **Thanh toán** (`App\Services\CheckoutService::checkout`, route `POST /checkout`, `throttle:10,1`): khóa lại ghế/vé đang giữ,
  kiểm tra sự kiện còn `published` và suất chưa bắt đầu, tạo đơn `confirmed` với `payment_method` ∈ `vietqr|momo|zalopay|card`.
- **Thông tin người nhận vé**: `App\Http\Requests\CheckoutRequest` bắt buộc `buyer_name`, `buyer_phone` (regex SĐT), `buyer_email`
  tùy chọn; lưu vào `bookings.buyer_*` (có thể khác hồ sơ tài khoản).
- **Mã giảm giá**: `POST /cart/discount` (`CartController::applyDiscount`, `throttle:20,1`) xem trước số tiền giảm trên tạm tính
  của giỏ; khi thanh toán `App\Services\DiscountService::resolve(..., lockForUpdate: true)` kiểm tra lại phía server, khóa dòng
  `discounts` rồi tăng `used_count` để 2 đơn đồng thời không vượt `max_uses`. Quy tắc nằm ở model:
  `Discount::unusableReason()` trả lý do tiếng Việt (ngừng áp dụng, chưa tới ngày, hết hạn — còn hiệu lực đến hết `end_date`,
  hết lượt, chưa đạt `min_order_value`), `Discount::calculateDiscount()` tính theo `percentage` (có trần `max_discount_amount`) hoặc `fixed`.
- **QR minh họa**: giỏ vé (`resources/views/cart/index.blade.php`, getter `demoQrUrl` trong `resources/js/app.js`) hiển thị QR chứa
  dòng chữ "DEMO THANH TOAN GIA LAP" — **không phải mã thanh toán thật** (không phải payload VietQR/EMVCo hay ví), quét bằng app
  ngân hàng không tạo lệnh chuyển tiền. QR trên vé trong lịch sử (`bookings/history.blade.php`) chỉ mã hóa `booking_code`.
- **Lịch sử đơn** (`GET /bookings`): hiển thị trạng thái thật của đơn (`Booking::statusLabel()`), gồm cả vé chọn ghế và vé theo khu.

---

## 9. Quản trị: trạng thái đơn, chặn xóa, tài khoản, báo cáo

- **Đổi trạng thái đơn** (`PATCH /admin/bookings/{booking}/status`, `Admin\BookingController::updateStatus`): chỉ `confirmed` ↔ `cancelled`,
  chạy trong transaction và khóa dòng đơn + ghế/vé liên quan.
  - Hủy: trả ghế về `available`, vé theo khu chuyển thành lượt giữ đã hết hạn (giữ dòng để tra cứu); bỏ qua ghế/vé đã thuộc đơn
    `confirmed` khác.
  - Xác nhận lại: **từ chối** nếu có ghế/vé đã bị người khác giữ hoặc mua, thuộc đơn khác, không còn tồn tại, hoặc khu không còn đủ sức chứa.
  - Đồng bộ lượt dùng mã giảm giá: hủy → hoàn 1 lượt `used_count`; xác nhận lại → dùng lại 1 lượt, mã hết lượt thì từ chối.
- **Chặn xóa**: sự kiện / suất diễn đã phát sinh đơn (kể cả đơn đã hủy — `Showtime::hasBookingItems()`, `BookingItem::scopeForShowtimes`)
  không xóa được; suất có đơn không đổi được sự kiện, suất có ghế giữ/bán không đổi được khán phòng; khán phòng có suất diễn không xóa
  được; danh mục còn sự kiện không xóa được. Người dùng **không tự xóa được tài khoản** khi đã có lịch sử đặt vé
  (`ProfileController::destroy`, vì `bookings.user_id` cascade sẽ xóa luôn đơn đã thanh toán).
- **Tài khoản**: admin khóa/mở khóa bằng AJAX (không tự khóa chính mình), đổi role admin/user (không tự đổi role mình).
  **Đặt lại mật khẩu** (`UserController::resetPassword`) sinh mật khẩu tạm ngẫu nhiên `Str::password(10)`, chỉ hiển thị một lần,
  đổi `remember_token` và xóa phiên trong bảng `sessions` để đăng xuất mọi thiết bị.
- **Dashboard & Báo cáo** (`Admin\DashboardController`, `Admin\ReportController`, bản in `admin/reports/pdf.blade.php`):
  số liệu từ `App\Services\SalesReportService` bằng truy vấn tổng hợp. Doanh thu = tổng `booking_items.price` của đơn `confirmed`
  (cả vé chọn ghế lẫn vé theo khu); **doanh thu theo sự kiện/danh mục đã trừ giảm giá** bằng cách phân bổ `discount_amount` theo tỉ lệ giá
  từng vé, nên tổng khớp `bookings.total_price`.

---

## 10. Tích hợp bên ngoài

| Dịch vụ | Biến `.env` | File | Khi thiếu key / lỗi |
| :--- | :--- | :--- | :--- |
| Google Maps | `GOOGLE_MAPS_EMBED_KEY` (tùy chọn) | `app/Support/MapEmbed.php` | Có key dùng Maps Embed API; không có thì dùng link nhúng công khai `maps.google.com/maps?q=…&output=embed`; luôn kèm nút "Chỉ đường". |
| OpenWeatherMap | `OPENWEATHER_API_KEY` (tùy chọn) | `app/Services/WeatherService.php` | Dự báo 5 ngày/3 giờ, chọn mốc gần giờ diễn nhất (lệch ≤ 3 giờ), timeout 5 giây, cache 30 phút theo tọa độ (lỗi nhớ 5 phút). Thiếu key / API lỗi / suất ngoài 5 ngày → **ẩn hẳn** khối thời tiết. Chỉ áp dụng địa điểm ngoài trời (`Room::isOutdoor()`: mẫu chuẩn hóa về `outdoor_concert`). |
| Google Gemini (chatbot) | `GEMINI_API_KEY` (tùy chọn), `GEMINI_MODEL` (mặc định `gemini-2.5-flash`) | `app/Services/GeminiChatService.php`, `ChatbotController`, `resources/views/components/ai-chatbot.blade.php` | `POST /chatbot/message` (`throttle:30,1`). Prompt kèm tối đa 8 sự kiện `published` từ DB; câu trả lời Markdown được escape HTML trước khi render. Thiếu key → server trả `{fallback: true}`; thiếu key hoặc request lỗi mạng/HTTP → widget dùng bộ trả lời theo từ khóa viết sẵn (`generateBotResponse`, chạy offline trong trình duyệt). |
| Google OAuth 2.0 | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | `app/Http/Controllers/Auth/GoogleAuthController.php`, `routes/auth.php` | Đăng nhập/Đăng ký 1 chạm. Tự động liên kết khi email đã xác minh (`email_verified = true`). Lỗi mạng/callback trả về login kèm thông báo tiếng Việt. |

Bản đồ và thời tiết hiển thị ở `resources/views/events/show.blade.php`; admin nhập vĩ độ/kinh độ trong form khán phòng (cả ma trận ghế lẫn sơ đồ khu).

---

## 11. Đăng nhập / Đăng ký Google (OAuth 2.0)

- **Thư viện**: `laravel/socialite`
- **Cơ chế**:
  - Người dùng bấm nút **Tiếp tục với Google** ở trang `/login` hoặc `/register`.
  - Google chuyển hướng về `/auth/google/callback`.
  - Nếu đã có tài khoản với `google_id` này: đăng nhập ngay.
  - Nếu đã có tài khoản với email tương ứng và Google xác nhận `email_verified = true`: tự động liên kết `google_id`, giữ nguyên mật khẩu cũ và lịch sử vé.
  - Nếu là tài khoản mới: tạo tài khoản mới với role `user`, mật khẩu ngẫu nhiên bảo mật, và `email_verified_at = now()`.
  - Nếu tài khoản đang bị khóa (`is_active = false`): từ chối đăng nhập và trả thông báo tiếng Việt.

