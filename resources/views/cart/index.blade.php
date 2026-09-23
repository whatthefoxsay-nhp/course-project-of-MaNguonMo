<x-site-layout :title="'Thanh Toán & Phát Hành Vé Điện Tử E-Ticket - TicketBox'">
    @php
        $jsItems = array_map(function($item) {
            return [
                'movie_title' => $item->movie->title,
                'category_name' => $item->movie->category->name ?? 'Sự Kiện',
                'poster_path' => $item->movie->poster_url,
                'showtime_str' => $item->showtime->start_time->format('H:i - d/m/Y'),
                'room_name' => $item->showtime->room->name ?? 'Khán Phòng Sự Kiện',
                'row_label' => $item->seat->row_label ?? '',
                'seat_number' => $item->seat->seat_number ?? '',
                'seat_type' => $item->seat->type ?? 'Tiêu chuẩn',
                'price' => $item->price,
            ];
        }, $items);
        $totalPrice = array_sum(array_map(fn ($item) => $item->price, $items));
        $firstItem = !empty($items) ? $items[0] : null;
        $currentUser = auth()->user();
        $userName = $currentUser->name ?? 'Nguyễn Văn Kiên';
        $userEmail = $currentUser->email ?? 'kien.nguyen@example.com';
        $userPhone = $currentUser->phone ?? '0988 123 456';
    @endphp

    <div 
        class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12"
        x-data="ticketPaymentManager({
            totalAmount: {{ $totalPrice }},
            items: {{ Js::from($jsItems) }},
            userName: '{{ addslashes($userName) }}',
            userEmail: '{{ addslashes($userEmail) }}',
            userPhone: '{{ addslashes($userPhone) }}',
            checkoutUrl: @js(route('checkout.store')),
            removeUrl: @js(route('cart.seats.destroy', '__ID__')),
        })"
    >
        <!-- Floating Toast Notification -->
        <div 
            x-show="showToast" 
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed bottom-6 right-6 z-50 max-w-sm bg-obsidian-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-gold-antique/40 flex items-center gap-3 backdrop-blur-lg"
            style="display: none;"
        >
            <div class="w-2.5 h-2.5 rounded-full bg-gold-antique animate-pulse"></div>
            <span class="text-xs font-semibold" x-text="toastMsg"></span>
        </div>

        <!-- Checkout Step Progress Indicator -->
        <div class="mb-8 bg-white rounded-3xl p-4 sm:p-5 border border-black/10 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2 sm:gap-4 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
                <!-- Step 1 Indicator -->
                <div class="flex items-center gap-2 shrink-0 cursor-pointer" @click="step === 'payment_qr' ? backToCart() : null">
                    <span 
                        class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black transition-all"
                        :class="step === 'cart' ? 'bg-gold-antique text-black shadow-md' : 'bg-sage-forest text-white'"
                    >
                        <span x-show="step === 'cart'">1</span>
                        <span x-show="step !== 'cart'">✓</span>
                    </span>
                    <span class="text-xs font-bold" :class="step === 'cart' ? 'text-black font-black' : 'text-gray-500'">
                        Giỏ Vé &amp; Người Nhận
                    </span>
                </div>

                <span class="text-gray-300 font-bold">&rarr;</span>

                <!-- Step 2 Indicator -->
                <div class="flex items-center gap-2 shrink-0">
                    <span 
                        class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black transition-all"
                        :class="step === 'payment_qr' ? 'bg-gold-antique text-black shadow-md animate-pulse' : (step === 'e_ticket' ? 'bg-sage-forest text-white' : 'bg-gray-100 text-gray-500')"
                    >
                        <span x-show="step !== 'e_ticket'">2</span>
                        <span x-show="step === 'e_ticket'">✓</span>
                    </span>
                    <span class="text-xs font-bold" :class="step === 'payment_qr' ? 'text-black font-black' : 'text-gray-500'">
                        Thanh Toán Đơn Hàng
                    </span>
                </div>

                <span class="text-gray-300 font-bold">&rarr;</span>

                <!-- Step 3 Indicator -->
                <div class="flex items-center gap-2 shrink-0">
                    <span 
                        class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black transition-all"
                        :class="step === 'e_ticket' ? 'bg-sage-forest text-white shadow-md' : 'bg-gray-100 text-gray-500'"
                    >
                        3
                    </span>
                    <span class="text-xs font-bold" :class="step === 'e_ticket' ? 'text-black font-black' : 'text-gray-500'">
                        Vé Điện Tử E-Ticket
                    </span>
                </div>
            </div>

            <!-- Countdown Timer Component -->
            <div 
                x-data="countdownTimer(0, {{ $expiresInSeconds }})" 
                class="bg-[#FFF8E1] rounded-2xl px-4 py-2 border border-[#FFE082] flex items-center gap-2.5 shrink-0"
            >
                <div class="w-2 h-2 rounded-full bg-gold-dark animate-ping"></div>
                <div class="text-xs">
                    <span class="text-gray-600 uppercase font-bold text-[10px]">Giữ vé trong:</span>
                    <span class="font-display font-black text-gold-dark text-sm ml-1" x-text="minutes + ':' + seconds">10:00</span>
                </div>
            </div>
        </div>

        @if (empty($items))
            <!-- Empty Cart State -->
            <div class="bg-white rounded-3xl p-12 text-center border border-black/10 space-y-4 shadow-sm">
                <div class="w-16 h-16 rounded-3xl bg-[#FAF9F6] border border-black/10 text-black mx-auto flex items-center justify-center shadow-sm">
                    <svg class="w-8 h-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <h3 class="font-display font-black text-xl text-black">Giỏ vé của bạn đang trống</h3>
                <p class="text-xs text-gray-500 max-w-sm mx-auto leading-relaxed">
                    Bạn chưa giữ vé nào trong phiên này. Hãy khám phá danh sách đại nhạc hội concert và sự kiện hấp dẫn để đặt chỗ ngay.
                </p>
                <a href="{{ route('movies.index') }}" class="btn-rose inline-flex items-center gap-2 px-7 py-3 rounded-full text-xs font-bold shadow-md">
                    <span>Khám phá sự kiện ngay</span>
                    <span>&rarr;</span>
                </a>
            </div>
        @else

            <!-- ========================================================= -->
            <!-- STEP 1: CART REVIEW, BUYER INFO & PAYMENT METHOD CHOICE   -->
            <!-- ========================================================= -->
            <div x-show="step === 'cart'" x-transition:enter="animate-fade-in">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Left Column: Tickets, Buyer Form & Method Selector (7 Cols) -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- Tickets List Card -->
                        <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4">
                            <div class="flex items-center justify-between border-b border-black/10 pb-4">
                                <h2 class="font-serif text-xl font-bold text-black">Danh Sách Vé Đang Giữ Chỗ</h2>
                                <span class="badge-gold text-xs px-3 py-1 rounded-full font-bold">
                                    {{ count($items) }} Vé Trong Giỏ
                                </span>
                            </div>

                            <div class="space-y-4 pt-2">
                                @foreach ($items as $item)
                                    <div class="p-4 rounded-2xl bg-[#FAF9F6] border border-black/10 flex flex-col sm:flex-row items-start sm:items-center gap-4 relative group hover:border-gold-antique transition-all">
                                        <!-- Poster Thumbnail -->
                                        <img src="{{ $item->movie->poster_url }}" alt="{{ $item->movie->title }}" class="w-16 h-20 object-cover rounded-xl shrink-0 bg-white border border-black/10">

                                        <!-- Item Info -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="badge-rose text-[9px] px-2 py-0.5 rounded-full font-black uppercase">
                                                    {{ $item->movie->category->name ?? 'Concert' }}
                                                </span>
                                                <span class="badge-sage text-[9px] px-2 py-0.5 rounded-full font-bold">
                                                    Đang giữ chỗ 10 phút
                                                </span>
                                            </div>
                                            <h4 class="font-display font-black text-black text-base truncate group-hover:text-gold-dark transition-colors">
                                                {{ $item->movie->title }}
                                            </h4>
                                            <p class="text-xs text-gray-500 mt-0.5 font-medium">
                                                {{ $item->showtime->start_time->format('H:i - d/m/Y') }} · {{ $item->showtime->room->name }}
                                            </p>
                                            <div class="mt-2 flex items-center gap-2">
                                                <span class="badge-gold text-xs px-2.5 py-0.5 rounded-lg font-bold">
                                                    Vị trí: {{ $item->seat->row_label }}{{ $item->seat->seat_number }} ({{ $item->seat->type }})
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Price -->
                                        <div class="text-right shrink-0">
                                            <span class="font-display font-black text-black text-lg">
                                                {{ number_format($item->price) }}₫
                                            </span>
                                            <button
                                                type="button"
                                                x-show="step === 'cart'"
                                                @click="removeItem({{ $item->id }})"
                                                class="mt-1 text-[11px] font-bold text-[#CC0000] hover:underline block"
                                            >
                                                Bỏ vé
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Buyer Information Form Card -->
                        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-black/10 shadow-sm space-y-6">
                            <div class="flex items-start justify-between gap-4 border-b border-black/10 pb-5">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-11 h-11 rounded-2xl bg-[#FFF8E1] border border-[#FFE082] text-gold-dark flex items-center justify-center shadow-xs shrink-0">
                                        <svg class="w-5 h-5 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-display font-black text-lg sm:text-xl text-black tracking-tight">
                                            Thông Tin Người Đặt &amp; Nhận Vé
                                        </h3>
                                        <p class="text-xs text-gray-500 font-medium mt-0.5">Thông tin sẽ được in trực tiếp lên Vé Điện Tử E-Ticket để đối soát cổng sự kiện.</p>
                                    </div>
                                </div>
                                <span class="badge-sage text-[10px] font-bold px-3 py-1 rounded-full border border-emerald-200/80 shrink-0 hidden sm:inline-flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Bảo Mật Thông Tin
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <!-- Field 1: Full Name -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-800 mb-2 flex items-center justify-between">
                                        <span>Họ và tên người đặt vé <span class="text-crimson font-black">*</span></span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </span>
                                        <input 
                                            type="text" 
                                            x-model="buyerInfo.fullName"
                                            placeholder="VD: Nguyễn Văn Kiên" 
                                            class="w-full rounded-2xl pl-10 pr-4 py-3 bg-[#FAF9F6] border border-black/10 focus:border-gold-dark focus:bg-white focus:ring-4 focus:ring-gold-antique/20 text-xs sm:text-sm font-semibold text-black placeholder-gray-400 transition-all shadow-2xs"
                                            required
                                        >
                                    </div>
                                </div>

                                <!-- Field 2: Phone Number -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-800 mb-2 flex items-center justify-between">
                                        <span>Số điện thoại nhận SMS vé <span class="text-crimson font-black">*</span></span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </span>
                                        <input 
                                            type="tel" 
                                            x-model="buyerInfo.phone"
                                            placeholder="VD: 0988 123 456" 
                                            class="w-full rounded-2xl pl-10 pr-4 py-3 bg-[#FAF9F6] border border-black/10 focus:border-gold-dark focus:bg-white focus:ring-4 focus:ring-gold-antique/20 text-xs sm:text-sm font-semibold text-black placeholder-gray-400 font-mono transition-all shadow-2xs"
                                            required
                                        >
                                    </div>
                                </div>

                                <!-- Field 3: Email -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-800 mb-2 flex items-center justify-between">
                                        <span>Email nhận vé E-Ticket <span class="text-crimson font-black">*</span></span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </span>
                                        <input 
                                            type="email" 
                                            x-model="buyerInfo.email"
                                            placeholder="VD: kien.nguyen@example.com" 
                                            class="w-full rounded-2xl pl-10 pr-4 py-3 bg-[#FAF9F6] border border-black/10 focus:border-gold-dark focus:bg-white focus:ring-4 focus:ring-gold-antique/20 text-xs sm:text-sm font-semibold text-black placeholder-gray-400 transition-all shadow-2xs"
                                            required
                                        >
                                    </div>
                                </div>

                                <!-- Field 4: National ID Card -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-800 mb-2 flex items-center justify-between">
                                        <span>Số CCCD / Hộ chiếu <span class="text-gray-400 font-normal text-[11px]">(Đối soát cổng)</span></span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                            </svg>
                                        </span>
                                        <input 
                                            type="text" 
                                            x-model="buyerInfo.idCard"
                                            placeholder="VD: 001201008899" 
                                            class="w-full rounded-2xl pl-10 pr-4 py-3 bg-[#FAF9F6] border border-black/10 focus:border-gold-dark focus:bg-white focus:ring-4 focus:ring-gold-antique/20 text-xs sm:text-sm font-semibold text-black placeholder-gray-400 font-mono transition-all shadow-2xs"
                                        >
                                    </div>
                                </div>

                                <!-- Field 5: City -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-800 mb-2">
                                        Tỉnh / Thành phố
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </span>
                                        <input 
                                            type="text" 
                                            x-model="buyerInfo.city"
                                            placeholder="VD: Hà Nội, TP.HCM, Đà Nẵng..." 
                                            class="w-full rounded-2xl pl-10 pr-4 py-3 bg-[#FAF9F6] border border-black/10 focus:border-gold-dark focus:bg-white focus:ring-4 focus:ring-gold-antique/20 text-xs sm:text-sm font-semibold text-black placeholder-gray-400 transition-all shadow-2xs"
                                        >
                                    </div>
                                </div>

                                <!-- Field 6: Address -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-800 mb-2">
                                        Địa chỉ liên hệ
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                        </span>
                                        <input 
                                            type="text" 
                                            x-model="buyerInfo.address"
                                            placeholder="Số nhà, tên đường, phường/xã..." 
                                            class="w-full rounded-2xl pl-10 pr-4 py-3 bg-[#FAF9F6] border border-black/10 focus:border-gold-dark focus:bg-white focus:ring-4 focus:ring-gold-antique/20 text-xs sm:text-sm font-semibold text-black placeholder-gray-400 transition-all shadow-2xs"
                                        >
                                    </div>
                                </div>

                                <!-- Field 7: Notes -->
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-800 mb-2">
                                        Ghi chú đơn vé (Tùy chọn)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                            </svg>
                                        </span>
                                        <input 
                                            type="text" 
                                            x-model="buyerInfo.notes"
                                            placeholder="Yêu cầu hỗ trợ thêm, ghi chú nhận vé..." 
                                            class="w-full rounded-2xl pl-10 pr-4 py-3 bg-[#FAF9F6] border border-black/10 focus:border-gold-dark focus:bg-white focus:ring-4 focus:ring-gold-antique/20 text-xs sm:text-sm font-semibold text-black placeholder-gray-400 transition-all shadow-2xs"
                                        >
                                    </div>
                                </div>

                                <!-- Helper Bottom Guarantee Banner -->
                                <div class="sm:col-span-2 pt-3 border-t border-black/5 flex items-center gap-2.5 text-xs text-gray-500 font-medium">
                                    <svg class="w-4 h-4 text-gold-dark shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Vé Điện Tử E-Ticket sẽ được gửi tức thì kèm mã QR bảo mật qua Email &amp; SMS sau khi giao dịch thành công.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Choice Card -->
                        <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4">
                            <div class="border-b border-black/10 pb-4">
                                <h3 class="font-serif text-lg font-bold text-black flex items-center gap-2">
                                    <svg class="w-5 h-5 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <span>Chọn Phương Thức Thanh Toán</span>
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">Hệ thống hỗ trợ thanh toán tự động qua VietQR liên ngân hàng, Ví điện tử và Thẻ quốc tế.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-2">
                                <!-- Option 1: VietQR -->
                                <label 
                                    class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                    :class="selectedMethod === 'vietqr' ? 'border-gold-antique bg-[#FFF8E1]/50 shadow-sm' : 'border-black/10 bg-[#FAF9F6] hover:border-black/20'"
                                >
                                    <input type="radio" name="payment_method" value="vietqr" x-model="selectedMethod" class="mt-1 text-gold-dark focus:ring-gold-dark">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-display font-black text-xs text-black">Chuyển Khoản VietQR 24/7</span>
                                            <span class="badge-gold text-[9px] px-2 py-0.2 rounded-full font-bold">Khuyên Dùng</span>
                                        </div>
                                        <p class="text-[11px] text-gray-500 mt-1">Quét mã QR qua Techcombank, Vietcombank, MBBank, BIDV...</p>
                                    </div>
                                </label>

                                <!-- Option 2: MoMo -->
                                <label 
                                    class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                    :class="selectedMethod === 'momo' ? 'border-[#A50064] bg-[#FDE8EE]/50 shadow-sm' : 'border-black/10 bg-[#FAF9F6] hover:border-black/20'"
                                >
                                    <input type="radio" name="payment_method" value="momo" x-model="selectedMethod" class="mt-1 text-[#A50064] focus:ring-[#A50064]">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-display font-black text-xs text-black">Ví Điện Tử MoMo</span>
                                        </div>
                                        <p class="text-[11px] text-gray-500 mt-1">Quét mã MoMo QR thanh toán tức thì qua App MoMo.</p>
                                    </div>
                                </label>

                                <!-- Option 3: ZaloPay -->
                                <label 
                                    class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                    :class="selectedMethod === 'zalopay' ? 'border-[#0068FF] bg-blue-50/50 shadow-sm' : 'border-black/10 bg-[#FAF9F6] hover:border-black/20'"
                                >
                                    <input type="radio" name="payment_method" value="zalopay" x-model="selectedMethod" class="mt-1 text-[#0068FF] focus:ring-[#0068FF]">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-display font-black text-xs text-black">Ví Điện Tử ZaloPay</span>
                                        </div>
                                        <p class="text-[11px] text-gray-500 mt-1">Thanh toán liền mạch qua Zalo &amp; Ví ZaloPay.</p>
                                    </div>
                                </label>

                                <!-- Option 4: Visa/Mastercard -->
                                <label 
                                    class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                    :class="selectedMethod === 'card' ? 'border-black bg-gray-100 shadow-sm' : 'border-black/10 bg-[#FAF9F6] hover:border-black/20'"
                                >
                                    <input type="radio" name="payment_method" value="card" x-model="selectedMethod" class="mt-1 text-black focus:ring-black">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-display font-black text-xs text-black">Thẻ Quốc Tế (Visa / Master)</span>
                                        </div>
                                        <p class="text-[11px] text-gray-500 mt-1">Cổng thanh toán quốc tế 3D-Secure an toàn.</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Order Summary & Trigger Checkout (5 Cols Sticky) -->
                    <div class="lg:col-span-5 space-y-4 lg:sticky lg:top-24 self-start">
                        <!-- High-Contrast Modern Order Summary Card -->
                        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-xl shadow-slate-200/50 space-y-6 relative overflow-hidden">
                            <!-- Top Ambient Accent Line -->
                            <div class="h-1.5 bg-gradient-to-r from-rose-500 via-amber-400 to-rose-600 absolute top-0 inset-x-0"></div>

                            <!-- Header: Title & Ticket Count Badge -->
                            <div class="flex items-center justify-between gap-3 pb-4 border-b border-slate-100">
                                <div>
                                    <h3 class="font-display font-black text-xl text-slate-900 tracking-tight">
                                        Tóm Tắt Đơn Vé
                                    </h3>
                                    <p class="text-xs text-slate-500 font-medium mt-0.5">Kiểm tra số lượng &amp; mức thanh toán</p>
                                </div>
                                <span class="badge-rose text-xs font-bold px-3 py-1.5 rounded-full shadow-xs shrink-0 flex items-center gap-1.5 border border-rose-200/70 whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                    </svg>
                                    <span>{{ count($items) }} Vé Đã Chọn</span>
                                </span>
                            </div>

                            <!-- Itemized Breakdown with Icons & Generous Spacing -->
                            <div class="space-y-4 text-xs font-medium">
                                <!-- Row 1: Subtotal -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5 text-slate-600 font-semibold">
                                        <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 shrink-0">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                            </svg>
                                        </div>
                                        <span>Tạm tính tiền vé:</span>
                                    </div>
                                    <span class="font-black text-sm text-slate-900 font-mono">{{ number_format($totalPrice) }}₫</span>
                                </div>

                                <!-- Row 2: Service Fee & E-Ticket QR -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5 text-slate-600 font-semibold">
                                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                            </svg>
                                        </div>
                                        <span>Phí xuất vé &amp; QR:</span>
                                    </div>
                                    <span class="text-xs font-black text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2.5 py-1 rounded-full inline-flex items-center gap-1 whitespace-nowrap">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        Miễn phí 0₫
                                    </span>
                                </div>

                                <!-- Row 3: Payment Method -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5 text-slate-600 font-semibold">
                                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                            </svg>
                                        </div>
                                        <span>Phương thức:</span>
                                    </div>
                                    <span class="font-bold text-slate-800 bg-slate-100 px-3 py-1 rounded-xl text-xs inline-flex items-center gap-1.5 shadow-2xs whitespace-nowrap" x-text="selectedMethod === 'vietqr' ? '🏦 VietQR 24/7' : (selectedMethod === 'momo' ? '🟣 Ví MoMo' : (selectedMethod === 'zalopay' ? '🔵 Ví ZaloPay' : '💳 Thẻ Quốc Tế'))">
                                        🏦 VietQR 24/7
                                    </span>
                                </div>
                            </div>

                            <!-- Visual Hierarchy Focal Point: Total Amount Sub-Card -->
                            <div class="bg-gradient-to-br from-amber-50/80 via-orange-50/40 to-amber-100/60 rounded-2xl p-4 sm:p-5 border border-amber-200/70 shadow-xs flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <span class="text-xs font-black uppercase tracking-wider text-amber-950 block">
                                        Tổng Thanh Toán
                                    </span>
                                    <span class="text-[11px] text-amber-900/70 font-medium block mt-0.5 whitespace-nowrap">
                                        Đã bao gồm thuế VAT
                                    </span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-display font-black text-2xl sm:text-3xl text-[#976D00] tracking-tight block whitespace-nowrap">
                                        {{ number_format($totalPrice) }}<span class="text-lg sm:text-xl font-black text-[#976D00] ml-0.5">₫</span>
                                    </span>
                                </div>
                            </div>

                            <!-- High-Prominence Action CTA Button with #C08497 Rose Taupe Color -->
                            <button 
                                type="button" 
                                @click="proceedToPayment()"
                                class="w-full py-4 px-6 rounded-2xl bg-[#C08497] hover:bg-[#A96B7E] text-white font-black text-base shadow-xl shadow-[#C08497]/30 hover:shadow-[#C08497]/50 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex items-center justify-center gap-3 cursor-pointer group whitespace-nowrap"
                            >
                                <span>Tiến Hành Thanh Toán</span>
                                <span class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center group-hover:translate-x-1 transition-transform shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </span>
                            </button>

                            <!-- Enhanced Security & Trust Logos Section -->
                            <div class="pt-3 border-t border-slate-100 space-y-3">
                                <div class="flex items-center justify-center gap-2 text-slate-700 text-xs font-bold whitespace-nowrap">
                                    <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                    <span>Giao Dịch Được Bảo Mật 256–Bit SSL</span>
                                </div>

                                <!-- Trust Security Seals -->
                                <div class="flex items-center justify-center gap-2 text-[10px] font-bold uppercase tracking-wider text-slate-600 flex-wrap">
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-700 border border-slate-200/80">
                                        NAPAS 24/7
                                    </span>
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-700 border border-slate-200/80">
                                        PCI-DSS Level 1
                                    </span>
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-700 border border-slate-200/80">
                                        VietQR Verified
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>


            <!-- ========================================================= -->
            <!-- STEP 2: PROFESSIONAL PAYMENT & QR CODE SCREEN             -->
            <!-- ========================================================= -->
            <div x-show="step === 'payment_qr'" x-transition:enter="animate-fade-in" style="display: none;">
                
                <!-- Payment Method Switcher Tabs at Step 2 -->
                <div class="bg-white rounded-3xl p-3 sm:p-4 border border-black/10 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto">
                        <!-- Tab 1: VietQR -->
                        <button 
                            type="button" 
                            @click="selectedMethod = 'vietqr'"
                            class="px-4 py-2 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                            :class="selectedMethod === 'vietqr' ? 'bg-black text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        >
                            <span>🏦 Chuyển khoản VietQR</span>
                        </button>

                        <!-- Tab 2: MoMo -->
                        <button 
                            type="button" 
                            @click="selectedMethod = 'momo'"
                            class="px-4 py-2 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                            :class="selectedMethod === 'momo' ? 'bg-[#A50064] text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        >
                            <span>🟣 Ví MoMo</span>
                        </button>

                        <!-- Tab 3: ZaloPay -->
                        <button 
                            type="button" 
                            @click="selectedMethod = 'zalopay'"
                            class="px-4 py-2 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                            :class="selectedMethod === 'zalopay' ? 'bg-[#0068FF] text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        >
                            <span>🔵 Ví ZaloPay</span>
                        </button>

                        <!-- Tab 4: Card -->
                        <button 
                            type="button" 
                            @click="selectedMethod = 'card'"
                            class="px-4 py-2 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                            :class="selectedMethod === 'card' ? 'bg-gold-dark text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        >
                            <span>💳 Thẻ Quốc Tế</span>
                        </button>
                    </div>

                    <button 
                        type="button" 
                        @click="backToCart()"
                        class="text-xs text-gray-500 hover:text-black font-bold flex items-center gap-1 cursor-pointer shrink-0 ml-auto"
                    >
                        <span>&larr; Sửa thông tin vé / người đặt</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <!-- Left: Bank / Wallet Details Card + Buyer Info Summary (7 Cols) -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- VIEW 1: VIETQR DETAILS -->
                        <div x-show="selectedMethod === 'vietqr'" class="bg-white rounded-3xl p-6 sm:p-7 border border-black/10 shadow-sm space-y-5">
                            <div class="border-b border-black/10 pb-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-serif font-black text-lg text-black">Thông Tin Chuyển Khoản Ngân Hàng</h4>
                                    <span class="badge-sage text-[10px] px-2.5 py-1 rounded-full font-bold">
                                        Khớp lệnh tự động 24/7
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Chọn ngân hàng thụ hưởng thuận tiện nhất cho bạn:</p>

                                <!-- Bank Selector Pills -->
                                <div class="flex items-center gap-2 mt-3 overflow-x-auto pb-1">
                                    <button 
                                        type="button" 
                                        @click="selectedBank = 'TCB'"
                                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer border"
                                        :class="selectedBank === 'TCB' ? 'bg-red-50 border-red-500 text-red-700 shadow-sm font-black' : 'bg-[#FAF9F6] border-black/10 text-gray-600 hover:border-black/30'"
                                    >
                                        Techcombank
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="selectedBank = 'VCB'"
                                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer border"
                                        :class="selectedBank === 'VCB' ? 'bg-emerald-50 border-emerald-600 text-emerald-800 shadow-sm font-black' : 'bg-[#FAF9F6] border-black/10 text-gray-600 hover:border-black/30'"
                                    >
                                        Vietcombank
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="selectedBank = 'MB'"
                                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer border"
                                        :class="selectedBank === 'MB' ? 'bg-blue-50 border-blue-600 text-blue-800 shadow-sm font-black' : 'bg-[#FAF9F6] border-black/10 text-gray-600 hover:border-black/30'"
                                    >
                                        MBBank
                                    </button>
                                </div>
                            </div>

                            <!-- Account Details Table -->
                            <div class="space-y-3.5 text-xs">
                                <!-- Bank Name -->
                                <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/5 flex items-center justify-between">
                                    <div>
                                        <span class="text-gray-500 block text-[11px] font-semibold">Ngân hàng thụ hưởng:</span>
                                        <span class="font-bold text-sm text-black" x-text="currentBank.fullName">Ngân hàng TMCP Kỹ Thương Việt Nam</span>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-lg text-white font-black text-[11px] uppercase tracking-wider" :class="currentBank.logoBg" x-text="currentBank.shortName">
                                        Techcombank
                                    </span>
                                </div>

                                <!-- Account Number -->
                                <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/5 flex items-center justify-between">
                                    <div>
                                        <span class="text-gray-500 block text-[11px] font-semibold">Số tài khoản thụ hưởng:</span>
                                        <span class="font-mono font-black text-base text-black tracking-wider" x-text="currentBank.accountNo">1903 8888 6688</span>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="copyToClipboard(currentBank.accountNo, 'Số tài khoản')"
                                        class="btn-ghost-light px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                        <span>Sao chép</span>
                                    </button>
                                </div>

                                <!-- Account Holder Name -->
                                <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/5 flex items-center justify-between">
                                    <div>
                                        <span class="text-gray-500 block text-[11px] font-semibold">Tên chủ tài khoản:</span>
                                        <span class="font-bold text-xs text-black" x-text="currentBank.accountHolder">CONG TY CP TICKETBOX VIETNAM</span>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="copyToClipboard(currentBank.accountHolder, 'Tên chủ tài khoản')"
                                        class="btn-ghost-light px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                        <span>Sao chép</span>
                                    </button>
                                </div>

                                <!-- Amount -->
                                <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/5 flex items-center justify-between">
                                    <div>
                                        <span class="text-gray-500 block text-[11px] font-semibold">Số tiền thanh toán:</span>
                                        <span class="font-display font-black text-lg text-crimson" x-text="formatCurrency(totalAmount)">500.000 ₫</span>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="copyToClipboard(totalAmount.toString(), 'Số tiền')"
                                        class="btn-ghost-light px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                        <span>Sao chép</span>
                                    </button>
                                </div>

                                <!-- Transfer Content / Order Code -->
                                <div class="bg-[#FFF8E1] rounded-2xl p-3.5 border border-[#FFE082] flex items-center justify-between">
                                    <div>
                                        <span class="text-gold-dark block text-[11px] font-bold uppercase">Nội dung chuyển khoản (Bắt buộc):</span>
                                        <span class="font-mono font-black text-base text-black tracking-wider" x-text="orderCode">TBX-829143</span>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="copyToClipboard(orderCode, 'Nội dung chuyển khoản')"
                                        class="btn-gold px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                        <span>Sao chép</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- VIEW 2: MOMO DETAILS -->
                        <div x-show="selectedMethod === 'momo'" class="bg-white rounded-3xl p-6 sm:p-7 border border-black/10 shadow-sm space-y-5" style="display: none;">
                            <div class="border-b border-black/10 pb-4 flex items-center justify-between">
                                <div>
                                    <h4 class="font-serif font-black text-lg text-black">Thanh Toán Qua Ví Điện Tử MoMo</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Mở App MoMo và quét mã QR để hoàn tất thanh toán trong 1 chạm.</p>
                                </div>
                                <div class="w-10 h-10 rounded-2xl bg-[#A50064] text-white flex items-center justify-center font-black text-xs shadow-md">
                                    MoMo
                                </div>
                            </div>

                            <div class="space-y-3.5 text-xs">
                                <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/5 flex items-center justify-between">
                                    <div>
                                        <span class="text-gray-500 block text-[11px] font-semibold">Tên đơn vị chấp nhận:</span>
                                        <span class="font-bold text-xs text-black" x-text="momoInfo.merchant">TICKETBOX VIETNAM</span>
                                    </div>
                                    <span class="badge-rose text-[10px] px-2.5 py-0.5 rounded-full font-bold">MoMo Business</span>
                                </div>
                                <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/5 flex items-center justify-between">
                                    <div>
                                        <span class="text-gray-500 block text-[11px] font-semibold">Số tiền thanh toán:</span>
                                        <span class="font-display font-black text-lg text-[#A50064]" x-text="formatCurrency(totalAmount)">500.000 ₫</span>
                                    </div>
                                    <span class="text-sage-forest font-bold text-xs">Miễn phí giao dịch</span>
                                </div>
                                <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/5 flex items-center justify-between">
                                    <div>
                                        <span class="text-gray-500 block text-[11px] font-semibold">Mã giao dịch:</span>
                                        <span class="font-mono font-black text-base text-black" x-text="orderCode">TBX-829143</span>
                                    </div>
                                    <button type="button" @click="copyToClipboard(orderCode, 'Mã giao dịch')" class="btn-ghost-light px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer">Sao chép</button>
                                </div>
                            </div>
                        </div>

                        <!-- VIEW 3: ZALOPAY DETAILS -->
                        <div x-show="selectedMethod === 'zalopay'" class="bg-white rounded-3xl p-6 sm:p-7 border border-black/10 shadow-sm space-y-5" style="display: none;">
                            <div class="border-b border-black/10 pb-4 flex items-center justify-between">
                                <div>
                                    <h4 class="font-serif font-black text-lg text-black">Thanh Toán Qua Ví ZaloPay</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Quét mã ZaloPay QR trực tiếp trên Zalo hoặc ứng dụng ZaloPay.</p>
                                </div>
                                <div class="w-10 h-10 rounded-2xl bg-[#0068FF] text-white flex items-center justify-center font-black text-xs shadow-md">
                                    Zalo
                                </div>
                            </div>

                            <div class="space-y-3.5 text-xs">
                                <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/5 flex items-center justify-between">
                                    <div>
                                        <span class="text-gray-500 block text-[11px] font-semibold">Đơn vị thụ hưởng:</span>
                                        <span class="font-bold text-xs text-black" x-text="zalopayInfo.merchant">TICKETBOX TICKET JSC</span>
                                    </div>
                                    <span class="badge-sage text-[10px] px-2.5 py-0.5 rounded-full font-bold">ZaloPay Merchant</span>
                                </div>
                                <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/5 flex items-center justify-between">
                                    <div>
                                        <span class="text-gray-500 block text-[11px] font-semibold">Số tiền cần thanh toán:</span>
                                        <span class="font-display font-black text-lg text-[#0068FF]" x-text="formatCurrency(totalAmount)">500.000 ₫</span>
                                    </div>
                                    <span class="text-sage-forest font-bold text-xs">Miễn phí giao dịch</span>
                                </div>
                                <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/5 flex items-center justify-between">
                                    <div>
                                        <span class="text-gray-500 block text-[11px] font-semibold">Mã đơn hàng:</span>
                                        <span class="font-mono font-black text-base text-black" x-text="orderCode">TBX-829143</span>
                                    </div>
                                    <button type="button" @click="copyToClipboard(orderCode, 'Mã đơn hàng')" class="btn-ghost-light px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer">Sao chép</button>
                                </div>
                            </div>
                        </div>

                        <!-- VIEW 4: CREDIT / DEBIT CARD DETAILS -->
                        <div x-show="selectedMethod === 'card'" class="bg-white rounded-3xl p-6 sm:p-7 border border-black/10 shadow-sm space-y-5" style="display: none;">
                            <div class="border-b border-black/10 pb-4 flex items-center justify-between">
                                <div>
                                    <h4 class="font-serif font-black text-lg text-black">Thanh Toán Thẻ Quốc Tế (Visa / Mastercard)</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Nhập thông tin thẻ thanh toán an toàn qua cổng bảo mật 3D-Secure.</p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-1 rounded bg-blue-900 text-white font-black text-[10px]">VISA</span>
                                    <span class="px-2 py-1 rounded bg-amber-600 text-white font-black text-[10px]">MC</span>
                                </div>
                            </div>

                            <div class="space-y-3.5 text-xs">
                                <div>
                                    <label class="block text-gray-600 font-bold mb-1">Số thẻ thanh toán</label>
                                    <input type="text" placeholder="4111 2222 3333 4444" class="glass-input w-full rounded-xl px-4 py-2.5 font-mono text-xs">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-gray-600 font-bold mb-1">Hạn sử dụng (MM/YY)</label>
                                        <input type="text" placeholder="12/28" class="glass-input w-full rounded-xl px-4 py-2.5 font-mono text-xs">
                                    </div>
                                    <div>
                                        <label class="block text-gray-600 font-bold mb-1">Mã bảo mật CVV</label>
                                        <input type="password" placeholder="•••" maxlength="3" class="glass-input w-full rounded-xl px-4 py-2.5 font-mono text-xs">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-gray-600 font-bold mb-1">Tên in trên thẻ</label>
                                    <input type="text" :value="buyerInfo.fullName.toUpperCase()" class="glass-input w-full rounded-xl px-4 py-2.5 font-mono uppercase text-xs">
                                </div>
                            </div>
                        </div>

                        <!-- BUYER INFORMATION SUMMARY CARD IN STEP 2 -->
                        <div class="bg-white rounded-3xl p-5 sm:p-6 border border-black/10 shadow-sm space-y-3">
                            <div class="flex items-center justify-between border-b border-black/10 pb-3">
                                <h5 class="font-serif font-bold text-sm text-black flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span>Thông Tin Người Nhận Vé</span>
                                </h5>
                                <button 
                                    type="button" 
                                    @click="backToCart()"
                                    class="text-xs text-rose-taupe hover:text-black font-bold flex items-center gap-1 cursor-pointer"
                                >
                                    <span>Chỉnh sửa</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Họ và tên:</span>
                                    <span class="text-black font-bold" x-text="buyerInfo.fullName">Nguyễn Văn Kiên</span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Số điện thoại:</span>
                                    <span class="text-black font-bold font-mono" x-text="buyerInfo.phone">0988 123 456</span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Email nhận vé:</span>
                                    <span class="text-black font-medium" x-text="buyerInfo.email">kien.nguyen@example.com</span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Số CCCD / CMND:</span>
                                    <span class="text-black font-bold font-mono" x-text="buyerInfo.idCard || 'Chưa cung cấp'">001201008899</span>
                                </div>
                                <div class="sm:col-span-2" x-show="buyerInfo.city || buyerInfo.address">
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Địa chỉ:</span>
                                    <span class="text-gray-700" x-text="(buyerInfo.address ? buyerInfo.address + ', ' : '') + (buyerInfo.city || '')">Số 18 Hoàng Diệu, Ba Đình, Hà Nội</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right: Ultra-Crisp Official QR Code Box (5 Cols) -->
                    <div class="lg:col-span-5 space-y-6">
                        
                        <!-- QR Code Container -->
                        <div class="bg-white rounded-3xl p-6 border-2 border-gold-antique/60 shadow-xl text-center space-y-4 relative overflow-hidden">
                            
                            <!-- Processing Overlay -->
                            <div 
                                x-show="isProcessingPayment" 
                                x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                class="absolute inset-0 bg-white/95 backdrop-blur-sm z-20 flex flex-col items-center justify-center p-6 text-center space-y-3"
                                style="display: none;"
                            >
                                <div class="w-12 h-12 rounded-full border-4 border-gold-antique border-t-transparent animate-spin"></div>
                                <h4 class="font-display font-black text-black text-base">Đang xác thực giao dịch...</h4>
                                <p class="text-xs text-gray-500">Hệ thống TicketBox đang đồng bộ mã vé E-Ticket của bạn.</p>
                            </div>

                            <!-- Top QR Header Badge -->
                            <div class="flex items-center justify-center gap-2">
                                <span class="badge-gold text-[10px] px-3 py-0.5 rounded-full font-black uppercase" x-show="selectedMethod === 'vietqr'">
                                    VIETQR · NAPAS247
                                </span>
                                <span class="badge-rose text-[10px] px-3 py-0.5 rounded-full font-black uppercase" x-show="selectedMethod === 'momo'">
                                    MOMO QR PAY
                                </span>
                                <span class="badge-sage text-[10px] px-3 py-0.5 rounded-full font-black uppercase" x-show="selectedMethod === 'zalopay'">
                                    ZALOPAY QR CODE
                                </span>
                                <span class="badge-dark text-[10px] px-3 py-0.5 rounded-full font-black uppercase" x-show="selectedMethod === 'card'">
                                    SECURE CHECKOUT
                                </span>
                            </div>

                            <!-- High-Fidelity Clickable QR Card (VietQR) -->
                            <div 
                                x-show="selectedMethod === 'vietqr'"
                                @click="simulatePaymentSuccess()"
                                class="cursor-pointer group relative mx-auto p-4 bg-[#FAF9F6] rounded-2xl border border-black/10 hover:border-gold-dark hover:shadow-lg transition-all"
                            >
                                <!-- Real VietQR Image with high crispness -->
                                <div class="w-60 max-w-full mx-auto bg-white rounded-xl p-2 shadow-sm border border-black/5 overflow-hidden group-hover:scale-[1.01] transition-transform">
                                    <img 
                                        :src="vietQrUrl" 
                                        alt="VietQR Payment Code"
                                        class="w-full h-auto object-contain rounded-lg"
                                        onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TICKETBOX_PAYMENT_TBX';"
                                    >
                                </div>
                                <p class="text-[11px] text-gray-500 mt-2 font-medium">Quét mã bằng ứng dụng Ngân hàng của bạn</p>
                            </div>

                            <!-- MoMo QR Card -->
                            <div 
                                x-show="selectedMethod === 'momo'"
                                @click="simulatePaymentSuccess()"
                                class="cursor-pointer group relative mx-auto p-4 bg-[#FAF9F6] rounded-2xl border border-[#A50064]/30 hover:border-[#A50064] hover:shadow-lg transition-all"
                                style="display: none;"
                            >
                                <div class="w-56 h-56 mx-auto bg-gradient-to-b from-[#A50064] to-[#80004D] rounded-2xl p-3 shadow-md flex flex-col items-center justify-between text-white">
                                    <div class="flex items-center justify-between w-full px-2">
                                        <span class="font-black text-xs tracking-wider">MoMo Pay</span>
                                        <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-bold">24/7</span>
                                    </div>
                                    <div class="w-36 h-36 bg-white rounded-xl p-2 flex items-center justify-center">
                                        <img 
                                            :src="'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=2|99|0988888999|TICKETBOX_VIETNAM||0|0|' + totalAmount + '|' + orderCode + '|transfer_myqr'" 
                                            alt="MoMo QR"
                                            class="w-full h-full object-contain"
                                        >
                                    </div>
                                    <span class="text-[11px] font-bold" x-text="formatCurrency(totalAmount)">500.000 ₫</span>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-2 font-medium">Quét mã bằng ứng dụng MoMo</p>
                            </div>

                            <!-- ZaloPay QR Card -->
                            <div 
                                x-show="selectedMethod === 'zalopay'"
                                @click="simulatePaymentSuccess()"
                                class="cursor-pointer group relative mx-auto p-4 bg-[#FAF9F6] rounded-2xl border border-[#0068FF]/30 hover:border-[#0068FF] hover:shadow-lg transition-all"
                                style="display: none;"
                            >
                                <div class="w-56 h-56 mx-auto bg-gradient-to-b from-[#0068FF] to-[#0047B3] rounded-2xl p-3 shadow-md flex flex-col items-center justify-between text-white">
                                    <div class="flex items-center justify-between w-full px-2">
                                        <span class="font-black text-xs tracking-wider">ZaloPay</span>
                                        <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-bold">QR</span>
                                    </div>
                                    <div class="w-36 h-36 bg-white rounded-xl p-2 flex items-center justify-center">
                                        <img 
                                            :src="'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=ZALOPAY_TICKETBOX_' + orderCode + '_' + totalAmount" 
                                            alt="ZaloPay QR"
                                            class="w-full h-full object-contain"
                                        >
                                    </div>
                                    <span class="text-[11px] font-bold" x-text="formatCurrency(totalAmount)">500.000 ₫</span>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-2 font-medium">Quét mã bằng ứng dụng Zalo hoặc ZaloPay</p>
                            </div>

                            <!-- Card Checkout Trigger -->
                            <div x-show="selectedMethod === 'card'" style="display: none;" class="p-4 bg-[#FAF9F6] rounded-2xl border border-black/10">
                                <div class="w-full h-36 bg-gradient-to-tr from-gray-900 via-gray-800 to-black rounded-2xl p-4 text-white flex flex-col justify-between shadow-lg">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-mono font-bold tracking-widest text-gold-antique">TICKETBOX VIP PASS</span>
                                        <span class="text-xs font-black">VISA</span>
                                    </div>
                                    <div class="font-mono text-sm tracking-widest text-center text-gray-300">•••• •••• •••• 8899</div>
                                    <div class="flex justify-between items-center text-[10px]">
                                        <span class="uppercase font-bold" x-text="buyerInfo.fullName">NGUYEN VAN KIEN</span>
                                        <span class="font-mono">12/28</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Primary Action Button -->
                            <button 
                                type="button" 
                                @click="simulatePaymentSuccess()"
                                class="btn-sage w-full py-3.5 rounded-2xl font-black text-xs sm:text-sm shadow-md flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <svg class="w-4 h-4 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Xác nhận đã hoàn tất thanh toán</span>
                            </button>
                        </div>

                    </div>

                </div>

            </div>


            <!-- ========================================================= -->
            <!-- STEP 3: OFFICIAL E-TICKET BOARDING PASS & QR DETAILS      -->
            <!-- ========================================================= -->
            <div x-show="step === 'e_ticket'" x-transition:enter="animate-fade-in" style="display: none;" class="space-y-8">
                
                <!-- Success Notification Bar -->
                <div class="bg-emerald-50 border border-emerald-300 rounded-3xl p-5 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-sage-forest text-white flex items-center justify-center shrink-0 shadow-md">
                            <svg class="w-6 h-6 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="badge-sage text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase">Thanh Toán Thành Công</span>
                                <span class="text-xs text-gray-500 font-mono font-bold" x-text="'Mã đơn: ' + orderCode">Mã đơn: TBX-829143</span>
                            </div>
                            <h2 class="font-serif font-black text-xl text-black mt-0.5">Vé Điện Tử E-Ticket Đã Được Kích Hoạt</h2>
                            <p class="text-xs text-gray-600">Quý khách xuất trình mã QR trên vé tại cổng an ninh hoặc lưu ảnh vé về máy để làm thủ tục check-in.</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2.5 w-full sm:w-auto">
                        <button 
                            type="button" 
                            @click="downloadTicketAsPNG()"
                            class="btn-gold flex-1 sm:flex-initial px-5 py-2.5 rounded-xl text-xs font-black shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Lưu vé về máy (PNG)</span>
                        </button>

                        <button 
                            type="button" 
                            @click="printTicket()"
                            class="btn-ghost-light px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>In vé</span>
                        </button>
                    </div>
                </div>

                <!-- LUXURY E-TICKET PASS BOARDING CARD -->
                <div id="printable-e-ticket" class="bg-white rounded-3xl border-2 border-gold-antique/60 overflow-hidden shadow-2xl relative">
                    
                    <!-- Top Ribbon -->
                    <div class="h-3 bg-gradient-to-r from-rose-taupe via-gold-antique to-sage-forest"></div>

                    <div class="grid grid-cols-1 lg:grid-cols-12">
                        
                        <!-- Left: Main Ticket Information Body (8 Cols) -->
                        <div class="lg:col-span-8 p-6 sm:p-8 space-y-6 border-b lg:border-b-0 lg:border-r border-dashed border-gold-antique/60 relative">
                            
                            <!-- Header Info -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="font-display font-black text-rose-taupe text-base tracking-widest">TICKETBOX</span>
                                    <span class="text-xs text-gray-400">|</span>
                                    <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">OFFICIAL PASS</span>
                                </div>
                                <span class="badge-sage text-[10px] px-3 py-1 rounded-full font-black uppercase flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>ĐÃ THANH TOÁN 100%</span>
                                </span>
                            </div>

                            <!-- Event Details -->
                            <div class="flex flex-col sm:flex-row gap-5 items-start">
                                @if ($firstItem)
                                    <img src="{{ $firstItem->movie->poster_url }}" alt="{{ $firstItem->movie->title }}" class="w-24 h-32 object-cover rounded-2xl shrink-0 bg-[#FAF9F6] border border-black/10 shadow-sm">
                                @endif

                                <div class="flex-1 min-w-0">
                                    <span class="badge-gold text-[9px] px-2.5 py-0.5 rounded-full font-black uppercase mb-1.5 inline-block">
                                        {{ $firstItem->movie->category->name ?? 'Sự Kiện Đặc Biệt' }}
                                    </span>
                                    <h3 class="font-serif font-black text-2xl text-black leading-tight">
                                        {{ $firstItem->movie->title ?? 'Sự Kiện TicketBox 2026' }}
                                    </h3>
                                    <p class="text-xs text-gray-600 font-medium mt-1">
                                        {{ $firstItem->showtime->start_time->format('H:i - d/m/Y') }} · {{ $firstItem->showtime->room->name }}
                                    </p>
                                </div>
                            </div>

                            <!-- Grid Details -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-4 border-y border-black/10 text-xs">
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Cổng Vào (Gate)</span>
                                    <span class="text-black font-black text-sm">CỔNG A1</span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Khu Vực (Zone)</span>
                                    <span class="text-rose-taupe font-black text-sm">VIP SEATED</span>
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Vị Trí Ghế / Hạng Vé</span>
                                    <span class="text-black font-black text-sm">
                                        @foreach ($items as $idx => $i)
                                            {{ $i->seat->row_label }}{{ $i->seat->seat_number }}{{ $idx < count($items) - 1 ? ', ' : '' }}
                                        @endforeach
                                    </span>
                                </div>
                            </div>

                            <!-- Buyer Info & Booking Code -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Chủ Sở Hữu Vé</span>
                                    <span class="text-black font-bold text-xs" x-text="buyerInfo.fullName">Nguyễn Văn Kiên</span>
                                    <span class="text-gray-500 block text-[11px]" x-text="buyerInfo.phone + ' · ' + buyerInfo.email">0988 123 456 · kien.nguyen@example.com</span>
                                    <span class="text-gray-500 block text-[11px]" x-show="buyerInfo.idCard" x-text="'CCCD: ' + buyerInfo.idCard">CCCD: 001201008899</span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-bold">Mã Tra Cứu E-Ticket</span>
                                    <span class="font-mono font-black text-black text-base" x-text="orderCode">TBX-829143</span>
                                    <span class="text-sage-forest block text-[11px] font-bold">Đã xác thực chữ ký số</span>
                                </div>
                            </div>

                            <!-- Security Footer Note -->
                            <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/5 text-[11px] text-gray-500 space-y-0.5">
                                <div class="font-bold text-gray-700">Lưu ý khi tham dự sự kiện:</div>
                                <div>• Vui lòng mang theo CCCD/Hộ chiếu trùng khớp tên trên vé khi đến cổng soát vé.</div>
                                <div>• Mỗi mã QR chỉ quét hợp lệ 01 lần tại cổng an ninh. Tuyệt đối không chia sẻ mã QR này lên mạng xã hội.</div>
                            </div>
                        </div>

                        <!-- Right: Ticket Stub with Check-In QR Code (4 Cols) -->
                        <div class="lg:col-span-4 p-6 sm:p-8 bg-slate-50/90 flex flex-col items-center justify-between text-center gap-6 relative">
                            
                            <!-- Header of Stub -->
                            <div class="w-full">
                                <span class="badge-dark text-[9px] px-3 py-1 rounded-full font-mono uppercase tracking-widest block mb-1">
                                    GATE CHECK-IN PASS
                                </span>
                                <span class="text-xs text-slate-600 font-bold block">Quét Mã Vào Cổng</span>
                            </div>

                            <!-- E-Ticket High-Res QR Code Frame -->
                            <div class="p-3.5 bg-white rounded-3xl shadow-md border-2 border-amber-400/60 space-y-2">
                                <div class="w-44 h-44 bg-white rounded-2xl p-2 flex items-center justify-center overflow-hidden border border-slate-100">
                                    <img 
                                        :src="'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=TICKETBOX_AUTH_PASS_' + orderCode" 
                                        alt="Check-in QR Code"
                                        class="w-full h-full object-contain"
                                    >
                                </div>
                                <span class="font-mono text-xs font-black text-slate-900 block tracking-widest" x-text="orderCode">TBX-829143</span>
                            </div>

                            <!-- Total & Verification Stamp -->
                            <div class="space-y-1">
                                <span class="text-[11px] text-slate-500 uppercase font-bold block">Tổng tiền đã thanh toán</span>
                                <span class="font-display font-black text-slate-900 text-xl">{{ number_format($totalPrice) }}₫</span>
                            </div>

                            <!-- Download CTA inside stub -->
                            <button 
                                type="button" 
                                @click="downloadTicketAsPNG()"
                                class="btn-dark w-full py-2.5 rounded-xl text-xs font-bold shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                <span>Tải ảnh vé về máy</span>
                            </button>

                        </div>

                    </div>
                </div>

                <!-- Bottom Navigation Options -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4">
                    <a href="{{ route('bookings.history') }}" class="btn-ghost-light px-6 py-3 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-sm">
                        <span>🎟️</span>
                        <span>Xem trong danh sách "Vé của tôi"</span>
                    </a>

                    <a href="{{ route('movies.index') }}" class="btn-rose px-7 py-3 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-md">
                        <span>Khám phá thêm sự kiện khác</span>
                        <span>&rarr;</span>
                    </a>
                </div>

            </div>

        @endif

    </div>
</x-site-layout>
