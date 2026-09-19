<x-admin-layout :header="'Quản Lý Mã Giảm Giá & Ưu Đãi'">
    <div class="space-y-6" x-data="{ createModalOpen: false, copyNotice: '' }">

        <!-- Top Action Bar & Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-black/10 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center shadow-md shadow-amber-500/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 border border-amber-200/80 px-2.5 py-0.5 rounded-full inline-block mb-1">Marketing &amp; Khuyến Mãi</span>
                    <h2 class="font-display font-black text-xl text-black">Chương Trình Mã Giảm Giá &amp; Voucher</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Tạo mới, phân bổ mã khuyến mãi và theo dõi hiệu quả sử dụng voucher ưu đãi đặt vé.</p>
                </div>
            </div>
            <div>
                <button 
                    type="button" 
                    @click="createModalOpen = true"
                    class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-black font-black px-5 py-3 rounded-2xl text-xs flex items-center gap-2 shadow-md shadow-amber-500/20 transition-all hover:scale-[1.02]"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>Tạo Mã Giảm Mới</span>
                </button>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-3xl border border-black/10 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <span class="text-xs text-gray-500 font-semibold">Mã Đang Hoạt Động</span>
                    <div class="font-display font-black text-2xl text-black mt-0.5">{{ $stats['total_active'] }} <span class="text-xs text-emerald-600 font-bold">Voucher</span></div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-black/10 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                <div>
                    <span class="text-xs text-gray-500 font-semibold">Tổng Lượt Sử Dụng</span>
                    <div class="font-display font-black text-2xl text-black mt-0.5">{{ number_format($stats['total_used']) }} <span class="text-xs text-gray-400 font-bold">lượt</span></div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-black/10 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <span class="text-xs text-gray-500 font-semibold">Tổng Tiền Trợ Giá</span>
                    <div class="font-display font-black text-2xl text-amber-600 mt-0.5">{{ number_format($stats['total_discount_amount'] / 1000000, 1) }}M <span class="text-xs text-gray-400 font-bold">VNĐ</span></div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-3xl border border-black/10 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <span class="text-xs text-gray-500 font-semibold">Sắp Hết Hạn (&lt;30 ngày)</span>
                    <div class="font-display font-black text-2xl text-rose-600 mt-0.5">{{ $stats['expiring_soon'] }} <span class="text-xs text-rose-400 font-bold">Mã</span></div>
                </div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white p-5 rounded-3xl border border-black/10 shadow-sm">
            <form method="GET" action="{{ route('admin.discounts.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-7 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Tìm theo mã voucher (VD: TICKETBOX2026), tiêu đề khuyến mãi..." 
                        class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl pl-10 pr-4 py-2.5 text-xs text-black placeholder-gray-400 focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 font-medium"
                    >
                </div>

                <div class="sm:col-span-3">
                    <select name="status" class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 text-xs text-black focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 font-medium">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang áp dụng</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Hết hạn / Đã dùng hết</option>
                    </select>
                </div>

                <div class="sm:col-span-2 flex gap-2">
                    <button type="submit" class="flex-1 bg-black text-white font-bold py-2.5 px-4 rounded-2xl text-xs hover:bg-black/80 transition-colors">
                        Lọc
                    </button>
                    @if(request('search') || request('status'))
                        <a href="{{ route('admin.discounts.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 px-3 rounded-2xl text-xs font-bold transition-colors flex items-center justify-center">
                            Xóa
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Copy Success Alert -->
        <div x-show="copyNotice" x-transition class="bg-emerald-500 text-white text-xs font-bold px-4 py-2.5 rounded-2xl shadow-lg flex items-center justify-between">
            <span x-text="copyNotice"></span>
            <button @click="copyNotice = ''" class="text-white hover:opacity-75">&times;</button>
        </div>

        <!-- Table of Discount Codes -->
        <div class="bg-white rounded-3xl border border-black/10 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#FAF9F6] text-gray-500 uppercase tracking-wider font-bold border-b border-black/10">
                        <tr>
                            <th class="py-3.5 px-5">Mã Voucher</th>
                            <th class="py-3.5 px-5">Thông Tin &amp; Mức Giảm</th>
                            <th class="py-3.5 px-5">Phạm Vi Áp Dụng</th>
                            <th class="py-3.5 px-5">Tiến Độ Sử Dụng</th>
                            <th class="py-3.5 px-5">Thời Hạn</th>
                            <th class="py-3.5 px-5">Trạng Thái</th>
                            <th class="py-3.5 px-5 text-right">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5 font-medium">
                        @forelse($discounts as $discount)
                            <tr class="hover:bg-amber-50/30 transition-colors">
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-black text-xs px-3 py-1.5 rounded-xl bg-amber-100 text-amber-900 border border-amber-300/80 shadow-xs tracking-wider">
                                            {{ $discount['code'] }}
                                        </span>
                                        <button 
                                            type="button" 
                                            @click="navigator.clipboard.writeText('{{ $discount['code'] }}'); copyNotice = 'Đã sao chép mã {{ $discount['code'] }} vào bộ nhớ tạm!'; setTimeout(() => copyNotice = '', 3000)"
                                            class="text-gray-400 hover:text-black p-1 rounded-lg hover:bg-black/5" 
                                            title="Sao chép mã"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="font-bold text-black text-sm">{{ $discount['title'] }}</div>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if($discount['discount_type'] === 'percentage')
                                            <span class="badge-gold text-[10px] px-2 py-0.5 rounded-full font-black">
                                                GIẢM {{ $discount['discount_value'] }}%
                                            </span>
                                            <span class="text-[11px] text-gray-500">Tối đa {{ number_format($discount['max_discount_amount']) }}đ</span>
                                        @else
                                            <span class="badge-rose text-[10px] px-2 py-0.5 rounded-full font-black">
                                                GIẢM {{ number_format($discount['discount_value']) }}đ
                                            </span>
                                        @endif
                                        <span class="text-gray-300">·</span>
                                        <span class="text-[11px] text-gray-500">Đơn từ {{ number_format($discount['min_order_value']) }}đ</span>
                                    </div>
                                </td>
                                <td class="py-4 px-5 text-gray-700">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-800">
                                        <svg class="w-3 h-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" /></svg>
                                        {{ $discount['applicable_to'] }}
                                    </span>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="font-bold text-black">{{ $discount['used_count'] }}/{{ $discount['max_uses'] }}</span>
                                            <span class="text-gray-500 font-semibold">{{ round(($discount['used_count'] / $discount['max_uses']) * 100) }}%</span>
                                        </div>
                                        <div class="w-28 bg-gray-200 h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-amber-500 h-full rounded-full" style="width: {{ min(100, round(($discount['used_count'] / $discount['max_uses']) * 100)) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-5 text-gray-600 text-xs">
                                    <div><span class="text-gray-400">Từ:</span> {{ date('d/m/Y', strtotime($discount['start_date'])) }}</div>
                                    <div><span class="text-gray-400">Đến:</span> <span class="font-bold text-gray-800">{{ date('d/m/Y', strtotime($discount['end_date'])) }}</span></div>
                                </td>
                                <td class="py-4 px-5">
                                    @if($discount['status'] === 'active')
                                        <span class="px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Đang kích hoạt
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full bg-gray-100 border border-gray-200 text-gray-500 text-[11px] font-bold inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            Đã hết hạn
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button 
                                            type="button" 
                                            @click="alert('Chức năng sửa thông tin mã giảm giá')" 
                                            class="p-2 rounded-xl text-gray-500 hover:text-black hover:bg-black/5 transition-colors"
                                            title="Chỉnh sửa mã"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="alert('Đã thay đổi trạng thái kích hoạt của mã')" 
                                            class="p-2 rounded-xl text-gray-500 hover:text-amber-600 hover:bg-amber-50 transition-colors"
                                            title="Bật / Tắt trạng thái"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-500">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3 text-gray-400">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                    </div>
                                    <p class="font-bold text-sm text-black">Không tìm thấy mã giảm giá phù hợp</p>
                                    <p class="text-xs text-gray-400 mt-1">Hãy thử tìm kiếm với từ khóa khác hoặc tạo mã khuyến mãi mới.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tạo Mã Giảm Giá Mới -->
        <div 
            x-show="createModalOpen" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            style="display: none;"
        >
            <div 
                @click.away="createModalOpen = false" 
                class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full border border-black/10 shadow-2xl space-y-5"
            >
                <div class="flex items-center justify-between pb-3 border-b border-black/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500 text-black flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <div>
                            <h3 class="font-display font-black text-lg text-black">Tạo Mã Giảm Giá Mới</h3>
                            <p class="text-xs text-gray-500">Phát hành voucher khuyến mãi cho sự kiện &amp; khách hàng</p>
                        </div>
                    </div>
                    <button @click="createModalOpen = false" class="text-gray-400 hover:text-black text-xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="alert('Mã giảm giá đã được tạo thành công trên hệ thống!'); createModalOpen = false;" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Mã Voucher (Code) <span class="text-rose-500">*</span></label>
                        <input type="text" placeholder="VD: SUMMER2026, VIPCONCERT" required class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 font-mono uppercase font-bold text-black focus:outline-none focus:border-black">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Tiêu Đề / Mô Tả Khuyến Mãi <span class="text-rose-500">*</span></label>
                        <input type="text" placeholder="VD: Ưu đãi giảm 20% cho thành viên mới" required class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 font-medium text-black focus:outline-none focus:border-black">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Loại Giảm Giá</label>
                            <select class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 font-medium text-black focus:outline-none focus:border-black">
                                <option value="percentage">Phần trăm (%)</option>
                                <option value="fixed">Số tiền cố định (VNĐ)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Giá Trị Giảm <span class="text-rose-500">*</span></label>
                            <input type="number" placeholder="20 hoặc 50000" required class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 font-medium text-black focus:outline-none focus:border-black">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Đơn Hàng Tối Thiểu (VNĐ)</label>
                            <input type="number" placeholder="200000" class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 font-medium text-black focus:outline-none focus:border-black">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Số Lượng Phát Hành</label>
                            <input type="number" placeholder="500" class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 font-medium text-black focus:outline-none focus:border-black">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Ngày Bắt Đầu</label>
                            <input type="date" value="{{ date('Y-m-d') }}" class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 font-medium text-black focus:outline-none focus:border-black">
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Ngày Hết Hạn</label>
                            <input type="date" value="{{ date('Y-m-d', strtotime('+3 months')) }}" class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 font-medium text-black focus:outline-none focus:border-black">
                        </div>
                    </div>

                    <div class="pt-3 flex gap-3">
                        <button type="button" @click="createModalOpen = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2.5 rounded-2xl transition-colors">
                            Hủy
                        </button>
                        <button type="submit" class="flex-1 bg-black hover:bg-gray-800 text-white font-bold py-2.5 rounded-2xl transition-colors">
                            Lưu &amp; Kích Hoạt
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-admin-layout>
