<x-admin-layout :header="'Lịch Diễn & Suất Diễn'">
    <div class="space-y-6">

        <!-- Top Action Bar & Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-black/10 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 text-black flex items-center justify-center shadow-md shadow-amber-500/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-black uppercase tracking-wider text-amber-800 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full inline-block mb-1">Lịch Trình Sự Kiện</span>
                    <h2 class="font-display font-black text-xl text-black">Quản Lý Lịch Diễn &amp; Suất Mở Bán</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Sắp xếp khung giờ biểu diễn, gán khán phòng và thiết lập giá vé tiêu chuẩn.</p>
                </div>
            </div>
            <div>
                <button type="button" class="btn-dark px-5 py-3 rounded-2xl text-xs font-black flex items-center gap-2 shadow-md hover:scale-[1.02] transition-all">
                    <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Mở Thêm Suất Diễn</span>
                </button>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white p-5 rounded-3xl border border-black/10 shadow-sm">
            <form method="GET" action="{{ route('admin.showtimes.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-6">
                    <select name="movie_id" class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 text-xs text-black focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 font-medium">
                        <option value="">-- Tất cả sự kiện / liveshow --</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}" {{ request('movie_id') == $event->id ? 'selected' : '' }}>{{ $event->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-5">
                    <select name="room_id" class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 text-xs text-black focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 font-medium">
                        <option value="">-- Tất cả khán phòng & sân khấu --</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-1 flex gap-2">
                    <button type="submit" class="w-full btn-dark rounded-2xl text-xs font-black flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Showtimes Table -->
        <div class="bg-white rounded-3xl border border-black/10 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-black min-w-[850px]">
                    <thead class="text-[11px] uppercase tracking-wider text-gray-600 border-b border-black/10 bg-[#FAF9F6]">
                        <tr>
                            <th class="py-4 px-6 font-bold whitespace-nowrap">Mã Suất</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Sự Kiện</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Khán Phòng / Sân Khấu</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Thời Gian Bắt Đầu</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Giá Vé Cơ Bản</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Tỷ Lệ Đặt Ghế</th>
                            <th class="py-4 px-6 font-bold text-right whitespace-nowrap">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse($showtimes as $showtime)
                            @php
                                $totalSeats = $showtime->showtimeSeats->count();
                                $bookedSeats = $showtime->showtimeSeats->where('status', 'booked')->count();
                                $occupancy = $totalSeats > 0 ? round(($bookedSeats / $totalSeats) * 100, 1) : 0;
                            @endphp
                            <tr class="hover:bg-[#FAF9F6] transition-colors">
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-1 rounded-xl bg-neutral-900 text-amber-300 font-mono font-black text-[11px] border border-black/20 shadow-sm">
                                        #ST-{{ $showtime->id }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="font-bold text-black text-sm">{{ $showtime->movie?->title ?? 'N/A' }}</div>
                                    <span class="text-[11px] font-semibold text-rose-600">{{ $showtime->movie?->category?->name }}</span>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-900 border border-indigo-200">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        {{ $showtime->room?->name ?? 'Chưa gán phòng' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="font-bold text-black text-xs flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $showtime->start_time->format('H:i') }} - {{ $showtime->end_time ? $showtime->end_time->format('H:i') : '' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">{{ $showtime->start_time->format('d/m/Y') }}</div>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="inline-block px-3 py-1 rounded-xl bg-amber-50 text-amber-900 border border-amber-200 font-black text-xs">
                                        {{ number_format($showtime->base_price, 0, ',', '.') }}₫
                                    </span>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-xs {{ $occupancy >= 80 ? 'text-rose-600' : ($occupancy >= 40 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $occupancy }}%</span>
                                        <span class="text-[11px] text-gray-400">({{ $bookedSeats }}/{{ $totalSeats }})</span>
                                    </div>
                                    <div class="w-24 h-2 bg-[#FAF9F6] rounded-full overflow-hidden border border-black/10 mt-1">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $occupancy >= 80 ? 'bg-rose-500' : ($occupancy >= 40 ? 'bg-amber-400' : 'bg-emerald-500') }}" style="width: {{ $occupancy }}%"></div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('showtimes.seats', $showtime->id) }}" target="_blank" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#FAF9F6] border border-black/10 hover:bg-black hover:text-white transition-all inline-flex items-center gap-1.5 shadow-sm" title="Xem sơ đồ">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span>Xem Sơ Đồ</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-500">
                                    <div class="w-12 h-12 rounded-2xl bg-[#FAF9F6] border border-black/10 text-gray-400 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <span class="font-bold text-black block">Không tìm thấy suất diễn nào</span>
                                    <span class="text-xs text-gray-400 mt-1 block">Hãy thử lọc theo sự kiện hoặc khán phòng khác.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($showtimes->hasPages())
                <div class="p-4 border-t border-black/5 bg-[#FAF9F6]">
                    {{ $showtimes->links() }}
                </div>
            @endif
        </div>

    </div>
</x-admin-layout>
