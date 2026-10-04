@props([
    'header' => null,
    'subtitle' => null,
    'breadcrumb' => null,
    'title' => null,
])
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

    <style>
        /* ==========================================================================
           Admin Sidebar Color System & Variables (WCAG AA Compliant)
           Theme: Navy Dark + Amber Accent
           ========================================================================== */
        :root {
            --sidebar-bg: #0F172A;
            --sidebar-bg-gradient: linear-gradient(180deg, #0F172A 0%, #1E293B 100%);
            --sidebar-footer-bg: #0A0F1D;
            --sidebar-header-bg: #0C1322;
            --item-bg: rgba(255, 255, 255, 0.05);
            --item-hover-bg: rgba(255, 255, 255, 0.09);
            --item-active-bg: rgba(245, 158, 11, 0.15);
            --item-active-text: #FFFFFF;
            --text-primary: #F1F5F9;
            --text-muted: #94A3B8;
            --group-title: #FCD34D;
            --accent: #F59E0B;
            --accent-soft: rgba(245, 158, 11, 0.2);
            --border-subtle: rgba(255, 255, 255, 0.1);
            --scrollbar-thumb: rgba(255, 255, 255, 0.2);
            --scrollbar-track: transparent;
        }

        /* Sidebar Container */
        .admin-sidebar {
            background: var(--sidebar-bg-gradient) !important;
            border-color: var(--border-subtle) !important;
            color: var(--text-primary);
        }

        /* Sidebar Custom Scrollbar */
        .admin-sidebar-nav {
            scrollbar-width: thin;
            scrollbar-color: var(--scrollbar-thumb) var(--scrollbar-track);
        }

        .admin-sidebar-nav::-webkit-scrollbar {
            width: 6px !important;
            height: 6px !important;
        }

        .admin-sidebar-nav::-webkit-scrollbar-track {
            background: var(--scrollbar-track) !important;
        }

        .admin-sidebar-nav::-webkit-scrollbar-thumb {
            background: var(--scrollbar-thumb) !important;
            border-radius: 9999px !important;
        }

        .admin-sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4) !important;
        }

        /* Base Nav Item */
        .admin-nav-item {
            background: var(--item-bg);
            border: 1px solid var(--border-subtle);
            border-left: 3px solid transparent;
            color: var(--text-primary);
            transition: all 180ms ease-in-out;
        }

        .admin-nav-item:hover {
            background: var(--item-hover-bg);
            border-color: rgba(255, 255, 255, 0.16);
            border-left-color: rgba(255, 255, 255, 0.3);
            color: #FFFFFF;
        }

        .admin-nav-item:hover .admin-nav-icon {
            color: var(--accent);
        }

        /* Active Nav Item - 3px left accent bar, subtle accent background, NO harsh white */
        .admin-nav-item.is-active {
            background: var(--item-active-bg) !important;
            border-color: rgba(245, 158, 11, 0.3) !important;
            border-left: 3px solid var(--accent) !important;
            color: var(--item-active-text) !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
        }

        .admin-nav-item.is-active .admin-nav-icon {
            color: var(--accent) !important;
        }

        /* Unified Single-Tone Icon */
        .admin-nav-icon {
            color: var(--text-muted);
            transition: color 180ms ease-in-out;
        }

        /* Focus Visible for Accessibility (WCAG AA) */
        .admin-nav-item:focus-visible,
        .admin-sidebar button:focus-visible,
        .admin-sidebar a:focus-visible {
            outline: 2px solid var(--accent) !important;
            outline-offset: 2px !important;
        }
    </style>
</head>
<body 
    x-data="{ 
        sidebarCollapsed: localStorage.getItem('admin_sidebar_collapsed') === 'true',
        toggleSidebar() { 
            this.sidebarCollapsed = !this.sidebarCollapsed; 
            localStorage.setItem('admin_sidebar_collapsed', this.sidebarCollapsed); 
        }
    }" 
    class="min-h-screen bg-[#FAF9F6] text-black font-sans antialiased selection:bg-rose-taupe selection:text-white"
>

    <!-- Modern Luxury Collapsible Sidebar (Navy Dark + Amber Accent) -->
    <aside 
        class="admin-sidebar fixed inset-y-0 left-0 border-r z-30 flex flex-col justify-between shadow-2xl transition-all duration-300 ease-in-out"
        :class="sidebarCollapsed ? 'w-20' : 'w-64'"
    >
        <div>
            <!-- Admin Brand & Collapse Toggle -->
            <div 
                class="h-20 flex items-center border-b transition-all duration-300"
                style="border-color: var(--border-subtle); background: var(--sidebar-header-bg);"
                :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between px-4'"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div 
                        class="w-11 h-11 rounded-2xl flex items-center justify-center font-display font-black text-2xl shadow-md shrink-0 cursor-pointer hover:scale-105 transition-transform"
                        style="background: linear-gradient(135deg, var(--accent) 0%, #D97706 100%); color: #0F172A;"
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
                        <span class="font-display font-black text-xl text-white block leading-none tracking-tight">Ticket<span style="color: var(--accent);">Admin</span></span>
                        <span class="text-[10px] uppercase tracking-widest font-semibold mt-1 block truncate" style="color: var(--text-muted);">Control Center</span>
                    </div>
                </div>

                <!-- Sidebar internal toggle button (visible when expanded) -->
                <button 
                    x-show="!sidebarCollapsed" 
                    @click="toggleSidebar()" 
                    type="button" 
                    class="w-8 h-8 rounded-xl bg-transparent hover:bg-white/10 text-[var(--text-muted)] hover:text-white flex items-center justify-center transition-all duration-200 shrink-0" 
                    title="Thu gọn sidebar"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="admin-sidebar-nav p-3 space-y-4 text-sm font-medium overflow-y-auto max-h-[calc(100vh-165px)]">
                <!-- Group 1: Báo cáo & Phân tích -->
                <div>
                    <div x-show="!sidebarCollapsed" x-transition class="px-2 pb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: var(--accent);"></span>
                        <span class="text-[11px] font-bold uppercase tracking-[0.08em] truncate" style="color: var(--group-title); opacity: 0.85;">Báo Cáo &amp; Thống Kê</span>
                    </div>
                    <div x-show="sidebarCollapsed" class="w-6 h-px mx-auto my-2" style="background-color: var(--border-subtle);"></div>
                    <div class="space-y-1.5">
                        <a 
                            href="{{ route('admin.dashboard') }}" 
                            :title="sidebarCollapsed ? 'Tổng Quan (KPIs)' : ''"
                            class="admin-nav-item group flex items-center gap-3 rounded-2xl {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <div class="admin-nav-icon w-7 h-7 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Tổng Quan (KPIs)</span>
                        </a>

                        <a 
                            href="{{ route('admin.reports.index') }}" 
                            :title="sidebarCollapsed ? 'Báo Cáo Doanh Thu' : ''"
                            class="admin-nav-item group flex items-center gap-3 rounded-2xl {{ request()->routeIs('admin.reports.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <div class="admin-nav-icon w-7 h-7 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Báo Cáo Doanh Thu</span>
                        </a>
                    </div>
                </div>

                <!-- Group 2: Quản trị nội dung -->
                <div>
                    <div x-show="!sidebarCollapsed" x-transition class="px-2 pb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: var(--accent);"></span>
                        <span class="text-[11px] font-bold uppercase tracking-[0.08em] truncate" style="color: var(--group-title); opacity: 0.85;">Quản Trị Nội Dung</span>
                    </div>
                    <div x-show="sidebarCollapsed" class="w-6 h-px mx-auto my-2" style="background-color: var(--border-subtle);"></div>
                    <div class="space-y-1.5">
                        <a 
                            href="{{ route('admin.categories.index') }}" 
                            :title="sidebarCollapsed ? 'Danh Mục' : ''"
                            class="admin-nav-item group flex items-center gap-3 rounded-2xl {{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <div class="admin-nav-icon w-7 h-7 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Danh Mục</span>
                        </a>

                        <a 
                            href="{{ route('admin.events.index') }}" 
                            :title="sidebarCollapsed ? 'Quản Lý Sự Kiện' : ''"
                            class="admin-nav-item group flex items-center gap-3 rounded-2xl {{ request()->routeIs('admin.events.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <div class="admin-nav-icon w-7 h-7 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Quản Lý Sự Kiện</span>
                        </a>

                        <a 
                            href="{{ route('admin.showtimes.index') }}" 
                            :title="sidebarCollapsed ? 'Lịch Diễn & Suất Chiếu' : ''"
                            class="admin-nav-item group flex items-center gap-3 rounded-2xl {{ request()->routeIs('admin.showtimes.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <div class="admin-nav-icon w-7 h-7 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Lịch Diễn &amp; Suất Chiếu</span>
                        </a>

                        <a 
                            href="{{ route('admin.rooms.index') }}" 
                            :title="sidebarCollapsed ? 'Khán Phòng & Sơ Đồ Ghế' : ''"
                            class="admin-nav-item group flex items-center gap-3 rounded-2xl {{ request()->routeIs('admin.rooms.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <div class="admin-nav-icon w-7 h-7 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Khán Phòng &amp; Sơ Đồ Ghế</span>
                        </a>
                    </div>
                </div>

                <!-- Group 3: Bán vé, Khách hàng & Mã giảm giá -->
                <div>
                    <div x-show="!sidebarCollapsed" x-transition class="px-2 pb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: var(--accent);"></span>
                        <span class="text-[11px] font-bold uppercase tracking-[0.08em] truncate" style="color: var(--group-title); opacity: 0.85;">Bán Vé &amp; Khách Hàng</span>
                    </div>
                    <div x-show="sidebarCollapsed" class="w-6 h-px mx-auto my-2" style="background-color: var(--border-subtle);"></div>
                    <div class="space-y-1.5">
                        <a 
                            href="{{ route('admin.bookings.index') }}" 
                            :title="sidebarCollapsed ? 'Danh Sách Đơn Đặt Vé' : ''"
                            class="admin-nav-item group flex items-center gap-3 rounded-2xl {{ request()->routeIs('admin.bookings.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <div class="admin-nav-icon w-7 h-7 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Danh Sách Đơn Đặt Vé</span>
                        </a>

                        <a 
                            href="{{ route('admin.discounts.index') }}" 
                            :title="sidebarCollapsed ? 'Quản Lý Mã Giảm' : ''"
                            class="admin-nav-item group flex items-center gap-3 rounded-2xl {{ request()->routeIs('admin.discounts.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <div class="admin-nav-icon w-7 h-7 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Quản Lý Mã Giảm</span>
                        </a>

                        <a 
                            href="{{ route('admin.users.index') }}" 
                            :title="sidebarCollapsed ? 'Quản Lý Tài Khoản' : ''"
                            class="admin-nav-item group flex items-center gap-3 rounded-2xl {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                        >
                            <div class="admin-nav-icon w-7 h-7 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" x-transition class="truncate">Quản Lý Tài Khoản</span>
                        </a>
                    </div>
                </div>

                <!-- Group 4: Hệ Thống -->
                <div>
                    <div x-show="!sidebarCollapsed" x-transition class="px-2 pb-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: var(--accent);"></span>
                        <span class="text-[11px] font-bold uppercase tracking-[0.08em] truncate" style="color: var(--group-title); opacity: 0.85;">Hệ Thống</span>
                    </div>
                    <div x-show="sidebarCollapsed" class="w-6 h-px mx-auto my-2" style="background-color: var(--border-subtle);"></div>
                    <a 
                        href="{{ route('home') }}" 
                        :title="sidebarCollapsed ? 'Xem Trang Khách Hàng' : ''"
                        class="admin-nav-item group flex items-center gap-3 rounded-2xl"
                        :class="sidebarCollapsed ? 'justify-center p-3' : 'px-3.5 py-2.5'"
                    >
                        <div class="admin-nav-icon w-7 h-7 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </div>
                        <span x-show="!sidebarCollapsed" x-transition class="truncate">Xem Trang Khách Hàng</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Admin Profile Footer Widget Card -->
        <div class="p-3 border-t" style="border-color: var(--border-subtle); background: var(--sidebar-footer-bg);">
            <div 
                class="rounded-2xl p-2.5 flex items-center transition-all duration-300 shadow-sm"
                style="background: var(--item-bg); border: 1px solid var(--border-subtle);"
                :class="sidebarCollapsed ? 'flex-col gap-2 justify-center' : 'justify-between'"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div 
                        class="w-10 h-10 rounded-xl font-bold flex items-center justify-center text-sm shadow-sm shrink-0" 
                        style="background: linear-gradient(135deg, rgba(245,158,11,0.85) 0%, rgba(217,119,6,0.95) 100%); color: #0F172A;"
                    >
                        AD
                    </div>
                    <div x-show="!sidebarCollapsed" x-transition class="text-xs min-w-0 truncate">
                        <span class="font-semibold block truncate" style="color: var(--text-primary);">{{ Auth::user()->name ?? 'Administrator' }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium inline-flex items-center gap-1.5 mt-0.5" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.25); color: #6EE7B7;">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Super Admin
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" :class="sidebarCollapsed ? 'w-full flex justify-center' : ''">
                    @csrf
                    <button 
                        type="submit" 
                        title="Đăng xuất" 
                        class="w-8 h-8 rounded-xl bg-transparent hover:bg-[#EF5350]/15 flex items-center justify-center transition-all duration-200 shrink-0"
                        style="color: var(--text-muted);"
                        onmouseover="this.style.color='#EF5350'"
                        onmouseout="this.style.color='var(--text-muted)'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Panel (Bright White Canvas) -->
    <div 
        class="min-h-screen flex flex-col min-w-0 bg-[#FAF9F6] transition-all duration-300 ease-in-out"
        :class="sidebarCollapsed ? 'pl-20' : 'pl-64'"
    >
        <!-- Admin Topbar (Bright Surface) -->
        <header class="h-20 bg-white/95 backdrop-blur-xl border-b border-black/10 px-4 sm:px-6 lg:px-8 flex items-center justify-between sticky top-0 z-20 shadow-sm">
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
                    @if (isset($breadcrumb))
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5 leading-none mb-1">
                            {!! $breadcrumb !!}
                        </div>
                    @endif
                    <h1 class="font-display font-black text-xl text-black truncate">{{ $header ?? 'Bảng Điều Khiển Quản Trị' }}</h1>
                    <p class="text-xs text-gray-500 mt-0.5 truncate hidden sm:block">{{ $subtitle ?? 'Quản trị người dùng, doanh thu, suất chiếu và kiểm soát lượt đặt vé' }}</p>
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

        <main class="p-4 sm:p-6 lg:p-7 flex-1 min-w-0 w-full">
            @include('admin.partials.flash')
            {{ $slot }}
        </main>
    </div>

</body>
</html>
