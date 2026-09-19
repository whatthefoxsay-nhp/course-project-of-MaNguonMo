<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-display font-black text-2xl text-black leading-tight">
                    {{ __('Bảng Điều Khiển Thành Viên') }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Quản lý vé, xem thông tin tài khoản và tích điểm ưu đãi</p>
            </div>

            @if(auth()->user()->hasRole('admin'))
                <a href="{{ route('admin.dashboard') }}" class="btn-dark px-4 py-2 rounded-2xl text-xs font-bold inline-flex items-center gap-2">
                    <span>Đến Trang Quản Trị Admin</span>
                    <span>&rarr;</span>
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- User Profile Welcome Hero Card -->
            <div class="bg-[#F5F5DC] rounded-3xl p-6 sm:p-8 border border-[#D8D8A8] shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl bg-gold-antique text-black font-display font-black text-2xl sm:text-3xl flex items-center justify-center shadow-md">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h3 class="font-display font-black text-xl sm:text-2xl text-black">{{ Auth::user()->name }}</h3>
                            @if(Auth::user()->hasRole('admin'))
                                <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-bold">Admin</span>
                            @else
                                <span class="badge-sage text-[10px] px-2.5 py-0.5 rounded-full font-bold">Thành viên VIP</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-600 mt-1">Email: <strong class="text-black">{{ Auth::user()->email }}</strong> · Tham gia từ {{ Auth::user()->created_at?->format('d/m/Y') }}</p>
                        <div class="flex items-center gap-2 mt-3">
                            <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-bold">120 Điểm Thưởng</span>
                            <span class="badge-rose text-[10px] px-2.5 py-0.5 rounded-full font-bold">Voucher: Giảm 20%</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <a href="{{ route('bookings.history') }}" class="btn-dark px-6 py-3 rounded-2xl text-xs font-black flex items-center justify-center gap-2.5 shadow-sm hover:shadow-md transition-all">
                        <svg class="w-4 h-4 text-gold-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                        <span>Vé Của Tôi</span>
                        <span class="w-2 h-2 rounded-full bg-rose-taupe animate-ping"></span>
                    </a>
                </div>
            </div>

            <!-- Upcoming Event Alert Banner (12h - 24h Notification) -->
            <div class="bg-gradient-to-r from-[#FFF8E1] via-[#FAF9F6] to-[#FDE8EE] rounded-3xl p-5 sm:p-6 border border-[#D8D8A8] shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#000000] text-gold-light flex items-center justify-center shrink-0 shadow-sm relative">
                        <svg class="w-6 h-6 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-rose-taupe border-2 border-white"></span>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="badge-rose px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase">Nhắc nhở: Sắp diễn ra trong 12 tiếng</span>
                            <span class="text-xs text-gray-500 font-bold">Hôm nay · 19:30</span>
                        </div>
                        <h4 class="font-display font-black text-sm sm:text-base text-black">
                            Live Concert Acoustic 2026 — Khán phòng Saigon Grand Hall
                        </h4>
                        <p class="text-xs text-gray-600">
                            Vui lòng mang theo mã QR vé điện tử (E-Ticket) để check-in nhanh tại cổng đón khách VIP.
                        </p>
                    </div>
                </div>

                <a href="{{ route('bookings.history') }}" class="btn-rose px-5 py-2.5 rounded-2xl text-xs font-black shrink-0 flex items-center gap-1.5 shadow-sm">
                    <span>Xem mã QR vé</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>

            <!-- Dashboard Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-3">
                    <div class="w-11 h-11 rounded-2xl bg-[#FAF9F6] border border-black/10 text-black flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                    </div>
                    <h4 class="font-display font-black text-lg text-black">Vé Sự Kiện Sắp Diễn Ra</h4>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Bạn chưa có lịch tham dự sự kiện nào hôm nay. Khám phá các đại nhạc hội concert và hội thảo hot nhất để lên lịch ngay!
                    </p>
                    <a href="{{ route('movies.index') }}" class="text-xs font-bold text-sage-forest hover:underline inline-block pt-1">
                        Xem lịch diễn sự kiện hot &rarr;
                    </a>
                </div>

                <!-- Card 2 -->
                <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-3">
                    <div class="w-11 h-11 rounded-2xl bg-[#FAF9F6] border border-black/10 text-black flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V4a2 2 0 10-2 2h2zm0 13a8 8 0 100-16 8 8 0 000 16z" />
                        </svg>
                    </div>
                    <h4 class="font-display font-black text-lg text-black">Ưu Đãi Hội Viên</h4>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Nhận voucher giảm giá 20% các sự kiện Live Concert, hội thảo và quà tặng check-in tại quầy VIP.
                    </p>
                    <span class="text-xs font-bold text-gold-dark inline-block pt-1">
                        Mã voucher: TICKETBOX2026
                    </span>
                </div>

                <!-- Card 3 -->
                <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-3">
                    <div class="w-11 h-11 rounded-2xl bg-[#FAF9F6] border border-black/10 text-black flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h4 class="font-display font-black text-lg text-black">Cài Đặt Tài Khoản</h4>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Cập nhật mật khẩu, số điện thoại liên hệ và cấu hình thông báo nhận mã vé điện tử SMS/Email.
                    </p>
                    <a href="{{ route('profile.edit') }}" class="text-xs font-bold text-rose-taupe hover:underline inline-block pt-1">
                        Chỉnh sửa hồ sơ &rarr;
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
