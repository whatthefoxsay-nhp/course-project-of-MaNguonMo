<x-site-layout :title="'Chọn Vé & Chỗ Ngồi: ' . $movie->title . ' - TicketBox'">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-8 py-8 sm:py-12 space-y-8">
        
        <!-- Navigation Breadcrumbs & Back link -->
        <div class="flex items-center justify-between">
            <nav class="flex items-center gap-2 text-xs text-gray-500 font-semibold">
                <a href="{{ route('home') }}" class="hover:text-black transition-colors">Trang chủ</a>
                <span>/</span>
                <a href="{{ route('movies.index') }}" class="hover:text-black transition-colors">Khám phá sự kiện</a>
                <span>/</span>
                <a href="{{ route('movies.show', $movie->slug) }}" class="hover:text-black transition-colors line-clamp-1">{{ $movie->title }}</a>
                <span>/</span>
                <span class="text-rose-taupe font-bold">Chọn Chỗ &amp; Đặt Vé</span>
            </nav>

            <a 
                href="{{ route('movies.show', $movie->slug) }}" 
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white border border-black/10 hover:border-black text-xs font-bold text-gray-700 hover:text-black transition-all shadow-sm"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                <span>Đổi suất diễn khác</span>
            </a>
        </div>

        <!-- Event Showcase Spotlight Header (Luxury Banner) -->
        <div class="relative bg-white rounded-3xl p-6 sm:p-8 border border-black/10 shadow-lg overflow-hidden">
            <!-- Background Atmospheric Ambient Lights -->
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-gradient-to-bl from-[#D4AF37]/15 to-[#C08497]/15 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <!-- Left: Poster + Core Info -->
                <div class="flex items-center gap-5">
                    <img 
                        src="{{ $movie->poster_url }}" 
                        alt="{{ $movie->title }}" 
                        class="w-20 h-28 sm:w-24 sm:h-32 object-cover rounded-2xl bg-[#FAF9F6] border border-black/10 shadow-md shrink-0"
                    >
                    <div class="space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge-rose text-[10px] px-2.5 py-0.5 rounded-full font-bold uppercase">
                                {{ $movie->category->name }}
                            </span>
                            <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase">
                                {{ $showtime->room->name }}
                            </span>
                            <span class="badge-sage text-[10px] px-2.5 py-0.5 rounded-full font-bold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-sage-forest animate-ping"></span>
                                Đang Mở Bán Trực Tuyến
                            </span>
                        </div>

                        <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-black text-black leading-tight">
                            {{ $movie->title }}
                        </h1>

                        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-600 font-semibold pt-1">
                            <span class="flex items-center gap-1.5 text-black font-bold">
                                <svg class="w-4 h-4 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                {{ $showtime->start_time->format('l, d/m/Y') }}
                            </span>
                            <span class="text-black/30">·</span>
                            <span class="flex items-center gap-1.5 text-rose-taupe font-bold">
                                <svg class="w-4 h-4 text-rose-taupe" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Giờ diễn: {{ $showtime->start_time->format('H:i') }}
                            </span>
                            <span class="text-black/30">·</span>
                            <span class="text-gray-500">
                                {{ $showtime->room->address ?? 'Khán phòng chính Sài Gòn' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right: Price highlights & Steps -->
                <div class="flex flex-row md:flex-col items-end justify-between md:justify-center w-full md:w-auto pt-4 md:pt-0 border-t md:border-t-0 border-black/10 shrink-0">
                    <div class="text-left md:text-right">
                        <span class="text-xs text-gray-500 font-bold block">Giá vé từ</span>
                        <span class="font-display font-black text-2xl sm:text-3xl text-black">
                            {{ number_format($showtime->base_price) }}₫
                        </span>
                    </div>

                    <div class="mt-2 flex items-center gap-1 text-[11px] text-sage-forest font-bold bg-[#EAF3EC] px-3 py-1 rounded-full border border-[#A3C9A8]">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <span>Nhận mã QR vé E-Ticket tức thì</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seat Map Interactive Component -->
        <x-seat-map 
            :showtime="$showtime" 
            :movie="$movie" 
            :seats="$seats" 
            :ticketTiers="$ticketTiers"
        />

    </div>
</x-site-layout>
