<x-site-layout :title="'Vé Sự Kiện Của Tôi - TicketBox'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        
        <!-- Header -->
        <div class="mb-8">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#C08497]/15 text-[#8F435B] border border-[#C08497]/30 text-[10px] font-black uppercase tracking-wider mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-[#C08497] animate-pulse"></span>
                Vé Điện Tử E-Ticket Sự Kiện
            </span>
            <h1 class="font-serif text-3xl font-black text-slate-900 tracking-tight">Lịch Sử Vé Đã Đặt</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">Xuất trình mã QR trên vé tại cổng soát vé khán phòng hoặc cổng an ninh sự kiện.</p>
        </div>

        @if (empty($bookings))
            <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/90 space-y-4 shadow-sm">
                <div class="w-16 h-16 rounded-3xl bg-[#FAF9F6] border border-slate-200/80 text-slate-500 mx-auto flex items-center justify-center shadow-xs">
                    <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <h3 class="font-display font-black text-xl text-slate-900">Bạn chưa có đơn đặt vé nào</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                    Các vé sau khi đặt thành công sẽ xuất hiện tại đây dưới dạng thẻ E-Ticket cao cấp kèm mã QR Check-in.
                </p>
                <a href="{{ route('events.index') }}" class="inline-flex items-center gap-2 px-7 py-3 rounded-full text-xs font-black text-white bg-[#C08497] hover:bg-[#A96B7E] shadow-md shadow-[#C08497]/30 transition-all">
                    <span>Khám phá sự kiện ngay</span>
                    <span>&rarr;</span>
                </a>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($bookings as $booking)
                    @php
                        $isConfirmed = $booking->status === 'confirmed';
                    @endphp

                    <!-- Luxury E-Ticket Boarding Pass Card -->
                    <div class="bg-white rounded-3xl border border-slate-200/90 overflow-hidden shadow-lg hover:shadow-xl hover:border-gold-antique transition-all relative">
                        <!-- Top Ambient Accent Line -->
                        <div class="h-1.5 bg-gradient-to-r from-rose-500 via-amber-400 to-rose-600"></div>

                        <div class="grid grid-cols-1 md:grid-cols-12">
                            
                            <!-- Left: Event Poster & Main Details (7 Cols) -->
                            <div class="md:col-span-7 p-6 sm:p-7 flex flex-col sm:flex-row gap-5 border-b md:border-b-0 md:border-r border-dashed border-slate-200/90 relative">
                                <img src="{{ $booking->movie->poster_url }}" alt="{{ $booking->movie->title }}" class="w-20 h-28 sm:w-28 sm:h-40 object-cover rounded-2xl bg-[#FAF9F6] shrink-0 border border-slate-200/80 shadow-xs">

                                <div class="flex-1 flex flex-col justify-between min-w-0">
                                    <div>
                                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                                            @if ($isConfirmed)
                                                <span class="badge-sage text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase flex items-center gap-1 border border-emerald-200/80">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    Đã Xác Nhận
                                                </span>
                                            @else
                                                <span class="badge-rose text-[10px] px-2.5 py-0.5 rounded-full font-bold">
                                                    Đã Hủy
                                                </span>
                                            @endif
                                            <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-bold">
                                                {{ $booking->movie->category->name }}
                                            </span>
                                        </div>

                                        <h3 class="font-display font-black text-slate-900 text-lg sm:text-xl leading-snug truncate group-hover:text-gold-dark transition-colors">
                                            {{ $booking->movie->title }}
                                        </h3>

                                        <div class="mt-3 text-xs text-slate-600 space-y-1.5 font-medium">
                                            <div class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                <span>Suất diễn: <strong class="text-slate-900 font-bold">{{ $booking->showtime->start_time->format('H:i - d/m/Y') }}</strong></span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                <span>Địa điểm: <strong class="text-slate-900 font-bold">{{ $booking->showtime->room->name ?? 'Khán Phòng Sự Kiện' }}</strong></span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" /></svg>
                                                <span>Vị trí ghế: <strong class="text-rose-600 font-black">{{ implode(', ', $booking->seats) }}</strong></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500 font-mono">
                                        <span>MÃ ĐẶT VÉ: <strong class="text-slate-900 font-black tracking-wider">{{ $booking->booking_code }}</strong></span>
                                        <span class="text-emerald-700 font-sans font-bold flex items-center gap-1">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            Đã xác thực
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: High-Res Real QR Code Pass Stub (5 Cols) -->
                            <div class="md:col-span-5 p-6 sm:p-7 bg-gradient-to-br from-slate-50/90 via-amber-50/20 to-rose-50/10 flex flex-col items-center justify-between text-center gap-4 relative">
                                
                                <!-- Header of Stub -->
                                <div class="w-full">
                                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 block mb-0.5 font-mono">
                                        GATE CHECK-IN PASS
                                    </span>
                                    <span class="text-xs text-slate-800 font-bold block">Quét Mã Vào Cổng Khán Phòng</span>
                                </div>

                                <!-- Ultra-Sharp QR Code Card with Viewfinder Styling -->
                                <div class="p-3.5 bg-white rounded-2xl shadow-md border border-slate-200/90 space-y-2 relative group hover:border-[#C08497] transition-all">
                                    <!-- Viewfinder HUD Corner Accents -->
                                    <div class="w-2.5 h-2.5 border-t-2 border-l-2 border-[#C08497] absolute top-2 left-2 rounded-tl-sm pointer-events-none"></div>
                                    <div class="w-2.5 h-2.5 border-t-2 border-r-2 border-[#C08497] absolute top-2 right-2 rounded-tr-sm pointer-events-none"></div>
                                    <div class="w-2.5 h-2.5 border-b-2 border-l-2 border-[#C08497] absolute bottom-2 left-2 rounded-bl-sm pointer-events-none"></div>
                                    <div class="w-2.5 h-2.5 border-b-2 border-r-2 border-[#C08497] absolute bottom-2 right-2 rounded-br-sm pointer-events-none"></div>

                                    <!-- QR Image -->
                                    <div class="w-32 h-32 bg-white rounded-xl p-1 flex items-center justify-center overflow-hidden">
                                        <img 
                                            src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=TICKETBOX_AUTH_PASS_{{ $booking->booking_code }}" 
                                            alt="Check-in QR Code" 
                                            class="w-full h-full object-contain"
                                            loading="lazy"
                                        >
                                    </div>

                                    <div class="pt-0.5">
                                        <span class="font-mono text-xs font-black text-slate-900 block tracking-widest">{{ $booking->booking_code }}</span>
                                        <span class="text-[9px] font-bold text-emerald-700 uppercase tracking-wider block mt-0.5">✓ HỢP LỆ ĐỐI SOÁT CỔNG</span>
                                    </div>
                                </div>

                                <!-- Total Price Block -->
                                <div>
                                    <span class="text-[10px] text-slate-500 uppercase tracking-wider block font-bold">Tổng chi phí thanh toán</span>
                                    <span class="font-display font-black text-[#976D00] text-xl sm:text-2xl">{{ number_format($booking->total_price) }}₫</span>
                                </div>

                                <!-- Download Ticket Button -->
                                <button 
                                    type="button" 
                                    onclick="window.downloadTicketPNG({
                                        movieTitle: '{{ addslashes($booking->movie->title) }}',
                                        showtime: '{{ $booking->showtime->start_time->format('H:i - d/m/Y') }}',
                                        venue: '{{ addslashes($booking->showtime->room->name ?? 'Khán Phòng Sự Kiện') }}',
                                        seats: '{{ implode(', ', $booking->seats) }}',
                                        orderCode: '{{ $booking->booking_code }}',
                                        totalPrice: {{ $booking->total_price }}
                                    })"
                                    class="w-full py-2.5 px-4 rounded-xl bg-[#C08497] hover:bg-[#A96B7E] text-white text-xs font-black shadow-md shadow-[#C08497]/25 hover:shadow-[#C08497]/40 flex items-center justify-center gap-1.5 transition-all cursor-pointer group"
                                >
                                    <svg class="w-4 h-4 text-white group-hover:-translate-y-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span>Lưu vé về máy (PNG)</span>
                                </button>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-site-layout>
