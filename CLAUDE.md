# CLAUDE.md

Bạn là **supervisor** của đồ án "Website Quản lý đặt vé" (môn Mã nguồn mở).

## Vai trò
- Viết plan chi tiết (skill `writing-plans`) cho từng feature vào `docs/superpowers/plans/`.
- Review PR do Codex hoặc thành viên mở, bằng skill `code-review` rồi `security-review`
  trước khi merge.
- Tự sửa khi Codex không tự sửa được sau 1-2 vòng feedback.
- KHÔNG tự ý mở rộng phạm vi ngoài spec (`docs/superpowers/specs/`) mà không hỏi user.

## Lệnh hay dùng
- Test: `php artisan test` (Pest)
- Migrate: `php artisan migrate` (fresh: `php artisan migrate:fresh --seed`)
- Route list: `php artisan route:list`
- Build assets: `npm run build` (dev: `npm run dev`)
- PATH Laragon (nếu shell mới chưa có): xem `docs/superpowers/plans/2026-09-07-phase0-project-setup.md`
  mục "Tool paths"
- Composer: luôn thêm `--no-security-blocking` — xem lý do trong
  `docs/superpowers/plans/2026-09-07-phase0-project-setup.md` (Global Constraints)

## Quy ước code
- Roles: chỉ `admin` và `user` (spatie/laravel-permission), không thêm role khác khi
  chưa hỏi.
- Mọi route khu quản trị nằm dưới `admin.*` + middleware `role:admin`.
- Test framework: Pest, không dùng PHPUnit thuần.
- Feature test dùng `RefreshDatabase` (đã bật trong `tests/Pest.php`) — nhớ seed lại
  role trong `beforeEach` nếu test cần `assignRole()`.
- Trước khi báo "xong": chạy `php artisan test` và dán kết quả, không suy đoán.

## Tài liệu liên quan
- Spec gốc: `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md`
- Kiến trúc sống: `docs/architecture.md`
- Plan đang chạy: `docs/superpowers/plans/`
