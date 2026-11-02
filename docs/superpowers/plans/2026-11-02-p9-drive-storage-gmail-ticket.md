# Plan 9 — Google Drive Image Storage & Gmail Ticket Delivery

- **Ngày**: 2026-11-02
- **Branch**: `feature/drive-gmail-ticket`
- **Phụ thuộc**: Plan 3 (Booking Engine), Plan 1 (Admin CRUD Events)
- **Trạng thái**: Implementation complete — chờ Claude review

---

## Mục tiêu

1. **Google Drive API**: Upload poster/banner sự kiện lên Google Drive thay vì lưu local
   (`storage/app/public`). Admin upload ảnh → hệ thống tự đẩy lên Drive folder, lấy link
   công khai về lưu vào `events.poster_path`. Graceful fallback: nếu thiếu credential Drive
   → giữ nguyên upload local như hiện tại.

2. **Gmail Ticket Delivery**: Sau khi đặt vé thành công (`CheckoutService::checkout()`),
   hệ thống tự gửi email xác nhận vé về Gmail của khách hàng. Email HTML đẹp (Blade
   template) + đính kèm PDF vé có QR code. Mặc định dùng Gmail API (OAuth2), fallback về
   SMTP nếu thiếu credential Gmail API.

---

## Phân tích kỹ thuật

### A. Google Drive API — Image Storage

#### A1. Package & Credentials

- Cài `google/apiclient` (Google API PHP Client) — package chính thức.
- Tạo **Service Account** trên Google Cloud Console:
  - Bật **Google Drive API**.
  - Tải file JSON credential → đặt tại `storage/app/google/service-account.json`
    (gitignore, không commit).
  - Tạo folder trong **Shared Drive** của Google Workspace và cấp Service Account quyền
    tạo/sửa/xóa file (Content manager hoặc quyền tương đương). Service Account không có
    dung lượng Drive và không thể sở hữu file trong My Drive; tài khoản Gmail cá nhân
    cần OAuth người dùng thay cho Service Account.
- Biến môi trường `.env`:
  ```
  GOOGLE_DRIVE_ENABLED=true
  GOOGLE_DRIVE_FOLDER_ID=<folder-id-trên-drive>
  GOOGLE_SERVICE_ACCOUNT_JSON=storage/app/google/service-account.json
  ```

#### A2. Service: `GoogleDriveService`

```
App\Services\GoogleDriveService
├── __construct()          — khởi tạo Google_Client + Drive service từ credential
├── isEnabled(): bool      — check env GOOGLE_DRIVE_ENABLED + credential file tồn tại
├── upload(UploadedFile $file, string $subfolder = 'posters'): ?string
│   — Upload file lên Drive folder, trả về public URL (webContentLink/webViewLink)
│   — Đặt permission "anyone can view" cho file vừa upload
│   — Return null nếu upload thất bại (log lỗi, để fallback local)
├── delete(string $fileId): bool
│   — Xóa file trên Drive theo file ID
└── extractFileId(string $url): ?string
    — Parse Google Drive URL để lấy file ID (cho việc xóa)
```

#### A3. Tích hợp vào EventController (Admin)

- **store() / update()**: Khi có file upload:
  1. Nếu `GoogleDriveService::isEnabled()` → upload lên Drive, lưu URL vào
     `poster_path`.
  2. Nếu không → giữ nguyên logic local hiện tại (`store('posters', 'public')`).
- **destroy() / update() (thay poster)**: Khi xóa poster cũ:
  1. Nếu poster_path là URL Drive → gọi `GoogleDriveService::delete()`.
  2. Nếu poster_path là path local → xóa trên disk `public` (như hiện tại).
- **Event model `getPosterUrlAttribute()`**: Đã hỗ trợ URL bắt đầu `http(s)://` → không
  cần sửa.

#### A4. Config

- Thêm key `google_drive` vào `config/services.php`:
  ```php
  'google_drive' => [
      'enabled'            => env('GOOGLE_DRIVE_ENABLED', false),
      'folder_id'          => env('GOOGLE_DRIVE_FOLDER_ID'),
      'service_account'    => env('GOOGLE_SERVICE_ACCOUNT_JSON', 'storage/app/google/service-account.json'),
  ],
  ```

---

### B. Gmail Ticket Delivery — Email + PDF vé

#### B1. Package & Credentials

- Cài `barryvdh/laravel-dompdf` — sinh PDF vé.
- Cài `simplesoftwareio/simple-qrcode` — sinh QR code chứa booking_code trong PDF.
- Gmail API transport:
  - Cài thêm `google/apiclient` (đã có từ phần A).
  - Tạo OAuth2 credential cho Gmail API trên Google Cloud Console (bật **Gmail API**).
  - Cấp scope `https://www.googleapis.com/auth/gmail.send`; cấu hình app ở Testing cần
    test user, refresh token có thể hết hạn sau 7 ngày.
  - Có thể lấy refresh token qua OAuth Playground bằng OAuth client riêng; khi đó thêm
    redirect URI `https://developers.google.com/oauthplayground` vào OAuth client.
  - `.env` cũng cần `GMAIL_OAUTH_CLIENT_ID`, `GMAIL_OAUTH_CLIENT_SECRET` và
    `GMAIL_OAUTH_REFRESH_TOKEN` để làm mới access token.
  - Hoặc dùng chung Service Account nếu domain workspace cho phép (domain-wide delegation).
  - Biến `.env`:
    ```
    GMAIL_API_ENABLED=true
    GMAIL_SENDER_EMAIL=ticketbox.noreply@gmail.com
    ```
  - Nếu `GMAIL_API_ENABLED=false` → fallback về SMTP (`MAIL_MAILER=smtp` chuẩn Laravel).

#### B2. Custom Mail Transport: `GmailApiTransport`

```
App\Mail\Transport\GmailApiTransport extends AbstractTransport
├── __construct(Google_Client $client, string $senderEmail)
├── doSend(SentMessage $message): void
│   — Chuyển Symfony MimeMessage → raw RFC 2822
│   — Gọi Gmail API: users.messages.send (base64url encoded)
```

- Đăng ký transport trong `AppServiceProvider::boot()`:
  ```php
  Mail::extend('gmail_api', function (array $config) {
      return new GmailApiTransport(...);
  });
  ```
- Thêm mailer `gmail_api` vào `config/mail.php`:
  ```php
  'gmail_api' => [
      'transport' => 'gmail_api',
  ],
  ```
- `.env`: `MAIL_MAILER=gmail_api` (hoặc giữ `smtp` nếu không cấu hình Gmail API).

#### B3. Mailable: `BookingConfirmationMail`

```
App\Mail\BookingConfirmationMail extends Mailable implements ShouldQueue
├── __construct(Booking $booking)
├── envelope(): Envelope
│   — subject: "🎫 Xác nhận vé — {booking_code} | {event_title}"
├── content(): Content
│   — Blade view: resources/views/emails/booking-confirmation.blade.php
│   — Data: booking, items, user, showtime, event, venue
├── attachments(): array
│   — Attach PDF vé sinh bởi TicketPdfService
```

**Blade template** (`emails/booking-confirmation.blade.php`):
- Header: logo + tên ứng dụng
- Thông tin đặt vé:
  - Mã vé: `booking_code`
  - Sự kiện: tên, poster (thumbnail)
  - Ngày/giờ: `showtime.start_time`
  - Địa điểm: `venue_name`, `venue_address`
  - Ghế: danh sách ghế đã đặt (khu, hàng, số)
  - Tổng tiền: `total_price` (format VNĐ)
  - Phương thức: `payment_method`
- Footer: lưu ý sử dụng vé, liên hệ hỗ trợ
- Style: inline CSS, tone luxury (gold + dark) khớp design system

#### B4. Service: `TicketPdfService`

```
App\Services\TicketPdfService
├── generate(Booking $booking): string
│   — Render Blade view `tickets/pdf.blade.php` → HTML
│   — Dùng DomPDF sinh PDF
│   — Chèn QR code (SimpleSoftwareIO\QrCode) encode booking_code
│   — Return path file PDF tạm (hoặc binary content)
```

**PDF template** (`views/tickets/pdf.blade.php`):
- Trang A5 ngang (landscape) hoặc A6 dọc — kiểu vé concert
- QR code lớn ở góc phải
- Thông tin: tên sự kiện, ngày giờ, ghế, mã vé, tên khách
- Design đẹp, có border decorative, tone gold/dark

#### B5. Tích hợp vào CheckoutService

Sau khi tạo booking thành công (cuối method `checkout()`):
```php
// Dispatch queued mail — không block checkout response
Mail::to($user->email)->queue(new BookingConfirmationMail($booking));
```

> **Queue**: Composer `dev` script đã chạy `php artisan queue:listen`. Email dispatch
> qua queue (`ShouldQueue`) để không làm chậm response checkout. Với `QUEUE_CONNECTION=sync`
> hiện tại, email gửi đồng bộ nhưng vẫn hoạt động đúng — chuyển sang `database`/`redis`
> khi cần tối ưu.

#### B6. Fallback Logic

```
if (config('services.gmail_api.enabled') && credential tồn tại):
    MAIL_MAILER = gmail_api
else:
    MAIL_MAILER = smtp (hoặc log cho dev)
```

Admin có thể chỉ cấu hình SMTP thường (Gmail App Password) trong `.env` mà không cần
Gmail API — email vẫn gửi bình thường qua Laravel Mail SMTP transport.

---

## Danh sách Task

### Task 1: Cài đặt packages
- [x] `composer require google/apiclient --no-security-blocking`
- [x] `composer require barryvdh/laravel-dompdf --no-security-blocking`
- [x] `composer require simplesoftwareio/simple-qrcode --no-security-blocking`
- [x] Commit: `chore: install google-apiclient, dompdf, simple-qrcode`

### Task 2: Cấu hình Google Drive
- [x] Thêm keys vào `.env.example` (GOOGLE_DRIVE_ENABLED, GOOGLE_DRIVE_FOLDER_ID,
      GOOGLE_SERVICE_ACCOUNT_JSON)
- [x] Thêm `google_drive` vào `config/services.php`
- [x] Thêm `storage/app/google/` vào `.gitignore`
- [x] Commit: `config: add Google Drive service configuration`

### Task 3: GoogleDriveService
- [x] Tạo `App\Services\GoogleDriveService` với methods: `isEnabled()`, `upload()`,
      `delete()`, `extractFileId()`
- [x] Unit test: mock Google Client, test upload trả URL, test fallback khi disabled
- [x] Commit: `feat: GoogleDriveService for event image storage`

### Task 4: Tích hợp Drive vào Admin EventController
- [x] Sửa `store()` và `update()`: thử upload Drive trước, fallback local
- [x] Sửa `destroy()` và `deleteStoredPoster()`: hỗ trợ xóa file Drive
- [x] Feature test: upload poster khi Drive enabled/disabled
- [x] Commit: `feat: integrate Google Drive upload for event posters`

### Task 5: Cấu hình Gmail API & Transport
- [x] Thêm keys Gmail API vào `.env.example` và `config/services.php`
- [x] Tạo `App\Mail\Transport\GmailApiTransport`
- [x] Đăng ký transport `gmail_api` trong `AppServiceProvider`
- [x] Thêm mailer `gmail_api` vào `config/mail.php`
- [x] Commit: `feat: Gmail API mail transport with SMTP fallback`

### Task 6: TicketPdfService + QR Code
- [x] Tạo `App\Services\TicketPdfService`
- [x] Tạo Blade template `views/tickets/pdf.blade.php` (design vé concert đẹp)
- [x] Test: sinh PDF cho booking, kiểm tra nội dung
- [x] Commit: `feat: TicketPdfService generates PDF ticket with QR code`

### Task 7: BookingConfirmationMail
- [x] Tạo `App\Mail\BookingConfirmationMail` (implements ShouldQueue)
- [x] Tạo Blade template `views/emails/booking-confirmation.blade.php`
- [x] Attach PDF vé vào email
- [x] Test: mail content, PDF attachment
- [x] Commit: `feat: BookingConfirmationMail with PDF ticket attachment`

### Task 8: Tích hợp vào CheckoutService
- [x] Thêm `Mail::to()->queue()` sau khi tạo booking thành công
- [x] Đảm bảo booking eager-load đủ relations cho email (items, seats, showtime, event)
- [x] Feature test: checkout dispatch mail
- [x] Commit: `feat: auto-send booking confirmation email after checkout`

### Task 9: Cập nhật tài liệu
- [x] Cập nhật `docs/architecture.md` — thêm mục Google Drive + Gmail Ticket
- [x] Cập nhật `.env.example` đầy đủ
- [x] Commit: `docs: update architecture for Drive storage & Gmail ticket delivery`

---

## Rủi ro & Giải pháp

| Rủi ro | Giải pháp |
|--------|-----------|
| Service Account không có quyền Drive | Hướng dẫn share folder cho email SA; fallback local nếu lỗi |
| Gmail API OAuth2 phức tạp setup | Fallback SMTP (Gmail App Password) luôn sẵn sàng |
| DomPDF render chậm với ảnh lớn | QR code dùng SVG inline, poster thumbnail nhỏ |
| Queue sync làm chậm checkout | Khuyến nghị chuyển `QUEUE_CONNECTION=database` cho production |
| Credential commit nhầm | `.gitignore` storage/app/google/, env vars cho sensitive data |

---

## Biến môi trường mới (tổng hợp)

```dotenv
# --- Google Drive API ---
GOOGLE_DRIVE_ENABLED=false
GOOGLE_DRIVE_FOLDER_ID=
GOOGLE_SERVICE_ACCOUNT_JSON=storage/app/google/service-account.json

# --- Gmail API ---
GMAIL_API_ENABLED=false
GMAIL_SENDER_EMAIL=

# --- Hoặc dùng SMTP thường (Gmail App Password) ---
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## Cây file mới / sửa

```
app/
├── Mail/
│   ├── BookingConfirmationMail.php          ← NEW
│   └── Transport/
│       └── GmailApiTransport.php            ← NEW
├── Services/
│   ├── CheckoutService.php                  ← EDIT (thêm dispatch mail)
│   ├── GoogleDriveService.php               ← NEW
│   └── TicketPdfService.php                 ← NEW
├── Http/Controllers/Admin/
│   └── EventController.php                  ← EDIT (Drive upload)
├── Providers/
│   └── AppServiceProvider.php               ← EDIT (register gmail_api transport)
config/
├── services.php                             ← EDIT (google_drive, gmail_api)
├── mail.php                                 ← EDIT (gmail_api mailer)
resources/views/
├── emails/
│   └── booking-confirmation.blade.php       ← NEW
├── tickets/
│   └── pdf.blade.php                        ← NEW
tests/Feature/
├── GoogleDriveServiceTest.php               ← NEW
├── TicketPdfServiceTest.php                 ← NEW
├── BookingConfirmationMailTest.php           ← NEW
└── CheckoutEmailTest.php                    ← NEW
.env.example                                 ← EDIT
.gitignore                                   ← EDIT
docs/architecture.md                         ← EDIT
```
