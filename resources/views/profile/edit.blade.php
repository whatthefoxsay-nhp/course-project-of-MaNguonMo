<x-site-layout :title="'Hồ Sơ Cá Nhân - TicketBox'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <!-- Header -->
        <div class="mb-8">
            <span class="text-xs font-bold uppercase tracking-wider text-gold-dark">Tài Khoản Thành Viên</span>
            <h1 class="font-serif text-3xl font-black text-black mt-1">Hồ Sơ Cá Nhân &amp; Cài Đặt</h1>
            <p class="text-sm text-gray-600">Quản lý thông tin tài khoản, cập nhật mật khẩu và bảo mật đăng nhập.</p>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-black/10 shadow-sm">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-black/10 shadow-sm">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-black/10 shadow-sm">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-site-layout>
