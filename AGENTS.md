# AGENTS.md

Bạn (Codex) là **executor** của đồ án "Website Quản lý đặt vé" (môn Mã nguồn mở).

## Vai trò
- Implement đúng theo plan trong `docs/superpowers/plans/<file>.md` mà Claude đã viết —
  không tự thêm tính năng ngoài plan.
- Mỗi plan = một branch riêng: `feature/<module>-<mô tả-ngắn>`, không commit thẳng vào
  `main`.
- Mở PR khi xong, chờ Claude review. Sửa theo comment, không tranh luận lan man — nếu
  không đồng ý với review, giải thích lý do kỹ thuật cụ thể trong PR.

## Lệnh hay dùng
- Test: `php artisan test` (Pest — không dùng PHPUnit thuần)
- Migrate: `php artisan migrate`
- PATH Laragon nếu shell chưa có, xem file plan Phase 0, mục "Tool paths":
  `docs/superpowers/plans/2026-09-07-phase0-project-setup.md`
- Composer: luôn thêm `--no-security-blocking` khi `composer require`/`create-project`
  liên quan tới `laravel/framework` — Composer 2.9's audit gate chặn nhầm toàn bộ dải
  version do advisory thiếu mốc "đã fix". Chạy `composer audit` để tự kiểm tra khi nghi
  ngờ.

## Quy tắc
- KHÔNG chạy `git push --force` lên `main`.
- KHÔNG sửa migration đã merge vào `main` — tạo migration mới nếu cần đổi schema.
- KHÔNG đổi role/middleware/tech stack đã chốt trong spec mà không hỏi qua Claude/user.
- Mỗi task trong plan kết thúc bằng: chạy test liên quan + commit — không gộp nhiều task
  vào 1 commit.

## Tài liệu liên quan
- Spec gốc: `docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md`
- Kiến trúc sống: `docs/architecture.md`
