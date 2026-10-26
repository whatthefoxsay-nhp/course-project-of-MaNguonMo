# Kiến trúc hệ thống (Living Architecture Document)

Cập nhật file này mỗi khi kiến trúc hoặc yêu cầu dự án thay đổi so với spec gốc. Nguồn sự thật ban đầu:
`docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md`.

---

## 1. Trạng thái hiện tại
- **Nền tảng**: Laravel 11.56.1, PHP 8.3 (Laragon), MySQL 8.4 (Database: `doanmnm_ticket`), Pest 4, Laravel Breeze (Blade Stack), Vite 6.
- **Phân quyền & Bảo mật**: `spatie/laravel-permission` với 2 role (`admin`, `user`), middleware `role:admin` bảo vệ toàn bộ tuyến đường `/admin/*`.
- **Định hướng sản phẩm**: Chuyên biệt hóa **100% cho Nền tảng Đặt vé Sự Kiện (Event Ticketing)** — bao gồm: Live Concert, Hòa Nhạc Giao Hưởng, Hội Thảo & Diễn Đàn Doanh Nghiệp (Summit/Expo), Triển Lãm Nghệ Thuật & Nhạc Kịch.
- **Tiến độ hoàn thành**:
  - **Database & Backend Models (Phase 1)**: Đã hoàn tất 10 migration files (`users`, `categories`, `movies`/`events`, `rooms`/`venues`, `seats`, `showtimes`, `showtime_seats`, `bookings`, `booking_items`, `comments`) cùng 10 Eloquent Models có quan hệ chặt chẽ.
  - **Quản lý Tài khoản Người Dùng (Admin User Management)**: `UserController` xử lý tìm kiếm đa năng (Tên/Email/SĐT), lọc theo Role (Admin/User), lọc theo Trạng thái (Active/Locked), phân trang (Pagination), và AJAX Toggle Khóa/Mở khóa tài khoản bảo mật kèm hộp thoại xác nhận.
  - **Admin CRUD (Plan 1)**: danh mục, sự kiện (upload poster), suất diễn (chặn trùng giờ, tự sinh ghế theo suất), bảo vệ khán phòng có vé bán ra.
  - **Engine đặt vé (Plan 3)**: giữ ghế AJAX chống đặt trùng, giỏ vé, thanh toán giả lập, lịch sử — đã bỏ hoàn toàn dữ liệu giả DemoCatalog.
  - **Giao diện & Trải nghiệm (Luxury Light Theme)**: Hoàn thiện hệ thống giao diện tone sáng sang trọng với bảng màu tương phản cao `#000000`, `#F5F5DC`, `#FFFFFF`, `#C08497`, `#D4AF37`, `#3A5A40`, `#CC0000`.

---

## 2. Các thay đổi & Cập nhật so với Spec ban đầu

| Ngày | Hạng mục | Chi tiết thay đổi | Lý do kỹ thuật & Yêu cầu |
| :--- | :--- | :--- | :--- |
| **2026-09-07** | **Frontend CSS** | Dùng **Tailwind CSS + Vite** thay vì Bootstrap 5 | Tránh xung đột với Laravel Breeze Blade stack, tận dụng tối đa hệ sinh thái utility-first. |
| **2026-09-18** | **Định hướng dịch vụ** | Chuyển toàn bộ sang **Vé Sự Kiện (Events / Concerts / Summits / Exhibitions)** | Chuẩn hóa thông điệp sản phẩm theo đúng đề bài đặt vé sự kiện chuyên nghiệp, loại bỏ thuật ngữ phim chiếu rạp. |
| **2026-09-18** | **Giao diện & Màu sắc** | Áp dụng **Luxury Light Theme** (Nền trắng #FFFFFF & Beige #F5F5DC, Text đen #000000) | Tối ưu độ tương phản, chữ và các thành phần nổi bật, loại bỏ hiện tượng chìm màu trên dark theme. |
| **2026-09-18** | **Component Layouts** | Tạo bộ proxy components trong `resources/views/components/layouts/` | Tương thích song song cả hai cú pháp Blade: `<x-admin-layout>` và `<x-layouts.admin>`. |
| **2026-09-20** | **Database Schema & Models** | Đổi bảng `movies` thành `events`, khóa ngoại `movie_id` $\rightarrow$ `event_id` trên `showtimes` và `comments`, tạo Model `Event` và Controller `EventController` | Chuẩn hóa tầng dữ liệu 100% hướng sự kiện (Event-Centric), loại bỏ hoàn toàn dấu vết phim rạp trong schema. |
| **2026-09-21** | **Chi tiết sự kiện** | Thêm cột `events.is_seated` (bool) và `events.details` (JSON: địa điểm, lineup, lịch trình, BTC, quy định) | Giữ nguyên giao diện chi tiết sự kiện giàu thông tin của nhóm mà không tạo thêm 5–6 bảng phụ. |
| **2026-09-21** | **An toàn dữ liệu** | Không sinh lại ghế / không xóa khán phòng khi đã có suất diễn; không xóa danh mục còn sự kiện; không xóa sự kiện/suất diễn đã bán vé | Các FK đang `cascade` — xóa nhầm sẽ mất vé đã bán. |
| **2026-09-21** | **Vé đứng (GA)** | Preset `mega_concert` sinh thêm hàng ghế ẩn `GA` loại `standing_pit` | Vé đứng dùng chung engine `showtime_seats`, server tự gán chỗ. |
| **2026-09-21** | **Engine đặt vé** | Giữ ghế = `showtime_seats.status=held` (+`held_by_user_id`, `held_until` 10 phút, `price_override` = giá chốt); khóa dòng `lockForUpdate()` trong transaction, tất cả-hoặc-không; mỗi user chỉ giữ ghế của 1 suất; tối đa 8 chỗ; lệnh `seats:release-expired` chạy mỗi phút | Đúng spec mục 3, không có bảng giỏ hàng riêng. |
| **2026-09-21** | **Thanh toán** | Thanh toán giả lập: chọn VietQR/MoMo/ZaloPay/Thẻ, server tạo booking `confirmed`, lưu `bookings.payment_method` | Spec mục 5: không tích hợp cổng thật. |
| **2026-09-21** | **Đồng bộ ghế** | Polling `GET /showtimes/{id}/seat-status` mỗi 15 giây | Spec mục 5: không dùng WebSocket. |
| **2026-09-23** | **Quản trị hệ thống** | Làm sạch dữ liệu thực trên Dashboard & Báo cáo (xóa bỏ số liệu giả lập); Hoàn thiện chi tiết đơn vé & giải phóng ghế khi hủy đơn; Khởi tạo bảng & Model `Discount` lưu trữ thật; Bổ sung đổi vai trò Admin/User và đặt lại mật khẩu | Đảm bảo phân hệ Admin vận hành 100% trên cơ sở dữ liệu thật MySQL, không còn mock/alert giả lập. |
| **2026-09-23** | **Public Catalog DB (Plan 2)** | Toàn bộ trang chủ, danh sách sự kiện, chi tiết sự kiện và sơ đồ ghế đọc trực tiếp từ MySQL; Thống nhất định giá qua `TicketTiers`; Trình bày sơ đồ ghế động qua `SeatMapPresenter`; Hỗ trợ cả Sân vận động và Khán phòng nhà hát | Loại bỏ hoàn toàn phụ thuộc vào `DemoCatalog` cho luồng xem sự kiện và sơ đồ ghế. |
| **2026-10-26** | **Sơ đồ theo khu vực (Plan 8)** | Thêm bảng `seat_zones`; `seats.zone_id` + `seats.pos_col`. Thay 6 loại ghế "concert" cố định, `SeatMapPresenter` và hạng vé VIP/Standard/Combo bằng khu do admin đặt (xem mục 2a) | Mỗi loại sự kiện có cách bố trí khác nhau; admin tự đặt tên, giá, màu từng khu. |
| **2026-10-26** | **Đổi trạng thái đơn (admin)** | Chỉ cho `pending→confirmed`, `pending→cancelled`, `confirmed→cancelled`; xác nhận bị từ chối nếu ghế đã thuộc đơn khác hoặc người khác đang giữ | Chặn bán trùng ghế khi xác nhận lại đơn đã hủy. |

### 2a. Sơ đồ chỗ ngồi theo khu vực

```
rooms 1─n seat_zones 1─n seats 1─n showtime_seats n─1 showtimes
seat_zones(id, room_id, code, name, kind[seated|table|standing], level, sort_order,
           surcharge, color, gate, description, perks json, capacity)
seats(+ zone_id, + pos_col)   -- pos_col = vị trí ô trong hàng; ô trống giữa 2 cột = lối đi
```

- **3 kiểu khu**: `seated` (ghế đánh số, chọn trên sơ đồ), `table` (mỗi `row_label` là một bàn, ghế quanh bàn, có nút "Chọn cả bàn"), `standing` (khu đứng / vào cửa tự do: `capacity` ghế ẩn, `row_label` = mã khu, `pos_col` null, khách chọn số lượng).
- **Giá** 1 vé = `showtimes.base_price` + `seat_zones.surcharge` (tối thiểu 0), chốt vào `showtime_seats.price_override` lúc giữ chỗ. `TicketTiers` chỉ còn dùng cho ghế cũ chưa có khu.
- **6 mẫu** (`App\Support\LayoutTemplates`): `mega_concert` (concert sân vận động: 2 Fanzone đứng + SVIP + VIP mặt sàn + khán đài C/A/D + Skybox), `theater_hall` (nhà hát: Stalls / Dress Circle / Gallery, lối đi giữa), `convention_center` (hội trường: VIP Keynote + 3 khối), `banquet_hall` (workshop/gala: bàn 8 và 6 ghế), `open_space` (triển lãm/festival: chỉ khu vào cửa theo sức chứa), `custom_grid` (lưới tùy chỉnh).
- **Đặt vé**: sự kiện `is_seated` → khu `seated`/`table` chọn ghế, khu `standing` chọn số lượng; sự kiện không sơ đồ → mọi khu chọn số lượng, server gán ghế trống đầu tiên của khu (`SeatHoldService`, khóa dòng như cũ). API giữ chỗ: `seat_ids[]` + `zones[id_khu] = số vé`.
- **Vẽ sơ đồ** (`App\Support\SeatMap` + `<x-seat-map>`): sân khấu theo preset ở trên; khu xếp theo `level` (gần sân khấu → xa), cùng `level` nằm cạnh nhau theo `sort_order`.
- **Admin** (`RoomLayoutRequest`): kiểm tra JSON sơ đồ (mã khu, nhãn hàng không trùng, ô trỏ đúng khu, sức chứa khu đứng 1–2.000, tổng ≤ 5.000 chỗ). Địa điểm đã có suất diễn chỉ sửa được tên/giá/màu/cổng/mô tả của khu, không đổi ghế.
- **Bỏ**: vé "Combo nhóm 2 người" (`combo_pass`) và hạng VIP Pass/Standard Pass cố định — thay bằng khu của địa điểm.

---

## 3. Bảng Màu & Phân Bổ Token (Design Tokens)

- **`#FFFFFF` (Canvas Base)**: Nền trắng ngà chính, card bề mặt dữ liệu, modal panels, input background.
- **`#F5F5DC` (Warm Beige / Cream)**: Khung toolbar bộ lọc, container thẻ ưu đãi, ô đăng nhập mẫu.
- **`#000000` (Obsidian Charcoal)**: Typography tiêu đề chính đậm nét, Sidebar quản trị Admin, nút bấm chính, Mega Footer.
- **`#C08497` (Rose Taupe)**: Nút hành động CTA "+ Đặt vé", badge nổi bật.
- **`#D4AF37` (Antique Gold)**: Nút Lọc dữ liệu, Huy hiệu VIP, Giá vé, Rating 5 sao.
- **`#3A5A40` (Forest Green / Sage)**: Badge & Nút "Hoạt động" / "Mở khóa tài khoản", Thông báo thành công (Success Toast).
- **`#CC0000` (Crimson Red)**: Badge & Nút "Đã bị khóa" / "Khóa tài khoản", Cảnh báo lỗi.

---

## 4. Kiểm thử & Độ bao phủ (Test Suite)
- Toàn bộ tính năng đều được kiểm thử bằng Pest PHP (`php artisan test`): **245 tests / 1692 assertions (Pass 100%, 2026-10-26)**.
- Bao gồm Feature Test cho:
  - Admin: Dashboard Real Data, Booking Management & Seat Release, Discount CRUD, Room Safety, Locked User, Category CRUD, Event CRUD, Showtime CRUD, User Role & Password Management.
  - Public & Engine: Public Catalog, Seat Map Page, Ticket Tiers & Seat Pricing, SeatHoldService, SeatHoldHttp, Checkout, Cart & Booking History.

---

## 5. Chạy lịch dọn ghế hết hạn

Môi trường dev: `php artisan schedule:work` (hoặc chạy tay `php artisan seats:release-expired`).
Kể cả không chạy lịch, ghế giữ quá hạn vẫn được coi là trống khi người khác giữ — lịch chỉ để dọn dữ liệu và hiển thị đúng.

