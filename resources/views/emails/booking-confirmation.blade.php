<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Biên lai và vé {{ $booking->booking_code }}</title>
</head>
<body style="margin:0;padding:32px 12px;background:#f7f5ef;color:#171717;font-family:Arial,Helvetica,sans-serif;">
    @php
        $firstShowtime = $items->first()?->showtimeSeat?->showtime;
        $firstEvent = $firstShowtime?->event;
        $address = $firstShowtime?->room?->address ?? $firstEvent?->venue_address ?? null;
        $paymentLabels = [
            'vietqr' => 'Chuyển khoản VietQR',
            'momo' => 'Ví MoMo',
            'zalopay' => 'Ví ZaloPay',
            'card' => 'Thẻ thanh toán',
        ];
    @endphp
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:680px;margin:0 auto;">
        <tr>
            <td style="padding:0 0 18px;text-align:center;">
                <span style="font-size:13px;font-weight:900;letter-spacing:4px;color:#b28c16;">TICKET<span style="color:#bd8295;">BOX</span></span>
            </td>
        </tr>
        <tr>
            <td style="background:#fff;border:1px solid #e7e1d5;border-radius:20px;overflow:hidden;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="padding:32px 36px 28px;background:#fbf8ee;border-bottom:1px solid #eee8db;">
                            <div style="display:inline-block;padding:7px 11px;border-radius:999px;background:#edf5ed;color:#35683e;font-size:10px;font-weight:bold;letter-spacing:1px;">✓ ĐẶT VÉ THÀNH CÔNG</div>
                            <h1 style="margin:18px 0 8px;color:#111;font-family:Georgia,'Times New Roman',serif;font-size:29px;line-height:1.2;">Vé của bạn đã sẵn sàng</h1>
                            <p style="margin:0;color:#626262;font-size:14px;line-height:1.7;">Xin chào {{ $user?->name ?? 'bạn' }}, cảm ơn bạn đã đặt vé cùng {{ config('app.name') }}. Biên lai và vé điện tử có mã QR được đính kèm trong email này.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 36px 8px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#17191b;border-radius:14px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <div style="color:#bcb6a9;font-size:10px;font-weight:bold;letter-spacing:2px;">MÃ ĐẶT VÉ</div>
                                        <div style="margin-top:7px;color:#e2c66f;font-size:21px;font-weight:bold;letter-spacing:2px;">{{ $booking->booking_code }}</div>
                                    </td>
                                    <td align="right" style="padding:18px 20px;">
                                        <div style="color:#bcb6a9;font-size:10px;letter-spacing:1px;">TRẠNG THÁI</div>
                                        <div style="margin-top:7px;color:#a9d6ad;font-size:12px;font-weight:bold;">{{ $booking->status === 'confirmed' ? 'ĐÃ XÁC NHẬN' : strtoupper($booking->status) }}</div>
                                    </td>
                                </tr>
                            </table>

                            <h2 style="margin:26px 0 12px;color:#171717;font-size:16px;">Thông tin sự kiện</h2>
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-top:1px solid #eee8db;">
                                <tr><td style="padding:12px 0 5px;color:#777;font-size:12px;">Sự kiện</td><td align="right" style="padding:12px 0 5px;color:#171717;font-size:13px;font-weight:bold;">{{ $firstEvent?->title ?? 'Sự kiện' }}</td></tr>
                                <tr><td style="padding:5px 0;color:#777;font-size:12px;">Ngày giờ</td><td align="right" style="padding:5px 0;color:#171717;font-size:13px;font-weight:bold;">{{ $firstShowtime?->start_time?->format('d/m/Y · H:i') ?? 'Theo lịch sự kiện' }}</td></tr>
                                <tr><td style="padding:5px 0;color:#777;font-size:12px;">Địa điểm</td><td align="right" style="padding:5px 0;color:#171717;font-size:13px;">{{ $firstShowtime?->room?->name ?? $firstEvent?->venue_name ?? 'Theo thông tin sự kiện' }}</td></tr>
                                @if ($address)
                                    <tr><td style="padding:5px 0;color:#777;font-size:12px;">Địa chỉ</td><td align="right" style="padding:5px 0;color:#171717;font-size:13px;">{{ $address }}</td></tr>
                                @endif
                            </table>

                            <h2 style="margin:24px 0 12px;color:#171717;font-size:16px;">Vé đã đặt <span style="color:#8b8b8b;font-size:12px;font-weight:normal;">({{ $items->count() }} vé)</span></h2>
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;border:1px solid #eee8db;border-radius:10px;">
                                <tr style="background:#faf8f3;">
                                    <th align="left" style="padding:11px 12px;color:#777;font-size:10px;letter-spacing:.5px;">VỊ TRÍ / HẠNG VÉ</th>
                                    <th align="right" style="padding:11px 12px;color:#777;font-size:10px;letter-spacing:.5px;">GIÁ VÉ</th>
                                </tr>
                                @foreach ($items as $item)
                                    @php($seat = $item->showtimeSeat?->seat)
                                    <tr>
                                        <td style="padding:12px;border-top:1px solid #eee8db;color:#171717;font-size:13px;font-weight:bold;">{{ $seat?->zone?->name ? $seat->zone->name.' · ' : '' }}{{ $seat ? $seat->row_label.$seat->seat_number : 'Vé đứng' }}</td>
                                        <td align="right" style="padding:12px;border-top:1px solid #eee8db;color:#171717;font-size:13px;">{{ number_format((int) $item->price, 0, ',', '.') }}₫</td>
                                    </tr>
                                @endforeach
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-top:18px;border-top:1px solid #e8e1d4;">
                                <tr><td style="padding:14px 0;color:#777;font-size:12px;">Biên lai thanh toán · {{ $paymentLabels[$booking->payment_method] ?? strtoupper($booking->payment_method) }}</td><td align="right" style="padding:14px 0;color:#171717;font-size:12px;">{{ $booking->created_at?->format('d/m/Y H:i') }}</td></tr>
                                <tr><td style="padding:0 0 20px;color:#171717;font-size:14px;font-weight:bold;">Tổng thanh toán</td><td align="right" style="padding:0 0 20px;color:#b28c16;font-size:22px;font-weight:bold;">{{ number_format((int) $booking->total_price, 0, ',', '.') }}₫</td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 36px 26px;background:#fbf8ee;border-top:1px solid #eee8db;color:#6f6a61;font-size:12px;line-height:1.7;">
                            <strong style="color:#171717;">Tệp đính kèm gồm:</strong> biên lai thanh toán và vé điện tử đã đặt (PDF). Vui lòng mở vé, xuất trình mã QR tại cổng soát vé. Mỗi vé chỉ được sử dụng một lần.<br>
                            Cần hỗ trợ? Hãy phản hồi email này và ghi mã đặt vé <strong>{{ $booking->booking_code }}</strong>.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td style="padding:18px 12px;text-align:center;color:#929089;font-size:11px;line-height:1.6;">Email tự động từ {{ config('app.name') }}<br>Vui lòng giữ lại biên lai và vé điện tử cho ngày diễn ra sự kiện.</td></tr>
    </table>
</body>
</html>
