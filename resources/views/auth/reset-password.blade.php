<x-guest-layout :title="'Đặt Lại Mật Khẩu'">
    
    <!-- Header Title -->
    <div class="text-center mb-6">
        <span class="text-[11px] font-black uppercase tracking-wider text-rose-taupe block mb-1">Bảo Mật Tài Khoản</span>
        <h1 class="font-serif text-3xl font-black text-black tracking-tight">Đặt Lại Mật Khẩu</h1>
        <p class="text-xs text-gray-500 mt-1 font-medium">Tạo mật khẩu mới an toàn cho tài khoản của bạn</p>
    </div>

    <!-- Global Error Messages -->
    @if ($errors->any())
        <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-crimson text-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-crimson shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Vui lòng kiểm tra lại:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="text-[11px] leading-relaxed pl-5 list-disc">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="space-y-1.5">
            <label for="email" class="block text-xs font-bold text-gray-800 uppercase tracking-wider">
                Địa Chỉ Email <span class="text-crimson">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                    </svg>
                </span>
                <input 
                    id="email" 
                    type="email" 
                    name="email" 
                    value="{{ old('email', $request->email) }}" 
                    required 
                    autofocus 
                    placeholder="name@example.com"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-black/15 focus:border-black focus:ring-2 focus:ring-black/10 rounded-2xl py-3 pl-11 pr-4 text-sm text-black placeholder-gray-400 transition-all font-medium"
                >
            </div>
        </div>

        <!-- Password -->
        <div class="space-y-1.5">
            <label for="password" class="block text-xs font-bold text-gray-800 uppercase tracking-wider">
                Mật Khẩu Mới <span class="text-crimson">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                <input 
                    id="password" 
                    type="password" 
                    name="password" 
                    required 
                    placeholder="Tối thiểu 8 ký tự"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-black/15 focus:border-black focus:ring-2 focus:ring-black/10 rounded-2xl py-3 pl-11 pr-4 text-sm text-black placeholder-gray-400 transition-all font-medium"
                >
            </div>
        </div>

        <!-- Confirm Password -->
        <div class="space-y-1.5">
            <label for="password_confirmation" class="block text-xs font-bold text-gray-800 uppercase tracking-wider">
                Xác Nhận Mật Khẩu Mới <span class="text-crimson">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </span>
                <input 
                    id="password_confirmation" 
                    type="password" 
                    name="password_confirmation" 
                    required 
                    placeholder="Nhập lại mật khẩu mới"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-black/15 focus:border-black focus:ring-2 focus:ring-black/10 rounded-2xl py-3 pl-11 pr-4 text-sm text-black placeholder-gray-400 transition-all font-medium"
                >
            </div>
        </div>

        <!-- Submit Button -->
        <button 
            type="submit" 
            class="w-full py-3.5 rounded-2xl bg-[#000000] hover:bg-neutral-800 text-white text-sm font-black shadow-md hover:shadow-lg flex items-center justify-center gap-2 transition-all tracking-wide mt-5"
        >
            <span>Cập Nhật Mật Khẩu</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </button>

        <!-- Back to Login -->
        <div class="text-center pt-3 text-xs text-gray-500 font-medium border-t border-black/10 mt-5">
            <a href="{{ route('login') }}" class="text-black font-black hover:text-rose-taupe underline underline-offset-4 transition-colors">
                Quay lại trang đăng nhập &rarr;
            </a>
        </div>
    </form>
</x-guest-layout>
