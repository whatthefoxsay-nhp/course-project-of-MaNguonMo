<section>
    <div class="flex items-center justify-between pb-5 border-b border-black/10">
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#C08497]/15 text-[#8F435B] border border-[#C08497]/30 text-[10px] font-black uppercase tracking-wider mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-taupe"></span>
                Bảo Mật Tài Khoản
            </span>
            <h2 class="text-xl font-black font-display text-slate-900 tracking-tight">
                Đổi Mật Khẩu Đăng Nhập
            </h2>
            <p class="mt-1 text-xs text-slate-500 font-medium">
                Sử dụng mật khẩu dài và kết hợp chữ số, ký tự đặc biệt để bảo vệ tài khoản tốt nhất.
            </p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-rose-50 border border-rose-200/80 flex items-center justify-center text-rose-taupe shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
        @csrf
        @method('put')

        <!-- Current Password -->
        <div class="space-y-1.5">
            <label for="update_password_current_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Mật Khẩu Hiện Tại <span class="text-rose-taupe font-black">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                <input 
                    id="update_password_current_password" 
                    name="current_password" 
                    :type="showCurrent ? 'text' : 'password'" 
                    autocomplete="current-password" 
                    placeholder="••••••••"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-11 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
                <button 
                    type="button" 
                    @click="showCurrent = !showCurrent" 
                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-rose-taupe transition-colors"
                    title="Hiện/Ẩn mật khẩu"
                >
                    <svg x-show="!showCurrent" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    <svg x-show="showCurrent" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
        </div>

        <!-- New Password -->
        <div class="space-y-1.5">
            <label for="update_password_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Mật Khẩu Mới <span class="text-rose-taupe font-black">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </span>
                <input 
                    id="update_password_password" 
                    name="password" 
                    :type="showNew ? 'text' : 'password'" 
                    autocomplete="new-password" 
                    placeholder="••••••••"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-11 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
                <button 
                    type="button" 
                    @click="showNew = !showNew" 
                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-rose-taupe transition-colors"
                    title="Hiện/Ẩn mật khẩu"
                >
                    <svg x-show="!showNew" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    <svg x-show="showNew" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
        </div>

        <!-- Confirm Password -->
        <div class="space-y-1.5">
            <label for="update_password_password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Xác Nhận Mật Khẩu Mới <span class="text-rose-taupe font-black">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </span>
                <input 
                    id="update_password_password_confirmation" 
                    name="password_confirmation" 
                    :type="showConfirm ? 'text' : 'password'" 
                    autocomplete="new-password" 
                    placeholder="••••••••"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-11 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
                <button 
                    type="button" 
                    @click="showConfirm = !showConfirm" 
                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-rose-taupe transition-colors"
                    title="Hiện/Ẩn mật khẩu"
                >
                    <svg x-show="!showConfirm" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    <svg x-show="showConfirm" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
        </div>

        <!-- Security recommendations hint -->
        <div class="p-4 rounded-2xl bg-[#FAF9F6] border border-black/5 text-xs text-slate-600 space-y-1.5">
            <span class="font-bold text-slate-800 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-sage-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Tiêu chuẩn mật khẩu an toàn:
            </span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px] pt-1">
                <span class="flex items-center gap-1 text-slate-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Tối thiểu 8 ký tự
                </span>
                <span class="flex items-center gap-1 text-slate-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Chứa cả chữ &amp; số
                </span>
                <span class="flex items-center gap-1 text-slate-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Không trùng thông tin cá nhân
                </span>
            </div>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-dark px-6 py-3 rounded-2xl text-xs font-bold inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                <span>Cập Nhật Mật Khẩu</span>
            </button>

            @if (session('status') === 'password-updated')
                <div
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3000)"
                    class="badge-sage px-3.5 py-1.5 rounded-full text-xs font-bold inline-flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5 text-sage-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Mật khẩu đã được cập nhật thành công!</span>
                </div>
            @endif
        </div>
    </form>
</section>
