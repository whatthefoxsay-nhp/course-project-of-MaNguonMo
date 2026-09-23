<x-admin-layout :header="'Chi Tiết Đơn Đặt Vé #' . $booking->booking_code">
    <div class="space-y-6">

        <!-- Top Header & Back Action -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-black/10 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 text-white flex items-center justify-center shadow-md shadow-blue-500/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-black uppercase tracking-wider text-blue-800 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded-full inline-block mb-1">
                        Chi Tiết Vé Điện Tử
                    </span>
                    <h2 class="font-display font-black text-xl text-black flex items-center gap-2">
                        <span>Đơn Vé: {{ $booking->booking_code }}</span>
                        @if($booking->status === 'confirmed')
                            <span class="px-2.5 py-0.5 rounded-full font-bold text-xs bg-emerald-50 text-emerald-800 border border-emerald-300">Đã xác nhận</span>
                        @elseif($booking->status === 'cancelled')
                            <span class="px-2.5 py-0.5 rounded-full font-bold text-xs bg-rose-50 text-rose-800 border border-rose-300">Đã hủy</span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full font-bold text-xs bg-amber-50 text-amber-800 border border-amber-300">Đang giữ chỗ</span>
                        @endif
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Đặt lúc {{ $booking->created_at ? $booking->created_at->format('H:i - d/m/Y') : 'N/A' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.bookings.index') }}" class="px-4 py-2.5 rounded-2xl text-xs font-bold bg-[#FAF9F6] border border-black/10 text-gray-700 hover:text-black hover:bg-neutral-100 transition-all flex items-center gap-1.5">
                    <span>&larr;</span> Quay lại danh sách
                </a>

                @if($booking->status !== 'cancelled')
                    <form method="POST" action="{{ route('admin.bookings.update-status', $booking) }}" onsubmit="return confirm('Bạn có chắc muốn HỦY đơn vé này? Toàn bộ ghế sẽ được tự động giải phóng để mở bán lại!')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="px-4 py-2.5 rounded-2xl text-xs font-bold text-[#CC0000] border border-[#CC0000]/30 hover:bg-[#CC0000] hover:text-white transition-all flex items-center gap-1.5 shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Hủy Vé &amp; Nhả Ghế</span>
                        </button>
                    </form>
                @endif

                @if($booking->status === 'pending')
                    <form method="POST" action="{{ route('admin.bookings.update-status', $booking) }}" onsubmit="return confirm('Xác nhận thanh toán cho đơn vé này?')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="btn-dark px-4 py-2.5 rounded-2xl text-xs font-black flex items-center gap-1.5 shadow-sm">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span>Xác Nhận Đơn</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @php
            $firstItem = $booking->items->first();
            $showtime = $firstItem?->showtimeSeat?->showtime;
            $movie = $showtime?->movie;
            $room = $showtime?->room;
        @endphp

        <!-- 2 Column Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Left: Customer & Event Info (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <!-- Customer Card -->
                <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4">
                    <h3 class="font-display font-black text-sm text-black uppercase tracking-wider border-b border-black/5 pb-3">
                        Thông Tin Khách Hàng
                    </h3>
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between items-center py-1">
                            <span class="text-gray-500">Họ và tên:</span>
                            <strong class="text-black text-sm">{{ $booking->user?->name ?? 'Khách vãng lai' }}</strong>
                        </div>
                        <div class="flex justify-between items-center py-1 border-t border-black/5">
                            <span class="text-gray-500">Email:</span>
                            <span class="text-black font-medium">{{ $booking->user?->email ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-t border-black/5">
                            <span class="text-gray-500">Số điện thoại:</span>
                            <span class="text-black font-medium">{{ $booking->user?->phone ?? 'Chưa cập nhật' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-t border-black/5">
                            <span class="text-gray-500">Tài khoản:</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ ($booking->user?->is_active ?? true) ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' }}">
                                {{ ($booking->user?->is_active ?? true) ? 'Đang hoạt động' : 'Đã bị khóa' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Event & Showtime Card -->
                <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4">
                    <h3 class="font-display font-black text-sm text-black uppercase tracking-wider border-b border-black/5 pb-3">
                        Sự Kiện &amp; Suất Diễn
                    </h3>
                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="text-gray-500 block">Tên chương trình:</span>
                            <strong class="text-black text-sm block mt-0.5">{{ $movie?->title ?? 'Sự kiện âm nhạc' }}</strong>
                            <span class="text-[11px] text-rose-600 font-semibold mt-0.5 block">{{ $movie?->category?->name }}</span>
                        </div>
                        <div class="pt-2 border-t border-black/5">
                            <span class="text-gray-500 block">Địa điểm / Khán phòng:</span>
                            <strong class="text-black block mt-0.5">{{ $room?->name ?? 'Chưa chỉ định' }}</strong>
                            <span class="text-gray-500 text-[11px] block">{{ $room?->address }}</span>
                        </div>
                        <div class="pt-2 border-t border-black/5">
                            <span class="text-gray-500 block">Thời gian biểu diễn:</span>
                            <strong class="text-amber-900 font-mono text-sm block mt-0.5">
                                {{ $showtime ? $showtime->start_time->format('H:i - d/m/Y') : 'N/A' }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Items Table & Total (7 cols) -->
            <div class="lg:col-span-7 bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b border-black/5 pb-3">
                    <h3 class="font-display font-black text-sm text-black uppercase tracking-wider">
                        Danh Sách Ghế Đã Đặt ({{ $booking->items->count() }} Vé)
                    </h3>
                    <span class="text-xs font-mono font-bold text-gray-500">Mã đơn: {{ $booking->booking_code }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#FAF9F6] text-gray-600 border-y border-black/10 uppercase text-[10px] font-bold">
                            <tr>
                                <th class="py-3 px-4">#</th>
                                <th class="py-3 px-4">Vị Trí Ghế</th>
                                <th class="py-3 px-4">Hạng Ghế</th>
                                <th class="py-3 px-4 text-right">Đơn Giá</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5">
                            @forelse($booking->items as $idx => $item)
                                @php
                                    $seat = $item->showtimeSeat?->seat;
                                    $seatType = $seat?->type ?? 'standard';
                                    $typeBadge = match($seatType) {
                                        'svip' => 'bg-amber-100 text-amber-900 border-amber-300 font-black',
                                        'vip' => 'bg-rose-100 text-rose-900 border-rose-300 font-bold',
                                        default => 'bg-neutral-100 text-neutral-800 border-neutral-200'
                                    };
                                @endphp
                                <tr>
                                    <td class="py-3 px-4 text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                    <td class="py-3 px-4 font-bold text-black">
                                        {{ $seat ? "Hàng {$seat->row_label} - Ghế {$seat->seat_number}" : 'Ghế #' . $item->id }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] border uppercase {{ $typeBadge }}">
                                            {{ strtoupper($seatType) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-black text-black font-mono">
                                        {{ number_format($item->price, 0, ',', '.') }}₫
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-gray-400">Không có bản ghi ghế nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t-2 border-black/10 font-bold bg-[#FAF9F6]">
                            <tr>
                                <td colspan="3" class="py-4 px-4 text-black text-sm">Tổng Cộng Thanh Toán:</td>
                                <td class="py-4 px-4 text-right text-base text-black font-black font-mono">
                                    {{ number_format($booking->total_price, 0, ',', '.') }}₫
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80 text-xs text-amber-900 space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Ghi chú quản trị:</span>
                    </div>
                    <p class="text-[11px] text-amber-800">
                        Khi nhấn "Hủy Vé &amp; Nhả Ghế", hệ thống sẽ chuyển trạng thái đơn hàng sang đã hủy và cập nhật trạng thái của tất cả các ghế trên về "Còn trống" (available) ngay lập tức.
                    </p>
                </div>
            </div>

        </div>

    </div>
</x-admin-layout>
