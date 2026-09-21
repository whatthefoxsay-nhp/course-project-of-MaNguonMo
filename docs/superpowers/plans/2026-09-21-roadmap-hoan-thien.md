# Lộ trình hoàn thiện dự án (sau khi merge nhánh UI của nhóm)

> **Dành cho Codex (executor):** File này là mục lục. Mỗi plan con bên dưới = **một nhánh
> riêng rẽ từ `main` + một PR**. Làm đúng thứ tự phụ thuộc. Trong mỗi plan, làm lần lượt
> từng task, mỗi task kết thúc bằng chạy test + commit riêng (xem `AGENTS.md`).

**Spec gốc:** `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md`
**Kiến trúc sống:** `docs/architecture.md`

## 1. Hiện trạng (2026-09-21, sau khi merge `feature/ui-core-function-ticketbox` vào `main`)

| Khu vực | Trạng thái |
|---|---|
| Auth (Breeze), khóa tài khoản lúc đăng nhập | Chạy thật |
| Admin: user (tìm/lọc/khóa AJAX), khán phòng + tự sinh ghế | Chạy thật |
| Admin: sự kiện, suất diễn, đơn đặt vé | Chỉ có trang danh sách, **chưa có thêm/sửa/xóa** |
| Admin: dashboard, báo cáo (in PDF bằng `window.print`) | Chạy thật trên DB |
| Admin: mã giảm giá | Dữ liệu giả, **giữ nguyên, không làm thêm** (quyết định của user) |
| Chatbot | Trả lời câu có sẵn phía client, **giữ nguyên** (quyết định của user) |
| Public: trang chủ, danh sách/chi tiết sự kiện, sơ đồ ghế, giỏ vé, thanh toán, lịch sử | **Toàn bộ là dữ liệu giả** từ `App\Support\DemoCatalog`, không gửi request nào lên server |
| Bình luận, thời tiết, bản đồ, tìm kiếm gợi ý | Giả lập phía client |

**Lỗi nghiêm trọng đã biết (sửa trong Plan 1):** `Admin\RoomController@update` xóa và sinh
lại toàn bộ ghế → cascade xóa `showtime_seats` và `booking_items` → **mất vé đã bán**.

## 2. Các quyết định đã chốt với user (2026-09-21)

1. Tính năng ngoài spec (mã giảm giá, chatbot, báo cáo PDF): **giữ nguyên như hiện tại**,
   không làm thêm, không gỡ.
2. Sự kiện không có sơ đồ ghế (hội thảo, triển lãm, workshop, festival…): khách chọn
   **hạng vé + số lượng**, server **tự gán ghế ẩn** trong cùng engine `showtime_seats`.
   Không tạo bảng mới. Vé đứng (GA) của concert cũng dùng cơ chế này.
3. Định hướng sản phẩm 100% "sự kiện" (bảng `events`, `event_id`) của nhóm được giữ.
   Model `Movie` + route `movies.*` là alias tương thích ngược, không xóa.
4. Frontend: Tailwind + Alpine.js (không phải Bootstrap như spec viết). Đã ghi ở
   `docs/architecture.md`.

## 3. Thứ tự thực hiện

```
Plan 1  admin-crud-core ──► Plan 2  public-catalog-db ──► Plan 3  booking-engine
                                        │
                                        ├──► Plan 4  interaction-ajax   (song song được với Plan 3)
                                        └──► Plan 5  external-apis      (song song được với Plan 3)
Plan 6  report-docs  (sau cùng, khi Plan 1–5 đã merge)
```

| # | File plan | Nhánh | Phụ thuộc | Nội dung |
|---|---|---|---|---|
| 1 | `2026-09-21-p1-admin-crud-core.md` | `feature/admin-crud-core` | — | Sửa lỗi xóa ghế, chặn user bị khóa, CRUD danh mục / sự kiện (upload poster) / suất diễn |
| 2 | `2026-09-21-p2-public-catalog-db.md` | `feature/public-catalog-db` | 1 | Trang public đọc DB thật: tìm kiếm + lọc + phân trang, chi tiết sự kiện, sơ đồ ghế; seeder mới |
| 3 | `2026-09-21-p3-booking-engine.md` | `feature/booking-engine` | 2 | Giữ ghế AJAX chống đặt trùng, hết hạn giữ ghế, giỏ vé thật, checkout giả lập, lịch sử; xóa `DemoCatalog` |
| 4 | `2026-09-21-p4-interaction-ajax.md` | `feature/interaction-ajax` | 2 | Bình luận AJAX, gợi ý tìm kiếm AJAX, avatar |
| 5 | `2026-09-21-p5-external-apis.md` | `feature/external-apis` | 2 | Google Maps embed + OpenWeatherMap |
| 6 | `2026-09-21-p6-report-docs.md` | `feature/report-docs` | 1–5 | ERD, use case, mô tả chức năng, README, checklist ảnh chụp |

Plan 3, 4, 5 cùng sửa `resources/views/events/show.blade.php` và `resources/js/app.js` ở
**các đoạn khác nhau**. Nếu chạy song song, PR merge sau phải rebase lên `main` trước.

## 4. Quy trình cho Codex với mỗi plan

```bash
git checkout main && git pull --ff-only   # nếu main chưa lên GitHub thì bỏ pull
git checkout -b <tên nhánh trong bảng>
# ... làm từng task theo plan, mỗi task 1 commit ...
php artisan test                          # phải xanh toàn bộ trước khi mở PR
git push -u origin <tên nhánh>
gh pr create --base main --title "<tiêu đề plan>" --body "<tóm tắt + output php artisan test>"
```

- Không commit thẳng vào `main`, không force-push `main`.
- Không sửa migration đã có trên `main`, luôn tạo migration mới.
- Không thêm tính năng ngoài plan. Gặp chỗ plan sai/thiếu → ghi vào mô tả PR, không tự mở rộng.
- Chạy `npm run build` sau mỗi task có sửa `resources/js` hoặc `resources/css`.
- Sau khi đổi seeder: `php artisan migrate:fresh --seed` (DB local chỉ là dữ liệu demo).

## 5. Định nghĩa "xong" cho toàn dự án

- Không còn tham chiếu nào tới `DemoCatalog` (`grep -rn DemoCatalog app resources tests` rỗng).
- Luồng đầy đủ chạy được trên trình duyệt: đăng ký → tìm sự kiện → chọn suất → chọn ghế
  (hoặc hạng vé) → giữ chỗ → giỏ vé → thanh toán giả lập → vé xuất hiện trong lịch sử;
  admin thấy đơn đó ở `/admin/bookings` và doanh thu ở dashboard.
- `php artisan test` xanh, có test chống đặt trùng ghế.
- `docs/report/` đủ ERD, use case, mô tả chức năng, ảnh chụp màn hình.
