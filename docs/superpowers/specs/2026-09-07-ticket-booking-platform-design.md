# Thiết kế: Website Quản lý Đặt vé (Movie/Event Ticketing)

- **Ngày**: 2026-09-07
- **Môn học**: Mã nguồn mở (lập trình PHP)
- **Đề tài**: Xây dựng website Quản lý đặt vé (tương tự TicketBox)
- **Trạng thái**: Approved by user, ready for implementation planning

## 1. Bối cảnh & mục tiêu

Đồ án nhóm (chủ dự án + 3+ thành viên), có sự hỗ trợ của hai AI agent chạy song song
trên cùng máy/cùng repo:

- **Claude Code** — vai trò *supervisor*: viết plan, review code, merge PR, sửa lỗi Codex
  không tự sửa được.
- **Codex CLI** — vai trò *executor*: implement đúng theo plan Claude viết, mở PR.

Deadline: hơn 1 tháng (thoải mái) → ưu tiên làm đủ core đúng rubric, sau đó đầu tư report
và phần mở rộng (AJAX/API) để tối đa điểm "sáng tạo mở rộng".

### Rubric chấm điểm (đầu vào ràng buộc thiết kế)

| Tiêu chí | Trọng số | Mô tả |
|---|---|---|
| Hoàn thiện chức năng chính | 40% | CRUD, auth, validation, pagination, search |
| Áp dụng công nghệ Laravel | 25% | Eloquent ORM, Migration, Blade, Middleware |
| Thiết kế & trải nghiệm | 15% | Giao diện đẹp, responsive, thân thiện |
| Báo cáo & thuyết trình | 10% | Báo cáo đầy đủ (ERD, Use Case, mô tả chức năng, ảnh chụp), thuyết trình rõ ràng |
| Sáng tạo & mở rộng | 10% | AJAX, API, tính năng nâng cao |

Thiết kế dưới đây được suy ra trực tiếp từ bảng này — không tiêu chí nào bị bỏ qua.

## 2. Công nghệ

- **Backend**: PHP 8.3 (Laragon), Laravel 11
- **DB**: MySQL 8.4 (Laragon)
- **Phân quyền**: `spatie/laravel-permission` (roles: `admin`, `user`) — middleware `role:admin`
  bảo vệ toàn bộ khu vực quản trị
- **Auth**: Laravel Breeze (đăng ký/đăng nhập/quên mật khẩu chuẩn)
- **View**: Blade + Bootstrap 5 (responsive, không cần build pipeline phức tạp)
- **AJAX**: Vanilla JS `fetch()` — giỏ vé (giữ ghế), tìm kiếm gợi ý, bình luận không reload
- **Upload**: Laravel Storage (disk `public`) — poster phim/sự kiện, avatar người dùng
- **API ngoài**:
  - Google Maps Embed API — hiển thị vị trí phòng chiếu/địa điểm sự kiện
  - OpenWeatherMap API — hiển thị thời tiết cho sự kiện ngoài trời (theo lat/lng phòng/địa điểm)
- **Test**: Pest (feature test cho auth, CRUD, và race-condition giữ ghế)

## 3. Mô hình dữ liệu (ERD tóm tắt)

Quyết định thiết kế quan trọng: **Movie và Event dùng chung một entity** (`movies`, cột
`type` = `movie|event`) và **dùng chung một engine đặt vé theo sơ đồ ghế** (`rooms`/`seats`),
vì cơ chế đặt vé của cả hai giống hệt nhau (chọn ghế theo suất). Điều này giảm trùng lặp
CRUD và khớp với cách đề bài dùng cụm từ "phim/sự kiện" như hai biến thể của cùng một khái niệm.

```
users            (id, name, email, password, phone, avatar, ...)
roles/permissions        -- do spatie/laravel-permission tự tạo
categories       (id, name)                              -- thể loại phim / loại sự kiện
movies           (id, type[movie|event], title, description,
                   poster_path, category_id, duration_minutes NULL,
                   status, created_at, ...)
rooms            (id, name, address, latitude NULL, longitude NULL, capacity)
seats            (id, room_id, row_label, seat_number, type[normal|vip])
showtimes        (id, movie_id, room_id, start_time, end_time, base_price)
showtime_seats   (id, showtime_id, seat_id, status[available|held|booked],
                   held_by_user_id NULL, held_until NULL, price_override NULL)
bookings         (id, user_id, booking_code, total_price,
                   status[confirmed|cancelled], created_at)
                 -- không có trạng thái "paid": checkout là xác nhận giả lập (mục 5),
                 -- "confirmed" nghĩa là đã hoàn tất bước xác nhận giả lập đó
booking_items    (id, booking_id, showtime_seat_id, price)
comments         (id, user_id, movie_id, content, created_at)
```

**Không có bảng `cart_items` riêng** — một dòng `showtime_seats` ở trạng thái `held`
gắn với `held_by_user_id` + `held_until` CHÍNH LÀ giỏ hàng của user đó. Giảm một tầng
state trùng lặp, vẫn đáp ứng yêu cầu "Tích hợp AJAX cho giỏ hàng".

### Xử lý tránh double-booking (race condition)

Khi user bấm chọn ghế (AJAX): transaction + `lockForUpdate()` trên dòng `showtime_seats`,
chỉ set `held` nếu đang `available`; nếu không → trả lỗi "ghế đã được giữ". Ghế `held` hết
hạn (`held_until` quá giờ) được giải phóng bằng scheduled command
(`schedule:run` mỗi phút) chuyển lại `available`. Đây vừa là yêu cầu kỹ thuật thật, vừa là
điểm tốt để viết trong báo cáo (validation + xử lý concurrency).

## 4. Luồng nghiệp vụ chính

- **User**: duyệt phim/sự kiện (search + filter theo category + pagination) → xem chi tiết
  (bản đồ + thời tiết nếu là event ngoài trời) → chọn suất chiếu → chọn ghế trên sơ đồ (AJAX
  giữ ghế) → checkout (mock — không tích hợp cổng thanh toán thật, ngoài phạm vi "phiên bản
  đơn giản") → xem lịch sử đặt vé → bình luận phim/sự kiện (AJAX).
- **Admin**: CRUD categories, movies/events, rooms, seats, showtimes; xem danh sách toàn bộ
  booking; quản lý user (đổi role, khóa tài khoản).

## 5. Ngoài phạm vi (non-goals, tránh scope creep)

- Không tích hợp cổng thanh toán thật (VNPay/Momo...) — checkout là xác nhận giả lập.
- Không dùng WebSocket/broadcasting real-time cho cập nhật ghế — AJAX polling/refresh khi
  submit là đủ theo đúng yêu cầu đề bài (chỉ yêu cầu AJAX, không yêu cầu realtime).
- Không đa ngôn ngữ (chỉ tiếng Việt).

## 6. Quy trình cộng tác nhiều agent + nhóm 3+ người

**Không chia thư mục cố định cho Claude vs Codex.** Trong Laravel một feature luôn động
đến nhiều thư mục cùng lúc (route + controller + view + migration) — chia theo thư mục sẽ
tạo bàn giao gượng gạo và không phản ánh đúng ranh giới trách nhiệm thực tế.

Thay vào đó, đơn vị chia việc là **branch theo feature**, không phải thư mục:

- Mỗi plan file (`docs/plans/NNN-<feature>.md`) ↔ một branch `feature/<module>-<mô tả>`
  ↔ một PR vào `main` trên GitHub.
- **Claude** viết plan, review PR (skill `code-review` + `security-review`), merge, sửa khi
  Codex không tự sửa được.
- **Codex** implement đúng theo plan đã duyệt, mở PR, sửa theo review comment.
- **Thành viên người thật** dùng git flow bình thường: clone, branch riêng, PR — không cần
  worktree vì họ không chạy đồng thời trong cùng thư mục vật lý với Claude.
- **Claude chạy trong git worktree riêng** (checkout riêng của cùng repo, ví dụ
  `../DoAnMNM-supervisor`) để không đụng độ khi Codex đang chạy đồng thời trên working tree
  chính — cả hai có thể chạy test/composer/artisan song song an toàn.

### Bộ nhớ dài hạn xuyên session

- `CLAUDE.md` (gốc repo) — hướng dẫn riêng cho Claude: vai trò supervisor, checklist review,
  lệnh chạy test/migrate, quy ước code.
- `AGENTS.md` (gốc repo) — Codex CLI tự động đọc file này — quy ước thực thi, cách chạy
  migration/test, branch naming, giới hạn phạm vi sửa.
- `docs/architecture.md` — ERD, sơ đồ module, decision log, cập nhật liên tục khi kiến trúc
  đổi.
- `docs/plans/` — mỗi feature một file plan (do skill `writing-plans` tạo), theo dõi trạng
  thái todo/in-progress/done.
- Plugin `claude-mem` (đã cài sẵn cho Claude) — bộ nhớ phiên riêng, bổ sung cho docs (docs
  dành cho người + cả hai agent đọc; claude-mem là bộ nhớ tìm kiếm được riêng của Claude).

## 7. Cây thư mục

```
DoAnMNM/
├── app/
│   ├── Http/Controllers/{Admin,User}/
│   ├── Http/Middleware/
│   ├── Models/
│   └── Services/            # BookingService, SeatHoldService...
├── database/{migrations,seeders,factories}/
├── resources/
│   ├── views/{admin,user,components,layouts}/
│   ├── js/                  # ajax: seat-hold, search, comment
│   └── css/
├── routes/{web.php,api.php}
├── tests/{Feature,Unit}/
├── docs/
│   ├── architecture.md
│   ├── erd.md
│   ├── plans/
│   ├── report/               # ERD hình, use case, ảnh chụp màn hình cho báo cáo cuối
│   └── superpowers/specs/
├── CLAUDE.md
├── AGENTS.md
└── README.md
```

## 8. Phân kỳ triển khai (tổng quan — plan chi tiết từng phase sẽ do skill writing-plans tạo riêng)

1. **Phase 0 — Setup nền tảng** (Claude làm, tránh nhiều người cùng khởi tạo xung đột):
   scaffold Laravel vào thư mục hiện có (đã có `docs/`, `CLAUDE.md` sẵn — cần dùng cách
   scaffold vào thư mục tạm rồi merge), cài Breeze + spatie/laravel-permission, seed 2 role,
   layout gốc, `CLAUDE.md`/`AGENTS.md`/`docs/` hoàn chỉnh, commit đầu tiên, tạo remote GitHub.
2. **Phase 1 — CRUD lõi + phân quyền**: categories, movies/events, rooms, seats, showtimes;
   middleware `role:admin`.
3. **Phase 2 — Engine đặt vé**: sơ đồ ghế theo suất chiếu, AJAX giữ/nhả ghế, checkout →
   booking, xử lý race condition, scheduled command giải phóng ghế hết hạn giữ.
4. **Phase 3 — Lịch sử & tương tác**: lịch sử đặt vé, search + pagination + validation, bình
   luận AJAX.
5. **Phase 4 — Upload & UI polish**: poster/avatar, responsive toàn site.
6. **Phase 5 — Tích hợp API ngoài**: Google Maps (địa điểm), OpenWeatherMap (thời tiết sự kiện).
7. **Phase 6 — Kiểm thử & báo cáo**: Pest test (auth, CRUD, race condition giữ ghế), hoàn
   thiện `docs/report/` (ERD, use case, ảnh chụp), chuẩn bị thuyết trình.

## 9. Kiểm thử

- Feature test: đăng ký/đăng nhập, CRUD admin (mỗi entity ít nhất 1 test), luồng đặt vé
  end-to-end, và test riêng cho race condition giữ ghế (2 request đồng thời chỉ 1 thành công).
- Không yêu cầu unit test cho từng model — ưu tiên feature test theo luồng nghiệp vụ vì đây
  là đồ án môn học, thời gian có hạn.
