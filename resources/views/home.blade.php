<x-site-layout :title="'Trang chủ - Vé Sự Kiện, Concert & Hội Thảo Đỉnh Cao'">
    
    <!-- Hero Section (Luxury Asymmetric Editorial Layout) -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-8 pt-6 sm:pt-10 pb-12 sm:pb-16">
        <div class="relative rounded-3xl overflow-hidden bg-white border border-black/10 p-6 sm:p-10 lg:p-14 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
            <!-- Ambient Lighting Blobs -->
            <div class="absolute -top-32 -right-32 w-[32rem] h-[32rem] rounded-full bg-gradient-to-br from-[#D4AF37]/15 to-[#C08497]/15 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-32 -left-32 w-[30rem] h-[30rem] rounded-full bg-[#3A5A40]/10 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                <!-- Left Editorial Text -->
                <div class="lg:col-span-7 space-y-5 sm:space-y-6">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#F5F5DC] border border-[#D8D8A8] shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-rose-taupe animate-ping"></span>
                        <span class="text-xs font-black text-rose-taupe uppercase tracking-wider">Mùa Sự Kiện &amp; Live Concert 2026</span>
                    </div>

                    <h1 class="font-serif text-3xl sm:text-5xl lg:text-6xl font-black text-black leading-[1.12] tracking-tight">
                        Trải Nghiệm <span class="italic font-normal text-gold-dark">Concert</span> &amp; Sự Kiện <span class="underline decoration-rose-taupe/60 decoration-wavy underline-offset-8">Đỉnh Cao</span>
                    </h1>

                    <p class="text-sm sm:text-base lg:text-lg text-gray-600 max-w-xl leading-relaxed font-normal">
                        Nền tảng phân phối vé điện tử chính hãng cho các đại nhạc hội bùng nổ, hòa nhạc thính phòng tinh tế, triển lãm đa giác quan và diễn đàn công nghệ quốc tế.
                    </p>

                    <!-- CTAs & Quick Feature Badges -->
                    <div class="pt-2 flex flex-wrap items-center gap-3 sm:gap-4">
                        <a href="{{ route('movies.index') }}" class="btn-rose px-8 py-3.5 rounded-full font-black text-sm flex items-center gap-2 shadow-md hover:shadow-lg transition-all">
                            <span>Khám phá sự kiện ngay</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>

                        <a href="#featured-section" class="btn-ghost-light px-7 py-3.5 rounded-full font-bold text-sm shadow-sm hover:border-black/30 transition-all">
                            Xem lịch diễn hot
                        </a>
                    </div>

                    <!-- Statistics Strip -->
                    <div class="pt-6 border-t border-black/10 grid grid-cols-3 gap-4 sm:gap-6 max-w-lg text-left">
                        <div>
                            <span class="font-display font-black text-2xl sm:text-3xl text-gold-dark block">50K+</span>
                            <span class="text-[11px] sm:text-xs text-gray-500 font-semibold">Vé đã phát hành</span>
                        </div>
                        <div>
                            <span class="font-display font-black text-2xl sm:text-3xl text-rose-taupe block">100+</span>
                            <span class="text-[11px] sm:text-xs text-gray-500 font-semibold">Show diễn độc quyền</span>
                        </div>
                        <div>
                            <span class="font-display font-black text-2xl sm:text-3xl text-sage-forest block">4.9/5</span>
                            <span class="text-[11px] sm:text-xs text-gray-500 font-semibold">Độ hài lòng khán giả</span>
                        </div>
                    </div>
                </div>

                <!-- Right Spotlight Feature Card -->
                <div class="lg:col-span-5 relative">
                    @if (isset($movies[0]))
                        @php $leadEvent = $movies[0]; @endphp
                        <div class="relative group">
                            <!-- Floating Glow behind card -->
                            <div class="absolute -inset-1.5 bg-gradient-to-r from-rose-taupe via-gold-antique to-sage-forest rounded-3xl blur-xl opacity-25 group-hover:opacity-50 transition duration-700"></div>
                            
                            <div class="relative bg-white rounded-3xl p-4 sm:p-5 border border-black/10 overflow-hidden shadow-2xl">
                                <div class="aspect-[16/10] sm:aspect-[4/3] rounded-2xl overflow-hidden bg-[#FAF9F6] relative">
                                    <img src="{{ $leadEvent->poster_path }}" alt="{{ $leadEvent->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-700 ease-out">
                                    
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-transparent"></div>
                                    
                                    <div class="absolute top-3 left-3 z-10">
                                        <span class="badge-gold px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider shadow-md">
                                            Spotlight Sự Kiện
                                        </span>
                                    </div>

                                    <div class="absolute bottom-3 left-3 right-3 text-white text-xs z-10 flex items-center justify-between">
                                        <span class="bg-black/60 backdrop-blur-md px-3 py-1 rounded-lg border border-white/20 font-bold">
                                            {{ $leadEvent->category->name }}
                                        </span>
                                        <span class="bg-white/95 backdrop-blur-md text-emerald-800 px-2.5 py-1 rounded-lg font-black text-[10px] flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Vé VIP Khán Đài
                                        </span>
                                    </div>
                                </div>

                                <div class="p-4 pt-4">
                                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1 font-semibold">
                                        <span class="text-gold-dark font-bold uppercase tracking-wider text-[10px]">Đang mở bán trực tuyến</span>
                                        <span class="text-sage-forest font-bold flex items-center gap-1 text-[11px]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sage-forest"></span> Khóa chỗ an toàn 10p
                                        </span>
                                    </div>
                                    <h3 class="font-serif font-black text-xl text-black line-clamp-1 group-hover:text-rose-taupe transition-colors">{{ $leadEvent->title }}</h3>
                                    <p class="text-xs text-gray-500 mt-1.5 line-clamp-2 leading-relaxed">{{ $leadEvent->description }}</p>
                                    
                                    <div class="mt-4 pt-3.5 border-t border-black/10 flex items-center justify-between">
                                        <div>
                                            <span class="text-[10px] text-gray-400 font-semibold block">Giá vé từ</span>
                                            <span class="font-display font-black text-black text-lg">{{ number_format($leadEvent->base_price ?? 250000, 0, ',', '.') }}₫</span>
                                        </div>
                                        <a href="{{ route('movies.show', $leadEvent->slug) }}" class="btn-gold px-5 py-2.5 rounded-xl text-xs font-black shadow-sm flex items-center gap-1.5 hover:shadow-md transition-all">
                                            <span>Chọn chỗ ngồi</span>
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Main Catalog & Category Tabs Section -->
    <section id="featured-section" class="max-w-[1440px] mx-auto px-4 sm:px-8 py-8 sm:py-12" x-data="{ currentTab: 'all' }">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 sm:mb-10 pb-4 border-b border-black/10">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-gold-antique"></span>
                    <span class="text-xs font-black uppercase tracking-wider text-gold-dark">Sắp Diễn Ra &amp; Mở Bán Sớm</span>
                </div>
                <h2 class="font-serif text-3xl sm:text-4xl font-black text-black tracking-tight">Danh Mục Sự Kiện Nổi Bật</h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Lựa chọn vị trí ngồi và khán phòng yêu thích từ các show diễn đẳng cấp</p>
            </div>

            <!-- Tab Buttons with Alpine.js -->
            <div class="inline-flex p-1 rounded-full bg-white border border-black/10 text-xs font-bold shadow-sm shrink-0">
                <button 
                    @click="currentTab = 'all'" 
                    :class="currentTab === 'all' ? 'bg-black text-white shadow-md' : 'text-gray-600 hover:text-black'"
                    class="px-5 py-2 rounded-full transition-all"
                >
                    Tất cả sự kiện
                </button>
                <button 
                    @click="currentTab = 'concert'" 
                    :class="currentTab === 'concert' ? 'bg-black text-white shadow-md' : 'text-gray-600 hover:text-black'"
                    class="px-5 py-2 rounded-full transition-all"
                >
                    Concert &amp; Hòa Nhạc
                </button>
                <button 
                    @click="currentTab = 'expo'" 
                    :class="currentTab === 'expo' ? 'bg-black text-white shadow-md' : 'text-gray-600 hover:text-black'"
                    class="px-5 py-2 rounded-full transition-all"
                >
                    Hội Thảo &amp; Triển Lãm
                </button>
            </div>
        </div>

        <!-- Event Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            @foreach ($movies as $event)
                <div>
                    <x-movie-card :movie="$event" />
                </div>
            @endforeach
        </div>

        <!-- View All Events Link -->
        <div class="mt-12 text-center">
            <a href="{{ route('movies.index') }}" class="inline-flex items-center gap-2.5 px-8 py-3.5 rounded-full bg-white border border-black/15 text-black font-black text-xs hover:border-black hover:bg-black hover:text-white shadow-sm hover:shadow-md transition-all">
                <span>Xem toàn bộ lịch diễn &amp; sự kiện 2026</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
    </section>

    <!-- Warm Beige Editorial Feature Section (Beige Luxury Surface) -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-8 py-12 sm:py-16">
        <div class="bg-[#F5F5DC] rounded-3xl p-8 sm:p-12 lg:p-16 text-black relative overflow-hidden border border-[#D8D8A8] shadow-sm">
            <!-- Background accent graphics -->
            <div class="absolute -right-20 -bottom-20 w-96 h-96 rounded-full bg-gold-antique/20 blur-3xl pointer-events-none"></div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center relative z-10">
                <div class="lg:col-span-7 space-y-4">
                    <span class="badge-gold px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider shadow-sm">
                        Đặc Quyền Khán Giả TicketBox
                    </span>
                    <h2 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-black text-black leading-tight">
                        Đặt Vé Liền Tay, <br><span class="italic font-normal text-rose-taupe">Giữ Chỗ Khán Đài Chuẩn Xác 100%</span>
                    </h2>
                    <p class="text-sm sm:text-base text-gray-700 leading-relaxed font-medium max-w-lg">
                        Hệ thống sơ đồ khán phòng và sân khấu thông minh với công nghệ khóa vị trí chống trùng vé (Double-booking Prevention), giữ chỗ trong 10 phút để bạn hoàn tất thanh toán an toàn.
                    </p>
                    <div class="pt-2 flex flex-wrap gap-4">
                        <a href="{{ route('movies.index') }}" class="btn-rose px-7 py-3.5 rounded-full text-xs font-black text-white shadow-md hover:shadow-lg transition-all">
                            Chọn sự kiện yêu thích ngay &rarr;
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-5 rounded-2xl bg-white border border-black/10 shadow-sm space-y-2 hover:border-rose-taupe transition-colors">
                        <div class="w-8 h-8 rounded-xl bg-[#FDE8EE] text-[#99334D] flex items-center justify-center font-black text-sm">1</div>
                        <h4 class="font-display font-black text-sm text-black">Chọn Suất &amp; Khán Đài</h4>
                        <p class="text-xs text-gray-500">Sơ đồ trực quan từ vé Tiêu chuẩn đến vé VIP Zone.</p>
                    </div>

                    <div class="p-5 rounded-2xl bg-white border border-black/10 shadow-sm space-y-2 hover:border-gold-antique transition-colors">
                        <div class="w-8 h-8 rounded-xl bg-[#FFF8E1] text-gold-dark flex items-center justify-center font-black text-sm">2</div>
                        <h4 class="font-display font-black text-sm text-black">Giữ Chỗ Tức Thì</h4>
                        <p class="text-xs text-gray-500">Tự động lock vị trí an toàn bằng giao dịch cơ sở dữ liệu.</p>
                    </div>

                    <div class="p-5 rounded-2xl bg-white border border-black/10 shadow-sm space-y-2 hover:border-sage-forest transition-colors">
                        <div class="w-8 h-8 rounded-xl bg-[#EAF3EC] text-sage-forest flex items-center justify-center font-black text-sm">3</div>
                        <h4 class="font-display font-black text-sm text-black">Nhận E-Ticket QR</h4>
                        <p class="text-xs text-gray-500">Mã vé điện tử lưu trực tiếp vào lịch sử đặt vé.</p>
                    </div>

                    <div class="p-5 rounded-2xl bg-white border border-black/10 shadow-sm space-y-2 hover:border-black transition-colors">
                        <div class="w-8 h-8 rounded-xl bg-[#FAF9F6] text-black border border-black/10 flex items-center justify-center font-black text-sm">4</div>
                        <h4 class="font-display font-black text-sm text-black">Check-in Nhanh</h4>
                        <p class="text-xs text-gray-500">Quét mã QR tại cổng sự kiện không cần in vé giấy.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Accordion Section with Alpine.js -->
    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14" x-data="{ activeFaq: null }">
        <div class="text-center mb-10">
            <span class="text-xs font-black uppercase tracking-wider text-rose-taupe">Hỏi &amp; Đáp</span>
            <h2 class="font-serif text-3xl font-black text-black mt-1">Câu Hỏi Thường Gặp Khi Đặt Vé Sự Kiện</h2>
        </div>

        <div class="space-y-4">
            <!-- FAQ 1 -->
            <div class="bg-white rounded-2xl border border-black/10 shadow-sm overflow-hidden transition-colors hover:border-black/20">
                <button 
                    @click="activeFaq = (activeFaq === 1 ? null : 1)" 
                    class="w-full p-5 text-left flex items-center justify-between gap-4 text-black font-bold text-sm sm:text-base hover:text-rose-taupe transition-colors"
                >
                    <span>Vé sự kiện của tôi được giữ trong thời gian bao lâu sau khi chọn chỗ?</span>
                    <span class="text-xl font-black text-gold-dark shrink-0" x-text="activeFaq === 1 ? '−' : '+'">+</span>
                </button>
                <div x-show="activeFaq === 1" x-collapse class="px-6 pb-5 pt-3.5 text-sm sm:text-base text-slate-800 font-medium leading-relaxed border-t border-slate-100 bg-[#FAF9F6]/70">
                    Sau khi chọn chỗ ngồi và thêm vào giỏ vé, hệ thống sẽ tạm khóa chỗ độc quyền cho bạn trong 10 phút để tránh bị người khác đặt trùng. Sau 10 phút nếu chưa thanh toán, vé sẽ tự động giải phóng.
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="bg-white rounded-2xl border border-black/10 shadow-sm overflow-hidden transition-colors hover:border-black/20">
                <button 
                    @click="activeFaq = (activeFaq === 2 ? null : 2)" 
                    class="w-full p-5 text-left flex items-center justify-between gap-4 text-black font-bold text-sm sm:text-base hover:text-rose-taupe transition-colors"
                >
                    <span>Làm sao để tôi kiểm tra lại mã vé E-Ticket khi đến sự kiện?</span>
                    <span class="text-xl font-black text-gold-dark shrink-0" x-text="activeFaq === 2 ? '−' : '+'">+</span>
                </button>
                <div x-show="activeFaq === 2" x-collapse class="px-6 pb-5 pt-3.5 text-sm sm:text-base text-slate-800 font-medium leading-relaxed border-t border-slate-100 bg-[#FAF9F6]/70">
                    Bạn có thể đăng nhập vào tài khoản và truy cập trang "Vé của tôi" (My Bookings) ở thanh điều hướng để xem mã vé điện tử QR Code cùng thông tin khán phòng và cửa vào sự kiện.
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="bg-white rounded-2xl border border-black/10 shadow-sm overflow-hidden transition-colors hover:border-black/20">
                <button 
                    @click="activeFaq = (activeFaq === 3 ? null : 3)" 
                    class="w-full p-5 text-left flex items-center justify-between gap-4 text-black font-bold text-sm sm:text-base hover:text-rose-taupe transition-colors"
                >
                    <span>Sự kiện ngoài trời có hiển thị thông tin thời tiết và bản đồ định vị không?</span>
                    <span class="text-xl font-black text-gold-dark shrink-0" x-text="activeFaq === 3 ? '−' : '+'">+</span>
                </button>
                <div x-show="activeFaq === 3" x-collapse class="px-6 pb-5 pt-3.5 text-sm sm:text-base text-slate-800 font-medium leading-relaxed border-t border-slate-100 bg-[#FAF9F6]/70">
                    Có! Đối với các sự kiện ngoài trời, hệ thống tự động tích hợp dự báo thời tiết và bản đồ chỉ đường Google Maps ngay trên trang chi tiết sự kiện.
                </div>
            </div>
        </div>
    </section>

</x-site-layout>
