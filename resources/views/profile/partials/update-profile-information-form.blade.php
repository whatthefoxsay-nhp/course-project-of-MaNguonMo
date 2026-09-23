<section>
    <div class="flex items-center justify-between pb-5 border-b border-black/10">
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gold-antique/15 text-gold-dark border border-gold-antique/30 text-[10px] font-black uppercase tracking-wider mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-gold-antique"></span>
                Hồ Sơ Thành Viên
            </span>
            <h2 class="text-xl font-black font-display text-slate-900 tracking-tight">
                Thông Tin Cá Nhân
            </h2>
            <p class="mt-1 text-xs text-slate-500 font-medium">
                Cập nhật họ tên, địa chỉ email định danh và số điện thoại liên hệ nhận vé.
            </p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-[#FFF8E1] border border-gold-antique/30 flex items-center justify-center text-gold-dark shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </div>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <!-- Full Name -->
        <div class="space-y-1.5">
            <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Họ Và Tên <span class="text-rose-taupe font-black">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </span>
                <input 
                    id="name" 
                    name="name" 
                    type="text" 
                    value="{{ old('name', $user->name) }}" 
                    required 
                    autofocus 
                    autocomplete="name" 
                    placeholder="Nguyễn Văn A"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-4 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
            </div>
            <x-input-error class="mt-1" :messages="$errors->get('name')" />
        </div>

        <!-- Email Address -->
        <div class="space-y-1.5">
            <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Địa Chỉ Email <span class="text-rose-taupe font-black">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                    </svg>
                </span>
                <input 
                    id="email" 
                    name="email" 
                    type="email" 
                    value="{{ old('email', $user->email) }}" 
                    required 
                    autocomplete="username" 
                    placeholder="user@example.com"
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-4 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
            </div>
            <x-input-error class="mt-1" :messages="$errors->get('email')" />
        </div>

        <!-- Phone Number -->
        <div class="space-y-1.5">
            <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Số Điện Thoại Nhận Mã Vé
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </span>
                <input 
                    id="phone" 
                    name="phone" 
                    type="tel" 
                    value="{{ old('phone', $user->phone) }}" 
                    placeholder="0912 345 678" 
                    autocomplete="tel" 
                    class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 pl-11 pr-4 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                >
            </div>
            <x-input-error class="mt-1" :messages="$errors->get('phone')" />
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-800 space-y-2">
                <div class="flex items-center gap-2 font-bold">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Địa chỉ email của bạn chưa được xác thực.</span>
                </div>
                <p>
                    <button form="send-verification" class="font-bold underline text-amber-900 hover:text-black">
                        Nhấn vào đây để gửi lại email kích hoạt tài khoản.
                    </button>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <p class="font-semibold text-emerald-700">
                        Liên kết xác thực mới đã được gửi tới hòm thư của bạn!
                    </p>
                @endif
            </div>
        @endif

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-rose px-6 py-3 rounded-2xl text-xs font-bold inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Lưu Thay Đổi</span>
            </button>

            @if (session('status') === 'profile-updated')
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
                    <span>Đã cập nhật hồ sơ thành công!</span>
                </div>
            @endif
        </div>
    </form>
</section>
