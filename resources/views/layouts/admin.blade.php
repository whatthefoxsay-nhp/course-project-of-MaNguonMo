<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin Dashboard' }} - TicketBox Management</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body 
    x-data="{ 
        sidebarCollapsed: localStorage.getItem('admin_sidebar_collapsed') === 'true',
        toggleSidebar() { 
            this.sidebarCollapsed = !this.sidebarCollapsed; 
            localStorage.setItem('admin_sidebar_collapsed', this.sidebarCollapsed); 
        } 
    }" 
    class="min-h-screen bg-[#FAF9F6] text-black font-sans flex antialiased selection:bg-rose-taupe selection:text-white"
>

    <!-- Modern Luxury Collapsible Sidebar (Deep Slate-Charcoal) -->
    <aside 
        class="fixed inset-y-0 left-0 bg-[#131722] text-slate-100 border-r border-slate-800/80 z-30 flex flex-col justify-between shadow-2xl transition-all duration-300 ease-in-out"
        :class="sidebarCollapsed ? 'w-20' : 'w-64'"
    >
        <div>
            <!-- Admin Brand & Collapse Toggle -->
            <div 
                class="h-20 flex items-center border-b border-slate-800/80 bg-[#0e121b]/80 backdrop-blur-md transition-all duration-300"
                :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between px-6'"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div 
                        class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-amber-400 via-amber-500 to-rose-400 flex items-center justify-center text-slate-950 font-display font-black text-xl shadow-lg shadow-amber-500/20 shrink-0 cursor-pointer"
                        @click="if(sidebarCollapsed) toggleSidebar()"
                        :title="sidebarCollapsed ? 'Nhấn để mở rộng sidebar' : ''"
                    >
                        T
                    </div>
                    <div 
                        x-show="!sidebarCollapsed" 
                        x-transition:enter="transition ease-out duration-200" 
                        x-transition:enter-start="opacity-0 -translate-x-2" 
                        x-transition:enter-end="opacity-100 translate-x-0"
                        class="min-w-0 truncate"
                    >
                        <span class="font-display font-black text-lg text-white block leading-none tracking-tight">Ticket<span class="text-amber-400">Admin</span></span>
                        <span class="text-[10px] text-slate-400 uppercase tracking-widest font-bold mt-1 block truncate">Control Center</span>
                    </div>
                </div>

                <!-- Sidebar internal toggle button (visible when expanded) -->
                <button 
                    x-show="!sidebarCollapsed"
                    @click="toggleSidebar()" 
                    type="button" 
                    class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition-colors shrink-0" 
                    title="Thu gọn sidebar"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="p-3 space-y-4 text-sm font-medium overflow-y-auto max-h-[calc(100vh-160px)]">
                <!-- Group 1: Báo cáo & Phân tích -->
                <div>
                    <div x-show="!sidebarCollapsed" x-transition class="px-3 pb-2 text-[10px] font-black uppercase tracking-wider text-slate-400 truncate">Báo Cáo &amp; Thống Kê</div>
                    <div x-show="sidebarCollapsed" class="w-6 h-px bg-slate-800 mx-auto my-2"></div>
                    <div class="space-y-1">
                        <a 
                            href="{{ route('admin.dashboard') }}" 
                            :title="sidebarCollapsed ? 'Tổng Quan (KPIs)' : ''"
                            class="flex items-center gap-3 rounded-2xl transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-slate-950' : 'text-amber-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Tổng Quan (KPIs)</span>
                        </a>

                        <a 
                            href="{{ route('admin.reports.index') }}" 
                            :title="sidebarCollapsed ? 'Báo Cáo Doanh Thu' : ''"
                            class="flex items-center gap-3 rounded-2xl transition-all {{ request()->routeIs('admin.reports.*') ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.reports.*') ? 'text-slate-950' : 'text-emerald-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Báo Cáo Doanh Thu</span>
                        </a>
                    </div>
                </div>

                <!-- Group 2: Quản trị nội dung -->
                <div>
                    <div x-show="!sidebarCollapsed" x-transition class="px-3 pb-2 text-[10px] font-black uppercase tracking-wider text-slate-400 truncate">Quản Trị Nội Dung</div>
                    <div x-show="sidebarCollapsed" class="w-6 h-px bg-slate-800 mx-auto my-2"></div>
                    <div class="space-y-1">
                        <a 
                            href="{{ route('admin.events.index') }}" 
                            :title="sidebarCollapsed ? 'Quản Lý Sự Kiện' : ''"
                            class="flex items-center gap-3 rounded-2xl transition-all {{ request()->routeIs('admin.events.*') ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.events.*') ? 'text-slate-950' : 'text-rose-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Quản Lý Sự Kiện</span>
                        </a>

                        <a 
                            href="{{ route('admin.showtimes.index') }}" 
                            :title="sidebarCollapsed ? 'Lịch Diễn & Suất Chiếu' : ''"
                            class="flex items-center gap-3 rounded-2xl transition-all {{ request()->routeIs('admin.showtimes.*') ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.showtimes.*') ? 'text-slate-950' : 'text-cyan-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Lịch Diễn &amp; Suất Chiếu</span>
                        </a>

                        <a 
                            href="{{ route('admin.rooms.index') }}" 
                            :title="sidebarCollapsed ? 'Khán Phòng & Sơ Đồ Ghế' : ''"
                            class="flex items-center gap-3 rounded-2xl transition-all {{ request()->routeIs('admin.rooms.*') ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.rooms.*') ? 'text-slate-950' : 'text-purple-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Khán Phòng &amp; Sơ Đồ Ghế</span>
                        </a>
                    </div>
                </div>

                <!-- Group 3: Bán vé, Khách hàng & Mã giảm giá -->
                <div>
                    <div x-show="!sidebarCollapsed" x-transition class="px-3 pb-2 text-[10px] font-black uppercase tracking-wider text-slate-400 truncate">Bán Vé &amp; Khách Hàng</div>
                    <div x-show="sidebarCollapsed" class="w-6 h-px bg-slate-800 mx-auto my-2"></div>
                    <div class="space-y-1">
                        <a 
                            href="{{ route('admin.bookings.index') }}" 
                            :title="sidebarCollapsed ? 'Danh Sách Đơn Đặt Vé' : ''"
                            class="flex items-center gap-3 rounded-2xl transition-all {{ request()->routeIs('admin.bookings.*') ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.bookings.*') ? 'text-slate-950' : 'text-blue-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Danh Sách Đơn Đặt Vé</span>
                        </a>

                        <a 
                            href="{{ route('admin.discounts.index') }}" 
                            :title="sidebarCollapsed ? 'Quản Lý Mã Giảm' : ''"
                            class="flex items-center gap-3 rounded-2xl transition-all {{ request()->routeIs('admin.discounts.*') ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.discounts.*') ? 'text-slate-950' : 'text-amber-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Quản Lý Mã Giảm</span>
                        </a>

                        <a 
                            href="{{ route('admin.users.index') }}" 
                            :title="sidebarCollapsed ? 'Quản Lý Tài Khoản' : ''"
                            class="flex items-center gap-3 rounded-2xl transition-all {{ request()->routeIs('admin.users.*') ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.users.*') ? 'text-slate-950' : 'text-emerald-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Quản Lý Tài Khoản</span>
                        </a>
                    </div>
                </div>

                <!-- Group 4: Hệ Thống -->
                <div>
                    <div x-show="!sidebarCollapsed" x-transition class="px-3 pb-2 text-[10px] font-black uppercase tracking-wider text-slate-400 truncate">Hệ Thống</div>
                    <div x-show="sidebarCollapsed" class="w-6 h-px bg-slate-800 mx-auto my-2"></div>
                    <a 
                        href="{{ route('home') }}" 
                        :title="sidebarCollapsed ? 'Xem Trang Khách Hàng' : ''"
                        class="flex items-center gap-3 rounded-2xl transition-all text-slate-300 hover:text-white hover:bg-slate-800/60"
                        :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                    >
                        <svg class="w-5 h-5 shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-transition class="truncate">Xem Trang Khách Hàng</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Admin Profile Footer -->
        <div class="p-3.5 border-t border-slate-800/80 bg-[#0e121b]/90">
            <div 
                class="flex items-center transition-all duration-300"
                :class="sidebarCollapsed ? 'flex-col gap-2 justify-center' : 'justify-between px-2'"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-amber-400 to-amber-500 text-slate-950 font-black flex items-center justify-center text-sm shadow-md shrink-0">
                        AD
                    </div>
                    <div x-show="!sidebarCollapsed" x-transition class="text-xs min-w-0 truncate">
                        <span class="font-bold text-white block truncate">{{ Auth::user()->name ?? 'Administrator' }}</span>
                        <span class="text-emerald-400 text-[10px] font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Super Admin
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" :class="sidebarCollapsed ? 'w-full flex justify-center' : ''">
                    @csrf
                    <button type="submit" title="Đăng xuất" class="text-slate-400 hover:text-rose-400 p-2 rounded-xl hover:bg-slate-800/60 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Panel (Bright White Canvas) -->
    <div 
        class="flex-1 flex flex-col min-w-0 bg-[#FAF9F6] transition-all duration-300 ease-in-out"
        :class="sidebarCollapsed ? 'pl-20' : 'pl-64'"
    >
        <!-- Admin Topbar (Bright Surface) -->
        <header class="h-20 bg-white/95 backdrop-blur-xl border-b border-black/10 px-6 sm:px-8 flex items-center justify-between sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-3.5 min-w-0">
                <!-- Topbar Toggle Button -->
                <button 
                    @click="toggleSidebar()" 
                    type="button" 
                    class="p-2.5 rounded-2xl border border-black/10 hover:bg-[#FAF9F6] hover:border-black/20 text-gray-700 hover:text-black transition-all flex items-center justify-center shadow-sm shrink-0" 
                    :title="sidebarCollapsed ? 'Mở rộng sidebar' : 'Thu gọn sidebar'"
                >
                    <svg 
                        class="w-5 h-5 transition-transform duration-300" 
                        :class="sidebarCollapsed ? 'rotate-180 text-amber-600' : 'text-gray-600'" 
                        fill="none" 
                        viewBox="0 0 24 24" 
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>

                <div class="min-w-0">
                    <h1 class="font-display font-black text-xl text-black truncate">{{ $header ?? 'Bảng Điều Khiển Quản Trị' }}</h1>
                    <p class="text-xs text-gray-500 mt-0.5 truncate hidden sm:block">Quản trị người dùng, doanh thu, suất chiếu và kiểm soát lượt đặt vé</p>
                </div>
            </div>

            <div class="flex items-center gap-4 shrink-0">
                <span class="px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="hidden sm:inline">Hệ thống hoạt động bình thường</span>
                    <span class="sm:hidden">Online</span>
                </span>
            </div>
        </header>

        <main class="p-6 sm:p-8 flex-1">
            @include('admin.partials.flash')
            {{ $slot }}
        </main>
    </div>

</body>
</html>
