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

    <title>{{ $title ?? ($header ?? 'Quản Trị Hệ Thống') }} - TicketBox Management</title>

    <!-- Google Fonts with Vietnamese diacritics support -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ==========================================================================
           Admin Sidebar & Canvas System (Ink & Antique Gold - High Contrast & Vivid)
           ========================================================================== */
        :root {
            --sidebar-ink: #141210;
            --sidebar-header: #0E0D0C;
            --sidebar-footer: #0E0D0C;
            --sidebar-text: #F5F5F4;              /* Crisp Stone-100, highly visible on dark ink */
            --sidebar-text-hover: #FFFFFF;        /* Pure white on hover */
            --sidebar-gold: #D4AF37;
            --sidebar-gold-soft: rgba(212, 175, 55, 0.22);
            --sidebar-border: rgba(255, 255, 255, 0.1);
            --canvas-bg: #FAF9F6;
            --card-bg: #FFFFFF;
            --toolbar-bg: #F5F1E8;
            --text-main: #1C1917;
            --text-secondary: #57534E;
        }

        .admin-sidebar {
            background-color: var(--sidebar-ink) !important;
            border-color: var(--sidebar-border) !important;
            color: var(--sidebar-text);
        }

        .admin-sidebar-nav {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.25) transparent;
        }

        .admin-sidebar-nav::-webkit-scrollbar {
            width: 4px !important;
        }

        .admin-sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25) !important;
            border-radius: 9999px !important;
        }

        /* Nav item base state: structured container, high-contrast text */
        .admin-nav-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-left: 3.5px solid transparent;
            color: var(--sidebar-text);
            font-weight: 500;
            transition: all 180ms ease-out;
        }

        .admin-nav-item:hover {
            background: rgba(255, 255, 255, 0.09);
            border-color: rgba(212, 175, 55, 0.3);
            border-left-color: rgba(212, 175, 55, 0.6);
            color: var(--sidebar-text-hover);
        }

        .admin-nav-item:hover .admin-nav-icon-box {
            background: rgba(212, 175, 55, 0.25);
            border-color: rgba(212, 175, 55, 0.5);
            color: #F5D061;
        }

        /* Active Menu Item: Left Gold Bar, Gold Gradient Pill, Gold Icon Box */
        .admin-nav-item.is-active {
            background: linear-gradient(90deg, rgba(212, 175, 55, 0.25) 0%, rgba(212, 175, 55, 0.08) 100%) !important;
            border-color: rgba(212, 175, 55, 0.4) !important;
            border-left: 4px solid #D4AF37 !important;
            color: #FFFFFF !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
        }

        .admin-nav-item.is-active .admin-nav-icon-box {
            background: #D4AF37 !important;
            border-color: #D4AF37 !important;
            color: #141210 !important;
            box-shadow: 0 2px 8px rgba(212, 175, 55, 0.45) !important;
        }

        /* Dedicated Icon Box for crisp visual anchors */
        .admin-nav-icon-box {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #F5D061;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 180ms ease-out;
        }

        .admin-group-label {
            color: #F5D061;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Focus Visible for Accessibility */
        .admin-nav-item:focus-visible,
        .admin-sidebar button:focus-visible,
        .admin-sidebar a:focus-visible {
            outline: 2px solid var(--sidebar-gold) !important;
            outline-offset: 2px !important;
        }
    </style>
</head>
<body 
    x-data="{ 
        sidebarCollapsed: localStorage.getItem('admin_sidebar_collapsed') === 'true',
        mobileDrawerOpen: false,
        toggleSidebar() { 
            this.sidebarCollapsed = !this.sidebarCollapsed; 
            localStorage.setItem('admin_sidebar_collapsed', this.sidebarCollapsed); 
        }
    }" 
    class="min-h-screen bg-[#FAF9F6] text-[#1C1917] font-sans antialiased selection:bg-[#C08497] selection:text-white"
>

    <!-- Mobile Drawer Overlay -->
    <div 
        x-show="mobileDrawerOpen" 
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileDrawerOpen = false"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden"
        style="display: none;"
    ></div>

    <!-- Editorial Luxury Collapsible Sidebar (Ink #141210 + Gold #D4AF37) -->
    <aside 
        class="admin-sidebar fixed inset-y-0 left-0 border-r z-50 lg:z-30 flex flex-col justify-between shadow-2xl transition-all duration-300 ease-out"
        :class="[
            sidebarCollapsed ? 'lg:w-20' : 'lg:w-72',
            mobileDrawerOpen ? 'translate-x-0 w-72' : '-translate-x-full lg:translate-x-0'
        ]"
    >
        <div class="flex-1 min-h-0 flex flex-col">
            <!-- Admin Brand & Collapse Toggle -->
            <div 
                class="h-20 flex items-center border-b border-white/[0.06] px-4 transition-all duration-300 shrink-0"
                style="background: var(--sidebar-header);"
                :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : 'justify-between'"
            >
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 min-w-0 group">
                    <div 
                        class="w-10 h-10 rounded-xl flex items-center justify-center font-display font-black text-xl shadow-md shrink-0 bg-[#D4AF37] text-[#141210] group-hover:scale-105 transition-transform"
                        :title="sidebarCollapsed ? 'TicketAdmin' : ''"
                    >
                        T
                    </div>
                    <div 
                        x-show="!sidebarCollapsed" 
                        class="min-w-0 truncate"
                    >
                        <span class="font-display font-semibold text-lg text-white block leading-none tracking-tight">Ticket<span class="text-[#D4AF37]">Admin</span></span>
                        <span class="text-[10px] uppercase tracking-[0.08em] font-medium text-[#D6D3D1] mt-1 block truncate">Hệ Thống Quản Trị</span>
                    </div>
                </a>

                <!-- Desktop Sidebar internal toggle button -->
                <button 
                    x-show="!sidebarCollapsed" 
                    @click="toggleSidebar()" 
                    type="button" 
                    class="hidden lg:flex w-8 h-8 rounded-lg bg-transparent hover:bg-white/[0.08] text-[#D6D3D1] hover:text-white items-center justify-center transition-colors shrink-0" 
                    title="Thu gọn sidebar"
                    aria-label="Thu gọn sidebar"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Mobile Close Drawer Button -->
                <button 
                    @click="mobileDrawerOpen = false" 
                    type="button" 
                    class="lg:hidden w-8 h-8 rounded-lg bg-transparent hover:bg-white/[0.08] text-[#D6D3D1] hover:text-white flex items-center justify-center transition-colors shrink-0" 
                    title="Đóng menu"
                    aria-label="Đóng menu"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="admin-sidebar-nav flex-1 p-3 space-y-6 text-sm overflow-y-auto">
                
                <!-- Group 1: Báo cáo & Thống kê -->
                <div>
                    <div x-show="!sidebarCollapsed" class="px-3 pb-2.5 admin-group-label truncate">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#D4AF37] shrink-0"></span>
                        <span>Báo Cáo &amp; Thống Kê</span>
                    </div>
                    <div x-show="sidebarCollapsed" class="hidden lg:block w-8 h-px mx-auto my-2.5 bg-white/[0.12]"></div>
                    <div class="space-y-1.5">
                        <a 
                            href="{{ route('admin.dashboard') }}" 
                            :title="sidebarCollapsed ? 'Tổng Quan (KPIs)' : ''"
                            class="admin-nav-item h-12 flex items-center gap-3 rounded-xl {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0 px-3' : 'px-3'"
                        >
                            <div class="admin-nav-icon-box">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.25 2.25L15 6" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap font-semibold text-[13.5px]">Tổng Quan (KPIs)</span>
                        </a>

                        <a 
                            href="{{ route('admin.reports.index') }}" 
                            :title="sidebarCollapsed ? 'Báo Cáo Doanh Thu' : ''"
                            class="admin-nav-item h-12 flex items-center gap-3 rounded-xl {{ request()->routeIs('admin.reports.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0 px-3' : 'px-3'"
                        >
                            <div class="admin-nav-icon-box">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap font-medium text-[13.5px]">Báo Cáo Doanh Thu</span>
                        </a>
                    </div>
                </div>

                <!-- Group 2: Quản trị nội dung -->
                <div>
                    <div x-show="!sidebarCollapsed" class="px-3 pb-2.5 admin-group-label truncate">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#D4AF37] shrink-0"></span>
                        <span>Quản Trị Nội Dung</span>
                    </div>
                    <div x-show="sidebarCollapsed" class="hidden lg:block w-8 h-px mx-auto my-2.5 bg-white/[0.12]"></div>
                    <div class="space-y-1.5">
                        <a 
                            href="{{ route('admin.categories.index') }}" 
                            :title="sidebarCollapsed ? 'Danh Mục Sự Kiện' : ''"
                            class="admin-nav-item h-12 flex items-center gap-3 rounded-xl {{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0 px-3' : 'px-3'"
                        >
                            <div class="admin-nav-icon-box">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.386a11.082 11.082 0 004.81-4.81c.486-.827.313-1.908-.386-2.607L10.759 3.659A2.25 2.25 0 009.568 3z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap font-medium text-[13.5px]">Danh Mục Sự Kiện</span>
                        </a>

                        <a 
                            href="{{ route('admin.events.index') }}" 
                            :title="sidebarCollapsed ? 'Quản Lý Sự Kiện' : ''"
                            class="admin-nav-item h-12 flex items-center gap-3 rounded-xl {{ request()->routeIs('admin.events.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0 px-3' : 'px-3'"
                        >
                            <div class="admin-nav-icon-box">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467l2.31-.66a.225.225 0 00.162-.216V7.262m0 0l-10.5 3m0 0v5.25a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467l2.31-.66a.225.225 0 00.162-.216V10.262m0 0l10.5-3" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap font-medium text-[13.5px]">Quản Lý Sự Kiện</span>
                        </a>

                        <a 
                            href="{{ route('admin.showtimes.index') }}" 
                            :title="sidebarCollapsed ? 'Lịch Diễn & Suất Chiếu' : ''"
                            class="admin-nav-item h-12 flex items-center gap-3 rounded-xl {{ request()->routeIs('admin.showtimes.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0 px-3' : 'px-3'"
                        >
                            <div class="admin-nav-icon-box">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap font-medium text-[13.5px]">Lịch Diễn &amp; Suất Chiếu</span>
                        </a>

                        <a 
                            href="{{ route('admin.rooms.index') }}" 
                            :title="sidebarCollapsed ? 'Khán Phòng & Sơ Đồ Ghế' : ''"
                            class="admin-nav-item h-12 flex items-center gap-3 rounded-xl {{ request()->routeIs('admin.rooms.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0 px-3' : 'px-3'"
                        >
                            <div class="admin-nav-icon-box">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap font-medium text-[13.5px]">Khán Phòng &amp; Sơ Đồ Ghế</span>
                        </a>
                    </div>
                </div>

                <!-- Group 3: Bán vé, Khách hàng & Mã giảm giá -->
                <div>
                    <div x-show="!sidebarCollapsed" class="px-3 pb-2.5 admin-group-label truncate">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#D4AF37] shrink-0"></span>
                        <span>Bán Vé &amp; Khách Hàng</span>
                    </div>
                    <div x-show="sidebarCollapsed" class="hidden lg:block w-8 h-px mx-auto my-2.5 bg-white/[0.12]"></div>
                    <div class="space-y-1.5">
                        <a 
                            href="{{ route('admin.bookings.index') }}" 
                            :title="sidebarCollapsed ? 'Danh Sách Đơn Đặt Vé' : ''"
                            class="admin-nav-item h-12 flex items-center gap-3 rounded-xl {{ request()->routeIs('admin.bookings.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0 px-3' : 'px-3'"
                        >
                            <div class="admin-nav-icon-box">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap font-medium text-[13.5px]">Danh Sách Đơn Đặt Vé</span>
                        </a>

                        <a 
                            href="{{ route('admin.discounts.index') }}" 
                            :title="sidebarCollapsed ? 'Quản Lý Mã Giảm' : ''"
                            class="admin-nav-item h-12 flex items-center gap-3 rounded-xl {{ request()->routeIs('admin.discounts.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0 px-3' : 'px-3'"
                        >
                            <div class="admin-nav-icon-box">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6H2.25m0 0H3m0 0h.375m0 0h.375m0 0H4.5m15 0h.375m0 0h.375m0 0H21m0 0h-.75m-.75 0h-.375m0 0h-.375m0 0H18M3.75 9v.75a.75.75 0 01-.75.75H2.25m19.5 0H21a.75.75 0 01-.75-.75V9m-16.5 4.5v.75a.75.75 0 01-.75.75H2.25m19.5 0H21a.75.75 0 01-.75-.75v-.75M3.75 18v.75a.75.75 0 01-.75.75H2.25m19.5 0H21a.75.75 0 01-.75-.75V18" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap font-medium text-[13.5px]">Quản Lý Mã Giảm Giá</span>
                        </a>

                        <a 
                            href="{{ route('admin.users.index') }}" 
                            :title="sidebarCollapsed ? 'Quản Lý Tài Khoản' : ''"
                            class="admin-nav-item h-12 flex items-center gap-3 rounded-xl {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0 px-3' : 'px-3'"
                        >
                            <div class="admin-nav-icon-box">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                            </div>
                            <span x-show="!sidebarCollapsed" class="whitespace-nowrap font-medium text-[13.5px]">Quản Lý Người Dùng</span>
                        </a>
                    </div>
                </div>

                <!-- Group 4: Hệ Thống -->
                <div>
                    <div x-show="!sidebarCollapsed" class="px-3 pb-2.5 admin-group-label truncate">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#D4AF37] shrink-0"></span>
                        <span>Liên Kết Ngoài</span>
                    </div>
                    <div x-show="sidebarCollapsed" class="hidden lg:block w-8 h-px mx-auto my-2.5 bg-white/[0.12]"></div>
                    <div class="space-y-1.5">
                        <a 
                            href="{{ route('home') }}" 
                            target="_blank"
                            :title="sidebarCollapsed ? 'Xem Trang Khách Hàng' : ''"
                            class="admin-nav-item h-12 flex items-center gap-3 rounded-xl group/link"
                            :class="sidebarCollapsed ? 'lg:justify-center lg:px-0 px-3' : 'px-3'"
                        >
                            <div class="admin-nav-icon-box">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </div>
                            <div x-show="!sidebarCollapsed" class="flex items-center justify-between flex-1 min-w-0">
                                <span class="whitespace-nowrap font-medium text-[13.5px]">Xem Trang Khách Hàng</span>
                                <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-white/[0.08] text-amber-300">Live ↗</span>
                            </div>
                        </a>
                    </div>
                </div>

            </nav>
        </div>

        <!-- Admin Profile Footer Widget -->
        <div class="p-3 border-t border-white/[0.1] shrink-0" style="background: var(--sidebar-footer);">
            <div 
                class="rounded-xl p-2.5 flex items-center bg-white/[0.03] border border-white/[0.06] transition-all duration-200"
                :class="sidebarCollapsed ? 'lg:flex-col lg:gap-2 lg:justify-center' : 'justify-between'"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div 
                        class="w-9 h-9 rounded-xl font-display font-bold flex items-center justify-center text-xs shrink-0 bg-[#D4AF37] text-[#141210] shadow-md shadow-[#D4AF37]/30"
                    >
                        AD
                    </div>
                    <div x-show="!sidebarCollapsed" class="text-xs min-w-0 truncate">
                        <span class="font-bold text-white block truncate leading-tight">{{ Auth::user()->name ?? 'Quản trị viên' }}</span>
                        <span class="inline-flex items-center gap-1.5 mt-0.5 text-[11px] text-stone-300 font-medium">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Quản trị cấp cao
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" :class="sidebarCollapsed ? 'lg:w-full lg:flex lg:justify-center' : ''">
                    @csrf
                    <button 
                        type="submit" 
                        title="Đăng xuất" 
                        aria-label="Đăng xuất"
                        class="w-8 h-8 rounded-lg bg-white/[0.05] hover:bg-rose-500/20 text-stone-300 hover:text-rose-300 border border-white/[0.08] hover:border-rose-500/40 flex items-center justify-center transition-colors shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Panel (Warm Canvas #FAF9F6) -->
    <div 
        class="min-h-screen flex flex-col min-w-0 bg-[#FAF9F6] transition-all duration-300 ease-out"
        :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'"
    >
        <!-- Admin Topbar (Crisp Luxury Header) -->
        <header class="h-20 bg-white/95 backdrop-blur-xl border-b border-stone-200/90 px-4 sm:px-6 lg:px-8 flex items-center justify-between sticky top-0 z-20 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)]">
            <div class="flex items-center gap-3.5 min-w-0">
                <!-- Mobile Drawer Toggle Button -->
                <button 
                    @click="mobileDrawerOpen = true" 
                    type="button" 
                    class="lg:hidden p-2 rounded-xl border border-stone-200 hover:bg-stone-100 text-stone-700 hover:text-stone-900 transition-colors flex items-center justify-center shrink-0" 
                    aria-label="Mở menu quản trị"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Desktop Sidebar Toggle Button -->
                <button 
                    @click="toggleSidebar()" 
                    type="button" 
                    class="hidden lg:flex p-2 rounded-xl border border-stone-200 hover:bg-stone-100 text-stone-700 hover:text-stone-900 transition-colors items-center justify-center shrink-0 shadow-sm" 
                    :title="sidebarCollapsed ? 'Mở rộng sidebar' : 'Thu gọn sidebar'"
                    aria-label="Chuyển đổi thu gọn sidebar"
                >
                    <svg 
                        class="w-4 h-4 transition-transform duration-300" 
                        :class="sidebarCollapsed ? 'rotate-180 text-amber-600' : 'text-stone-600'" 
                        fill="none" 
                        viewBox="0 0 24 24" 
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>

                <!-- Page Header & Hierarchy -->
                <div class="min-w-0">
                    @if (isset($breadcrumb))
                        <div class="text-[11px] font-bold text-stone-600 uppercase tracking-[0.08em] flex items-center gap-1.5 leading-none mb-1 truncate">
                            {!! $breadcrumb !!}
                        </div>
                    @endif
                    <h1 class="font-serif font-bold text-xl sm:text-2xl text-stone-900 tracking-tight truncate leading-tight">
                        {{ $header ?? 'Bảng Điều Khiển Quản Trị' }}
                    </h1>
                    @if(isset($subtitle))
                        <p class="text-xs text-stone-600 mt-0.5 truncate hidden sm:block font-medium">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            <!-- Topbar Right Actions -->
            <div class="flex items-center gap-3 shrink-0">
                @if (isset($actions))
                    {!! $actions !!}
                @endif
                <span class="px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-bold inline-flex items-center gap-2 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                    <span class="hidden sm:inline">Hệ thống bình thường</span>
                    <span class="sm:hidden">Online</span>
                </span>
            </div>
        </header>

        <main class="p-4 sm:p-6 lg:p-8 flex-1 min-w-0 w-full max-w-[1600px] mx-auto">
            @include('admin.partials.flash')
            {{ $slot }}
        </main>
    </div>

</body>
</html>
