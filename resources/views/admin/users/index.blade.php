<x-admin-layout :header="'Quản Lý Tài Khoản Khách Hàng & Quản Trị'">
    <div x-data="userManagement" class="space-y-6">
        <!-- KPI Mini Stat Cards Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Stat 1: Total Users -->
            <div class="bg-white rounded-3xl p-5 border border-black/10 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Tổng Tài Khoản</span>
                    <span class="font-display font-black text-2xl text-black block mt-1">{{ number_format($stats['total']) }}</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-700 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>

            <!-- Stat 2: Active Users -->
            <div class="bg-white rounded-3xl p-5 border border-black/10 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Đang Hoạt Động</span>
                    <span class="font-display font-black text-2xl text-emerald-800 block mt-1">{{ number_format($stats['active']) }}</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-700 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Stat 3: Locked Users -->
            <div class="bg-white rounded-3xl p-5 border border-black/10 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-700">Đã Bị Khóa</span>
                    <span class="font-display font-black text-2xl text-rose-700 block mt-1">{{ number_format($stats['locked']) }}</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-700 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>

            <!-- Stat 4: Admins -->
            <div class="bg-white rounded-3xl p-5 border border-black/10 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-800">Quản Trị Viên</span>
                    <span class="font-display font-black text-2xl text-amber-900 block mt-1">{{ number_format($stats['admins']) }}</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-700 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white rounded-3xl p-5 sm:p-6 border border-black/10 shadow-sm">
            <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-col md:flex-row items-stretch md:items-center gap-3">
                <!-- Search Input -->
                <div class="relative flex-1">
                    <svg class="w-4 h-4 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input 
                        type="text" 
                        name="q" 
                        value="{{ $filters['q'] }}" 
                        placeholder="Tìm kiếm theo Tên, Email hoặc Số điện thoại..." 
                        class="bg-[#FAF9F6] border border-black/10 pl-11 pr-4 py-2.5 rounded-2xl w-full text-xs text-black placeholder-gray-400 focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 font-medium"
                    >
                </div>

                <!-- Role Filter -->
                <div class="w-full md:w-48">
                    <select name="role" class="bg-[#FAF9F6] border border-black/10 px-4 py-2.5 rounded-2xl w-full text-xs text-black font-medium focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10">
                        <option value="">Tất cả vai trò</option>
                        <option value="admin" @selected($filters['role'] === 'admin')>Quản trị viên (Admin)</option>
                        <option value="user" @selected($filters['role'] === 'user')>Khách hàng (User)</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-full md:w-48">
                    <select name="status" class="bg-[#FAF9F6] border border-black/10 px-4 py-2.5 rounded-2xl w-full text-xs text-black font-medium focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected($filters['status'] === 'active')>Đang hoạt động</option>
                        <option value="locked" @selected($filters['status'] === 'locked')>Đã bị khóa</option>
                    </select>
                </div>

                <!-- Submit and Reset Buttons -->
                <div class="flex items-center gap-2 shrink-0">
                    <button type="submit" class="btn-dark px-5 py-2.5 rounded-2xl text-xs font-black flex items-center justify-center gap-1.5 shadow-sm">
                        <svg class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        <span>Lọc</span>
                    </button>

                    @if (!empty($filters['q']) || !empty($filters['role']) || $filters['status'] !== '')
                        <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 rounded-2xl text-xs font-bold bg-[#FAF9F6] border border-black/10 text-gray-700 hover:text-black" title="Xóa bộ lọc">
                            Xóa lọc
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Users Table Container -->
        <div class="bg-white rounded-3xl border border-black/10 overflow-hidden shadow-sm space-y-4">
            <div class="p-6 pb-2 flex items-center justify-between">
                <div>
                    <h3 class="font-display font-black text-lg text-black">Danh Sách Người Dùng Hệ Thống</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Quản lý trạng thái khóa / mở khóa và quyền hạn của các tài khoản</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-black min-w-[750px]">
                    <thead class="text-[11px] uppercase tracking-wider text-gray-600 border-y border-black/10 bg-[#FAF9F6]">
                        <tr>
                            <th class="py-3.5 px-6 font-bold whitespace-nowrap">Người Dùng</th>
                            <th class="py-3.5 px-4 font-bold whitespace-nowrap">Số Điện Thoại</th>
                            <th class="py-3.5 px-4 font-bold whitespace-nowrap">Vai Trò</th>
                            <th class="py-3.5 px-4 font-bold whitespace-nowrap">Trạng Thái</th>
                            <th class="py-3.5 px-4 font-bold whitespace-nowrap">Ngày Tham Gia</th>
                            <th class="py-3.5 px-6 font-bold text-right whitespace-nowrap">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse ($users as $user)
                            @php
                                $isCurrentAdmin = ($user->id === Auth::id());
                                $isAdmin = $user->hasRole('admin');
                            @endphp
                            <tr class="hover:bg-[#FAF9F6] transition-colors">
                                <!-- User Info with Avatar / Initials -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl {{ $isAdmin ? 'bg-gradient-to-br from-amber-400 to-amber-600 text-black shadow-sm ring-2 ring-amber-300' : 'bg-gradient-to-br from-slate-700 to-slate-900 text-white shadow-sm' }} flex items-center justify-center font-display font-black text-sm shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-black truncate">{{ $user->name }}</span>
                                                @if ($isCurrentAdmin)
                                                    <span class="px-2 py-0.5 rounded-full font-black text-[9px] bg-amber-100 text-amber-900 border border-amber-300">Bạn</span>
                                                @endif
                                            </div>
                                            <span class="text-[11px] text-gray-400 block truncate">{{ $user->email }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Phone -->
                                <td class="py-4 px-4 text-gray-700 font-medium whitespace-nowrap">
                                    {{ $user->phone ?? 'Chưa cập nhật' }}
                                </td>

                                <!-- Role -->
                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if ($isAdmin)
                                        <span class="px-3 py-1 rounded-full font-black text-[11px] whitespace-nowrap inline-flex items-center gap-1.5 bg-amber-50 text-amber-900 border border-amber-300">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            Quản trị viên
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full font-bold text-[11px] whitespace-nowrap inline-flex items-center gap-1 bg-slate-100 text-slate-700 border border-slate-200">
                                            Khách hàng
                                        </span>
                                    @endif
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span 
                                        id="user-status-badge-{{ $user->id }}" 
                                        class="{{ $user->is_active ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-rose-50 text-rose-800 border-rose-300' }} border text-[11px] px-3 py-1 rounded-full font-black whitespace-nowrap inline-flex items-center gap-1.5 shadow-sm"
                                    >
                                        <span class="w-2 h-2 rounded-full {{ $user->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                                        {{ $user->is_active ? 'Hoạt động' : 'Đã bị khóa' }}
                                    </span>
                                </td>

                                <!-- Join Date -->
                                <td class="py-4 px-4 text-gray-600 text-[11px] whitespace-nowrap">
                                    {{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A' }}
                                </td>

                                <!-- Action Buttons -->
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($isCurrentAdmin)
                                            <span class="text-[11px] text-gray-400 italic">Tài khoản của bạn</span>
                                        @else
                                            <!-- Toggle Role -->
                                            <form method="POST" action="{{ route('admin.users.toggle-role', $user) }}" onsubmit="return confirm('Bạn có chắc muốn đổi vai trò của [{{ addslashes($user->name) }}]?')">
                                                @csrf
                                                @method('PATCH')
                                                <button 
                                                    type="submit" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold border border-black/10 hover:bg-black hover:text-white transition-all"
                                                    title="{{ $isAdmin ? 'Hạ quyền xuống Khách hàng (User)' : 'Thăng quyền lên Quản trị viên (Admin)' }}"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                    <span>{{ $isAdmin ? 'Hạ User' : 'Lên Admin' }}</span>
                                                </button>
                                            </form>

                                            <!-- Reset Password -->
                                            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" onsubmit="return confirm('Đặt lại mật khẩu cho [{{ addslashes($user->name) }}] về mặc định (TicketBox@2026)?')">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="p-1.5 rounded-xl text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 border border-black/10 transition-colors"
                                                    title="Đặt lại mật khẩu tạm"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                                </button>
                                            </form>

                                            <!-- Lock / Unlock Button -->
                                            <button 
                                                id="user-action-btn-{{ $user->id }}"
                                                type="button" 
                                                @click="openConfirm({{ $user->id }}, '{{ addslashes($user->name) }}', {{ $user->is_active ? 'true' : 'false' }})"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm whitespace-nowrap {{ $user->is_active ? 'bg-rose-50 text-rose-800 border border-rose-200 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' }}"
                                                title="{{ $user->is_active ? 'Khóa tài khoản này' : 'Mở khóa tài khoản này' }}"
                                            >
                                                @if ($user->is_active)
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                    <span>Khóa</span>
                                                @else
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                    </svg>
                                                    <span>Mở khóa</span>
                                                @endif
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-500">
                                    <div class="w-12 h-12 rounded-2xl bg-[#FAF9F6] border border-black/10 text-gray-400 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <span class="font-bold text-black block">Không tìm thấy tài khoản phù hợp</span>
                                    <span class="text-xs text-gray-400 mt-1 block">Hãy thử tìm với từ khóa khác hoặc xóa bộ lọc.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Custom Pagination Bar -->
            @if ($users->hasPages())
                <div class="p-6 border-t border-black/10 bg-[#FAF9F6]">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        <!-- Confirmation Modal (High Contrast Light Dialog) -->
        <div 
            x-show="showConfirmModal" 
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display: none;"
        >
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeConfirm()"></div>

            <!-- Dialog Content -->
            <div 
                class="relative bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full border border-black/15 shadow-2xl z-10 text-center space-y-4"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="scale-95 translate-y-4"
                x-transition:enter-end="scale-100 translate-y-0"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="scale-100 translate-y-0"
                x-transition:leave-end="scale-95 translate-y-4"
            >
                <div 
                    class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center border shadow-sm"
                    :class="targetUser.is_active ? 'bg-red-50 text-crimson border-red-200' : 'bg-emerald-50 text-sage-forest border-emerald-200'"
                >
                    <template x-if="targetUser.is_active">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </template>
                    <template x-if="!targetUser.is_active">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                        </svg>
                    </template>
                </div>

                <h3 class="font-serif font-black text-2xl text-black">
                    <span x-text="targetUser.is_active ? 'Xác Nhận Khóa Tài Khoản?' : 'Xác Nhận Mở Khóa?'"></span>
                </h3>

                <p class="text-xs text-gray-600 leading-relaxed">
                    <template x-if="targetUser.is_active">
                        <span>Bạn có chắc chắn muốn khóa tài khoản <strong class="text-black" x-text="targetUser.name"></strong> không? Người dùng này sẽ <strong>không thể đăng nhập</strong> vào hệ thống sau khi bị khóa.</span>
                    </template>
                    <template x-if="!targetUser.is_active">
                        <span>Bạn có muốn mở khóa tài khoản <strong class="text-black" x-text="targetUser.name"></strong> không? Người dùng sẽ có thể đăng nhập và mua vé bình thường.</span>
                    </template>
                </p>

                <div class="pt-4 flex gap-3">
                    <button 
                        type="button" 
                        @click="confirmToggle()"
                        :disabled="isSubmitting"
                        class="flex-1 py-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-md"
                        :class="targetUser.is_active ? 'btn-crimson' : 'btn-sage'"
                    >
                        <span x-show="isSubmitting" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <span x-text="targetUser.is_active ? 'Khóa Tài Khoản' : 'Mở Khóa Ngay'"></span>
                    </button>

                    <button 
                        type="button" 
                        @click="closeConfirm()"
                        :disabled="isSubmitting"
                        class="btn-ghost-light px-5 py-3 rounded-xl text-xs font-bold"
                    >
                        Hủy Bỏ
                    </button>
                </div>
            </div>
        </div>

        <!-- Global Toast Notification -->
        <div 
            x-show="showToast" 
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="fixed bottom-6 right-6 z-50 bg-black text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border border-white/20"
            style="display: none;"
        >
            <span class="w-2.5 h-2.5 rounded-full" :class="toastType === 'success' ? 'bg-emerald-400' : 'bg-red-400'"></span>
            <span class="text-sm font-bold" x-text="toastMessage"></span>
        </div>
    </div>

    <!-- Clean Alpine.js Component Script (No inline HTML attribute escaping issues) -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('userManagement', () => ({
                showConfirmModal: false,
                isSubmitting: false,
                targetUser: { id: null, name: '', is_active: true },
                showToast: false,
                toastMessage: '',
                toastType: 'success',

                openConfirm(id, name, isActive) {
                    this.targetUser = { id: id, name: name, is_active: isActive };
                    this.showConfirmModal = true;
                },

                closeConfirm() {
                    this.showConfirmModal = false;
                    this.targetUser = { id: null, name: '', is_active: true };
                },

                notify(msg, type = 'success') {
                    this.toastMessage = msg;
                    this.toastType = type;
                    this.showToast = true;
                    setTimeout(() => { this.showToast = false; }, 3500);
                },

                async confirmToggle() {
                    if (!this.targetUser.id) return;
                    this.isSubmitting = true;

                    try {
                        const response = await fetch(`/admin/users/${this.targetUser.id}/toggle-status`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            const badgeEl = document.getElementById(`user-status-badge-${this.targetUser.id}`);
                            const btnEl = document.getElementById(`user-action-btn-${this.targetUser.id}`);

                            if (badgeEl) {
                                if (data.user.is_active) {
                                    badgeEl.className = 'badge-sage text-[10px] px-2.5 py-1 rounded-full font-bold whitespace-nowrap inline-flex items-center gap-1.5';
                                    badgeEl.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-sage-forest"></span> Hoạt động';
                                } else {
                                    badgeEl.className = 'badge-crimson text-[10px] px-2.5 py-1 rounded-full font-bold whitespace-nowrap inline-flex items-center gap-1.5';
                                    badgeEl.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-crimson"></span> Đã bị khóa';
                                }
                            }

                            if (btnEl) {
                                if (data.user.is_active) {
                                    btnEl.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm whitespace-nowrap bg-red-50 text-crimson border border-red-200 hover:bg-red-100';
                                    btnEl.setAttribute('title', 'Khóa tài khoản này');
                                    btnEl.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg><span>Khóa</span>';
                                } else {
                                    btnEl.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm whitespace-nowrap bg-emerald-50 text-sage-forest border border-emerald-200 hover:bg-emerald-100';
                                    btnEl.setAttribute('title', 'Mở khóa tài khoản này');
                                    btnEl.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg><span>Mở khóa</span>';
                                }
                            }

                            this.notify(data.message, 'success');
                            this.closeConfirm();
                        } else {
                            this.notify(data.message || 'Có lỗi xảy ra, vui lòng thử lại.', 'error');
                        }
                    } catch (error) {
                        this.notify('Không thể kết nối máy chủ. Vui lòng kiểm tra lại.', 'error');
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            }));
        });
    </script>
</x-admin-layout>
