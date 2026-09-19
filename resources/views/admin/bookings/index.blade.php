<x-admin-layout :header="'Danh Sách Đơn Đặt Vé'">
    <div class="space-y-6">

        <!-- Top Header & Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-black/10 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 text-white flex items-center justify-center shadow-md shadow-blue-500/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-black uppercase tracking-wider text-blue-800 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded-full inline-block mb-1">Giao Dịch Đặt Chỗ</span>
                    <h2 class="font-display font-black text-xl text-black">Quản Lý Vé &amp; Đơn Hàng Của Khách</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Theo dõi chi tiết các lượt giữ chỗ, đơn thanh toán và xuất vé điện tử.</p>
                </div>
            </div>
            <div>
                <a href="{{ route('admin.reports.index') }}" class="btn-dark px-5 py-3 rounded-2xl text-xs font-black flex items-center gap-2 shadow-md hover:scale-[1.02] transition-all">
                    <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Xem Báo Cáo Doanh Thu</span>
                </a>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white p-5 rounded-3xl border border-black/10 shadow-sm">
            <form method="GET" action="{{ route('admin.bookings.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-8 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm theo mã đơn (TBX-...), tên hoặc email khách hàng..." class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl pl-10 pr-4 py-2.5 text-xs text-black placeholder-gray-400 focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 font-medium">
                </div>

                <div class="sm:col-span-3">
                    <select name="status" class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 text-xs text-black focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 font-medium">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Đang giữ chỗ</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>

                <div class="sm:col-span-1 flex gap-2">
                    <button type="submit" class="w-full btn-dark rounded-2xl text-xs font-black flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="bg-white rounded-3xl border border-black/10 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-black min-w-[850px]">
                    <thead class="text-[11px] uppercase tracking-wider text-gray-600 border-b border-black/10 bg-[#FAF9F6]">
                        <tr>
                            <th class="py-4 px-6 font-bold whitespace-nowrap">Mã Đơn Vé</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Khách Hàng</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Sự Kiện &amp; Địa Điểm</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Suất Diễn</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Vị Trí Ghế</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Tổng Tiền</th>
                            <th class="py-4 px-6 font-bold text-right whitespace-nowrap">Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse($bookings as $booking)
                            @php
                                $firstItem = $booking->items->first();
                                $movie = $firstItem?->showtimeSeat?->showtime?->movie;
                                $showtime = $firstItem?->showtimeSeat?->showtime;
                                $room = $firstItem?->showtimeSeat?->showtime?->room;
                                $seatCount = $booking->items->count();
                            @endphp
                            <tr class="hover:bg-[#FAF9F6] transition-colors">
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <span class="inline-block px-3 py-1.5 rounded-xl bg-neutral-900 text-amber-300 font-mono font-black text-xs border border-black/20 shadow-sm">
                                        {{ $booking->booking_code }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-sm shrink-0">
                                            {{ strtoupper(substr($booking->user?->name ?? 'K', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-black">{{ $booking->user?->name ?? 'Khách vãng lai' }}</div>
                                            <span class="text-[11px] text-gray-400">{{ $booking->user?->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="font-bold text-black text-sm">{{ $movie?->title ?? 'Sự kiện âm nhạc' }}</div>
                                    <span class="text-[11px] font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">{{ $room?->name }}</span>
                                </td>
                                <td class="py-4 px-4 text-black whitespace-nowrap">
                                    <div class="font-bold text-black text-xs flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $showtime ? $showtime->start_time->format('H:i') : '19:00' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">{{ $showtime ? $showtime->start_time->format('d/m/Y') : 'Hôm nay' }}</div>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="px-3 py-1 rounded-xl font-black text-xs bg-amber-50 text-amber-900 border border-amber-200 inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                        {{ $seatCount }} vé
                                    </span>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="font-black text-black text-sm">
                                        {{ number_format($booking->total_price, 0, ',', '.') }}₫
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    @if($booking->status === 'confirmed')
                                        <span class="px-3 py-1 rounded-full font-black text-[11px] whitespace-nowrap inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-sm">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>Đã xác nhận</span>
                                        </span>
                                    @elseif($booking->status === 'cancelled')
                                        <span class="px-3 py-1 rounded-full font-black text-[11px] whitespace-nowrap inline-flex items-center gap-1.5 bg-rose-50 text-rose-800 border border-rose-300 shadow-sm">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            <span>Đã hủy</span>
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full font-black text-[11px] whitespace-nowrap inline-flex items-center gap-1.5 bg-amber-50 text-amber-800 border border-amber-300 shadow-sm">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            <span>Đang giữ chỗ</span>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-500">
                                    <div class="w-12 h-12 rounded-2xl bg-[#FAF9F6] border border-black/10 text-gray-400 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                    </div>
                                    <span class="font-bold text-black block">Chưa có đơn đặt vé nào</span>
                                    <span class="text-xs text-gray-400 mt-1 block">Hãy thử lọc theo từ khóa hoặc trạng thái khác.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($bookings->hasPages())
                <div class="p-4 border-t border-black/5 bg-[#FAF9F6]">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>

    </div>
</x-admin-layout>
