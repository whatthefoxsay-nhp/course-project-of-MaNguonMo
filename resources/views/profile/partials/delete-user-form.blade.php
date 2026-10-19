<section>
    <div class="flex items-center justify-between pb-5 border-b border-rose-200/60">
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-100/80 text-crimson border border-rose-200 text-[10px] font-black uppercase tracking-wider mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-crimson animate-pulse"></span>
                Vùng Nguy Hiểm
            </span>
            <h2 class="text-xl font-black font-display text-crimson tracking-tight">
                Xóa Tài Khoản Vĩnh Viễn
            </h2>
            <p class="mt-1 text-xs text-rose-700/80 font-medium">
                Một khi tài khoản đã xóa, toàn bộ dữ liệu lịch sử đặt vé, danh sách vé điện tử và quyền lợi thành viên sẽ bị xóa vĩnh viễn và không thể khôi phục.
            </p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-rose-100/70 border border-rose-300/50 flex items-center justify-center text-crimson shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </div>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <p class="text-xs text-gray-600 max-w-lg leading-relaxed">
            Vui lòng chắc chắn rằng bạn đã tải xuống các vé điện tử QR Code hoặc hoàn tất các thủ tục trước khi thực hiện thao tác này.
        </p>

        <button
            type="button"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="btn-crimson px-5 py-3 rounded-2xl text-xs font-bold inline-flex items-center gap-2 shrink-0 shadow-sm"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>Yêu Cầu Xóa Tài Khoản</span>
        </button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-8 space-y-6">
            @csrf
            @method('delete')

            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-100 flex items-center justify-center text-crimson shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-black font-display text-slate-900">
                        Xác nhận xóa tài khoản?
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Thao tác này là vĩnh viễn và không thể hoàn tác sau khi xác nhận.
                    </p>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed bg-[#FAF9F6] p-4 rounded-2xl border border-black/5">
                Toàn bộ dữ liệu của bạn, bao gồm lịch sử giao dịch và vé điện tử chưa sử dụng sẽ bị hủy bỏ. Vui lòng nhập mật khẩu đăng nhập để xác nhận xóa.
            </p>

            <div class="space-y-1.5">
                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Nhập Mật Khẩu Xác Nhận <span class="text-crimson font-black">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="••••••••"
                        class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-red-500 focus:ring-4 focus:ring-red-500/15 rounded-2xl py-3 pl-11 pr-4 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs"
                    />
                </div>
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-black/10">
                <button 
                    type="button" 
                    x-on:click="$dispatch('close')" 
                    class="btn-ghost-light px-5 py-2.5 rounded-2xl text-xs font-bold"
                >
                    Hủy Bỏ
                </button>

                <button 
                    type="submit" 
                    class="btn-crimson px-5 py-2.5 rounded-2xl text-xs font-bold inline-flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Xác Nhận Xóa Vĩnh Viễn</span>
                </button>
            </div>
        </form>
    </x-modal>
</section>
