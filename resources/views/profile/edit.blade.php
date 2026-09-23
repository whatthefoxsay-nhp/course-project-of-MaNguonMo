<x-site-layout :title="'Hồ Sơ & Cài Đặt Tài Khoản - TicketBox'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        
        <!-- Header Banner with Breadcrumb & Title -->
        <div class="mb-8 sm:mb-10">
            <div class="flex flex-wrap items-center gap-2 mb-3 text-xs">
                <a href="{{ route('home') }}" class="text-gray-500 hover:text-black font-semibold transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Trang chủ</span>
                </a>
                <span class="text-gray-300">/</span>
                <span class="text-gold-dark font-bold">Hồ sơ cá nhân &amp; Cài đặt</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gold-antique/15 text-gold-dark border border-gold-antique/30 text-[10px] font-black uppercase tracking-wider mb-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-gold-antique animate-pulse"></span>
                        Trung Tâm Tài Khoản Thành Viên
                    </span>
                    <h1 class="font-serif text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">Hồ Sơ Cá Nhân &amp; Cài Đặt</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1.5 font-medium max-w-2xl">
                        Quản lý thông tin định danh, tùy chỉnh mật khẩu và kiểm soát quyền bảo mật tài khoản TicketBox.
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('bookings.history') }}" class="btn-ghost-light text-xs font-bold px-4 py-2.5 rounded-2xl flex items-center gap-2 shadow-2xs">
                        <svg class="w-4 h-4 text-sage-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                        <span>Vé của tôi</span>
                    </a>
                    <a href="{{ route('cart.index') }}" class="btn-rose text-xs font-bold px-4 py-2.5 rounded-2xl flex items-center gap-2 shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span>Giỏ vé</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Two-Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
            
            <!-- Left Column: Member Card & Quick Navigation (4 cols) -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Member Profile Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-black/10 shadow-[0_15px_35px_-10px_rgba(0,0,0,0.06)] relative overflow-hidden">
                    <div class="absolute -top-12 -right-12 w-32 h-32 rounded-full bg-gold-antique/10 blur-2xl pointer-events-none"></div>
                    <div class="absolute -bottom-10 -left-10 w-28 h-28 rounded-full bg-rose-taupe/10 blur-2xl pointer-events-none"></div>

                    <div class="relative text-center">
                        <!-- Big Avatar with luxury gradient border -->
                        <div class="w-24 h-24 rounded-3xl bg-gradient-to-tr from-gold-antique via-rose-taupe to-sage-forest p-[2.5px] shadow-lg mx-auto">
                            <div class="w-full h-full rounded-[21px] bg-[#FAF9F6] flex items-center justify-center font-display font-black text-3xl text-black">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        </div>

                        <h2 class="mt-4 font-display font-black text-lg sm:text-xl text-slate-900 tracking-tight">
                            {{ $user->name }}
                        </h2>
                        <p class="text-xs text-gray-500 font-medium truncate mt-0.5">{{ $user->email }}</p>

                        <!-- Badges -->
                        <div class="flex flex-wrap items-center justify-center gap-1.5 mt-3">
                            <span class="badge-gold px-3 py-0.5 rounded-full text-[10px] font-black inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-gold-antique animate-pulse"></span>
                                VIP MEMBER
                            </span>
                            @if ($user->hasRole('admin'))
                                <span class="badge-rose px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase">
                                    Quản Trị Viên
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 pt-5 border-t border-black/10 space-y-3 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 font-medium">Trạng thái:</span>
                            <span class="inline-flex items-center gap-1.5 text-sage-forest font-bold">
                                <span class="w-2 h-2 rounded-full bg-sage-forest animate-pulse"></span>
                                Hoạt động
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 font-medium">Số điện thoại:</span>
                            <span class="text-slate-900 font-semibold font-mono">{{ $user->phone ?? 'Chưa cập nhật' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 font-medium">Ngày gia nhập:</span>
                            <span class="text-slate-900 font-semibold">{{ $user->created_at ? $user->created_at->format('d/m/Y') : '09/2026' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Shortcuts Card -->
                <div class="bg-white rounded-3xl p-5 border border-black/10 shadow-sm space-y-1">
                    <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 block px-3 py-1 mb-1">Truy Cập Nhanh</span>
                    
                    <a href="{{ route('bookings.history') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-slate-800 hover:bg-[#FAF9F6] font-semibold text-xs transition-colors group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-xl bg-sage-50 text-sage-forest flex items-center justify-center group-hover:scale-105 transition-transform">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                </svg>
                            </div>
                            <span>Vé của tôi (E-Tickets)</span>
                        </div>
                        <span class="text-gray-400 group-hover:text-black">&rarr;</span>
                    </a>

                    <a href="{{ route('cart.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-slate-800 hover:bg-[#FAF9F6] font-semibold text-xs transition-colors group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-xl bg-rose-50 text-rose-taupe flex items-center justify-center group-hover:scale-105 transition-transform">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <span>Giỏ vé đang giữ</span>
                        </div>
                        <span class="text-gray-400 group-hover:text-black">&rarr;</span>
                    </a>

                    <a href="{{ route('movies.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-slate-800 hover:bg-[#FAF9F6] font-semibold text-xs transition-colors group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-xl bg-[#FFF8E1] text-gold-dark flex items-center justify-center group-hover:scale-105 transition-transform">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </div>
                            <span>Khám phá show diễn</span>
                        </div>
                        <span class="text-gray-400 group-hover:text-black">&rarr;</span>
                    </a>

                    @if ($user->hasRole('admin'))
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-gold-dark hover:bg-[#FFF8E1] font-bold text-xs transition-colors group">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-xl bg-gold-antique/20 text-gold-dark flex items-center justify-center group-hover:scale-105 transition-transform">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <span>Trang Quản Trị Hệ Thống</span>
                            </div>
                            <span class="text-gold-dark font-bold">&rarr;</span>
                        </a>
                    @endif

                    <div class="pt-2 mt-1 border-t border-black/10">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-crimson hover:bg-red-50 font-bold text-xs transition-colors">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Đăng xuất tài khoản</span>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Settings Forms (8 cols) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Card 1: Thông tin cá nhân -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-black/10 shadow-[0_15px_35px_-10px_rgba(0,0,0,0.06)]">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <!-- Card 2: Mật khẩu & Bảo mật -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-black/10 shadow-[0_15px_35px_-10px_rgba(0,0,0,0.06)]">
                    @include('profile.partials.update-password-form')
                </div>

                <!-- Card 3: Vùng nguy hiểm -->
                <div class="bg-[#FFF9F9] rounded-3xl p-6 sm:p-8 border border-rose-200 shadow-sm">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-site-layout>
