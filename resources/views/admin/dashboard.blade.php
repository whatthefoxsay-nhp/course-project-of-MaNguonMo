<x-admin-layout 
    :header="'Báo Cáo & Thống Kê Tổng Quan'"
    :subtitle="'Theo dõi dữ liệu doanh thu bán vé, sức chứa khán đài và xếp hạng show diễn trực tiếp.'"
>
    <x-slot:breadcrumb>
        <span class="text-stone-500 font-medium">Tổng quan</span>
        <span class="text-stone-400">›</span>
        <span class="text-stone-900 font-bold">Giám sát hệ thống</span>
    </x-slot:breadcrumb>

    <x-slot:actions>
        <span class="hidden md:inline-flex items-center text-xs font-semibold text-stone-700 bg-white px-3.5 py-2 rounded-xl border border-stone-200 shadow-sm">
            Cập nhật lúc: <strong class="ml-1 text-stone-900 font-bold">{{ now()->format('H:i d/m/Y') }}</strong>
        </span>
        <a 
            href="{{ route('admin.reports.index') }}" 
            class="admin-btn-primary"
        >
            <svg class="w-4 h-4 text-[#F5D061]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.25 2.25L15 6" />
            </svg>
            <span class="hidden sm:inline">Xem Báo Cáo Doanh Thu</span>
            <span class="sm:hidden">Báo Cáo</span>
        </a>
    </x-slot:actions>

    <div class="space-y-8">

        <!-- 4 KPI Cards Row (High Contrast, Rich Icons, Vivid Accents) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            
            <!-- Stat 1: Total Revenue (Hero Ink Card with Vivid Gold) -->
            <div class="kpi-card-hero relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-[0.08em] text-[#D4AF37]">Tổng Doanh Thu Vé</span>
                    <div class="w-10 h-10 rounded-xl bg-[#D4AF37]/20 text-[#F5D061] flex items-center justify-center shrink-0 border border-[#D4AF37]/40 shadow-sm">
                        <svg class="w-5 h-5 text-[#F5D061]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="font-display font-bold tabular-nums text-3xl sm:text-[34px] {{ $totalRevenue > 0 ? 'text-[#F5D061]' : 'text-stone-400' }} tracking-tight leading-tight">
                        {{ number_format($totalRevenue, 0, ',', '.') }}₫
                    </div>
                    @if($totalRevenue > 0)
                        <div class="flex items-center gap-2 mt-3">
                            <span class="px-2.5 py-0.5 rounded-full bg-[#D4AF37]/25 border border-[#D4AF37]/50 text-[#FDE047] text-[10px] font-bold">
                                Đã đối soát
                            </span>
                            <span class="text-stone-300 text-xs font-medium">từ đơn đã xác nhận</span>
                        </div>
                    @else
                        <div class="mt-3 text-xs text-stone-400 font-medium">
                            Chưa có giao dịch phát sinh
                        </div>
                    @endif
                </div>
            </div>

            <!-- Stat 2: Total Bookings (Crisp White Card with Rose Accent) -->
            <div class="kpi-card relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="admin-eyebrow">Vé Đã Đặt Thành Công</span>
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-[#C08497] flex items-center justify-center shrink-0 border border-rose-200 shadow-sm">
                        <svg class="w-5 h-5 text-[#C08497]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    @if($confirmedBookings > 0)
                        <div class="admin-metric leading-tight">
                            {{ number_format($confirmedBookings, 0, ',', '.') }} <span class="text-base font-semibold text-stone-600">đơn</span>
                        </div>
                        <div class="mt-3 flex items-center gap-1.5 text-xs text-stone-600 font-medium">
                            <span class="w-2 h-2 rounded-full bg-[#C08497]"></span>
                            Tổng <strong class="text-stone-900 font-bold">{{ $totalBookings }}</strong> lượt đặt chỗ
                        </div>
                    @else
                        <div class="admin-metric text-stone-400 leading-tight">
                            0 <span class="text-base font-medium text-stone-400">đơn</span>
                        </div>
                        <div class="mt-3 text-xs text-stone-500 font-medium">
                            Đang chờ lượt đặt vé đầu tiên
                        </div>
                    @endif
                </div>
            </div>

            <!-- Stat 3: Seat Occupancy Rate (Crisp White Card with Emerald Accent) -->
            <div class="kpi-card relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="admin-eyebrow">Tỷ Lệ Lấp Đầy Ghế</span>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200 shadow-sm">
                        <svg class="w-5 h-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="admin-metric leading-tight text-stone-900">
                        {{ $occupancyRate }}%
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs text-stone-600 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Toàn bộ các suất diễn đang có
                    </div>
                </div>
            </div>

            <!-- Stat 4: Active Showtimes (Crisp White Card with Amber Accent) -->
            <div class="kpi-card relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="admin-eyebrow">Suất Diễn Đang Chạy</span>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center shrink-0 border border-amber-200 shadow-sm">
                        <svg class="w-5 h-5 text-amber-800" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    @if($activeShowtimesCount > 0)
                        <div class="admin-metric leading-tight text-stone-900">
                            {{ $activeShowtimesCount }} <span class="text-base font-semibold text-stone-600">Suất</span>
                        </div>
                        <div class="mt-3">
                            <span class="badge-semantic-sage font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                {{ $activeEventsCount }} Sự kiện mở bán
                            </span>
                        </div>
                    @else
                        <div class="admin-metric text-stone-400 leading-tight">
                            0 <span class="text-base font-medium text-stone-400">Suất</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs">
                            <span class="badge-semantic-neutral font-medium">
                                Chưa có suất mở bán
                            </span>
                            <a href="{{ route('admin.showtimes.create') }}" class="font-bold text-amber-800 hover:text-amber-900 hover:underline flex items-center gap-1">
                                <span>+ Thêm suất</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Section 2: Analytics Grid (Live Occupancy & Category Revenue) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left: Live Occupancy Progress by Auditorium (7 Cols) -->
            <div class="lg:col-span-7 admin-card p-6 sm:p-7 space-y-6">
                <div class="flex items-center justify-between border-b border-stone-200 pb-4">
                    <div>
                        <span class="admin-eyebrow">Theo Dõi Sức Chứa Khán Phòng</span>
                        <h3 class="font-serif font-bold text-lg text-stone-900 mt-0.5">Tỷ Lệ Lấp Đầy Khán Đài &amp; Sân Khấu</h3>
                    </div>
                    <span class="badge-semantic-sage">
                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                        Dữ Liệu Thực Tế
                    </span>
                </div>

                <div class="space-y-4 text-xs">
                    @forelse($rooms as $room)
                        <div class="p-4 rounded-xl bg-white border border-stone-200/90 shadow-sm space-y-2.5">
                            <div class="flex justify-between items-center text-stone-900">
                                <span class="font-bold text-sm flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-sm"></span>
                                    {{ $room['name'] }}
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200/90 text-amber-900 font-bold tabular-nums text-xs shadow-sm">
                                    {{ $room['rate'] }}% <span class="text-stone-600 font-medium ml-1">({{ number_format($room['booked']) }}/{{ number_format($room['total']) }} vé)</span>
                                </span>
                            </div>
                            <!-- Thicker Progress Bar with vivid amber-gold gradient -->
                            <div class="h-3.5 w-full bg-stone-100 rounded-full overflow-hidden p-0.5 border border-stone-200 shadow-inner">
                                <div class="h-full bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 rounded-full transition-all duration-700 ease-out shadow-sm" style="width: max(2%, {{ min(100, $room['rate']) }}%)"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-stone-500 bg-stone-50 rounded-xl border border-dashed border-stone-200">
                            <svg class="w-8 h-8 mx-auto text-stone-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                            Chưa có dữ liệu khán phòng hoặc suất diễn.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right: Revenue Breakdown by Category (5 Cols) -->
            <div class="lg:col-span-5 admin-card p-6 sm:p-7 space-y-5">
                <div class="flex items-center justify-between border-b border-stone-200 pb-4">
                    <div>
                        <span class="admin-eyebrow">Cơ Cấu Doanh Số</span>
                        <h3 class="font-serif font-bold text-lg text-stone-900 mt-0.5">Phân Bổ Theo Thể Loại Sự Kiện</h3>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse($categories as $cat)
                        @php
                            $catRatio = $totalRevenue > 0 ? round(($cat['revenue'] / $totalRevenue) * 100, 1) : 0;
                        @endphp
                        <div class="p-4 rounded-xl bg-white border border-stone-200 shadow-sm hover:border-[#D4AF37] hover:shadow-md transition-all">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-amber-700 text-white flex items-center justify-center font-display font-bold text-sm shrink-0 shadow-sm">
                                        {{ substr($cat['name'], 0, 1) }}
                                    </div>
                                    <div class="min-w-0 truncate">
                                        <h4 class="text-sm font-bold text-stone-900 truncate">{{ $cat['name'] }}</h4>
                                        <span class="text-xs text-stone-600 font-medium">{{ $catRatio }}% tổng doanh thu</span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-3">
                                    <div class="font-display font-bold tabular-nums text-sm text-stone-900">{{ number_format($cat['revenue'], 0, ',', '.') }}₫</div>
                                    <span class="px-2 py-0.5 rounded-md bg-stone-100 border border-stone-200 text-[11px] text-stone-700 font-semibold">{{ $cat['tickets'] }} vé</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-stone-500 bg-stone-50 rounded-xl border border-dashed border-stone-200">
                            Chưa có danh mục sự kiện nào.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Section 3: Top Performing Events -->
        <div class="admin-card p-6 sm:p-7 space-y-6">
            <div class="flex items-center justify-between border-b border-stone-200 pb-4">
                <div>
                    <span class="admin-eyebrow">Xếp Hạng Bán Vé</span>
                    <h3 class="font-serif font-bold text-lg text-stone-900 mt-0.5">Top Sự Kiện Có Sức Hút Cao Nhất</h3>
                </div>
                <a href="{{ route('admin.events.index') }}" class="text-xs font-bold text-stone-700 hover:text-stone-900 flex items-center gap-1 transition-colors">
                    <span>Xem toàn bộ sự kiện</span>
                    <span>&rarr;</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @forelse($topEvents as $index => $event)
                    <div class="p-4 rounded-xl bg-white border border-stone-200 shadow-sm hover:border-[#D4AF37] hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-16 rounded-lg overflow-hidden flex-shrink-0 bg-stone-100 border border-stone-200 shadow-sm">
                                @if($event['poster'])
                                    <img src="{{ Str::startsWith($event['poster'], ['http://', 'https://']) ? $event['poster'] : asset('storage/' . $event['poster']) }}" alt="{{ $event['title'] }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-xs font-bold text-stone-400">TB</div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                @if($index === 0)
                                    <span class="px-2.5 py-0.5 rounded-full bg-gradient-to-r from-amber-500 to-amber-700 text-white text-[10px] font-bold shadow-sm">
                                        TOP 1
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-stone-100 border border-stone-200 text-stone-700 text-[10px] font-bold">
                                        TOP {{ $index + 1 }}
                                    </span>
                                @endif
                                <h4 class="font-bold text-xs text-stone-900 truncate mt-1">{{ $event['title'] }}</h4>
                                <p class="text-[11px] text-stone-600 truncate font-medium">{{ $event['category'] }}</p>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-stone-200 flex justify-between text-xs">
                            <span class="text-stone-600 font-medium">Lấp đầy:</span>
                            <span class="font-bold tabular-nums text-stone-900">{{ $event['fill_rate'] }}% <span class="text-[11px] text-stone-600 font-normal">({{ $event['booked_seats'] }} vé)</span></span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-4 text-center py-8 text-stone-500 bg-stone-50 rounded-xl border border-dashed border-stone-200">
                        Chưa có dữ liệu sự kiện mở bán.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Section 4: Recent Bookings Table Log -->
        <div class="admin-card overflow-hidden space-y-4">
            <div class="p-6 pb-2 flex items-center justify-between">
                <div>
                    <span class="admin-eyebrow">Nhật Ký Giao Dịch Thời Gian Thực</span>
                    <h3 class="font-serif font-bold text-lg text-stone-900 mt-0.5">Lượt Đặt Vé Sự Kiện Mới Nhất</h3>
                </div>
                <a href="{{ route('admin.bookings.index') }}" class="admin-btn-secondary text-xs">
                    <span>Xem toàn bộ giao dịch</span>
                    <span>&rarr;</span>
                </a>
            </div>

            <!-- Data Table with High Legibility & WCAG AA Contrast -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-stone-900">
                    <thead class="border-y border-stone-200 bg-stone-100/80">
                        <tr>
                            <th class="py-3 px-6 admin-eyebrow">Mã Đơn</th>
                            <th class="py-3 px-4 admin-eyebrow">Khách Hàng</th>
                            <th class="py-3 px-4 admin-eyebrow">Sự Kiện / Show Diễn</th>
                            <th class="py-3 px-4 admin-eyebrow">Suất Diễn</th>
                            <th class="py-3 px-4 admin-eyebrow">Khu Vực / Ghế</th>
                            <th class="py-3 px-4 admin-eyebrow">Tổng Tiền</th>
                            <th class="py-3 px-6 admin-eyebrow text-right">Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200/70">
                        @forelse($recentBookings as $booking)
                            @php
                                $firstItem = $booking->items->first();
                                $showtime = $firstItem?->showtimeSeat?->showtime ?? $firstItem?->zoneTicket?->showtime;
                                $movie = $showtime?->movie;
                                $seat = $firstItem?->showtimeSeat?->seat;
                                $seatLabel = $seat ? "Ghế {$seat->row_label}{$seat->seat_number}" : ($firstItem?->zoneTicket ? $firstItem->zoneTicket->zone->name.' — vé số '.$firstItem->zoneTicket->id : ($booking->items->count() > 0 ? $booking->items->count() . ' vé' : 'N/A'));
                            @endphp
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="py-4 px-6 font-mono font-bold text-stone-900">{{ $booking->booking_code }}</td>
                                <td class="py-4 px-4 font-bold text-stone-900">{{ $booking->user?->name ?? 'Khách vãng lai' }}</td>
                                <td class="py-4 px-4 text-stone-900 font-bold">{{ $movie?->title ?? 'N/A' }}</td>
                                <td class="py-4 px-4 text-stone-600 font-medium">{{ $showtime ? $showtime->start_time->format('H:i - d/m/Y') : 'N/A' }}</td>
                                <td class="py-4 px-4">
                                    <span class="badge-semantic-neutral text-[11px]">
                                        {{ $seatLabel }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 font-display font-bold tabular-nums text-xs text-stone-900">{{ number_format($booking->total_price, 0, ',', '.') }}₫</td>
                                <td class="py-4 px-6 text-right">
                                    @if($booking->status === 'confirmed')
                                        <span class="badge-semantic-sage text-[11px]">
                                            Đã xác nhận
                                        </span>
                                    @elseif($booking->status === 'cancelled')
                                        <span class="badge-semantic-crimson text-[11px]">
                                            Đã hủy
                                        </span>
                                    @else
                                        <span class="badge-semantic-gold text-[11px]">
                                            Đang giữ chỗ
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-stone-500 font-medium">
                                    Chưa có lượt đặt vé nào trên hệ thống.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-admin-layout>
