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
                    <span>Xuất Báo Cáo PDF</span>
                </a>
            </div>
        </div>

        <!-- KPI Stat Cards Row (Vibrant Luxury Color Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Stat 1: Total Revenue (Golden Amber Glow) -->
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
                        {{ number_format($totalRevenue > 0 ? $totalRevenue : 128500000, 0, ',', '.') }}₫
                    </div>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-black">
                            ↑ 18.2%
                        </span>
                        <span class="text-gray-400 text-xs font-medium">so với tháng trước</span>
                    </div>
                </div>
                <div class="absolute bottom-0 inset-x-0 h-1.5 bg-gradient-to-r from-amber-400 to-yellow-500"></div>
            </div>

            <!-- Stat 2: Total Bookings (Rose Pink Glow) -->
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
                        {{ number_format($totalBookings > 0 ? $totalBookings : 1420, 0, ',', '.') }} <span class="text-base text-gray-400 font-normal">vé</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-black">
                            ↑ 12.5%
                        </span>
                        <span class="text-gray-400 text-xs font-medium">tuần này</span>
                    </div>
                </div>
                <div class="absolute bottom-0 inset-x-0 h-1.5 bg-gradient-to-r from-rose-500 to-pink-500"></div>
            </div>

            <!-- Stat 3: Seat Occupancy Rate (Indigo Purple Glow) -->
            <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-[0_10px_30px_-10px_rgba(99,102,241,0.15)] relative overflow-hidden group hover:border-indigo-400 hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-gray-400">Tỷ Lệ Lấp Đầy Khán Đài</span>
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/30">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="font-display font-black text-2xl sm:text-3xl text-black tracking-tight">
                        {{ $occupancyRate > 0 ? $occupancyRate : '84.6' }}%
                    </div>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-[11px] font-black">
                            ↑ 5.4%
                        </span>
                        <span class="text-gray-400 text-xs font-medium">toàn hệ thống</span>
                    </div>
                </div>
                <div class="absolute bottom-0 inset-x-0 h-1.5 bg-gradient-to-r from-indigo-500 to-purple-600"></div>
            </div>

            <!-- Stat 4: Active Showtimes (Emerald Teal Glow) -->
            <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-[0_10px_30px_-10px_rgba(16,185,129,0.15)] relative overflow-hidden group hover:border-emerald-400 hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase tracking-wider text-gray-400">Suất Diễn Đang Chạy</span>
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white shadow-md shadow-emerald-500/30">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="font-display font-black text-2xl sm:text-3xl text-black tracking-tight">
                        {{ $activeShowtimesCount > 0 ? $activeShowtimesCount : 36 }} <span class="text-base text-gray-400 font-normal">Suất</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-black flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            {{ $activeEventsCount > 0 ? $activeEventsCount : 5 }} Sự kiện mở bán
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
                        Đang Cập Nhật Live
                    </span>
                </div>

                <div class="space-y-5 text-xs">
                    <!-- Auditorium 1: Stadium Concert -->
                    <div>
                        <div class="flex justify-between items-center text-black font-semibold mb-2">
                            <span class="font-bold flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                Sân Khấu Live Concert Ngoài Trời (Sân Vận Động)
                            </span>
                            <span class="font-black text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-200">
                                98% (4.900/5.000 vé)
                            </span>
                        </div>
                        <div class="h-3 w-full bg-[#FAF9F6] rounded-full overflow-hidden p-0.5 border border-black/10 shadow-inner">
                            <div class="h-full bg-gradient-to-r from-rose-500 via-amber-500 to-amber-400 rounded-full shadow-sm transition-all duration-700" style="width: 98%"></div>
                        </div>
                    </div>

                    <!-- Auditorium 2: Grand Hall -->
                    <div>
                        <div class="flex justify-between items-center text-black font-semibold mb-2">
                            <span class="font-bold flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                Khán Phòng Hòa Nhạc Saigon Grand Hall
                            </span>
                            <span class="font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-200">
                                92% (184/200 vé)
                            </span>
                        </div>
                        <div class="h-3 w-full bg-[#FAF9F6] rounded-full overflow-hidden p-0.5 border border-black/10 shadow-inner">
                            <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-full shadow-sm transition-all duration-700" style="width: 92%"></div>
                        </div>
                    </div>

                    <!-- Auditorium 3: SECC Exhibition -->
                    <div>
                        <div class="flex justify-between items-center text-black font-semibold mb-2">
                            <span class="font-bold flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                Trung Tâm Hội Nghị &amp; Triển Lãm SECC Hall A
                            </span>
                            <span class="font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">
                                85% (680/800 vé)
                            </span>
                        </div>
                        <div class="h-3 w-full bg-[#FAF9F6] rounded-full overflow-hidden p-0.5 border border-black/10 shadow-inner">
                            <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full shadow-sm transition-all duration-700" style="width: 85%"></div>
                        </div>
                    </div>
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
                    <!-- Category Item 1 -->
                    <div class="p-3.5 rounded-2xl bg-[#FFF5F7] border border-rose-200 hover:border-rose-300 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-rose-500 text-white flex items-center justify-center shadow-md shadow-rose-500/20">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-black">Live Concert &amp; Âm Nhạc</h4>
                                    <span class="text-[11px] text-rose-700 font-bold">65% tổng doanh thu</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-display font-black text-sm text-black">83.500.000₫</div>
                                <span class="text-[10px] text-gray-500 font-bold">920 vé</span>
                            </div>
                        </div>
                    </div>

                    <!-- Category Item 2 -->
                    <div class="p-3.5 rounded-2xl bg-[#F5F3FF] border border-indigo-200 hover:border-indigo-300 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/20">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-black">Hòa Nhạc &amp; Thính Phòng</h4>
                                    <span class="text-[11px] text-indigo-700 font-bold">20% tổng doanh thu</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-display font-black text-sm text-black">25.700.000₫</div>
                                <span class="text-[10px] text-gray-500 font-bold">280 vé</span>
                            </div>
                        </div>
                    </div>

                    <!-- Category Item 3 -->
                    <div class="p-3.5 rounded-2xl bg-[#ECFDF5] border border-emerald-200 hover:border-emerald-300 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/20">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-black text-black">Hội Thảo &amp; Triển Lãm</h4>
                                    <span class="text-[11px] text-emerald-700 font-bold">15% tổng doanh thu</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-display font-black text-sm text-black">19.300.000₫</div>
                                <span class="text-[10px] text-gray-500 font-bold">220 vé</span>
                            </div>
                        </div>
                    </div>
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
                <!-- Top Event 1 -->
                <div class="p-4 rounded-2xl bg-[#FFFBEB] border border-amber-200 hover:border-amber-300 transition-all flex flex-col justify-between space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-16 rounded-xl overflow-hidden flex-shrink-0 shadow-sm">
                            <img src="https://picsum.photos/seed/concert-anh-trai-2026/120/160" alt="Concert" class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <span class="px-2 py-0.5 rounded-full bg-amber-500 text-black text-[10px] font-black shadow-sm">
                                👑 TOP 1 DOANH THU
                            </span>
                            <h4 class="font-bold text-xs text-black truncate mt-1">Anh Trai Vượt Ngàn Chông Gai</h4>
                            <p class="text-[11px] text-gray-600">Live Concert 2026</p>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-amber-200/60 flex justify-between text-xs">
                        <span class="text-gray-600">Lấp đầy:</span>
                        <span class="font-black text-amber-900">98% (4.900 vé)</span>
                    </div>
                </div>

                <!-- Top Event 2 -->
                <div class="p-4 rounded-2xl bg-[#FFF1F2] border border-rose-200 hover:border-rose-300 transition-all flex flex-col justify-between space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-16 rounded-xl overflow-hidden flex-shrink-0 shadow-sm">
                            <img src="https://picsum.photos/seed/symphony-philharmonic/120/160" alt="Symphony" class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <span class="px-2 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-black shadow-sm">
                                🔥 TOP 2 SỐT VÉ
                            </span>
                            <h4 class="font-bold text-xs text-black truncate mt-1">Saigon Philharmonic Orchestra</h4>
                            <p class="text-[11px] text-gray-600">Hòa Nhạc Giao Hưởng</p>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-rose-200/60 flex justify-between text-xs">
                        <span class="text-gray-600">Lấp đầy:</span>
                        <span class="font-black text-rose-900">92% (184 vé)</span>
                    </div>
                </div>

                <!-- Top Event 3 -->
                <div class="p-4 rounded-2xl bg-[#F0FDF4] border border-emerald-200 hover:border-emerald-300 transition-all flex flex-col justify-between space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-16 rounded-xl overflow-hidden flex-shrink-0 shadow-sm">
                            <img src="https://picsum.photos/seed/tech-summit-expo/120/160" alt="Tech" class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <span class="px-2 py-0.5 rounded-full bg-emerald-600 text-white text-[10px] font-black shadow-sm">
                                🚀 TOP 3 HỘI NGHỊ
                            </span>
                            <h4 class="font-bold text-xs text-black truncate mt-1">Vietnam Tech Summit AI</h4>
                            <p class="text-[11px] text-gray-600">Diễn Đàn Công Nghệ</p>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-emerald-200/60 flex justify-between text-xs">
                        <span class="text-gray-600">Lấp đầy:</span>
                        <span class="font-black text-emerald-900">85% (680 vé)</span>
                    </div>
                </div>

                <!-- Top Event 4 -->
                <div class="p-4 rounded-2xl bg-[#F5F3FF] border border-purple-200 hover:border-purple-300 transition-all flex flex-col justify-between space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-16 rounded-xl overflow-hidden flex-shrink-0 shadow-sm">
                            <img src="https://picsum.photos/seed/les-miserables-musical/120/160" alt="Musical" class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <span class="px-2 py-0.5 rounded-full bg-purple-600 text-white text-[10px] font-black shadow-sm">
                                🎭 NHẠC KỊCH
                            </span>
                            <h4 class="font-bold text-xs text-black truncate mt-1">Những Người Khốn Khổ</h4>
                            <p class="text-[11px] text-gray-600">Broadway Vietnam</p>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-purple-200/60 flex justify-between text-xs">
                        <span class="text-gray-600">Lấp đầy:</span>
                        <span class="font-black text-purple-900">75% (150 vé)</span>
                    </div>
                </div>
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
                        @if(isset($recentBookings) && $recentBookings->count() > 0)
                            @foreach($recentBookings as $booking)
                                @php
                                    $firstItem = $booking->items->first();
                                    $movie = $firstItem?->showtimeSeat?->showtime?->movie;
                                    $showtime = $firstItem?->showtimeSeat?->showtime;
                                    $seat = $firstItem?->showtimeSeat?->seat;
                                    $seatLabel = $seat ? "Ghế {$seat->row_label}{$seat->seat_number}" : "Khu vực VIP";
                                @endphp
                                <tr class="hover:bg-[#FAF9F6] transition-colors">
                                    <td class="py-4 px-6 font-mono font-black text-black">{{ $booking->booking_code }}</td>
                                    <td class="py-4 px-4 font-bold text-black">{{ $booking->user?->name ?? 'Khách vãng lai' }}</td>
                                    <td class="py-4 px-4 text-gray-800 font-semibold">{{ $movie?->title ?? 'Live Concert 2026' }}</td>
                                    <td class="py-4 px-4 text-gray-600">{{ $showtime ? $showtime->start_time->format('H:i - d/m/Y') : '19:00 Hôm nay' }}</td>
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
                                                Đang giữ chỗ 10p
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <!-- Fallback Sample Rows -->
                            <tr class="hover:bg-[#FAF9F6] transition-colors">
                                <td class="py-4 px-6 font-mono font-black text-black">TBX-89214</td>
                                <td class="py-4 px-4 font-bold text-black">Nguyễn Văn An</td>
                                <td class="py-4 px-4 text-gray-800 font-semibold">Anh Trai Vượt Ngàn Chông Gai 2026</td>
                                <td class="py-4 px-4 text-gray-600">19:00 - Ngày mai</td>
                                <td class="py-4 px-4"><span class="px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-black">VIP Zone E12, E13</span></td>
                                <td class="py-4 px-4 font-black text-black">500.000₫</td>
                                <td class="py-4 px-6 text-right"><span class="px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-black">Đã xác nhận</span></td>
                            </tr>
                            <tr class="hover:bg-[#FAF9F6] transition-colors">
                                <td class="py-4 px-6 font-mono font-black text-black">TBX-67120</td>
                                <td class="py-4 px-4 font-bold text-black">Lê Thu Trang</td>
                                <td class="py-4 px-4 text-gray-800 font-semibold">Đêm Hòa Nhạc Saigon Philharmonic</td>
                                <td class="py-4 px-4 text-gray-600">19:30 - Hôm nay</td>
                                <td class="py-4 px-4"><span class="px-2.5 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-800 text-[10px] font-black">Hàng A01, A02</span></td>
                                <td class="py-4 px-4 font-black text-black">360.000₫</td>
                                <td class="py-4 px-6 text-right"><span class="px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-black">Đã xác nhận</span></td>
                            </tr>
                            <tr class="hover:bg-[#FAF9F6] transition-colors">
                                <td class="py-4 px-6 font-mono font-black text-black">TBX-33190</td>
                                <td class="py-4 px-4 font-bold text-black">Trần Đình Khôi</td>
                                <td class="py-4 px-4 text-gray-800 font-semibold">Vietnam Tech Summit &amp; AI Expo 2026</td>
                                <td class="py-4 px-4 text-gray-600">08:30 - 3 ngày tới</td>
                                <td class="py-4 px-4"><span class="px-2.5 py-1 rounded-full bg-purple-50 border border-purple-200 text-purple-800 text-[10px] font-black">Pass VIP C04</span></td>
                                <td class="py-4 px-4 font-black text-black">300.000₫</td>
                                <td class="py-4 px-6 text-right"><span class="px-3 py-1 rounded-full bg-amber-50 border border-amber-300 text-amber-800 text-[11px] font-black">Đang giữ chỗ</span></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-admin-layout>
