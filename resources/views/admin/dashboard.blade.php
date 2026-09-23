<x-admin-layout :header="'Báo Cáo & Thống Kê Tổng Quan'">
    <div class="space-y-8">
        
        <!-- Header Subtitle & Quick Filter Info -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 sm:p-7 rounded-3xl border border-black/10 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.03)]">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span class="text-xs font-black uppercase tracking-wider text-amber-600">Trung Tâm Quản Trị &amp; Giám Sát Thời Gian Thực</span>
                </div>
                <h2 class="font-display font-black text-2xl text-black">Tổng Quan Hiệu Suất Hệ Thống</h2>
                <p class="text-xs text-gray-500 mt-1 font-medium">Theo dõi dữ liệu doanh thu bán vé, sức chứa khán đài và xếp hạng show diễn trực tiếp.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold text-gray-500 bg-[#FAF9F6] px-3.5 py-2 rounded-xl border border-black/10">
                    Cập nhật: <strong class="text-black">{{ now()->format('H:i d/m/Y') }}</strong>
                </span>
                <a href="{{ route('admin.reports.index') }}" class="btn-rose px-5 py-2.5 rounded-xl text-xs font-black flex items-center gap-2 shadow-sm hover:shadow-md transition-all">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Xem Báo Cáo Doanh Thu</span>
                </a>
            </div>
        </div>

        <!-- KPI Stat Cards Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Stat 1: Total Revenue -->
            <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-[0_10px_30px_-10px_rgba(245,158,11,0.15)] relative overflow-hidden group hover:border-amber-400 hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-gray-400">Tổng Doanh Thu Vé</span>
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-amber-500 to-yellow-400 flex items-center justify-center text-black shadow-md shadow-amber-500/30">
                        <svg class="w-5 h-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="font-display font-black text-2xl sm:text-3xl text-black tracking-tight">
                        {{ number_format($totalRevenue, 0, ',', '.') }}₫
                    </div>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-black">
                            Đã đối soát
                        </span>
                        <span class="text-gray-400 text-xs font-medium">từ đơn confirmed</span>
                    </div>
                </div>
                <div class="absolute bottom-0 inset-x-0 h-1.5 bg-gradient-to-r from-amber-400 to-yellow-500"></div>
            </div>

            <!-- Stat 2: Total Bookings -->
            <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-[0_10px_30px_-10px_rgba(244,63,94,0.15)] relative overflow-hidden group hover:border-rose-400 hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-gray-400">Vé Đã Đặt Thành Công</span>
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-rose-500 to-pink-500 flex items-center justify-center text-white shadow-md shadow-rose-500/30">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="font-display font-black text-2xl sm:text-3xl text-black tracking-tight">
                        {{ number_format($confirmedBookings, 0, ',', '.') }} <span class="text-base text-gray-400 font-normal">đơn</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="text-gray-400 text-xs font-medium">Tổng {{ $totalBookings }} lượt đặt chỗ</span>
                    </div>
                </div>
                <div class="absolute bottom-0 inset-x-0 h-1.5 bg-gradient-to-r from-rose-500 to-pink-500"></div>
            </div>

            <!-- Stat 3: Seat Occupancy Rate -->
            <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-[0_10px_30px_-10px_rgba(99,102,241,0.15)] relative overflow-hidden group hover:border-indigo-400 hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-gray-400">Tỷ Lệ Lấp Đầy Ghế</span>
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/30">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="font-display font-black text-2xl sm:text-3xl text-black tracking-tight">
                        {{ $occupancyRate }}%
                    </div>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="text-gray-400 text-xs font-medium">toàn bộ các suất diễn</span>
                    </div>
                </div>
                <div class="absolute bottom-0 inset-x-0 h-1.5 bg-gradient-to-r from-indigo-500 to-purple-600"></div>
            </div>

            <!-- Stat 4: Active Showtimes -->
            <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-[0_10px_30px_-10px_rgba(16,185,129,0.15)] relative overflow-hidden group hover:border-emerald-400 hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-gray-400">Suất Diễn Sắp & Đang Chạy</span>
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white shadow-md shadow-emerald-500/30">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="font-display font-black text-2xl sm:text-3xl text-black tracking-tight">
                        {{ $activeShowtimesCount }} <span class="text-base text-gray-400 font-normal">Suất</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-black flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            {{ $activeEventsCount }} Sự kiện mở bán
                        </span>
                    </div>
                </div>
                <div class="absolute bottom-0 inset-x-0 h-1.5 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
            </div>
        </div>

        <!-- Section 2: Analytics Grid (Live Occupancy & Category Revenue) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left: Live Occupancy Progress by Auditorium (7 Cols) -->
            <div class="lg:col-span-7 bg-white rounded-3xl p-6 sm:p-7 border border-black/10 shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b border-black/5 pb-4">
                    <div>
                        <span class="text-xs font-black uppercase tracking-wider text-rose-taupe">Theo Dõi Sức Chứa Khán Phòng</span>
                        <h3 class="font-display font-black text-lg text-black mt-0.5">Tỷ Lệ Lấp Đầy Khán Đài &amp; Sân Khấu</h3>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-black flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Dữ Liệu Thực Tế
                    </span>
                </div>

                <div class="space-y-5 text-xs">
                    @forelse($rooms as $room)
                        <div>
                            <div class="flex justify-between items-center text-black font-semibold mb-2">
                                <span class="font-bold flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                    {{ $room['name'] }}
                                </span>
                                <span class="font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-200">
                                    {{ $room['rate'] }}% ({{ number_format($room['booked']) }}/{{ number_format($room['total']) }} vé)
                                </span>
                            </div>
                            <div class="h-3 w-full bg-[#FAF9F6] rounded-full overflow-hidden p-0.5 border border-black/10 shadow-inner">
                                <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-full shadow-sm transition-all duration-700" style="width: {{ min(100, $room['rate']) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-gray-400">
                            Chưa có dữ liệu khán phòng hoặc suất diễn.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right: Revenue Breakdown by Category (5 Cols) -->
            <div class="lg:col-span-5 bg-white rounded-3xl p-6 sm:p-7 border border-black/10 shadow-sm space-y-5">
                <div class="flex items-center justify-between border-b border-black/5 pb-4">
                    <div>
                        <span class="text-xs font-black uppercase tracking-wider text-amber-600">Cơ Cấu Doanh Số</span>
                        <h3 class="font-display font-black text-lg text-black mt-0.5">Phân Bổ Theo Thể Loại Sự Kiện</h3>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse($categories as $cat)
                        @php
                            $catRatio = $totalRevenue > 0 ? round(($cat['revenue'] / $totalRevenue) * 100, 1) : 0;
                        @endphp
                        <div class="p-3.5 rounded-2xl bg-[#FAF9F6] border border-black/10 hover:border-amber-300 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-amber-500 text-black flex items-center justify-center shadow-md font-bold text-xs">
                                        {{ substr($cat['name'], 0, 1) }}
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black text-black">{{ $cat['name'] }}</h4>
                                        <span class="text-[11px] text-amber-700 font-bold">{{ $catRatio }}% tổng doanh thu</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-display font-black text-sm text-black">{{ number_format($cat['revenue'], 0, ',', '.') }}₫</div>
                                    <span class="text-[10px] text-gray-500 font-bold">{{ $cat['tickets'] }} vé</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-gray-400">
                            Chưa có danh mục sự kiện nào.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Section 3: Top Performing Events -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-black/10 shadow-sm space-y-6">
            <div class="flex items-center justify-between border-b border-black/5 pb-4">
                <div>
                    <span class="text-xs font-black uppercase tracking-wider text-emerald-700">Xếp Hạng Bán Vé</span>
                    <h3 class="font-display font-black text-lg text-black mt-0.5">Top Sự Kiện Có Sức Hút Cao Nhất</h3>
                </div>
                <a href="{{ route('admin.events.index') }}" class="text-xs font-bold text-black hover:text-rose-taupe hover:underline flex items-center gap-1 transition-colors">
                    <span>Xem toàn bộ sự kiện</span> &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @forelse($topEvents as $index => $event)
                    <div class="p-4 rounded-2xl bg-[#FAF9F6] border border-black/10 hover:border-amber-300 transition-all flex flex-col justify-between space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-16 rounded-xl overflow-hidden flex-shrink-0 bg-neutral-200 shadow-sm">
                                @if($event['poster'])
                                    <img src="{{ Str::startsWith($event['poster'], ['http://', 'https://']) ? $event['poster'] : asset('storage/' . $event['poster']) }}" alt="{{ $event['title'] }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-xs font-bold text-gray-400">TB</div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <span class="px-2 py-0.5 rounded-full bg-amber-500 text-black text-[10px] font-black shadow-sm">
                                    TOP {{ $index + 1 }}
                                </span>
                                <h4 class="font-bold text-xs text-black truncate mt-1">{{ $event['title'] }}</h4>
                                <p class="text-[11px] text-gray-600 truncate">{{ $event['category'] }}</p>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-black/5 flex justify-between text-xs">
                            <span class="text-gray-600">Lấp đầy:</span>
                            <span class="font-black text-amber-900">{{ $event['fill_rate'] }}% ({{ $event['booked_seats'] }} vé)</span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-4 text-center py-6 text-gray-400">
                        Chưa có dữ liệu sự kiện mở bán.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Section 4: Recent Bookings Table Log -->
        <div class="bg-white rounded-3xl border border-black/10 overflow-hidden shadow-sm space-y-4">
            <div class="p-6 pb-2 flex items-center justify-between">
                <div>
                    <span class="text-xs font-black uppercase tracking-wider text-rose-taupe">Nhật Ký Giao Dịch Thời Gian Thực</span>
                    <h3 class="font-display font-black text-lg text-black mt-0.5">Lượt Đặt Vé Sự Kiện Mới Nhất</h3>
                </div>
                <a href="{{ route('admin.bookings.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-[#FAF9F6] border border-black/10 hover:bg-black hover:text-white transition-all flex items-center gap-1.5 shadow-sm">
                    <span>Xem toàn bộ giao dịch</span>
                    <span>&rarr;</span>
                </a>
            </div>

            <!-- Data Table with High Legibility -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-black">
                    <thead class="text-[11px] uppercase tracking-wider text-gray-600 border-y border-black/10 bg-[#FAF9F6]">
                        <tr>
                            <th class="py-3.5 px-6 font-bold">Mã Đơn</th>
                            <th class="py-3.5 px-4 font-bold">Khách Hàng</th>
                            <th class="py-3.5 px-4 font-bold">Sự Kiện / Show Diễn</th>
                            <th class="py-3.5 px-4 font-bold">Suất Diễn</th>
                            <th class="py-3.5 px-4 font-bold">Khu Vực / Ghế</th>
                            <th class="py-3.5 px-4 font-bold">Tổng Tiền</th>
                            <th class="py-3.5 px-6 font-bold text-right">Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse($recentBookings as $booking)
                            @php
                                $firstItem = $booking->items->first();
                                $movie = $firstItem?->showtimeSeat?->showtime?->movie;
                                $showtime = $firstItem?->showtimeSeat?->showtime;
                                $seat = $firstItem?->showtimeSeat?->seat;
                                $seatLabel = $seat ? "Ghế {$seat->row_label}{$seat->seat_number}" : ($booking->items->count() > 0 ? $booking->items->count() . ' vé' : 'N/A');
                            @endphp
                            <tr class="hover:bg-[#FAF9F6] transition-colors">
                                <td class="py-4 px-6 font-mono font-black text-black">{{ $booking->booking_code }}</td>
                                <td class="py-4 px-4 font-bold text-black">{{ $booking->user?->name ?? 'Khách vãng lai' }}</td>
                                <td class="py-4 px-4 text-gray-800 font-semibold">{{ $movie?->title ?? 'N/A' }}</td>
                                <td class="py-4 px-4 text-gray-600">{{ $showtime ? $showtime->start_time->format('H:i - d/m/Y') : 'N/A' }}</td>
                                <td class="py-4 px-4">
                                    <span class="px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-black">
                                        {{ $seatLabel }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 font-black text-black">{{ number_format($booking->total_price, 0, ',', '.') }}₫</td>
                                <td class="py-4 px-6 text-right">
                                    @if($booking->status === 'confirmed')
                                        <span class="px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-black">
                                            Đã xác nhận
                                        </span>
                                    @elseif($booking->status === 'cancelled')
                                        <span class="px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-800 text-[11px] font-black">
                                            Đã hủy
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full bg-amber-50 border border-amber-300 text-amber-800 text-[11px] font-black">
                                            Đang giữ chỗ
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-400">
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
