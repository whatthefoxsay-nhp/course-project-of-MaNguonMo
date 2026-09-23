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
                <table class="w-full text-left text-xs text-black">
                    <thead class="text-[11px] uppercase tracking-wider text-gray-600 border-b border-black/10 bg-[#FAF9F6]">
                        <tr>
                            <th class="py-3 px-3.5 font-bold whitespace-nowrap">Mã Đơn Vé</th>
                            <th class="py-3 px-3 font-bold whitespace-nowrap">Khách Hàng</th>
                            <th class="py-3 px-3 font-bold whitespace-nowrap">Sự Kiện &amp; Địa Điểm</th>
                            <th class="py-3 px-3 font-bold whitespace-nowrap">Suất Diễn</th>
                            <th class="py-3 px-2.5 font-bold whitespace-nowrap text-center">Số Lượng</th>
                            <th class="py-3 px-3 font-bold whitespace-nowrap">Tổng Tiền</th>
                            <th class="py-3 px-3 font-bold whitespace-nowrap">Trạng Thái</th>
                            <th class="py-3 px-3.5 font-bold text-right whitespace-nowrap">Thao Tác</th>
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
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="inline-block px-2.5 py-1 rounded-xl bg-neutral-900 text-amber-300 font-mono font-black text-xs border border-black/20 shadow-xs hover:underline" title="Xem chi tiết đơn vé">
                                        {{ $booking->booking_code }}
                                    </a>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white flex items-center justify-center font-bold text-[10px] shadow-xs shrink-0">
                                            {{ strtoupper(substr($booking->user?->name ?? 'K', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0 max-w-[120px] sm:max-w-[150px]">
                                            <div class="font-bold text-black text-xs truncate" title="{{ $booking->user?->name ?? 'Khách vãng lai' }}">{{ $booking->user?->name ?? 'Khách vãng lai' }}</div>
                                            <span class="text-[10px] text-gray-400 block truncate" title="{{ $booking->user?->email }}">{{ $booking->user?->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="min-w-0 max-w-[160px] sm:max-w-[200px] lg:max-w-[240px]">
                                        <div class="font-bold text-black text-xs line-clamp-1 hover:line-clamp-none transition-all" title="{{ $movie?->title ?? 'N/A' }}">
                                            {{ $movie?->title ?? 'N/A' }}
                                        </div>
                                        <span class="inline-block text-[10px] font-semibold text-indigo-700 bg-indigo-50 px-1.5 py-0.2 rounded border border-indigo-100 mt-0.5 truncate max-w-full">
                                            {{ $room?->name ?? 'Chưa gán phòng' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-black whitespace-nowrap">
                                    <div class="font-bold text-black text-xs flex items-center gap-1">
                                        <svg class="w-3 h-3 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $showtime ? $showtime->start_time->format('H:i') : 'N/A' }}
                                    </div>
                                    <div class="text-[10px] text-gray-500 mt-0.5">{{ $showtime ? $showtime->start_time->format('d/m/Y') : 'N/A' }}</div>
                                </td>
                                <td class="py-3 px-2.5 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-lg font-black text-xs bg-amber-50 text-amber-900 border border-amber-200 inline-flex items-center gap-1">
                                        {{ $seatCount }} vé
                                    </span>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="font-black text-black text-xs sm:text-sm">
                                        {{ number_format($booking->total_price, 0, ',', '.') }}₫
                                    </span>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    @if($booking->status === 'confirmed')
                                        <span class="px-2.5 py-0.5 rounded-full font-black text-[11px] whitespace-nowrap inline-flex items-center gap-1 bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>Đã xác nhận</span>
                                        </span>
                                    @elseif($booking->status === 'cancelled')
                                        <span class="px-2.5 py-0.5 rounded-full font-black text-[11px] whitespace-nowrap inline-flex items-center gap-1 bg-rose-50 text-rose-800 border border-rose-300 shadow-xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span>Đã hủy</span>
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full font-black text-[11px] whitespace-nowrap inline-flex items-center gap-1 bg-amber-50 text-amber-800 border border-amber-300 shadow-xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span>Đang giữ chỗ</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="px-2.5 py-1 rounded-xl text-xs font-bold bg-[#FAF9F6] border border-black/10 hover:bg-black hover:text-white transition-all shadow-xs">
                                            Chi Tiết
                                        </a>

                                        @if($booking->status !== 'cancelled')
                                            <form method="POST" action="{{ route('admin.bookings.update-status', $booking) }}" onsubmit="return confirm('Hủy đơn vé [{{ $booking->booking_code }}]? Toàn bộ ghế sẽ được giải phóng.')">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="px-2 py-1 rounded-xl text-xs font-bold text-[#CC0000] border border-[#CC0000]/30 hover:bg-[#CC0000] hover:text-white transition-all shadow-xs" title="Hủy đơn và nhả ghế">
                                                    Hủy
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-gray-500">
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
