<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Một hậu tố thương hiệu duy nhất, kể cả khi view con đã tự thêm "- TicketBox". --}}
    <title>{{ preg_replace('/\s*-\s*TicketBox(\s+\S+)?\s*$/u', '', $title ?? 'Đặt Vé Sự Kiện, Concert & Hội Thảo Trực Tuyến') }} - TicketBox</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body 
    x-data="{ ...searchModal(), mobileMenuOpen: false }"
    class="min-h-screen bg-[#FAF9F6] text-black flex flex-col font-sans selection:bg-rose-taupe selection:text-white relative overflow-x-hidden"
>
    <!-- Background Ambient Luxury Glows (Depth & Texture) -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute -top-[20%] -left-[10%] w-[55vw] h-[55vw] rounded-full bg-[#C08497]/[0.08] blur-[130px]"></div>
        <div class="absolute top-[30%] -right-[15%] w-[50vw] h-[50vw] rounded-full bg-[#D4AF37]/[0.08] blur-[140px]"></div>
        <div class="absolute bottom-[10%] left-[15%] w-[45vw] h-[45vw] rounded-full bg-[#3A5A40]/[0.06] blur-[130px]"></div>
    </div>

    @php
        $navCartCount = auth()->check() ? \App\Models\ShowtimeSeat::heldBy(auth()->user())->count() : 0;
        $upcomingShowtimes = $upcomingShowtimes ?? collect();
        $soonShowtimes = $upcomingShowtimes->filter(fn ($s) => \App\Support\UpcomingShowtimes::isSoon($s));
    @endphp

    <!-- Full-Width Modern Luxury Navbar -->
    <header class="sticky top-0 z-40 w-full bg-white/95 backdrop-blur-xl border-b border-black/10 shadow-sm transition-all">
        <div class="max-w-[1440px] mx-auto px-3 sm:px-6 lg:px-8 py-2.5 sm:py-3 flex items-center justify-between gap-2 lg:gap-3 xl:gap-6">
            
            <!-- Left: Brand Logo & Mobile Toggle -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <button 
                    type="button" 
                    @click="mobileMenuOpen = !mobileMenuOpen" 
                    class="lg:hidden p-2 rounded-xl bg-black/5 text-black hover:bg-black/10 focus:outline-none"
                    aria-label="Toggle menu"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <a href="{{ route('home') }}" class="flex items-center gap-2 sm:gap-2.5 group shrink-0">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-2xl bg-gradient-to-tr from-gold-antique to-rose-taupe flex items-center justify-center text-black font-display font-black text-base sm:text-lg shadow-sm group-hover:scale-105 transition-transform">
                        T
                    </div>
                    <span class="font-display font-black text-lg sm:text-xl tracking-tight text-black group-hover:text-gold-dark transition-colors">
                        Ticket<span class="text-rose-taupe">Box</span>
                    </span>
                </a>
            </div>

            <!-- Center: Multi-Category Event Navigation Links (Desktop) -->
            <nav class="hidden lg:flex items-center gap-1 xl:gap-2 text-xs xl:text-sm font-semibold shrink-0">
                <a href="{{ route('home') }}" class="px-2.5 xl:px-3 py-1.5 rounded-full transition-colors whitespace-nowrap {{ request()->routeIs('home') ? 'bg-[#000000] text-white font-bold' : 'text-gray-700 hover:text-black hover:bg-black/5' }}">
                    Trang chủ
                </a>

                <!-- Concert & Events Dropdown with seamless hover bridge -->
                <div 
                    class="relative" 
                    x-data="{ open: false, timeout: null }" 
                    @mouseenter="clearTimeout(timeout); open = true" 
                    @mouseleave="timeout = setTimeout(() => { open = false }, 200)"
                >
                    <button 
                        @click="open = !open"
                        class="flex items-center gap-1 px-2.5 xl:px-3 py-1.5 rounded-full text-gray-700 hover:text-black hover:bg-black/5 transition-colors whitespace-nowrap {{ request()->routeIs('events.*', 'movies.*') ? 'text-black font-bold bg-black/5' : '' }}"
                    >
                        <span class="hidden 2xl:inline">Khám Phá </span><span>Sự Kiện</span>
                        <svg class="w-3.5 h-3.5 opacity-60 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown panel with seamless hover hit area (pt-2 wrapper) -->
                    <div 
                        x-show="open" 
                        x-transition:enter="ease-out duration-150" 
                        x-transition:enter-start="opacity-0 translate-y-1" 
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        @click.away="open = false"
                        class="absolute left-0 top-full pt-1.5 w-72 z-50"
                        style="display: none;"
                    >
                        <div class="bg-white rounded-3xl p-3 border border-black/10 shadow-[0_20px_45px_-15px_rgba(0,0,0,0.18)] text-xs space-y-1 max-h-[80vh] overflow-y-auto">
                            <a href="{{ route('events.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-gray-800 hover:bg-[#FAF9F6] font-semibold transition-colors">
                                <span>Tất cả sự kiện &amp; show diễn</span>
                                <span class="badge-rose text-[9px] px-2 py-0.5 rounded-full font-bold">Hot</span>
                            </a>
                            <a href="{{ route('events.index', ['q' => 'Concert']) }}" class="flex items-center justify-between px-3.5 py-2 rounded-2xl text-gray-800 hover:bg-[#FAF9F6] font-semibold transition-colors">
                                <span>Live Concert</span>
                                <span class="badge-gold text-[9px] px-2 py-0.5 rounded-full font-bold">VIP</span>
                            </a>
                            <a href="{{ route('events.index', ['q' => 'Hòa Nhạc']) }}" class="block px-3.5 py-2 rounded-2xl text-gray-700 hover:bg-[#FAF9F6] font-medium transition-colors">
                                Hòa Nhạc &amp; Giao Hưởng
                            </a>
                            <a href="{{ route('events.index', ['q' => 'Hội Thảo']) }}" class="block px-3.5 py-2 rounded-2xl text-gray-700 hover:bg-[#FAF9F6] font-medium transition-colors">
                                Hội Thảo &amp; Diễn Đàn
                            </a>
                            <a href="{{ route('events.index', ['q' => 'Triển Lãm']) }}" class="block px-3.5 py-2 rounded-2xl text-gray-700 hover:bg-[#FAF9F6] font-medium transition-colors">
                                Triển Lãm &amp; Trải Nghiệm
                            </a>
                            <a href="{{ route('events.index', ['q' => 'Fan Meeting']) }}" class="flex items-center justify-between px-3.5 py-2 rounded-2xl text-gray-800 hover:bg-[#FAF9F6] font-semibold transition-colors">
                                <span>Fan Meeting &amp; Ký Tặng</span>
                                <span class="bg-pink-100 text-pink-800 text-[9px] px-2 py-0.5 rounded-full font-bold">Exclusive</span>
                            </a>
                            <a href="{{ route('events.index', ['q' => 'Nhạc Kịch']) }}" class="block px-3.5 py-2 rounded-2xl text-gray-700 hover:bg-[#FAF9F6] font-medium transition-colors">
                                Nhạc Kịch &amp; Sân Khấu
                            </a>
                            <a href="{{ route('events.index', ['q' => 'Festival']) }}" class="block px-3.5 py-2 rounded-2xl text-gray-700 hover:bg-[#FAF9F6] font-medium transition-colors">
                                Festival &amp; Lễ Hội
                            </a>
                            <a href="{{ route('events.index', ['q' => 'Masterclass']) }}" class="block px-3.5 py-2 rounded-2xl text-gray-700 hover:bg-[#FAF9F6] font-medium transition-colors">
                                Workshop &amp; Masterclass
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Lịch Diễn & Khán Phòng -->
                <a href="{{ route('events.index') }}" class="px-2.5 xl:px-3 py-1.5 rounded-full text-gray-700 hover:text-black hover:bg-black/5 transition-colors whitespace-nowrap">
                    <span>Lịch Diễn</span><span class="hidden 2xl:inline"> &amp; Khán Phòng</span>
                </a>

                @auth
                    <a href="{{ route('bookings.history') }}" class="relative inline-flex items-center gap-1.5 px-2.5 xl:px-3 py-1.5 rounded-full transition-colors whitespace-nowrap {{ request()->routeIs('bookings.*') ? 'bg-[#000000] text-white font-bold' : 'text-gray-700 hover:text-black hover:bg-black/5' }}">
                        <span>Vé của tôi</span>
                        @if ($soonShowtimes->isNotEmpty())
                            <!-- Nhắc suất diễn đã đặt vé sắp diễn ra trong 24 giờ tới -->
                            <span class="flex h-2 w-2 relative" title="Có {{ $soonShowtimes->count() }} suất diễn bạn đã đặt vé sắp diễn ra trong 24 giờ tới">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-taupe opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-taupe"></span>
                            </span>
                        @endif
                    </a>
                @endauth

            </nav>

            <!-- Right: Search Modal Trigger + Notification Bell + Cart + Auth / Profile -->
            <div class="flex items-center gap-1.5 sm:gap-2.5 shrink-0">
                <!-- Search Button (Refined Luxury Input Trigger) -->
                <button 
                    type="button"
                    @click="open()" 
                    class="bg-[#FAF9F6] hover:bg-white border border-black/10 hover:border-black/25 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-full text-xs flex items-center gap-2 text-gray-500 hover:text-black transition-all shadow-sm group w-28 sm:w-32 lg:w-28 xl:w-36 2xl:w-48 shrink min-w-0"
                    title="Tìm kiếm sự kiện"
                >
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-400 group-hover:text-black transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span class="truncate font-medium text-[11px] sm:text-xs">Tìm sự kiện...</span>
                </button>

                @auth
                    <!-- Notification Bell Dropdown (Events within 12h - 24h) -->
                    <div class="relative shrink-0" x-data="{ open: false }">
                        <button 
                            @click="open = !open" 
                            class="relative w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-[#FAF9F6] border border-black/10 flex items-center justify-center text-black hover:bg-white hover:border-black/30 transition-all shadow-sm shrink-0"
                            title="Thông báo nhắc nhở sự kiện"
                        >
                            <svg class="w-4 h-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if ($soonShowtimes->isNotEmpty())
                                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-taupe"></span>
                            @endif
                        </button>

                        <div 
                            x-show="open" 
                            @click.away="open = false" 
                            x-transition:enter="ease-out duration-150" 
                            x-transition:enter-start="opacity-0 scale-95" 
                            x-transition:enter-end="opacity-100 scale-100" 
                            x-transition:leave="ease-in duration-100" 
                            x-transition:leave-start="opacity-100 scale-100" 
                            x-transition:leave-end="opacity-0 scale-95" 
                            class="absolute right-0 top-full pt-1.5 w-80 z-50"
                            style="display: none;"
                        >
                            <div class="bg-white rounded-3xl p-4 border border-black/10 shadow-[0_20px_45px_-15px_rgba(0,0,0,0.18)] text-xs space-y-3">
                                <div class="flex items-center justify-between pb-2 border-b border-black/10">
                                    <h4 class="font-display font-black text-black text-sm">Thông Báo Sự Kiện</h4>
                                    @if ($upcomingShowtimes->isNotEmpty())
                                        <span class="badge-rose text-[9px] px-2 py-0.5 rounded-full font-bold">{{ $upcomingShowtimes->count() }} suất sắp tới</span>
                                    @endif
                                </div>

                                @forelse ($upcomingShowtimes as $upcoming)
                                    @php
                                        $isSoon = \App\Support\UpcomingShowtimes::isSoon($upcoming);
                                        $startsAt = $upcoming->start_time;
                                        $dayLabel = $startsAt->isToday() ? 'Hôm nay' : ($startsAt->isTomorrow() ? 'Ngày mai' : $startsAt->format('d/m/Y'));
                                    @endphp
                                    <a href="{{ route('bookings.history') }}" class="block p-3 rounded-2xl transition-colors space-y-1 {{ $isSoon ? 'bg-[#FFF8E1] hover:bg-[#F5F5DC] border border-[#D8D8A8]' : 'bg-[#FAF9F6] hover:bg-white border border-black/10' }}">
                                        <div class="flex items-center justify-between text-[10px]">
                                            @if ($isSoon)
                                                <span class="badge-gold px-2 py-0.5 rounded-full font-black">SẮP DIỄN RA TRONG 24H</span>
                                            @else
                                                <span class="badge-sage px-2 py-0.5 rounded-full font-black">SẮP TỚI</span>
                                            @endif
                                            <span class="text-gray-500 font-bold">{{ $dayLabel }} {{ $startsAt->format('H:i') }}</span>
                                        </div>
                                        <h5 class="font-display font-black text-xs text-black">{{ $upcoming->event?->title ?? 'Sự kiện' }}</h5>
                                        @if ($upcoming->room?->name)
                                            <p class="text-[11px] text-gray-600 leading-tight">{{ $upcoming->room->name }}</p>
                                        @endif
                                    </a>
                                @empty
                                    <p class="py-3 text-center text-[11px] text-gray-500">
                                        Bạn chưa có suất diễn nào sắp tới.
                                    </p>
                                @endforelse

                                <div class="text-center pt-1">
                                    <a href="{{ route('bookings.history') }}" class="text-[11px] font-bold text-rose-taupe hover:underline">
                                        Xem tất cả vé đã đặt &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endauth

                <!-- Cart Button -->
                <a 
                    href="{{ route('cart.index') }}" 
                    class="relative w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-[#FAF9F6] border border-black/10 flex items-center justify-center text-black hover:bg-[#FAF9F6]/80 hover:border-gold-antique transition-all shadow-sm shrink-0"
                    title="Giỏ vé của bạn"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
                    </svg>
                    @if ($navCartCount > 0)
                        <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-taupe text-white text-[9px] font-bold flex items-center justify-center border-2 border-white shadow-sm">
                            {{ $navCartCount > 9 ? '9+' : $navCartCount }}
                        </span>
                    @endif
                </a>

                <!-- User Account Profile / Auth -->
                @auth
                    <div class="relative shrink-0" x-data="{ open: false }">
                        <button 
                            @click="open = !open" 
                            class="flex items-center gap-1.5 sm:gap-2 pl-1 sm:pl-1.5 pr-2 sm:pr-3 py-1 sm:py-1.5 rounded-full bg-[#FAF9F6] border border-black/10 hover:border-black/25 text-xs font-bold text-black shadow-sm shrink-0"
                        >
                            @if (Auth::user()->avatar_url)
                                <img src="{{ Auth::user()->avatar_url }}" alt="" class="w-6 h-6 sm:w-7 sm:h-7 rounded-full object-cover border border-black/10 shadow-sm shrink-0">
                            @else
                                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-gold-antique text-black flex items-center justify-center text-[10px] sm:text-[11px] font-black shadow-sm shrink-0">
                                    {{ Auth::user()->initials }}
                                </span>
                            @endif
                            <span class="hidden sm:inline max-w-[70px] md:max-w-[90px] xl:max-w-[130px] truncate">{{ Auth::user()->name }}</span>
                            <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>

                        <!-- Dropdown wrapper with seamless positioning -->
                        <div 
                            x-show="open" 
                            @click.away="open = false" 
                            x-transition:enter="ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 top-full pt-1.5 w-56 z-50"
                            style="display: none;"
                        >
                            <div class="bg-white rounded-3xl p-3 border border-black/10 shadow-[0_20px_45px_-15px_rgba(0,0,0,0.18)] text-xs">
                                @if (Auth::user()->hasRole('admin'))
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-gold-dark hover:bg-[#FFF8E1] font-bold">
                                        <svg class="w-4 h-4 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                        Trang Quản Trị Admin
                                    </a>
                                    <div class="my-1 border-t border-black/5"></div>
                                @endif
                                <a href="{{ route('bookings.history') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-gray-800 hover:bg-[#FAF9F6] font-semibold">
                                    <svg class="w-4 h-4 text-sage-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" /></svg>
                                    Vé đã đặt (E-Tickets)
                                </a>
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-gray-800 hover:bg-[#FAF9F6] font-semibold">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    Hồ sơ cá nhân &amp; Cài đặt
                                </a>
                                <div class="my-1 border-t border-black/10"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl text-crimson hover:bg-red-50 font-bold">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                        Đăng xuất
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="text-xs font-bold text-gray-700 hover:text-black px-2.5 sm:px-3 py-1.5 sm:py-2 transition-colors whitespace-nowrap">
                        Đăng nhập
                    </a>
                    <a href="{{ route('register') }}" class="btn-rose text-xs font-black px-3.5 sm:px-5 py-1.5 sm:py-2.5 rounded-full shadow-sm whitespace-nowrap">
                        Đăng ký
                    </a>
                @endauth
            </div>
        </div>

        <!-- Mobile Navigation Menu Drawer -->
        <div 
            x-show="mobileMenuOpen" 
            x-transition:enter="ease-out duration-200" 
            x-transition:enter-start="opacity-0 -translate-y-4" 
            x-transition:enter-end="opacity-100 translate-y-0"
            class="lg:hidden mt-1 bg-white border-t border-black/10 px-4 py-4 space-y-2 text-sm shadow-xl"
            style="display: none;"
        >
            <a href="{{ route('home') }}" class="block px-3 py-2 rounded-xl text-black font-bold hover:bg-[#FAF9F6]">Trang chủ</a>
            <a href="{{ route('events.index') }}" class="block px-3 py-2 rounded-xl text-gray-700 font-medium hover:bg-[#FAF9F6]">Khám Phá Sự Kiện</a>
            <a href="{{ route('events.index', ['q' => 'Concert']) }}" class="block px-3 py-2 rounded-xl text-gray-700 font-medium hover:bg-[#FAF9F6]">Live Concert &amp; Đại Nhạc Hội</a>
            <a href="{{ route('cart.index') }}" class="flex items-center justify-between px-3 py-2 rounded-xl text-gold-dark font-bold hover:bg-[#FAF9F6]">
                <span>Giỏ Vé Của Bạn</span>
                @if ($navCartCount > 0)
                    <span class="badge-rose text-[10px] px-2 py-0.5 rounded-full font-black">{{ $navCartCount }}</span>
                @endif
            </a>

            @if (auth()->user()?->hasRole('admin'))
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3 py-2 rounded-xl text-gold-dark font-black bg-[#FFF8E1] hover:bg-[#FFF3CD]">
                    <span>Trang Quản Trị Admin</span>
                    <span class="badge-gold text-[9px] px-2 py-0.5 rounded-full font-black">QUẢN TRỊ</span>
                </a>
            @endif

            @auth
                <a href="{{ route('bookings.history') }}" class="block px-3 py-2 rounded-xl text-sage-forest font-bold hover:bg-[#FAF9F6]">Vé Của Tôi (E-Ticket)</a>
                <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-xl text-gray-800 font-bold hover:bg-[#FAF9F6]">Hồ Sơ Cá Nhân &amp; Cài Đặt</a>
                <form method="POST" action="{{ route('logout') }}" class="pt-2 border-t border-black/10">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-xl text-crimson font-bold hover:bg-red-50">
                        Đăng xuất
                    </button>
                </form>
            @else
                <div class="pt-2 border-t border-black/10 flex items-center gap-3">
                    <a href="{{ route('login') }}" class="flex-1 text-center py-2 rounded-xl bg-black/5 font-bold text-gray-800">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="flex-1 text-center py-2 rounded-xl btn-rose font-bold">Đăng ký</a>
                </div>
            @endauth
        </div>
    </header>

    <!-- Global Search Modal (Bright Dialog) — gợi ý AJAX qua route search.suggestions -->
    <div
        x-show="isOpen"
        @keydown.escape.window="if (isOpen) close()"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4"
        role="dialog"
        aria-modal="true"
        aria-label="Tìm kiếm sự kiện"
        style="display: none;"
    >
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="close()"></div>
        <div class="relative w-full max-w-2xl bg-white rounded-3xl border border-black/15 p-6 sm:p-7 shadow-2xl z-10">
            <div class="flex items-center gap-3 border-b border-black/10 pb-4">
                <svg class="w-5 h-5 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <form action="{{ route('movies.index') }}" method="GET" class="flex-1">
                    <input
                        id="global-search-input"
                        type="text"
                        name="q"
                        maxlength="100"
                        autocomplete="off"
                        data-suggest-url="{{ route('search.suggestions') }}"
                        x-model="query"
                        @input="handleSearch()"
                        placeholder="Tìm kiếm sự kiện, concert, hòa nhạc, hội thảo, triển lãm..."
                        class="w-full bg-transparent border-0 text-black placeholder-gray-400 focus:ring-0 text-base font-semibold"
                    >
                </form>
                <button type="button" @click="close()" title="Đóng (Esc)" class="text-xs text-gray-600 hover:text-black px-3 py-1 rounded-xl bg-[#F5F5DC] border border-[#D8D8A8] font-bold">ESC</button>
            </div>

            <!-- Live Search Results (AJAX) -->
            <div class="mt-4" x-show="query.trim().length >= 2" style="display: none;" aria-live="polite">
                <div x-show="isLoading" class="flex items-center gap-2 text-xs text-gray-500 py-2">
                    <svg class="w-4 h-4 animate-spin text-gold-dark" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span>Đang tìm…</span>
                </div>
                <p x-show="!isLoading && hasError" class="text-xs text-[#CC0000] font-semibold py-2">Không thể tải gợi ý lúc này, nhấn Enter để tìm kiếm.</p>
                <p x-show="!isLoading && !hasError && results.length === 0" class="text-xs text-gray-500 py-2">
                    Không tìm thấy sự kiện phù hợp với "<span class="font-bold text-black" x-text="query.trim()"></span>".
                </p>
                <ul x-show="!isLoading && results.length > 0" class="divide-y divide-black/5">
                    <template x-for="item in results" :key="item.url">
                        <li>
                            <a :href="item.url" class="flex items-center gap-3 py-2.5 px-2 rounded-xl hover:bg-[#FAF9F6]">
                                <img :src="item.poster_url" alt="" loading="lazy" class="w-10 h-14 object-cover rounded-lg border border-black/10 shrink-0">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-black truncate" x-text="item.title"></p>
                                    <p class="text-[11px] text-gray-500">
                                        <span x-text="item.category"></span>
                                        <span x-show="item.price_from" x-text="' · Từ ' + item.price_from"></span>
                                    </p>
                                </div>
                            </a>
                        </li>
                    </template>
                </ul>
                <a x-show="!isLoading" :href="'{{ route('movies.index') }}?q=' + encodeURIComponent(query.trim())" class="mt-3 inline-block text-xs font-bold text-gold-dark hover:underline">Xem tất cả kết quả &rarr;</a>
            </div>

            <!-- Popular events (tải AJAX khi mở modal) -->
            <div class="mt-4" x-show="query.trim().length < 2 && popular.length > 0" style="display: none;">
                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 block mb-2">Sự kiện nổi bật</span>
                <div class="flex flex-wrap gap-2">
                    <template x-for="item in popular" :key="item.url">
                        <a :href="item.url" class="badge-gold px-3.5 py-1 rounded-full text-xs font-bold max-w-full truncate" x-text="item.title"></a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Page Content -->
    <main class="flex-1 relative z-10">
        {{ $slot }}
    </main>

    <!-- Sleek Compact Luxury Footer -->
    <footer id="footer-support" class="mt-16 sm:mt-20 relative z-10 border-t border-white/10 bg-[#000000] text-white">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-8 py-10 sm:py-12">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
                
                <!-- Left: Brand Identity & Quick Support (5 cols) -->
                <div class="lg:col-span-5 space-y-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-gold-antique to-rose-taupe flex items-center justify-center text-black font-display font-black text-base shadow-sm">
                            T
                        </div>
                        <span class="font-display font-black text-xl text-white tracking-tight">Ticket<span class="text-rose-light">Box</span></span>
                    </div>

                    <p class="text-xs text-gray-400 leading-relaxed max-w-sm">
                        Website quản lý đặt vé sự kiện, concert và hội thảo — đồ án môn Mã nguồn mở. Đây là sản phẩm học tập, không phải dịch vụ bán vé thương mại.
                    </p>

                    <div class="flex flex-wrap items-center gap-4 text-xs text-gray-300 pt-1 font-medium">
                        <div class="flex items-center gap-1.5">
                            <span class="text-gold-antique font-bold">Liên hệ:</span>
                            <span class="text-rose-light font-semibold">hotro@example.com</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Essential Grouped Links (7 cols) -->
                <div class="lg:col-span-7 grid grid-cols-2 sm:grid-cols-3 gap-6 text-xs">
                    <!-- Col 1: Khám Phá -->
                    <div class="space-y-2.5">
                        <h4 class="font-display text-[11px] font-black uppercase tracking-wider text-gold-light">
                            Khám Phá Sự Kiện
                        </h4>
                        <ul class="space-y-2 text-gray-400 font-medium">
                            <li><a href="{{ route('events.index', ['q' => 'Concert']) }}" class="hover:text-gold-antique transition-colors">Live Concert &amp; Liveshow</a></li>
                            <li><a href="{{ route('events.index', ['q' => 'Hòa Nhạc']) }}" class="hover:text-gold-antique transition-colors">Hòa Nhạc Giao Hưởng</a></li>
                            <li><a href="{{ route('events.index', ['q' => 'Summit']) }}" class="hover:text-gold-antique transition-colors">Hội Thảo &amp; Diễn Đàn</a></li>
                            <li><a href="{{ route('events.index', ['q' => 'Triển Lãm']) }}" class="hover:text-gold-antique transition-colors">Triển Lãm Nghệ Thuật</a></li>
                        </ul>
                    </div>

                    <!-- Col 2: Dành Cho Khách Hàng -->
                    <div class="space-y-2.5">
                        <h4 class="font-display text-[11px] font-black uppercase tracking-wider text-gold-light">
                            Khách Hàng
                        </h4>
                        <ul class="space-y-2 text-gray-400 font-medium">
                            <li><a href="{{ route('events.index') }}" class="hover:text-gold-antique transition-colors">Tất cả sự kiện</a></li>
                            <li><a href="{{ route('cart.index') }}" class="hover:text-gold-antique transition-colors">Giỏ vé của bạn</a></li>
                            <li><a href="{{ route('bookings.history') }}" class="hover:text-gold-antique transition-colors">Vé đã đặt (E-Ticket)</a></li>
                            <li><a href="{{ route('home') }}#faq" class="hover:text-gold-antique transition-colors">Câu hỏi thường gặp (FAQ)</a></li>
                        </ul>
                    </div>

                    <!-- Col 3: Tài Khoản -->
                    <div class="space-y-2.5">
                        <h4 class="font-display text-[11px] font-black uppercase tracking-wider text-gold-light">
                            Tài Khoản
                        </h4>
                        <ul class="space-y-2 text-gray-400 font-medium">
                            @auth
                                <li><a href="{{ route('profile.edit') }}" class="hover:text-gold-antique transition-colors">Hồ sơ cá nhân</a></li>
                                @if (Auth::user()->hasRole('admin'))
                                    <li><a href="{{ route('admin.dashboard') }}" class="text-gold-light font-bold hover:underline">Vào Bảng Quản Trị Admin</a></li>
                                @endif
                            @else
                                <li><a href="{{ route('login') }}" class="hover:text-gold-antique transition-colors">Đăng nhập</a></li>
                                <li><a href="{{ route('register') }}" class="hover:text-gold-antique transition-colors">Đăng ký tài khoản</a></li>
                            @endauth
                        </ul>
                    </div>
                </div>

            </div>

            <!-- Compact Bottom Bar -->
            <div class="mt-8 pt-6 border-t border-white/10 text-center sm:text-left text-[11px] text-gray-400 font-medium">
                © {{ date('Y') }} TicketBox — Đồ án môn Mã nguồn mở. Sản phẩm phục vụ mục đích học tập.
            </div>
        </div>
    </footer>

    <!-- AI Virtual Assistant Chatbot Widget -->
    <x-ai-chatbot />
</body>
</html>
