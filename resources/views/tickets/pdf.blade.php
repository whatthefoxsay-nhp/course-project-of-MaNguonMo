<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Vé {{ $booking->booking_code }}</title>
    <style>
        @page { margin: 22px; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #101112; color: #f7f2e8; font-family: "DejaVu Sans", sans-serif; }
        .ticket { border: 2px solid #c8a96a; border-radius: 16px; min-height: 500px; padding: 26px 30px; position: relative; }
        .brand { color: #c8a96a; font-size: 11px; letter-spacing: 3px; text-transform: uppercase; }
        .heading { color: #fff; font-size: 24px; margin: 18px 0 5px; }
        .code { color: #d8bd83; font-size: 13px; letter-spacing: 2px; }
        .details { border-collapse: collapse; margin-top: 24px; width: 72%; }
        .details td { border-bottom: 1px solid #383838; padding: 8px 6px; vertical-align: top; }
        .label { color: #b6ad9c; font-size: 9px; text-transform: uppercase; width: 118px; }
        .value { color: #fff; font-size: 11px; }
        .qr { position: absolute; right: 30px; top: 105px; text-align: center; width: 130px; }
        .qr svg { height: 112px; width: 112px; background: #fff; padding: 7px; }
        .qr-label { color: #b6ad9c; font-size: 8px; margin-top: 8px; }
        .footer { border-top: 1px solid #383838; bottom: 20px; color: #b6ad9c; font-size: 8px; left: 30px; padding-top: 10px; position: absolute; right: 30px; }
        .seat-list { margin: 0; padding: 0; }
        .seat-list li { list-style: none; margin-bottom: 3px; }
    </style>
</head>
<body>
    <main class="ticket">
        <div class="brand">{{ config('app.name') }} · Vé điện tử</div>
        <h1 class="heading">Vé xác nhận đặt chỗ</h1>
        <div class="code">{{ $booking->booking_code }}</div>

        <table class="details">
            <tr>
                <td class="label">Khách hàng</td>
                <td class="value">{{ $booking->user?->name ?? 'Khách hàng' }}</td>
            </tr>
            <tr>
                <td class="label">Sự kiện / suất diễn</td>
                <td class="value">
                    @foreach ($booking->items as $item)
                        @php($showtime = $item->showtimeSeat?->showtime)
                        {{ $showtime?->event?->title ?? 'Sự kiện' }}
                        @if ($showtime?->start_time)
                            · {{ $showtime->start_time->format('d/m/Y H:i') }}
                        @endif
                        <br>
                    @endforeach
                </td>
            </tr>
            <tr>
                <td class="label">Địa điểm</td>
                <td class="value">
                    @php($firstShowtime = $booking->items->first()?->showtimeSeat?->showtime)
                    {{ $firstShowtime?->room?->name ?? $firstShowtime?->event?->venue_name ?? 'Theo thông tin sự kiện' }}
                    @if ($firstShowtime?->room?->address || $firstShowtime?->event?->venue_address)
                        · {{ $firstShowtime->room?->address ?? $firstShowtime->event?->venue_address }}
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Ghế</td>
                <td class="value">
                    <ul class="seat-list">
                        @foreach ($booking->items as $item)
                            @php($seat = $item->showtimeSeat?->seat)
                            <li>{{ $seat?->zone?->name ? $seat->zone->name.' · ' : '' }}{{ $seat ? $seat->row_label.$seat->seat_number : 'Vé đứng' }}</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
            <tr>
                <td class="label">Tổng thanh toán</td>
                <td class="value">{{ number_format((int) $booking->total_price, 0, ',', '.') }}₫</td>
            </tr>
        </table>

        <aside class="qr">
            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(240)->margin(0)->generate($booking->booking_code) !!}
            <div class="qr-label">Quét mã để xác nhận vé</div>
        </aside>

        <footer class="footer">Vui lòng xuất trình mã QR tại cổng soát vé. Mỗi mã chỉ được sử dụng một lần.</footer>
    </main>
</body>
</html>
