<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Biên lai {{ $booking->booking_code }}</title>
    <style>
        @page { margin: 34px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #171717; font-family: "DejaVu Sans", sans-serif; font-size: 10px; }
        .brand { color: #a17d13; font-size: 11px; font-weight: bold; letter-spacing: 3px; }
        h1 { margin: 20px 0 5px; font-size: 23px; }
        .muted { color: #77736b; }
        .code-box { background: #17191b; border-radius: 8px; color: #e2c66f; margin: 22px 0; padding: 14px 16px; }
        .code-label { color: #c5c0b5; font-size: 8px; letter-spacing: 1.5px; }
        .code { font-size: 16px; font-weight: bold; letter-spacing: 2px; margin-top: 6px; }
        h2 { font-size: 12px; margin: 22px 0 9px; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border-bottom: 1px solid #e8e3d9; padding: 8px 6px; text-align: left; }
        th { background: #faf8f3; color: #706c64; font-size: 8px; }
        .right { text-align: right; }
        .total { border-top: 2px solid #d4af37; margin-top: 14px; }
        .total td { border: 0; font-weight: bold; padding-top: 13px; }
        .amount { color: #a17d13; font-size: 17px; }
        .footer { border-top: 1px solid #e8e3d9; color: #77736b; font-size: 8px; line-height: 1.6; margin-top: 30px; padding-top: 12px; }
    </style>
</head>
<body>
    @php
        $firstShowtime = $booking->items->first()?->showtimeSeat?->showtime;
        $firstEvent = $firstShowtime?->event;
        $paymentLabels = [
            'vietqr' => 'Chuyển khoản VietQR',
            'momo' => 'Ví MoMo',
            'zalopay' => 'Ví ZaloPay',
            'card' => 'Thẻ thanh toán',
        ];
    @endphp
    <div class="brand">TICKETBOX</div>
    <h1>Biên lai thanh toán</h1>
    <div class="muted">Xác nhận giao dịch đặt vé của bạn</div>

    <div class="code-box">
        <div class="code-label">MÃ ĐẶT VÉ</div>
        <div class="code">{{ $booking->booking_code }}</div>
    </div>

    <h2>Thông tin sự kiện</h2>
    <table>
        <tr><td class="muted">Sự kiện</td><td class="right">{{ $firstEvent?->title ?? 'Sự kiện' }}</td></tr>
        <tr><td class="muted">Ngày giờ</td><td class="right">{{ $firstShowtime?->start_time?->format('d/m/Y · H:i') ?? 'Theo lịch sự kiện' }}</td></tr>
        <tr><td class="muted">Địa điểm</td><td class="right">{{ $firstShowtime?->room?->name ?? $firstEvent?->venue_name ?? 'Theo thông tin sự kiện' }}</td></tr>
    </table>

    <h2>Chi tiết vé đã thanh toán</h2>
    <table>
        <tr><th>Vị trí / Hạng vé</th><th class="right">Thành tiền</th></tr>
        @foreach ($booking->items as $item)
            @php($seat = $item->showtimeSeat?->seat)
            <tr>
                <td>{{ $seat?->zone?->name ? $seat->zone->name.' · ' : '' }}{{ $seat ? $seat->row_label.$seat->seat_number : 'Vé đứng' }}</td>
                <td class="right">{{ number_format((int) $item->price, 0, ',', '.') }}₫</td>
            </tr>
        @endforeach
    </table>

    <table class="total">
        <tr><td class="muted">Phương thức thanh toán</td><td class="right">{{ $paymentLabels[$booking->payment_method] ?? strtoupper($booking->payment_method) }}</td></tr>
        <tr><td>Tổng thanh toán</td><td class="right amount">{{ number_format((int) $booking->total_price, 0, ',', '.') }}₫</td></tr>
        <tr><td class="muted">Thời gian tạo biên lai</td><td class="right muted">{{ $booking->created_at?->format('d/m/Y H:i') }}</td></tr>
    </table>

    <div class="footer">Biên lai được tạo tự động bởi TicketBox. Mã đặt vé {{ $booking->booking_code }} dùng để tra cứu thông tin đơn hàng. Vé điện tử có mã QR được gửi kèm riêng trong email.</div>
</body>
</html>
