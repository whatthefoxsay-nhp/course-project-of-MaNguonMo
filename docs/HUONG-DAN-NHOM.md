# Hướng dẫn chạy dự án & lỗi tồn đọng (cho cả nhóm)

Cập nhật: 26/10/2026 · Nhánh: `feature/update_UI` · `php artisan test`: 278 passed (1792 assertions)

## 1. Sau khi pull code mới

Nhánh vừa có nhiều thay đổi về database (bảng `seat_zones`, cột mới ở `seats`, `rooms`). **Phải làm lại DB** mới chạy đúng.

```bash
git pull
composer install --no-security-blocking
npm install
php artisan migrate:fresh --seed   # XÓA toàn bộ dữ liệu local cũ rồi tạo lại dữ liệu demo
php artisan storage:link           # để hiện ảnh poster / avatar upload (chỉ cần chạy 1 lần)
npm run build
php artisan serve --port=8080      # mở http://127.0.0.1:8080
```

- Nếu dùng Laragon tự phục vụ (`http://doanmnm.test`) thì bỏ qua `php artisan serve`.
- Muốn giữ dữ liệu đang có: tạo database khác (vd. `doanmnm_demo`), đổi `DB_DATABASE` trong `.env` rồi mới chạy `migrate:fresh --seed`.
- Tùy chọn: `php artisan schedule:work` ở cửa sổ riêng để dọn chỗ giữ quá hạn mỗi phút.

### File `.env`
Cài đặt từ đầu thì làm theo `README.md`. Nếu đã có `.env`, chỉ cần thêm 2 dòng cuối:

```
OPENWEATHER_API_KEY=
GOOGLE_MAPS_EMBED_KEY=
```

- `OPENWEATHER_API_KEY`: hỏi trưởng nhóm, hoặc tự đăng ký miễn phí ở openweathermap.org (key mới có thể mất ~1 giờ mới chạy). Để trống thì trang sự kiện hiện "Chưa có dự báo", không lỗi.
- `GOOGLE_MAPS_EMBED_KEY`: nhóm chưa có, để trống vẫn hiện bản đồ.
- Google OAuth là tùy chọn. Khi được cung cấp credentials, thêm `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` và `GOOGLE_REDIRECT_URI` vào `.env`; URI phải khớp chính xác với Authorized redirect URI trong Google Cloud Console (mặc định `${APP_URL}/auth/google/callback`). Nếu để trống thì nút Google sẽ ẩn và đăng nhập email/mật khẩu vẫn hoạt động.
- **Không commit file `.env` và không dán key vào code, tài liệu hay tin nhắn công khai.**
- Sau khi sửa `.env`: `php artisan config:clear`.

### Tài khoản demo (mật khẩu `password`)
| Vai trò | Email |
|---|---|
| Quản trị | admin@ticketbox.vn |
| Thành viên | user@ticketbox.vn |

Cần tài khoản thứ hai (vd. demo 2 người giành cùng một ghế) thì tự đăng ký ở cửa sổ ẩn danh.

### Chạy test
```bash
php artisan test
```
Mọi PR phải giữ test xanh. Test dùng SQLite trong bộ nhớ, không đụng DB MySQL của bạn và không gọi API thật.

## 2. Những thay đổi lớn cần biết

| Phần | Thay đổi | Đọc thêm |
|---|---|---|
| Sơ đồ chỗ ngồi | Địa điểm chia thành **khu**: ghế đánh số, bàn tiệc, khu đứng. Giá vé = giá suất diễn + phụ thu của khu. Có 6 mẫu bố trí; admin sửa tên/giá/màu khu và tô ô trong trình dựng sơ đồ | `docs/architecture.md` mục 2a |
| Giữ chỗ | API giữ chỗ nhận `seat_ids` + `zones[id_khu] = số vé` (không còn `tiers`). Đã bỏ vé "Combo 2 người" | `app/Services/SeatHoldService.php` |
| Dữ liệu demo | 8 sự kiện ở 8 địa điểm, mỗi loại một kiểu bố trí | `database/seeders/DatabaseSeeder.php` |
| Đơn đặt vé (admin) | Chỉ được chuyển chờ → xác nhận/hủy, xác nhận → hủy; hủy là trạng thái cuối | `app/Http/Controllers/Admin/BookingController.php` |
| Mới thêm | Bình luận AJAX, gợi ý tìm kiếm, ảnh đại diện, bản đồ + thời tiết, tọa độ địa điểm | `docs/architecture.md` mục 2 |
| Báo cáo | ERD, use case, chức năng theo rubric, checklist ảnh, kịch bản demo | `docs/report/` |

## 3. Lỗi tồn đọng — cần người nhận

Ai nhận lỗi nào thì điền tên vào cột "Người nhận" và tạo nhánh riêng (mục 5).

| # | Mức độ | Lỗi | Vị trí | Gợi ý cách sửa | Người nhận |
|---|---|---|---|---|---|
| 1 | Cao | Ở màn hình điện thoại (375px) **mọi trang public** bị tràn ngang (trang rộng ~490px), người dùng phải kéo sang ngang | `resources/views/layouts/site.blade.php` — cụm nút bên phải thanh menu (`flex items-center gap-1.5 sm:gap-2.5 shrink-0`: ô tìm kiếm, chuông, giỏ, tài khoản) và khối trang trí `absolute top-[30%] -right-[15%] w-[50vw]` | Ẩn ô tìm kiếm dạng chữ / tên người dùng dưới `sm`, chỉ giữ icon; thêm `overflow-x-hidden` cho khối nền trang trí. Kiểm tra bằng DevTools 375px: `document.documentElement.scrollWidth` phải = 375 | |
| 2 | Trung bình | Trang chi tiết đơn của admin hiện **mã khu** (vd. `SVIP`, `GA-L`) thay vì tên khu; vé khu đứng hiện "Hàng GA-L - Ghế 3" khó hiểu | `resources/views/admin/bookings/show.blade.php` khoảng dòng 166–185 | Dùng `$seat->zone?->name` cho cột "Hạng ghế"; nếu `$seat->zone?->isStanding()` thì hiện "Vé số N" thay cho hàng/ghế. Eager load `items.showtimeSeat.seat.zone` trong `Admin/BookingController@show` | |
| 3 | Thấp | Khu ghế admin vừa thêm nhưng chưa tô ô nào vẫn hiện một **thẻ trống** "còn 0 chỗ" trên trang khách | `app/Support/SeatMap.php` (`forShowtime`) | Bỏ khu có `total === 0` khỏi `levels` và `zoneCards`; thêm test trong `tests/Feature/SeatMapDataTest.php` | |
| 4 | Thấp | Ô tìm kiếm không thoát ký tự `%` và `_`: gõ `%` thì khớp mọi sự kiện | `app/Http/Controllers/SearchSuggestionController.php`, `app/Http/Controllers/EventController.php` (`index`) | Thoát ký tự trước khi ghép: `addcslashes($query, '%_\\')`; thêm test gõ `%` → 0 kết quả | |
| 5 | Thấp | Lần đầu xem trang sự kiện (chưa có cache thời tiết) mà API OpenWeather chậm thì trang chờ tối đa 5 giây | `app/Services/WeatherService.php` (`Http::timeout(5)`) | Giảm timeout xuống 2 giây, hoặc tải khối thời tiết bằng AJAX sau khi trang hiện | |
| 6 | Thấp | Khi rất nhiều người cùng mua vé khu đứng trên MySQL, có thể báo "chỉ còn N chỗ" ít hơn thực tế (**không** bán quá chỗ) | `app/Services/SeatHoldService.php` (vòng gán ghế theo khu) | Nếu lấy thiếu ghế thì thử lại 1 lần trước khi báo lỗi | |
| 7 | Thấp | View cũ không còn dùng, chứa bình luận giả viết cứng | `resources/views/movies/index.blade.php`, `resources/views/movies/show.blade.php` | Xóa 2 file (route `movies.*` vẫn trỏ sang `EventController` và view `events.*`); chạy lại test | |

### Đã biết nhưng chấp nhận (không cần sửa trừ khi nhóm đổi ý)
- **Mã giảm giá** có trang quản lý nhưng **chưa áp dụng ở bước thanh toán** — ngoài phạm vi đề tài, cần cả nhóm quyết định có làm không.
- **Chatbot** trả lời bằng câu có sẵn phía trình duyệt — giữ nguyên theo quyết định 21/09.
- **Đặt lại mật khẩu** (admin) đặt mật khẩu tạm `TicketBox@<năm>`, dễ đoán — chấp nhận vì là đồ án môn học (`app/Http/Controllers/Admin/UserController.php`).
- **Bản đồ** dùng link nhúng công khai của Google vì chưa có key Maps.

## 4. Việc còn lại (không phải lỗi)

| Việc | Hướng dẫn | Người nhận |
|---|---|---|
| Chụp 23 ảnh màn hình cho báo cáo | `docs/report/anh-chup.md`, lưu vào `docs/report/screenshots/` | |
| Điền tên thành viên | Bảng cuối `README.md`, cột "Người nói" trong `docs/report/kich-ban-demo.md` | |
| Tập thuyết trình theo kịch bản | `docs/report/kich-ban-demo.md` | |
| Chuyển báo cáo sang Word (nếu thầy yêu cầu) | Lấy nội dung `docs/report/*.md`; xuất sơ đồ Mermaid ra ảnh bằng https://mermaid.live | |

## 5. Quy trình nhận và sửa lỗi

```bash
git checkout feature/update_UI && git pull
git checkout -b fix/<mo-ta-ngan>        # vd. fix/mobile-header-overflow
# sửa code + viết test (Pest) cho lỗi
php artisan test                        # phải xanh toàn bộ
npm run build                           # nếu có sửa resources/js hoặc resources/css
git push -u origin fix/<mo-ta-ngan>
```

Sau đó mở Pull Request vào `feature/update_UI`, dán kết quả `php artisan test` vào mô tả PR. Không sửa migration đã có trên nhánh chung — cần đổi DB thì tạo migration mới.
