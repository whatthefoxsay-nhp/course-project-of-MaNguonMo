<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Vé {{ $booking->booking_code }}</title>
    <style>
        @page { margin: 22px; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f7f5ef; color: #17202c; font-family: "DejaVu Sans", sans-serif; }
        .ticket { background: #fff; border: 1px solid #e3e7ec; border-radius: 18px; overflow: hidden; }
        .accent { border-collapse: collapse; height: 6px; width: 100%; }
        .accent td { height: 6px; padding: 0; }
        .brand-row { padding: 22px 26px 18px; }
        .brand { color: #bd941b; font-size: 10px; font-weight: bold; letter-spacing: 3px; text-transform: uppercase; }
        .status { background: #eaf5ec; border: 1px solid #cde6d0; border-radius: 20px; color: #31713c; font-size: 8px; font-weight: bold; padding: 6px 10px; }
        .content { border-collapse: collapse; width: 100%; }
        .details-panel { padding: 8px 26px 20px; vertical-align: top; width: 66%; }
        .qr-panel { background: #faf8f3; border-left: 1px dashed #d9dce0; padding: 20px 14px; text-align: center; vertical-align: middle; width: 34%; }
        .title { color: #101820; font-size: 22px; font-weight: bold; line-height: 1.3; margin: 10px 0 8px; }
        .booking-code { color: #bd8295; font-size: 11px; font-weight: bold; letter-spacing: 2px; }
        .section-label { color: #9a7918; font-size: 8px; font-weight: bold; letter-spacing: 1.2px; margin: 20px 0 7px; text-transform: uppercase; }
        .event-title { color: #17202c; font-size: 14px; font-weight: bold; line-height: 1.4; }
        .meta { color: #626c78; font-size: 9px; line-height: 1.55; margin-top: 5px; }
        .seats { color: #bd526d; font-size: 10px; font-weight: bold; line-height: 1.6; }
        .qr-heading { color: #88909a; font-size: 8px; font-weight: bold; letter-spacing: 1.2px; }
        .qr-subheading { color: #17202c; font-size: 10px; font-weight: bold; margin: 5px 0 12px; }
        .qr-card { background: #fff; border: 1px solid #e2e4e8; border-radius: 12px; display: inline-block; padding: 10px; }
        .qr-card img { display: block; height: 138px; width: 138px; }
        .qr-code { color: #17202c; font-family: "DejaVu Sans Mono", monospace; font-size: 9px; font-weight: bold; letter-spacing: 1px; margin-top: 10px; }
        .qr-valid { color: #31713c; font-size: 7px; font-weight: bold; margin-top: 5px; }
        .price-box { border-top: 1px solid #e5e7eb; margin-top: 16px; padding-top: 12px; }
        .price-label { color: #626c78; font-size: 8px; text-transform: uppercase; }
        .price { color: #976d00; font-size: 18px; font-weight: bold; margin-top: 4px; }
        .bottom { border-top: 1px solid #eceef1; padding: 13px 26px 16px; }
        .bottom td { color: #7b838c; font-size: 8px; }
        .bottom strong { color: #17202c; }
        .footer { color: #7b838c; font-size: 7px; line-height: 1.5; padding-top: 5px; text-align: right; }
    </style>
</head>
<body>
    @php
        $firstShowtime = $booking->items->first()?->showtimeSeat?->showtime;
        $firstEvent = $firstShowtime?->event;
        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(320)
            ->margin(2)
            ->generate('TICKETBOX_AUTH_PASS_'.$booking->booking_code);
        $qrDataUri = 'data:image/svg+xml;base64,'.base64_encode($qrSvg);
    @endphp
    <main class="ticket">
        <table class="accent" role="presentation"><tr><td width="34%" style="background:#c08497"></td><td width="33%" style="background:#d4af37"></td><td width="33%" style="background:#c08497"></td></tr></table>
        <table class="brand-row" role="presentation" width="100%">
            <tr>
                <td class="brand">{{ config('app.name') }} · Vé điện tử</td>
                <td align="right"><span class="status">● {{ $booking->status === 'confirmed' ? 'ĐÃ XÁC NHẬN' : strtoupper($booking->status) }}</span></td>
            </tr>
        </table>

        <table class="content" role="presentation">
            <tr>
                <td class="details-panel">
                    <div class="title">Vé xác nhận đặt chỗ</div>
                    <div class="booking-code">{{ $booking->booking_code }}</div>

                    <div class="section-label">Thông tin sự kiện</div>
                    <div class="event-title">{{ $firstEvent?->title ?? 'Sự kiện' }}</div>
                    <div class="meta">Suất diễn: <strong>{{ $firstShowtime?->start_time?->format('H:i · d/m/Y') ?? 'Theo lịch sự kiện' }}</strong></div>
                    <div class="meta">Địa điểm: <strong>{{ $firstShowtime?->room?->name ?? $firstEvent?->venue_name ?? 'Khán phòng sự kiện' }}</strong></div>
                    @if ($firstShowtime?->room?->address || $firstEvent?->venue_address)
                        <div class="meta">Địa chỉ: {{ $firstShowtime?->room?->address ?? $firstEvent?->venue_address }}</div>
                    @endif

                    <div class="section-label">Vị trí ghế / hạng vé</div>
                    <div class="seats">
                        @foreach ($booking->items as $item)
                            @php($seat = $item->showtimeSeat?->seat)
                            <div>{{ $seat?->zone?->name ? $seat->zone->name.' · ' : '' }}{{ $seat ? $seat->row_label.$seat->seat_number : 'Vé đứng' }}</div>
                        @endforeach
                    </div>

                    <div class="price-box">
                        <div class="price-label">Tổng chi phí thanh toán</div>
                        <div class="price">{{ number_format((int) $booking->total_price, 0, ',', '.') }}₫</div>
                    </div>
                </td>
                <td class="qr-panel">
                    <div class="qr-heading">GATE CHECK-IN PASS</div>
                    <div class="qr-subheading">Quét mã vào cổng khán phòng</div>
                    <div class="qr-card"><img src="{{ $qrDataUri }}" alt="QR {{ $booking->booking_code }}"></div>
                    <div class="qr-code">{{ $booking->booking_code }}</div>
                    <div class="qr-valid">MÃ QR ĐỐI CHIẾU VÉ</div>
                </td>
            </tr>
        </table>

        <table class="bottom" role="presentation" width="100%">
            <tr>
                <td>VÉ ĐIỆN TỬ: <strong>Đối chiếu bằng mã QR</strong></td>
                <td align="right">MÃ ĐẶT VÉ: <strong>{{ $booking->booking_code }}</strong></td>
            </tr>
            <tr><td colspan="2" class="footer">Vui lòng xuất trình mã QR tại cổng soát vé và giữ mã riêng tư.</td></tr>
        </table>
    </main>
</body>
</html>
