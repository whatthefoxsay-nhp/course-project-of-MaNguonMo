<x-guest-layout :title="'Khôi Phục Mật Khẩu'">
    
    <!-- Header Title -->
    <div class="text-center mb-6">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#C08497]/15 text-[#8F435B] border border-[#C08497]/30 text-[10px] font-black uppercase tracking-wider mb-2">
            <span class="w-1.5 h-1.5 rounded-full bg-[#C08497] animate-pulse"></span>
            Hỗ Trợ Tài Khoản
        </span>
        <h1 class="font-serif text-3xl font-black text-slate-900 tracking-tight">Quên Mật Khẩu?</h1>
        <p class="text-xs text-slate-500 mt-1.5 font-medium leading-relaxed">
            Nhập địa chỉ email đăng ký của bạn. Chúng tôi sẽ gửi liên kết khôi phục mật khẩu qua email ngay tức thì.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
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
                    :value="old('email')" 
                    required 
                    autofocus 
                    placeholder="name@example.com"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-4 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Submit Button (#C08497 Rose Taupe) -->
        <button 
            type="submit" 
            class="w-full py-3.5 sm:py-4 px-6 rounded-2xl bg-[#C08497] hover:bg-[#A96B7E] text-white text-sm sm:text-base font-black shadow-xl shadow-[#C08497]/30 hover:shadow-[#C08497]/50 hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2.5 transition-all duration-200 cursor-pointer group mt-5"
        >
            <span>Gửi Liên Kết Đặt Lại Mật Khẩu</span>
            <span class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center group-hover:translate-x-1 transition-transform">
                <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </span>
        </button>

        <!-- Back to Login -->
        <div class="text-center pt-3 text-xs text-slate-500 font-medium border-t border-slate-100 mt-5">
            Nhớ mật khẩu rồi? 
            <a href="{{ route('login') }}" class="text-[#C08497] font-black hover:text-[#9A5369] underline underline-offset-4 transition-colors">
                Quay lại đăng nhập &rarr;
            </a>
        </div>
    </form>
</x-guest-layout>
