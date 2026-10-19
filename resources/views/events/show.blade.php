<x-site-layout :title="$movie->title . ' - Chi Tiết & Đặt Vé Sự Kiện Thực Tế'">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-8 py-8 sm:py-12 space-y-10">
        
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-gray-500 font-semibold">
            <a href="{{ route('home') }}" class="hover:text-black transition-colors">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('movies.index') }}" class="hover:text-black transition-colors">Khám phá sự kiện</a>
            <span>/</span>
            <span class="text-rose-taupe line-clamp-1 font-bold">{{ $movie->title }}</span>
        </nav>

        <!-- Main Showcase Grid (Editorial Luxury Layout) -->
        <div class="bg-white rounded-3xl p-6 sm:p-10 border border-black/10 shadow-lg relative overflow-hidden">
            <!-- Atmospheric Ambient Lighting -->
            <div class="absolute -top-32 -right-32 w-96 h-96 bg-gradient-to-br from-[#D4AF37]/15 via-[#C08497]/15 to-transparent rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-10">
                
                <!-- Left Column: Big Luxury Poster, Fast Quick-Facts & Organizers -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="relative group rounded-3xl overflow-hidden bg-[#FAF9F6] border border-black/10 shadow-md aspect-[3/4]">
                        <img 
                            src="{{ $movie->poster_url }}" 
                            alt="{{ $movie->title }}" 
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent"></div>
                        
                        <div class="absolute top-4 left-4 flex flex-col gap-2">
                            <span class="badge-gold px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider shadow-md">
                                {{ $movie->category->name }}
                            </span>
                            @if ($movie->is_seated_concert)
                                <span class="badge-rose px-3 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-md">
                                    🏟️ Sơ Đồ Khán Đài Concert
                                </span>
                            @else
                                <span class="badge-sage px-3 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-md">
                                    🎫 Vé Trực Tiếp Theo Hạng
                                </span>
                            @endif
                        </div>

                        <!-- Poster Bottom Caption -->
                        <div class="absolute bottom-4 left-4 right-4 text-white space-y-1">
                            <span class="text-[11px] text-gold-light uppercase tracking-widest font-black block">Chính Hãng TicketBox VIP</span>
                            <h3 class="font-display font-black text-lg text-white leading-tight line-clamp-2">{{ $movie->title }}</h3>
                        </div>
                    </div>

                    <!-- Event Specs Card -->
                    <div class="bg-[#FAF9F6] rounded-3xl p-5 border border-black/10 space-y-3 text-xs shadow-sm">
                        <h4 class="font-display font-black text-sm text-black border-b border-black/10 pb-2 flex items-center justify-between">
                            <span>Thông Tin Show Diễn</span>
                            <span class="badge-gold text-[9px] px-2 py-0.2 rounded-full font-bold">Xác Thực 100%</span>
                        </h4>
                        
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Thể loại sự kiện:</span>
                            <span class="text-black font-bold">{{ $movie->category->name }}</span>
                        </div>
                        @if ($movie->duration_minutes)
                            <div class="flex items-center justify-between text-gray-600">
                                <span>Thời lượng biểu diễn:</span>
                                <span class="text-black font-bold">{{ $movie->duration_minutes }} phút</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Độ tuổi quy định:</span>
                            <span class="text-black font-bold">{{ $movie->entry_policy['age'] ?? '12+ (Khán giả từ 12 tuổi)' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Hình thức nhận vé:</span>
                            <span class="text-sage-forest font-bold">Mã QR Vé Điện Tử E-Ticket</span>
                        </div>
                        <div class="flex items-center justify-between text-gray-600 pt-1 border-t border-black/5">
                            <span>Trạng thái phát hành:</span>
                            <span class="text-sage-forest font-black flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-sage-forest animate-ping"></span>
                                Đang mở bán trực tuyến
                            </span>
                        </div>
                    </div>

                    <!-- Organizers & Partners Widget -->
                    @if (!empty($movie->organizers))
                        <div class="bg-[#FAF9F6] rounded-3xl p-5 border border-black/10 space-y-3 text-xs shadow-sm">
                            <h4 class="font-display font-black text-sm text-black border-b border-black/10 pb-2">Đơn Vị Tổ Chức &amp; Tài Trợ</h4>
                            
                            <div class="space-y-2">
                                <div>
                                    <span class="text-[10px] text-gray-500 font-bold uppercase block">Ban Tổ Chức Chính:</span>
                                    <strong class="text-black font-bold">{{ $movie->organizers['lead'] ?? 'Ban tổ chức sự kiện' }}</strong>
                                </div>
                                @if (!empty($movie->organizers['sponsor']))
                                    <div class="pt-1.5 border-t border-black/5">
                                        <span class="text-[10px] text-gray-500 font-bold uppercase block">Nhà Tài Trợ Kim Cương:</span>
                                        <strong class="text-gold-dark font-bold">{{ $movie->organizers['sponsor'] }}</strong>
                                    </div>
                                @endif
                                @if (!empty($movie->organizers['media']))
                                    <div class="pt-1.5 border-t border-black/5">
                                        <span class="text-[10px] text-gray-500 font-bold uppercase block">Đối Tác Truyền Thông:</span>
                                        <span class="text-gray-700 font-medium">{{ $movie->organizers['media'] }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Direct Quick Action / Showtime Selection Box -->
                    @if (!empty($showtimes))
                        <div 
                            class="bg-[#FAF9F6] rounded-3xl p-5 border border-black/10 space-y-4 shadow-sm"
                            x-data="{ 
                                selectedShowtimeId: {{ $showtimes[0]->id }},
                                get targetUrl() {
                                    return '{{ url('/showtimes') }}/' + this.selectedShowtimeId + '/seats';
                                }
                            }"
                        >
                            <div class="flex items-center justify-between pb-2 border-b border-black/10">
                                <span class="text-[10px] font-black uppercase tracking-wider text-gold-dark">
                                    {{ count($showtimes) > 1 ? 'Chọn Suất Diễn Để Đặt Vé' : 'Suất Diễn Chính Thức' }}
                                </span>
                                @if (count($showtimes) > 1)
                                    <span class="badge-gold text-[9px] px-2 py-0.5 rounded-full font-black">
                                        {{ count($showtimes) }} Suất Mở Bán
                                    </span>
                                @else
                                    <span class="badge-rose text-[9px] px-2 py-0.5 rounded-full font-bold">
                                        1 Suất Duy Nhất
                                    </span>
                                @endif
                            </div>

                            @if (count($showtimes) > 1)
                                <!-- Multi-showtimes picker under poster -->
                                <div class="space-y-2">
                                    <span class="text-[11px] text-gray-500 font-medium block">
                                        Chọn 1 trong {{ count($showtimes) }} suất diễn bạn muốn tham dự:
                                    </span>
                                    <div class="grid grid-cols-1 gap-2">
                                        @foreach ($showtimes as $st)
                                            <button 
                                                type="button"
                                                @click="selectedShowtimeId = {{ $st->id }}"
                                                class="w-full text-left p-3 rounded-2xl border transition-all flex items-center justify-between gap-3 text-xs cursor-pointer"
                                                :class="selectedShowtimeId === {{ $st->id }} ? 'bg-black text-white border-black shadow-md font-bold' : 'bg-white text-gray-800 border-black/10 hover:border-black/30 font-medium'"
                                            >
                                                <div class="flex items-center gap-2.5">
                                                    <span 
                                                        class="w-4 h-4 rounded-full flex items-center justify-center border text-[9px]"
                                                        :class="selectedShowtimeId === {{ $st->id }} ? 'border-gold-antique bg-gold-antique text-black font-black' : 'border-gray-300'"
                                                    >
                                                        <span x-show="selectedShowtimeId === {{ $st->id }}">✓</span>
                                                    </span>
                                                    <div>
                                                        <strong class="block text-xs" :class="selectedShowtimeId === {{ $st->id }} ? 'text-white' : 'text-slate-900'">
                                                            {{ $st->session_label }}
                                                        </strong>
                                                        <span class="text-[11px]" :class="selectedShowtimeId === {{ $st->id }} ? 'text-gray-300' : 'text-gray-500'">
                                                            {{ $st->start_time->format('H:i - d/m/Y') }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <span 
                                                    class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase"
                                                    :class="selectedShowtimeId === {{ $st->id }} ? 'bg-white/20 text-gold-light' : 'bg-black/5 text-gray-600'"
                                                >
                                                    {{ number_format($st->base_price, 0, ',', '.') }}₫
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <!-- Single showtime summary -->
                                <div class="bg-white p-3.5 rounded-2xl border border-black/10 text-xs space-y-1">
                                    <div class="flex items-center justify-between text-gray-500 text-[11px]">
                                        <span>Khán phòng:</span>
                                        <strong class="text-black">{{ $showtimes[0]->room->name }}</strong>
                                    </div>
                                    <div class="flex items-center justify-between text-gray-500 text-[11px]">
                                        <span>Thời gian:</span>
                                        <strong class="text-rose-taupe font-bold">{{ $showtimes[0]->start_time->format('H:i - d/m/Y') }}</strong>
                                    </div>
                                </div>
                            @endif

                            <a 
                                :href="targetUrl" 
                                class="w-full btn-rose py-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition-all"
                            >
                                @if ($movie->is_seated_concert)
                                    <span>Chọn Ghế Khán Đài &amp; Đặt Vé Ngay</span>
                                @else
                                    <span>Đặt Vé Tham Dự Ngay</span>
                                @endif
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Right Column: Details, Real-world Venue, Timeline, Lineup, Tiers, Showtimes & Policy -->
                <div class="lg:col-span-8 flex flex-col justify-between space-y-10">
                    <div>
                        <!-- Header & Badges -->
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span class="badge-rose px-3.5 py-1 rounded-full text-xs font-bold uppercase">
                                {{ $movie->category->name }}
                            </span>
                            <a href="#binh-luan" class="badge-gold px-3.5 py-1 rounded-full text-xs font-black inline-flex items-center gap-1.5 hover:opacity-90">
                                <svg class="w-3.5 h-3.5 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                <span>{{ $commentsTotal > 0 ? number_format($commentsTotal, 0, ',', '.').' cảm nhận khán giả' : 'Chưa có cảm nhận' }}</span>
                            </a>
                            <span class="badge-sage px-3 py-1 rounded-full text-xs font-bold">
                                Cam Kết Vé Thật 100%
                            </span>
                        </div>

                        <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-black text-black leading-tight tracking-tight">
                            {{ $movie->title }}
                        </h1>

                        <!-- Description -->
                        <div class="mt-4 text-sm sm:text-base text-gray-700 leading-relaxed font-normal border-b border-black/10 pb-6">
                            {{ $movie->description }}
                        </div>

                        <!-- 1. REAL-WORLD VENUE & LOCATION INFORMATION (Địa Điểm, Cổng Vào & Bãi Đỗ Xe) -->
                        <div class="mt-8 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-gold-dark">Địa Điểm &amp; Hạ Tầng Tổ Chức</span>
                                    <h3 class="font-display font-black text-xl text-black">Thông Tin Địa Điểm &amp; Hướng Dẫn Di Chuyển</h3>
                                </div>
                                <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-bold">Quy mô lớn</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Venue Name & Address -->
                                <div class="bg-[#FAF9F6] rounded-3xl p-5 border border-black/10 space-y-3 shadow-sm">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-amber-100 text-gold-dark flex items-center justify-center shrink-0 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        </div>
                                        <div class="space-y-1">
                                            <span class="text-[10px] text-gray-500 font-bold uppercase">Sân khấu / Khán phòng:</span>
                                            <h4 class="font-display font-black text-sm text-black">{{ $movie->venue_name ?? $venue?->name ?? 'Đang cập nhật' }}</h4>
                                            <p class="text-xs text-gray-600 leading-relaxed">{{ $movie->venue_address ?? $venue?->address ?? 'Đang cập nhật' }}</p>
                                        </div>
                                    </div>

                                    @if (!empty($movie->venue_gates))
                                        <div class="pt-3 border-t border-black/10 text-xs space-y-1">
                                            <span class="font-bold text-black flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                                                Phân luồng cổng vào (Gate Entry):
                                            </span>
                                            <p class="text-gray-600 text-[11px] leading-relaxed pl-3.5">{{ $movie->venue_gates }}</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Parking Guide & Transportation -->
                                <div class="bg-[#FAF9F6] rounded-3xl p-5 border border-black/10 space-y-3 shadow-sm">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-sage-forest flex items-center justify-center shrink-0 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                        </div>
                                        <div class="space-y-1">
                                            <span class="text-[10px] text-gray-500 font-bold uppercase">Hướng dẫn bãi giữ xe:</span>
                                            <h4 class="font-display font-black text-sm text-black">Bãi Đỗ Xe Máy &amp; Ô Tô</h4>
                                            <p class="text-xs text-gray-600 leading-relaxed">{{ $movie->parking_info ?? 'Có bãi giữ xe máy và ô tô tại tầng hầm và khuôn viên sân vận động (sức chứa trên 2.000 phương tiện).' }}</p>
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-black/10 flex items-center justify-between text-[11px]">
                                        <span class="text-gray-500">Khuyến nghị di chuyển:</span>
                                        <span class="text-rose-taupe font-bold">Nên đến trước 45 - 60 phút</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. OFFICIAL PRODUCTION TIMELINE & SCHEDULE (Lịch Trình Sự Kiện Thực Tế) -->
                        @if (!empty($movie->timeline))
                            <div class="mt-8 space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-gold-dark">Quy Trình Diễn Ra</span>
                                        <h3 class="font-display font-black text-xl text-black">Lịch Trình Chi Tiết Đêm Diễn</h3>
                                    </div>
                                    <span class="text-xs text-gray-500 italic">Timeline chính thức từ BTC</span>
                                </div>

                                <div class="space-y-3">
                                    @foreach ($movie->timeline as $index => $item)
                                        <div class="bg-[#FAF9F6] rounded-2xl p-4 border border-black/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-gold-antique transition-all shadow-sm">
                                            <div class="flex items-start sm:items-center gap-3.5">
                                                <span class="px-3 py-1 rounded-xl bg-black text-white font-mono font-bold text-xs shrink-0 shadow-sm">
                                                    {{ $item['time'] }}
                                                </span>
                                                <div>
                                                    <h5 class="font-display font-black text-sm text-black">{{ $item['title'] }}</h5>
                                                    <p class="text-xs text-gray-600 leading-relaxed mt-0.5">{{ $item['desc'] }}</p>
                                                </div>
                                            </div>
                                            <span class="text-[10px] text-sage-forest font-bold shrink-0 self-end sm:self-auto uppercase">
                                                Bước {{ $index + 1 }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- 3. PROMINENT PARTICIPANTS, HOSTS & SPECIAL GUESTS SHOWCASE -->
                        @if (!empty($movie->lineup) || !empty($movie->participants_title))
                            <div class="mt-8 space-y-5">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-gold-dark">Nhân Vật &amp; Đơn Vị Tâm Điểm</span>
                                        <h3 class="font-display font-black text-xl sm:text-2xl text-black">
                                            {{ $movie->participants_title ?? 'Dàn Khách Mời & Nghệ Sĩ Trình Diễn' }}
                                        </h3>
                                    </div>
                                    @if (!empty($movie->lineup))
                                        <span class="badge-rose text-xs px-3 py-1 rounded-full font-black self-start sm:self-auto shadow-sm">
                                            {{ count($movie->lineup) }}+ Nhân Vật Nổi Bật
                                        </span>
                                    @endif
                                </div>

                                <!-- Participants Summary Highlight Banner -->
                                @if (!empty($movie->participants_summary))
                                    <div class="bg-gradient-to-r from-amber-900/10 via-amber-500/10 to-rose-500/10 rounded-2xl p-4 border border-gold-antique/30 flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-xl bg-gold-antique text-black font-black text-sm flex items-center justify-center shrink-0 shadow-sm">
                                            ⭐
                                        </span>
                                        <p class="text-xs sm:text-sm text-black font-semibold leading-relaxed">
                                            {{ $movie->participants_summary }}
                                        </p>
                                    </div>
                                @endif

                                <!-- Host MC & Special Guests Highlight Row -->
                                @if (!empty($movie->host_mc) || !empty($movie->special_guests))
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                        @if (!empty($movie->host_mc))
                                            <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/10 flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center text-xs font-black shrink-0">
                                                    🎙️
                                                </div>
                                                <div class="text-xs">
                                                    <span class="text-[10px] text-gray-500 font-bold uppercase block">Người Dẫn Chương Trình (Host / MC):</span>
                                                    <strong class="text-black font-bold">{{ $movie->host_mc }}</strong>
                                                </div>
                                            </div>
                                        @endif

                                        @if (!empty($movie->special_guests))
                                            <div class="bg-[#FAF9F6] rounded-2xl p-3.5 border border-black/10 flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-800 flex items-center justify-center text-xs font-black shrink-0">
                                                    ✨
                                                </div>
                                                <div class="text-xs">
                                                    <span class="text-[10px] text-gray-500 font-bold uppercase block">Khách Mời Danh Dự (Special Guests):</span>
                                                    <strong class="text-rose-taupe font-bold">{{ $movie->special_guests }}</strong>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Participant Cards Grid -->
                                @if (!empty($movie->lineup))
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3.5">
                                        @foreach ($movie->lineup as $artist)
                                            <div class="bg-[#FAF9F6] rounded-2xl p-4 border border-black/10 hover:border-gold-antique hover:shadow-lg transition-all text-center space-y-2 group relative overflow-hidden flex flex-col justify-between">
                                                
                                                <!-- Top badge if available -->
                                                @if (!empty($artist['badge']))
                                                    <div class="flex justify-center">
                                                        <span class="badge-gold text-[8px] font-black px-2 py-0.2 rounded-full uppercase tracking-wider line-clamp-1 shadow-sm">
                                                            {{ $artist['badge'] }}
                                                        </span>
                                                    </div>
                                                @endif

                                                <div class="space-y-1.5">
                                                    <div class="w-14 h-14 mx-auto rounded-full bg-gradient-to-tr from-gold-dark via-amber-300 to-rose-400 text-black flex items-center justify-center font-display font-black text-lg shadow-md group-hover:scale-110 transition-transform">
                                                        <span>{{ mb_substr($artist['name'], 0, 1) }}</span>
                                                    </div>
                                                    
                                                    <h5 class="font-display font-black text-xs sm:text-sm text-black line-clamp-1 group-hover:text-gold-dark transition-colors">
                                                        {{ $artist['name'] }}
                                                    </h5>
                                                    
                                                    <p class="text-[10px] text-gray-500 font-medium line-clamp-1">
                                                        {{ $artist['role'] }}
                                                    </p>
                                                </div>

                                                <div class="pt-1">
                                                    <span class="inline-block bg-white border border-black/10 text-[9px] font-bold px-2 py-0.5 rounded-full text-gray-700 w-full line-clamp-1">
                                                        {{ $artist['tag'] ?? 'Official Participant' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- 4. TICKET TIERS OVERVIEW (Bảng Giá & Quyền Lợi Hạng Vé) -->
                        <div class="mt-8 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-gold-dark">Hạng Vé &amp; Quyền Lợi</span>
                                    <h3 class="font-display font-black text-xl text-black">Bảng Giá Vé Sự Kiện</h3>
                                </div>
                                <span class="text-xs text-gray-500 italic hidden sm:inline">Chọn suất diễn bên dưới để mở sơ đồ chỗ ngồi</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                                @foreach ($ticketTiers as $key => $tier)
                                    <div class="bg-[#FAF9F6] rounded-2xl p-4 border border-black/10 hover:border-black/30 hover:shadow-md transition-all space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="{{ $tier['badge'] }} text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase flex items-center gap-1">
                                                <span>{{ $tier['icon'] }}</span>
                                                <span>{{ $tier['name'] }}</span>
                                            </span>
                                            <span class="font-display font-black text-sm text-black">
                                                {{ number_format($tier['price'], 0, ',', '.') }}₫
                                            </span>
                                        </div>

                                        <p class="text-[11px] text-gray-600 leading-relaxed">
                                            {{ $tier['description'] }}
                                        </p>

                                        @if (!empty($tier['perks']))
                                            <div class="flex flex-wrap gap-1 pt-1">
                                                @foreach ($tier['perks'] as $perk)
                                                    <span class="bg-white border border-black/10 text-[9px] font-bold px-2 py-0.5 rounded-full text-gray-700">
                                                        ✓ {{ $perk }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 5. SHOWTIME SELECTOR SECTION (Lịch Diễn & Suất Diễn) -->
                        <div class="mt-8 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-gold-dark">Lịch Diễn &amp; Suất Chiếu</span>
                                    <h3 class="font-display font-black text-xl text-black">
                                        @if (count($showtimes) > 1)
                                            Sự Kiện Có {{ count($showtimes) }} Suất Diễn — Chọn Suất Bạn Muốn Tham Dự
                                        @elseif ($movie->is_seated_concert)
                                            Suất Diễn Duy Nhất — Sơ Đồ Khán Phòng Sân Vận Động
                                        @else
                                            Suất Diễn Duy Nhất — Đặt Vé Tham Dự Trực Tiếp
                                        @endif
                                    </h3>
                                </div>
                                <span class="text-xs text-rose-taupe font-bold">Chọn suất để giữ chỗ &rarr;</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-{{ min(count($showtimes), 3) }} gap-4">
                                @forelse ($showtimes as $showtime)
                                    <a 
                                        href="{{ route('showtimes.seats', $showtime->id) }}"
                                        class="group bg-white rounded-3xl p-5 border-2 border-black/10 hover:border-gold-antique hover:shadow-xl transition-all block relative overflow-hidden flex flex-col justify-between"
                                    >
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="badge-gold text-[11px] px-3 py-1 rounded-full font-black tracking-wide uppercase shadow-sm">
                                                    {{ $showtime->session_short_label ?? ('Suất ' . $loop->iteration) }}
                                                </span>
                                                <span class="text-[11px] text-gray-700 bg-gray-100 font-bold px-2.5 py-0.5 rounded-md border border-gray-200">
                                                    {{ $showtime->room->name }}
                                                </span>
                                            </div>

                                            <div class="mb-3">
                                                <div class="flex items-baseline gap-2">
                                                    <span class="font-display font-black text-3xl text-black group-hover:text-gold-dark transition-colors">
                                                        {{ $showtime->start_time->format('H:i') }}
                                                    </span>
                                                    <span class="text-xs text-gray-700 font-bold">
                                                        {{ $showtime->start_time->locale('vi')->isoFormat('dddd, DD/MM/YYYY') }}
                                                    </span>
                                                </div>
                                                <div class="text-xs text-slate-800 font-semibold mt-1">
                                                    {{ $showtime->session_label }}
                                                </div>
                                            </div>

                                            <div class="text-xs text-gray-700 space-y-1.5 font-medium bg-[#FAF9F6] p-3 rounded-2xl border border-black/5">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-gray-700">Giá vé khởi điểm:</span>
                                                    <strong class="text-rose-taupe font-black text-sm">{{ number_format($showtime->base_price, 0, ',', '.') }}₫</strong>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-gray-700">Trạng thái:</span>
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                        Đang mở bán
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 pt-3 border-t border-black/10 flex items-center justify-between text-xs font-black text-black group-hover:text-gold-dark">
                                            <span>
                                                @if ($movie->is_seated_concert)
                                                    Chọn Ghế Suất Này
                                                @else
                                                    Đặt Vé Suất Này
                                                @endif
                                            </span>
                                            <span class="transition-transform group-hover:translate-x-1">&rarr;</span>
                                        </div>
                                    </a>
                                @empty
                                    <div class="col-span-full bg-[#FAF9F6] rounded-2xl p-8 text-center text-xs text-gray-500 border border-black/5">
                                        Hiện chưa có suất diễn mở bán cho sự kiện này. Vui lòng quay lại sau!
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- 6. ENTRY & SECURITY POLICY (Quy Định Vào Cổng & An Ninh Sự Kiện) -->
                        @if (!empty($movie->entry_policy))
                            <div class="mt-8 space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-gold-dark">Quy Định &amp; Điều Khoản</span>
                                        <h3 class="font-display font-black text-xl text-black">Quy Định Vào Cổng &amp; An Ninh Sự Kiện</h3>
                                    </div>
                                    <span class="badge-rose text-[10px] px-2.5 py-0.5 rounded-full font-bold">Khán giả lưu ý</span>
                                </div>

                                <div class="bg-[#FAF9F6] rounded-3xl p-6 border border-black/10 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs shadow-sm">
                                    <div class="space-y-3">
                                        <div class="space-y-1">
                                             <span class="font-bold text-black flex items-center gap-1.5">
                                                 <span class="text-gold-dark">🔞</span> Độ tuổi tham gia:
                                             </span>
                                             <p class="text-slate-800 font-medium leading-relaxed pl-5">{{ $movie->entry_policy['age'] }}</p>
                                        </div>
                                        <div class="space-y-1">
                                             <span class="font-bold text-black flex items-center gap-1.5">
                                                 <span class="text-sage-forest">🎫</span> Thủ tục Check-in:
                                             </span>
                                             <p class="text-slate-800 font-medium leading-relaxed pl-5">{{ $movie->entry_policy['checkin'] }}</p>
                                        </div>
                                        <div class="space-y-1">
                                             <span class="font-bold text-black flex items-center gap-1.5">
                                                 <span class="text-blue-500">🔄</span> Chính sách hoàn/đổi vé:
                                             </span>
                                             <p class="text-slate-800 font-medium leading-relaxed pl-5">{{ $movie->entry_policy['refund'] }}</p>
                                        </div>
                                    </div>

                                    <div class="space-y-3 md:border-l md:border-black/10 md:pl-4">
                                        <div class="space-y-1">
                                             <span class="font-bold text-emerald-800 flex items-center gap-1.5">
                                                 <span>✅</span> Vật dụng ĐƯỢC PHÉP mang vào:
                                             </span>
                                             <p class="text-slate-800 font-medium leading-relaxed pl-5">{{ $movie->entry_policy['allowed'] }}</p>
                                        </div>
                                        <div class="space-y-1">
                                            <span class="font-bold text-rose-800 flex items-center gap-1.5">
                                                <span>🚫</span> Vật dụng NGHIÊM CẤM (Prohibited):
                                            </span>
                                            <p class="text-rose-900 font-medium leading-relaxed pl-5">{{ $movie->entry_policy['prohibited'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- 7. WEATHER INTEGRATION & LOCATION QUICK VIEW -->
                        {{-- Bản đồ Google Maps (mọi địa điểm có tọa độ/địa chỉ) + dự báo OpenWeatherMap (chỉ địa điểm ngoài trời, chỉ khi có dự báo thật) --}}
                        @php
                            $venueName = $movie->venue_name ?? $venue?->name;
                            $venueAddress = $movie->venue_address ?? $venue?->address;
                        @endphp
                        @if ($mapUrl || $weather)
                            <div class="mt-8 grid grid-cols-1 {{ $mapUrl && $weather ? 'sm:grid-cols-2' : '' }} gap-4">
                                @if ($mapUrl)
                                    <!-- Location Card (Google Maps) -->
                                    <div class="bg-[#FAF9F6] rounded-3xl p-5 border border-black/10 shadow-sm">
                                        <div class="flex items-center justify-between gap-2 mb-3">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <span class="text-xs font-bold text-black uppercase tracking-wider">Bản Đồ Địa Điểm</span>
                                            </div>
                                            @if ($directionsUrl)
                                                <a href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-full bg-black px-3 py-1 text-[11px] font-bold text-white hover:bg-gray-800 transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                                                    Chỉ đường
                                                </a>
                                            @endif
                                        </div>
                                        @if ($venueName)
                                            <p class="text-sm font-bold text-black">{{ $venueName }}</p>
                                        @endif
                                        @if ($venueAddress)
                                            <p class="text-[11px] text-gray-500 mb-3">{{ $venueAddress }}</p>
                                        @endif
                                        <iframe
                                            src="{{ $mapUrl }}"
                                            class="w-full h-56 rounded-2xl border border-black/10"
                                            loading="lazy"
                                            referrerpolicy="no-referrer-when-downgrade"
                                            allowfullscreen
                                            title="Bản đồ địa điểm {{ $venueName }}"
                                        ></iframe>
                                    </div>
                                @endif

                                @if ($weather)
                                    <!-- Weather Card (OpenWeatherMap) — chỉ hiện khi có dự báo thật -->
                                    <div class="bg-[#FAF9F6] rounded-3xl p-5 border border-black/10 shadow-sm">
                                        <div class="flex items-center gap-2 mb-3">
                                            <svg class="w-4 h-4 text-sage-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" />
                                            </svg>
                                            <span class="text-xs font-bold text-black uppercase tracking-wider">Dự Báo Thời Tiết Sự Kiện</span>
                                        </div>
                                        <div class="rounded-2xl bg-white border border-black/10 p-4 flex items-center gap-4 shadow-inner">
                                            <img src="{{ $weather['icon_url'] }}" alt="{{ $weather['description'] }}" class="w-16 h-16 shrink-0" loading="lazy">
                                            <div class="space-y-0.5 text-xs">
                                                <div class="font-display font-black text-2xl text-black">{{ $weather['temp'] }}°C</div>
                                                <div class="font-bold text-black">{{ $weather['description'] }}</div>
                                                <div class="text-gray-600">Độ ẩm: {{ $weather['humidity'] }}% · Gió: {{ $weather['wind_kmh'] }} km/h</div>
                                                <div class="{{ $weather['is_favorable'] ? 'text-sage-forest' : 'text-[#CC0000]' }} font-bold">
                                                    {{ $weather['is_favorable'] ? 'Thuận lợi tổ chức' : 'Có thể mưa — nhớ mang áo mưa' }}
                                                </div>
                                            </div>
                                        </div>
                                        <p class="mt-2 text-[10px] text-gray-500">
                                            Dự báo lúc {{ $weather['forecast_at']->format('H:i d/m') }} cho suất {{ $weatherShowtime->start_time->format('H:i d/m/Y') }} · Nguồn: OpenWeatherMap
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- 8. COMMENTS / REVIEWS SECTION (AJAX: comments.index / store / destroy) -->
                        <div id="binh-luan" class="mt-10 pt-8 border-t border-black/10 scroll-mt-24"
                             x-data="eventComments({
                                 comments: @js($comments, JSON_UNESCAPED_UNICODE),
                                 total: @js($commentsTotal),
                                 nextPageUrl: @js($commentsNextUrl),
                                 storeUrl: @js(route('comments.store', $movie)),
                                 destroyUrl: @js(route('comments.destroy', '__ID__')),
                                 loginUrl: @js(route('login')),
                                 canComment: @js(auth()->check()),
                             })">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-display font-black text-lg text-black">Đánh Giá &amp; Cảm Nhận Khán Giả</h3>
                                <span class="text-xs text-gray-500" x-text="total + ' bình luận'">{{ $commentsTotal }} bình luận</span>
                            </div>

                            @auth
                                <!-- Comment Input Form -->
                                <form @submit.prevent="submit()" class="bg-[#FAF9F6] rounded-2xl p-3 border border-black/10 mb-2 flex gap-2">
                                    <label for="comment-input" class="sr-only">Nội dung bình luận</label>
                                    <input
                                        id="comment-input"
                                        type="text"
                                        x-model="newComment"
                                        maxlength="1000"
                                        autocomplete="off"
                                        placeholder="Chia sẻ cảm nhận của bạn về sự kiện này..."
                                        class="bg-transparent border-0 flex-1 min-w-0 text-xs text-black placeholder-gray-500 focus:ring-0 font-medium"
                                    >
                                    <button
                                        type="submit"
                                        :disabled="isPosting || newComment.trim().length === 0"
                                        class="btn-rose px-5 py-2 rounded-xl text-xs font-bold shrink-0 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                        x-text="isPosting ? 'Đang gửi...' : 'Gửi'"
                                    >Gửi</button>
                                </form>
                            @else
                                <div class="bg-[#FAF9F6] rounded-2xl p-4 border border-black/10 mb-2 flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-xs text-gray-600 font-medium">Đăng nhập để chia sẻ cảm nhận của bạn về sự kiện này.</p>
                                    <a href="{{ route('login') }}" class="btn-rose px-5 py-2 rounded-xl text-xs font-bold shrink-0 shadow-sm">Đăng nhập để bình luận</a>
                                </div>
                            @endauth
                            <p x-show="error" x-text="error" role="alert" class="text-[11px] font-bold text-[#CC0000] mb-2" style="display: none;"></p>

                            <!-- Comment list -->
                            <div class="space-y-3 mt-4">
                                <p x-show="comments.length === 0" class="text-xs text-gray-500" @if (count($comments) > 0) style="display: none;" @endif>Chưa có bình luận nào. Hãy là người đầu tiên!</p>
                                <template x-for="c in comments" :key="c.id">
                                    <div class="bg-[#FAF9F6] rounded-2xl p-4 border border-black/10 space-y-1.5 shadow-sm">
                                        <div class="flex items-center justify-between text-xs gap-3">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <template x-if="c.avatar_url">
                                                    <img :src="c.avatar_url" alt="" class="w-7 h-7 rounded-full object-cover border border-black/10 shrink-0">
                                                </template>
                                                <template x-if="!c.avatar_url">
                                                    <span class="w-7 h-7 rounded-full bg-gold-antique text-black flex items-center justify-center text-[10px] font-black shrink-0" x-text="c.initials"></span>
                                                </template>
                                                <span class="font-bold text-black truncate" x-text="c.user"></span>
                                            </div>
                                            <div class="flex items-center gap-3 shrink-0">
                                                <time class="text-[10px] text-gray-500" :datetime="c.created_at" x-text="c.time"></time>
                                                <button type="button" x-show="c.can_delete" @click="remove(c)" class="text-[10px] font-bold text-[#CC0000] hover:underline">Xóa</button>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-700 whitespace-pre-line break-words" x-text="c.text"></p>
                                    </div>
                                </template>

                                <div x-show="nextPageUrl" class="pt-1 text-center" @if (! $commentsNextUrl) style="display: none;" @endif>
                                    <button type="button" @click="loadMore()" :disabled="isLoadingMore"
                                            class="btn-ghost-light px-5 py-2 rounded-xl text-xs font-bold disabled:opacity-50"
                                            x-text="isLoadingMore ? 'Đang tải...' : 'Xem thêm bình luận'">Xem thêm bình luận</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-site-layout>
