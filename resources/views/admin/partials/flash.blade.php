<div class="space-y-3 mb-6">
    {{-- Success Alert --}}
    @if (session('success'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 5000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
             class="p-4 sm:p-4.5 rounded-2xl bg-emerald-50/90 border border-emerald-200/80 text-emerald-900 shadow-sm flex items-center justify-between gap-4" 
             role="alert">
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h4 class="font-display font-black text-sm text-emerald-950 leading-tight">Thao tác thành công</h4>
                    <p class="text-xs text-emerald-800 mt-0.5 font-medium truncate sm:whitespace-normal">{{ session('success') }}</p>
                </div>
            </div>
            <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-900 p-1.5 rounded-xl hover:bg-emerald-100/60 transition-colors cursor-pointer shrink-0" title="Đóng thông báo">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Status / Info Alert --}}
    @if (session('status'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 5000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
             class="p-4 sm:p-4.5 rounded-2xl bg-sky-50/90 border border-sky-200/80 text-sky-900 shadow-sm flex items-center justify-between gap-4" 
             role="alert">
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-sky-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h4 class="font-display font-black text-sm text-sky-950 leading-tight">Thông báo hệ thống</h4>
                    <p class="text-xs text-sky-800 mt-0.5 font-medium truncate sm:whitespace-normal">{{ session('status') }}</p>
                </div>
            </div>
            <button type="button" @click="show = false" class="text-sky-600 hover:text-sky-900 p-1.5 rounded-xl hover:bg-sky-100/60 transition-colors cursor-pointer shrink-0" title="Đóng thông báo">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Error / Rejection Alert --}}
    @if (session('error'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 8000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
             class="p-4 sm:p-4.5 rounded-2xl bg-rose-50/90 border border-rose-200/80 text-rose-900 shadow-sm flex items-center justify-between gap-4" 
             role="alert">
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h4 class="font-display font-black text-sm text-rose-950 leading-tight">Thao tác không thành công</h4>
                    <p class="text-xs text-rose-800 mt-0.5 font-medium truncate sm:whitespace-normal">{{ session('error') }}</p>
                </div>
            </div>
            <button type="button" @click="show = false" class="text-rose-600 hover:text-rose-900 p-1.5 rounded-xl hover:bg-rose-100/60 transition-colors cursor-pointer shrink-0" title="Đóng thông báo">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Warning Alert --}}
    @if (session('warning'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 6000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
             class="p-4 sm:p-4.5 rounded-2xl bg-amber-50/90 border border-amber-200/80 text-amber-900 shadow-sm flex items-center justify-between gap-4" 
             role="alert">
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h4 class="font-display font-black text-sm text-amber-950 leading-tight">Cảnh báo</h4>
                    <p class="text-xs text-amber-800 mt-0.5 font-medium truncate sm:whitespace-normal">{{ session('warning') }}</p>
                </div>
            </div>
            <button type="button" @click="show = false" class="text-amber-600 hover:text-amber-900 p-1.5 rounded-xl hover:bg-amber-100/60 transition-colors cursor-pointer shrink-0" title="Đóng thông báo">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Validation Errors Alert --}}
    @if ($errors->any())
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
             class="p-4 sm:p-5 rounded-2xl bg-rose-50/90 border border-rose-200/80 text-rose-900 shadow-sm flex items-start justify-between gap-4" 
             role="alert">
            <div class="flex items-start gap-3.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h4 class="font-display font-black text-sm text-rose-950 leading-tight">Thao tác thất bại (Lỗi dữ liệu)</h4>
                    <ul class="list-disc list-inside mt-1.5 space-y-0.5 text-xs text-rose-800 font-medium">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" @click="show = false" class="text-rose-600 hover:text-rose-900 p-1.5 rounded-xl hover:bg-rose-100/60 transition-colors cursor-pointer shrink-0" title="Đóng thông báo">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif
</div>
