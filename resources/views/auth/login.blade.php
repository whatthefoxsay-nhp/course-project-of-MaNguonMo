<x-guest-layout :title="'Đăng Nhập Tài Khoản'">
    
    <!-- Header Title -->
    <div class="text-center mb-7">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#C08497]/15 text-[#8F435B] border border-[#C08497]/30 text-[10px] font-black uppercase tracking-wider mb-2">
            <span class="w-1.5 h-1.5 rounded-full bg-[#C08497] animate-pulse"></span>
            Cổng Thành Viên &amp; Quản Trị
        </span>
        <h1 class="font-serif text-3xl font-black text-slate-900 tracking-tight">Đăng Nhập</h1>
        <p class="text-xs text-slate-500 mt-1 font-medium">Chào mừng bạn quay trở lại với nền tảng vé TicketBox</p>
    </div>

    <!-- Session Status / Alert -->
    @if (session('status'))
        <div class="mb-5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Global Error Messages -->
    @if ($errors->any())
        <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-crimson text-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-crimson shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Thông tin đăng nhập chưa chính xác:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="text-[11px] leading-relaxed pl-5 list-disc">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4 sm:space-y-5" x-data="{ 
        emailVal: @js(old('email', '')),
        showPass: false,
        fillAccount(email, pass) {
            this.emailVal = email;
            const passEl = document.getElementById('password');
            if (passEl) {
                passEl.value = pass;
                passEl.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    }">
        @csrf

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
                    x-model="emailVal"
                    value="{{ old('email') }}" 
                    required 
                    autofocus 
                    placeholder="name@example.com"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-4 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
            </div>
        </div>

        <!-- Password -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Mật Khẩu <span class="text-rose-600 font-black">*</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-[#C08497] hover:text-[#9A5369] transition-colors font-bold">
                        Quên mật khẩu?
                    </a>
                @endif
            </div>

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
                    placeholder="••••••••"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-11 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >

                <button 
                    type="button" 
                    @click="showPass = !showPass" 
                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-[#C08497] transition-colors"
                >
                    <svg x-show="!showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    <svg x-show="showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                </button>
            </div>
        </div>

        <!-- Remember Me Checkbox -->
        <div class="flex items-center justify-between pt-0.5">
            <label for="remember_me" class="inline-flex items-center cursor-pointer select-none">
                <input 
                    id="remember_me" 
                    type="checkbox" 
                    name="remember"
                    class="w-4 h-4 rounded bg-[#FAF9F6] border-slate-300 text-[#C08497] focus:ring-[#C08497] focus:ring-offset-0 transition-colors"
                >
                <span class="ml-2 text-xs text-slate-600 font-semibold">Ghi nhớ phiên đăng nhập</span>
            </label>
        </div>

        <!-- Submit Button (#C08497 Rose Taupe with subtle hover shadow & micro-animation) -->
        <button 
            type="submit" 
            class="w-full py-3.5 sm:py-4 px-6 rounded-2xl bg-[#C08497] hover:bg-[#A96B7E] text-white text-sm sm:text-base font-black shadow-xl shadow-[#C08497]/30 hover:shadow-[#C08497]/50 hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2.5 transition-all duration-200 cursor-pointer group"
        >
            <span>Đăng Nhập Ngay</span>
            <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center group-hover:translate-x-1 transition-transform">
                <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </span>
        </button>

        <!-- Divider: Hoặc -->
        <div class="relative my-4">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-slate-200"></div>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-[#FAF9F6] px-3 text-slate-400 font-bold tracking-wider">Hoặc tiếp tục với</span>
            </div>
        </div>

        <!-- Google OAuth Button -->
        <a 
            href="{{ route('auth.google.redirect') }}" 
            class="w-full py-3 px-4 rounded-2xl bg-white hover:bg-slate-50 border border-slate-200/90 text-slate-700 hover:text-slate-900 text-xs sm:text-sm font-bold shadow-2xs hover:shadow-sm hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-3 transition-all duration-200 group cursor-pointer"
        >
            <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Tiếp tục với Google</span>
        </a>

        <!-- Quick Demo One-Click Login Box: chỉ hiện ở môi trường local -->
        @if (app()->environment('local'))
        <div class="pt-5 border-t border-slate-100">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block text-center mb-2.5">
                Đăng nhập nhanh tài khoản mẫu:
            </span>
            <div class="grid grid-cols-2 gap-2.5">
                <!-- Admin Demo Quick Button (Antique Gold) -->
                <button 
                    type="button" 
                    @click="fillAccount('admin@ticketbox.vn', 'password')"
                    class="p-3.5 rounded-2xl bg-gradient-to-br from-amber-50/90 via-orange-50/40 to-amber-100/60 hover:from-amber-100 hover:to-orange-100/80 border border-amber-200/80 hover:border-amber-300 text-left transition-all shadow-2xs group cursor-pointer"
                >
                    <div class="text-xs text-slate-900 font-black flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#AA8820] shadow-xs"></span>
                            <span class="text-[#976D00] font-black">Admin</span>
                        </span>
                        <span class="text-[10px] text-amber-600 font-black group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                    </div>
                    <div class="text-[11px] text-slate-600 font-mono font-semibold truncate mt-1">admin@ticketbox.vn</div>
                </button>

                <!-- User Demo Quick Button (Forest Sage) -->
                <button 
                    type="button" 
                    @click="fillAccount('user@ticketbox.vn', 'password')"
                    class="p-3.5 rounded-2xl bg-gradient-to-br from-emerald-50/90 via-teal-50/40 to-emerald-100/60 hover:from-emerald-100 hover:to-teal-100/80 border border-emerald-200/80 hover:border-emerald-300 text-left transition-all shadow-2xs group cursor-pointer"
                >
                    <div class="text-xs text-slate-900 font-black flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-[#3A5A40] shadow-xs"></span>
                            <span class="text-[#2A402E] font-black">Khách hàng</span>
                        </span>
                        <span class="text-[10px] text-emerald-600 font-black group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                    </div>
                    <div class="text-[11px] text-slate-600 font-mono font-semibold truncate mt-1">user@ticketbox.vn</div>
                </button>
            </div>
        </div>
        @endif

        <!-- Register Link -->
        <div class="text-center pt-2 text-xs text-slate-500 font-medium">
            Chưa có tài khoản? 
            <a href="{{ route('register') }}" class="text-[#C08497] font-black hover:text-[#9A5369] underline underline-offset-4 transition-colors">
                Đăng ký tài khoản mới &rarr;
            </a>
        </div>

    </form>

</x-guest-layout>
