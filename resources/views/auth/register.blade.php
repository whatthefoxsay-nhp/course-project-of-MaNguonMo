<x-guest-layout :title="'Đăng Ký Tài Khoản Mới'">
    
    <!-- Header Title -->
    <div class="text-center mb-7">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#C08497]/15 text-[#8F435B] border border-[#C08497]/30 text-[10px] font-black uppercase tracking-wider mb-2">
            <span class="w-1.5 h-1.5 rounded-full bg-[#C08497] animate-pulse"></span>
            Thành Viên Mới
        </span>
        <h1 class="font-serif text-3xl font-black text-slate-900 tracking-tight">Tạo Tài Khoản</h1>
        <p class="text-xs text-slate-500 mt-1 font-medium">Đăng ký để nhận voucher ưu đãi và quản lý vé thuận tiện</p>
    </div>

    <!-- Global Error Messages -->
    @if ($errors->any())
        <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-crimson text-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-crimson shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Vui lòng kiểm tra lại thông tin:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="text-[11px] leading-relaxed pl-5 list-disc">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ showPass: false, showConfirmPass: false }">
        @csrf

        <!-- Full Name -->
        <div class="space-y-1.5">
            <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Họ Và Tên <span class="text-rose-600 font-black">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </span>
                <input 
                    id="name" 
                    type="text" 
                    name="name" 
                    value="{{ old('name') }}" 
                    required 
                    autofocus 
                    placeholder="Nguyễn Văn A"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-4 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
            </div>
        </div>

        <!-- Email Address -->
        <div class="space-y-1.5">
            <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Địa Chỉ Email <span class="text-rose-600 font-black">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                    </svg>
                </span>
                <input 
                    id="email" 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    placeholder="name@example.com"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-4 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
            </div>
        </div>

        <!-- Phone Number (Optional) -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Số Điện Thoại
                </label>
                <span class="text-[10px] text-slate-400 font-medium">Nhận vé &amp; thông báo</span>
            </div>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </span>
                <input 
                    id="phone" 
                    type="tel" 
                    name="phone" 
                    value="{{ old('phone') }}" 
                    placeholder="0912 345 678"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-4 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
            </div>
        </div>

        <!-- Password -->
        <div class="space-y-1.5">
            <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Mật Khẩu <span class="text-rose-600 font-black">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                <input 
                    id="password" 
                    :type="showPass ? 'text' : 'password'" 
                    name="password" 
                    required 
                    placeholder="Tối thiểu 8 ký tự"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-11 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
                <button 
                    type="button" 
                    @click="showPass = !showPass" 
                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-[#C08497] transition-colors"
                >
                    <svg x-show="!showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showPass" x-cloak class="w-4 h-4 text-[#C08497]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Confirm Password -->
        <div class="space-y-1.5">
            <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Xác Nhận Mật Khẩu <span class="text-rose-600 font-black">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </span>
                <input 
                    id="password_confirmation" 
                    :type="showConfirmPass ? 'text' : 'password'" 
                    name="password_confirmation" 
                    required 
                    placeholder="Nhập lại mật khẩu"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-11 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
                <button 
                    type="button" 
                    @click="showConfirmPass = !showConfirmPass" 
                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-[#C08497] transition-colors"
                >
                    <svg x-show="!showConfirmPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showConfirmPass" x-cloak class="w-4 h-4 text-[#C08497]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Submit Button (#C08497 Rose Taupe) -->
        <button 
            type="submit" 
            class="w-full py-3.5 sm:py-4 px-6 rounded-2xl bg-[#C08497] hover:bg-[#A96B7E] text-white text-sm sm:text-base font-black shadow-xl shadow-[#C08497]/30 hover:shadow-[#C08497]/50 hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2.5 transition-all duration-200 cursor-pointer group mt-5"
        >
            <span>Đăng Ký Tài Khoản</span>
            <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center group-hover:translate-x-1 transition-transform">
                <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </span>
        </button>

        <!-- Login Link -->
        <div class="text-center pt-3 text-xs text-slate-500 font-medium border-t border-slate-100 mt-5">
            Đã có tài khoản? 
            <a href="{{ route('login') }}" class="text-[#C08497] font-black hover:text-[#9A5369] underline underline-offset-4 transition-colors">
                Đăng nhập ngay &rarr;
            </a>
        </div>

    </form>

</x-guest-layout>
