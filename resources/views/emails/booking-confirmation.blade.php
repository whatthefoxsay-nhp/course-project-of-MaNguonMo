<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Xác nhận vé {{ $booking->booking_code }}</title>
</head>
<body style="margin:0;padding:28px 12px;background:#111213;color:#f8f5ee;font-family:Arial,Helvetica,sans-serif;">
    @php($firstShowtime = $items->first()?->showtimeSeat?->showtime)
    @php($firstEvent = $firstShowtime?->event)
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:680px;margin:0 auto;background:#191a1b;border:1px solid #51442b;border-radius:16px;overflow:hidden;">
        <tr>
            <td style="padding:30px 34px 24px;border-bottom:1px solid #38332a;">
                <div style="font-size:12px;letter-spacing:4px;color:#c9a85e;font-weight:bold;">{{ strtoupper(config('app.name')) }}</div>
                <h1 style="margin:20px 0 8px;font-size:25px;line-height:1.25;color:#fff;">Đặt vé thành công!</h1>
                <p style="margin:0;color:#c8c3b9;font-size:14px;line-height:1.6;">Xin chào {{ $user?->name ?? 'bạn' }}, vé điện tử của bạn đã sẵn sàng. Mã QR được đính kèm trong PDF.</p>
            </td>
        </tr>
        <tr>
            <td style="padding:26px 34px 8px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td valign="top" style="padding-right:18px;">
                            <div style="font-size:11px;letter-spacing:2px;color:#b7aa8b;text-transform:uppercase;">Mã đặt vé</div>
                            <div style="margin-top:8px;font-size:20px;letter-spacing:2px;color:#e3c77f;font-weight:bold;">{{ $booking->booking_code }}</div>
                        </td>
                        @if ($firstEvent)
                            <td width="92" valign="top">
                                <img src="{{ $firstEvent->poster_url }}" width="88" alt="Poster {{ $firstEvent->title }}" style="display:block;width:88px;height:124px;object-fit:cover;border-radius:8px;border:1px solid #51442b;">
                            </td>
                        @endif
                    </tr>
                </table>

                <h2 style="margin:24px 0 10px;color:#e8d6a6;font-size:15px;">Thông tin vé</h2>
                @foreach ($items as $item)
                    @php($showtime = $item->showtimeSeat?->showtime)
                    @php($event = $showtime?->event)
                    @php($seat = $item->showtimeSeat?->seat)
                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 12px;border-top:1px solid #38332a;">
                        <tr><td style="padding:12px 0 4px;color:#a9a49a;font-size:12px;">Sự kiện</td><td align="right" style="padding:12px 0 4px;color:#fff;font-size:13px;font-weight:bold;">{{ $event?->title ?? 'Sự kiện' }}</td></tr>
                        <tr><td style="padding:4px 0;color:#a9a49a;font-size:12px;">Ngày giờ</td><td align="right" style="padding:4px 0;color:#fff;font-size:13px;">{{ $showtime?->start_time?->format('d/m/Y H:i') ?? 'Theo lịch sự kiện' }}</td></tr>
                        <tr><td style="padding:4px 0;color:#a9a49a;font-size:12px;">Địa điểm</td><td align="right" style="padding:4px 0;color:#fff;font-size:13px;">{{ $showtime?->room?->name ?? $event?->venue_name ?? 'Theo thông tin sự kiện' }}</td></tr>
                        <tr><td style="padding:4px 0;color:#a9a49a;font-size:12px;">Địa chỉ</td><td align="right" style="padding:4px 0;color:#fff;font-size:13px;">{{ $showtime?->room?->address ?? $event?->venue_address ?? '—' }}</td></tr>
                        <tr><td style="padding:4px 0 12px;color:#a9a49a;font-size:12px;">Ghế</td><td align="right" style="padding:4px 0 12px;color:#fff;font-size:13px;">{{ $seat?->zone?->name ? $seat->zone->name.' · ' : '' }}{{ $seat ? $seat->row_label.$seat->seat_number : 'Vé đứng' }}</td></tr>
                    </table>
                @endforeach

                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-top:1px solid #51442b;">
                    <tr><td style="padding:16px 0;color:#c8c3b9;font-size:13px;">Phương thức thanh toán</td><td align="right" style="padding:16px 0;color:#fff;font-size:13px;">{{ strtoupper($booking->payment_method) }}</td></tr>
                    <tr><td style="padding:0 0 22px;color:#c8c3b9;font-size:13px;">Tổng thanh toán</td><td align="right" style="padding:0 0 22px;color:#e3c77f;font-size:18px;font-weight:bold;">{{ number_format((int) $booking->total_price, 0, ',', '.') }}₫</td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding:20px 34px 28px;background:#141516;border-top:1px solid #38332a;color:#a9a49a;font-size:12px;line-height:1.7;">
                Vui lòng mở tệp PDF đính kèm và xuất trình mã QR tại cổng soát vé. Mỗi mã vé chỉ được sử dụng một lần.<br>
                Cảm ơn bạn đã lựa chọn {{ config('app.name') }}.
            </td>
        </tr>
    </table>
</body>
</html>
