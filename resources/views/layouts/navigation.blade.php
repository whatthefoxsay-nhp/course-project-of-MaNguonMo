<nav x-data="{ open: false }" class="bg-white/95 backdrop-blur-md border-b border-black/10 sticky top-0 z-40 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-gold-antique to-rose-taupe flex items-center justify-center text-black font-display font-black text-lg shadow-sm">
                            T
                        </div>
                        <span class="font-display font-black text-xl text-black tracking-tight">
                            Ticket<span class="text-rose-taupe">Box</span>
                        </span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-5 sm:-my-px sm:ms-8 sm:flex items-center text-xs sm:text-sm font-semibold">
                    <a href="{{ route('home') }}" class="px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap {{ request()->routeIs('home') ? 'bg-[#000000] text-white font-bold' : 'text-gray-700 hover:text-black hover:bg-black/5' }}">
                        Trang chủ
                    </a>

                    <a href="{{ route('movies.index') }}" class="px-3.5 py-1.5 rounded-full text-gray-700 hover:text-black hover:bg-black/5 transition-colors whitespace-nowrap {{ request()->routeIs('movies.*') ? 'bg-[#000000] text-white font-bold' : '' }}">
                        Khám Phá Sự Kiện
                    </a>

                    <a href="{{ route('movies.index') }}" class="px-3.5 py-1.5 rounded-full text-gray-700 hover:text-black hover:bg-black/5 transition-colors whitespace-nowrap">
                        Lịch Diễn &amp; Khán Phòng
                    </a>

                    <a href="{{ route('bookings.history') }}" class="relative inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full transition-colors whitespace-nowrap {{ request()->routeIs('bookings.*') ? 'bg-[#000000] text-white font-bold' : 'text-gray-700 hover:text-black hover:bg-black/5' }}">
                        <span>Vé của tôi</span>
                        <span class="flex h-2 w-2 relative" title="Có 1 sự kiện sắp diễn ra trong 12 tiếng">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-taupe opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-taupe"></span>
                        </span>
                    </a>

                    @if(auth()->user()?->hasRole('admin'))
                        <a href="{{ route('admin.dashboard') }}" class="px-3.5 py-1.5 rounded-full transition-colors text-gold-dark font-black hover:text-black hover:bg-[#FFF8E1] bg-[#FFF8E1]/80 border border-gold-antique/40 inline-flex items-center gap-1.5 whitespace-nowrap shadow-xs">
                            <svg class="w-3.5 h-3.5 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Trang Quản Trị</span>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Right Controls: Notification Bell + Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:gap-3 sm:ms-6">
                <!-- Notification Bell Dropdown -->
                <div class="relative" x-data="{ openNotification: false }">
                    <button 
                        @click="openNotification = !openNotification" 
                        class="relative w-9 h-9 rounded-full bg-[#FAF9F6] border border-black/10 flex items-center justify-center text-black hover:bg-white hover:border-black/30 transition-all shadow-sm shrink-0"
                        title="Thông báo sự kiện"
                    >
                        <svg class="w-4 h-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-taupe"></span>
                    </button>

                    <div 
                        x-show="openNotification" 
                        @click.away="openNotification = false" 
                        x-transition:enter="ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="absolute right-0 top-full pt-2 w-80 z-50"
                        style="display: none;"
                    >
                        <div class="bg-white rounded-3xl p-4 border border-black/10 shadow-[0_20px_45px_-15px_rgba(0,0,0,0.18)] text-xs space-y-3">
                            <div class="flex items-center justify-between pb-2 border-b border-black/10">
                                <h4 class="font-display font-black text-black text-sm">Thông Báo Sự Kiện</h4>
                                <span class="badge-rose text-[9px] px-2 py-0.5 rounded-full font-bold">1 Nhắc Nhở</span>
                            </div>

                            <a href="{{ route('bookings.history') }}" class="block p-3 rounded-2xl bg-[#FFF8E1] hover:bg-[#F5F5DC] border border-[#D8D8A8] transition-colors space-y-1">
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="badge-gold px-2 py-0.5 rounded-full font-black">SẮP DIỄN RA TRONG 12H</span>
                                    <span class="text-gray-500 font-bold">Hôm nay 19:30</span>
                                </div>
                                <h5 class="font-display font-black text-xs text-black">Live Concert Acoustic 2026</h5>
                                <p class="text-[11px] text-gray-600 leading-tight">
                                    Khán phòng Saigon Grand Hall. Vui lòng mở mã QR vé điện tử để làm thủ tục check-in.
                                </p>
                            </a>

                            <div class="text-center pt-1">
                                <a href="{{ route('bookings.history') }}" class="text-[11px] font-bold text-rose-taupe hover:underline">
                                    Xem tất cả vé đã đặt &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-2xl bg-[#F5F5DC] border border-[#D8D8A8] text-xs font-bold text-black hover:bg-[#E8E8C8] focus:outline-none transition ease-in-out duration-150 shadow-sm">
                            <div class="w-6 h-6 rounded-full bg-gold-antique text-black font-black flex items-center justify-center text-[10px]">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <div>{{ Auth::user()->name }}</div>

                            <svg class="fill-current h-4 w-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 text-xs text-gray-500 border-b border-black/5 bg-[#FAF9F6]">
                            <span class="text-[10px] uppercase font-bold text-gray-400 block tracking-wider">Tài khoản</span>
                            <span class="font-bold text-black truncate block mt-0.5">{{ Auth::user()->email }}</span>
                        </div>

                        <div class="p-1.5 space-y-0.5">
                            <x-dropdown-link :href="route('profile.edit')" class="rounded-xl text-xs font-semibold hover:bg-[#FAF9F6] flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>Thông tin cá nhân</span>
                            </x-dropdown-link>

                            @if(auth()->user()?->hasRole('admin'))
                                <x-dropdown-link :href="route('admin.dashboard')" class="rounded-xl text-xs font-bold text-gold-dark hover:bg-[#FFF8E1] flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span>Trang Quản Trị Admin</span>
                                </x-dropdown-link>
                            @else
                                <x-dropdown-link :href="route('bookings.history')" class="rounded-xl text-xs font-semibold hover:bg-[#FAF9F6] flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                    </svg>
                                    <span>Lịch sử đặt vé</span>
                                </x-dropdown-link>
                            @endif

                            <div class="border-t border-black/5 my-1"></div>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();"
                                        class="rounded-xl text-xs font-bold text-crimson hover:bg-red-50 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-crimson" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Đăng xuất</span>
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-xl text-black hover:bg-gray-100 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-black/10 bg-white">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('home')">
                Trang chủ
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('movies.index')">
                Khám Phá Sự Kiện
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('movies.index')">
                Lịch Diễn &amp; Khán Phòng
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('bookings.history')">
                <span class="flex items-center justify-between">
                    <span>Vé của tôi</span>
                    <span class="badge-rose text-[9px] px-2 py-0.5 rounded-full font-bold">E-Ticket</span>
                </span>
            </x-responsive-nav-link>
            @if(auth()->user()?->hasRole('admin'))
                <x-responsive-nav-link :href="route('admin.dashboard')" class="text-gold-dark font-black bg-[#FFF8E1]">
                    Trang Quản Trị Admin
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-3 border-t border-black/10 bg-[#FAF9F6]">
            <div class="px-4">
                <div class="font-bold text-base text-black">{{ Auth::user()->name }}</div>
                <div class="font-medium text-xs text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Thông tin cá nhân
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('bookings.history')">
                    Lịch sử đặt vé
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        Đăng xuất
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
