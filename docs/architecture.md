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
- Toàn bộ tính năng đều được kiểm thử bằng Pest PHP (`php artisan test`): **80 tests / 283 assertions (Pass 100%)**.
- Bao gồm Feature Test cho Admin Room Safety, Locked User, Category CRUD, Event CRUD, Showtime CRUD.
