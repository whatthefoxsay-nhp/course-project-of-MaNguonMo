# Plan 7 — Quản lý khán phòng theo khu vực (Zone-based Venue)

> **For agentic workers:** Thực hiện từng task, mỗi task = chạy test liên quan + 1 commit. Steps dùng checkbox (`- [ ]`).

**Goal:** Coi khán phòng là tập hợp nhiều **khu**, mỗi khu có cách bán vé riêng. Bỏ ghế ảo cho khu đứng.
Sức chứa phòng = tổng sức chứa các khu (không nhập tay). Preset chỉ là cấu hình khởi tạo, admin chỉnh từng khu sau đó.

**Nhánh:** `feature/venue-zone-layout` (đã có `room_zones`, `seats.zone_id`, chưa merge `main`).

## Quyết định đã chốt (11/10/2026)

| # | Vấn đề | Quyết định |
|---|---|---|
| D1 | Tồn kho khu đứng / vé vào cửa | Bảng mới `zone_tickets`: 1 dòng = 1 vé đang giữ/đã bán. `booking_items.showtime_seat_id` thành nullable + thêm `zone_ticket_id`. Chống bán vượt bằng `lockForUpdate()` trên dòng `room_zones` rồi đếm vé còn hiệu lực. |
| D2 | Chuyển phòng cũ | Tạo khu cho **mọi** phòng; gom ghế theo tiền tố hàng + loại ghế → gán `zone_id`. Ghế ảo GA **chưa** gắn `booking_items`/đang giữ → xóa, cộng vào sức chứa khu đứng. Ghế GA đã bán/giữ → giữ lại làm vé cũ (legacy), vẫn tính vào sức chứa. |
| D3 | Khu bàn/nhóm | Mỗi bàn = 1 nhóm ghế thật (`row_label` = mã bàn). Khách chọn từng chỗ + nút "Đặt cả bàn". |
| D4 | Lệch sức chứa | Lệnh `rooms:migrate-zones` có `--dry-run` in báo cáo từng phòng; duyệt xong chạy thật. Sau đó `rooms.capacity` luôn = tổng khu. |

## Mô hình dữ liệu

`room_zones.kind` ∈ `seated | standing | table | admission`

| kind | Ghế thật (`seats`) | Sức chứa | Bán vé |
|---|---|---|---|
| `seated` | Có, `zone_id` = khu | = số ghế active | Chọn ghế trên sơ đồ |
| `table` | Có, mỗi bàn 1 `row_label` | = số ghế | Chọn chỗ / cả bàn |
| `standing` | **Không** | nhập tay | Theo số lượng → `zone_tickets` |
| `admission` | **Không** | nhập tay | Theo số lượng → `zone_tickets` |

`zone_tickets`: `id, showtime_id, zone_id, status(held|booked), held_by_user_id, held_until, price, timestamps`,
index `(showtime_id, zone_id, status)`. Vé giữ quá hạn coi như không tồn tại (giống `ShowtimeSeat::publicStatus`).

## Global Constraints

- **Không sửa migration đã merge** (`booking_items`, `seats`...): mọi đổi schema = migration mới.
  Hai migration `2026_10_11_00000{1,2}` chưa merge nhưng vẫn thêm migration mới cho nhất quán.
- Giữ nguyên quy tắc: giữ chỗ 10 phút, tối đa 8 vé/lượt, 1 user chỉ giữ 1 suất, JSON lỗi tiếng Việt 422.
- `TicketTiersTest` hiện có phải tiếp tục xanh (`standing_pit` vẫn là pass tier, nhưng giờ cấp vé từ `zone_tickets`).
- Test chạy SQLite → test chống bán vượt bằng request nối tiếp.
- `npm run build` sau task có sửa JS; `php artisan test` xanh trước PR.

## Tasks

### Task 1 — Schema
- [ ] Migration `2026_10_12_000001_extend_room_zones_kinds`: thêm cột `ticket_tier` (string, nullable), `meta` (json, nullable — vd. số chỗ/bàn); comment kind mới.
- [ ] Migration `2026_10_12_000002_create_zone_tickets_table`.
- [ ] Migration `2026_10_12_000003_add_zone_ticket_id_to_booking_items`: `showtime_seat_id` → nullable, thêm `zone_ticket_id` nullable FK; ràng buộc ứng dụng: đúng 1 trong 2 khác null.
- [ ] Model `ZoneTicket` + quan hệ (`Showtime::zoneTickets`, `RoomZone::tickets`, `BookingItem::zoneTicket`).
- [ ] `RoomZone`: hằng `KIND_TABLE`, `KIND_ADMISSION`; `sellsByQuantity()`, `hasSeats()`, `effectiveCapacity()`.
- [ ] Test: `RoomZoneModelTest` mở rộng (kind, capacity rules). Commit `feat(venue): add zone kinds and zone_tickets inventory`.

### Task 2 — `Room::recalculateCapacity()`
- [ ] Sức chứa = Σ(seated/table: số ghế thuộc khu; standing/admission: `capacity`) + ghế legacy chưa có khu.
- [ ] Gọi sau mỗi lần lưu khu. Test unit. Commit.

### Task 3 — `ZoneLayoutGenerator` (thay `RoomSeatGenerator` cho phòng zoned)
- [ ] Nhận mảng khu từ admin → upsert `room_zones`, sinh ghế thật **chỉ** cho `seated`/`table`, không tạo ghế GA.
- [ ] Từ chối sửa khu đã có suất diễn bán vé (giống quy tắc hiện tại của `RoomSeatGenerator`).
- [ ] Test: khu đứng 500 chỗ → 0 dòng `seats`, capacity = 500 + ghế ngồi. Commit.

### Task 4 — Preset theo mục đích (chỉ là cấu hình khởi tạo)
- [ ] `VenuePresets`: `outdoor_concert`, `theater`, `fixed_hall`, `conference_tables`, `admission_only`; trả về danh sách khu mặc định (không ép số ghế mẫu = sức chứa).
- [ ] Giữ alias `mega_concert`/`sayhi_concert*` → map sang `outdoor_concert` để không vỡ dữ liệu/test cũ. Commit.

### Task 5 — Admin: CRUD khu
- [ ] `RoomController` + builder: danh sách khu (thêm/sửa/xóa/sắp xếp), form theo kind
  (seated: hàng × ghế + cọ vẽ hiện có; table: số bàn × chỗ/bàn; standing/admission: sức chứa), tổng sức chứa tự tính, ô capacity read-only.
- [ ] `AdminRoomBuilderTest` cho từng kind. Commit.

### Task 6 — Đặt vé theo số lượng qua `zone_tickets`
- [ ] `SeatHoldService::hold()` nhận thêm `zones: {zone_id: qty}`; với khu `standing/admission`: lock `room_zones` row, đếm vé hiệu lực, tạo `zone_tickets` held.
- [ ] `release`, `releaseExpired`, giới hạn 8 vé, "1 user 1 suất" tính gộp ghế + zone_tickets.
- [ ] `HoldSeatsRequest`: thêm `zones`. Giữ tương thích `tiers.standing_pit` → map về khu đứng đầu tiên.
- [ ] Test: bán vượt sức chứa bị chặn; vé hết hạn được bán lại; tổng 8 vé. Commit.

### Task 7 — Giỏ vé, checkout, lịch sử, admin booking
- [ ] `CartController`/`CheckoutService`: gộp ghế đang giữ + `zone_tickets` đang giữ → `booking_items` (seat hoặc zone_ticket).
- [ ] Hủy đơn admin nhả cả `zone_tickets`. E-ticket hiển thị "Khu đứng GA — vé số N".
- [ ] Cập nhật `CheckoutTest`, `CartAndHistoryTest`. Commit.

### Task 8 — Trang chọn chỗ khách hàng
- [ ] `SeatMapPresenter` trả thêm `zones` (kind, còn trống, giá). Khu ngồi/bàn → sơ đồ ghế; khu đứng/vé vào cửa → stepper số lượng (dùng UI zone matrix hiện có).
- [ ] `app.js`: gửi `zones` thay cho `standingCount`. Nút "Đặt cả bàn". `npm run build`. Cập nhật `SeatMapPageTest`. Commit.

### Task 9 — Lệnh chuyển dữ liệu `rooms:migrate-zones {--dry-run}`
- [ ] Với mỗi phòng chưa có khu: gom ghế theo (tiền tố `row_label`, `type`) → tạo khu `seated`, gán `zone_id`.
- [ ] Ghế `standing_pit`: không có `booking_items` & không đang giữ → xóa (kèm `showtime_seats`), cộng vào `capacity` khu `standing`; còn lại giữ làm legacy gắn khu đứng.
- [ ] In bảng: phòng | capacity cũ | Σ khu | lệch | số ghế GA xóa/giữ. Dry-run chạy trong transaction rồi rollback.
- [ ] Test trên seeder. Commit.

### Task 10 — Seeder & tài liệu
- [ ] Seeder tạo phòng theo khu (không ghế ảo). Cập nhật `docs/architecture.md`. Commit.

## Rủi ro
- `booking_items.showtime_seat_id` nullable ảnh hưởng mọi chỗ `->showtimeSeat->seat` (lịch sử, admin, PDF báo cáo) → grep & null-safe trong Task 7.
- Seed `DatabaseSeederTest` đếm số ghế cố định (212/252) → cập nhật kỳ vọng ở Task 10.
