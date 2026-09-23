<x-admin-layout :header="'Quản Lý Sự Kiện & Liveshow'">
    <div class="space-y-6">

        <!-- Top Action Bar & Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-black/10 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-500 to-pink-600 text-white flex items-center justify-center shadow-md shadow-rose-500/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-black uppercase tracking-wider text-rose-600 bg-rose-50 border border-rose-200/80 px-2.5 py-0.5 rounded-full inline-block mb-1">Danh Mục Sự Kiện</span>
                    <h2 class="font-display font-black text-xl text-black">Danh Sách Sự Kiện &amp; Đêm Diễn</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Quản lý các chương trình ca nhạc, concert, hội nghị và triển lãm mở bán trên hệ thống.</p>
                </div>
            </div>
            <div>
                <a href="{{ route('admin.events.create') }}" class="btn-rose px-5 py-3 rounded-2xl text-xs font-black flex items-center gap-2 shadow-md shadow-rose-500/20 transition-all hover:scale-[1.02]">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>Thêm Sự Kiện Mới</span>
                </a>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white p-5 rounded-3xl border border-black/10 shadow-sm">
            <form method="GET" action="{{ route('admin.events.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-6 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm theo tên sự kiện, nghệ sĩ, diễn viên..." class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl pl-10 pr-4 py-2.5 text-xs text-black placeholder-gray-400 focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 font-medium">
                </div>

                <div class="sm:col-span-3">
                    <select name="category_id" class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 text-xs text-black focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 font-medium">
                        <option value="">-- Tất cả thể loại --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <select name="status" class="w-full bg-[#FAF9F6] border border-black/10 rounded-2xl px-4 py-2.5 text-xs text-black focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10 font-medium">
                        <option value="">-- Trạng thái --</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Đang mở bán</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Bản nháp</option>
                    </select>
                </div>

                <div class="sm:col-span-1 flex gap-2">
                    <button type="submit" class="w-full btn-dark rounded-2xl text-xs font-black flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Events Table -->
        <div class="bg-white rounded-3xl border border-black/10 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-black min-w-[850px]">
                    <thead class="text-[11px] uppercase tracking-wider text-gray-600 border-b border-black/10 bg-[#FAF9F6]">
                        <tr>
                            <th class="py-4 px-6 font-bold whitespace-nowrap">Sự Kiện</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Thể Loại</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Thời Lượng</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Số Suất Diễn</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Ngày Khởi Chiếu</th>
                            <th class="py-4 px-4 font-bold whitespace-nowrap">Trạng Thái</th>
                            <th class="py-4 px-6 font-bold text-right whitespace-nowrap">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse($events as $event)
                            <tr class="hover:bg-[#FAF9F6] transition-colors">
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-12 h-16 rounded-xl bg-black/10 overflow-hidden flex-shrink-0 border border-black/10 shadow-sm">
                                            <img src="{{ $event->poster_url }}" alt="{{ $event->title }}" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <div class="font-bold text-black text-sm hover:text-rose-600 transition-colors">{{ $event->title }}</div>
                                            <div class="text-gray-400 text-[11px] mt-0.5 max-w-xs truncate">{{ $event->description }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-purple-50 text-purple-800 border border-purple-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                        {{ $event->category?->name ?? 'Chưa phân loại' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-semibold bg-[#FAF9F6] border border-black/10 text-gray-800 font-mono">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $event->duration_minutes }} phút
                                    </span>
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black bg-amber-50 text-amber-900 border border-amber-200">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        {{ $event->showtimes_count ?? 0 }} suất
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-gray-600 font-medium whitespace-nowrap">
                                    {{ $event->release_date ? \Carbon\Carbon::parse($event->release_date)->format('d/m/Y') : 'Chưa xếp lịch' }}
                                </td>
                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if($event->status === 'published')
                                        <span class="px-3 py-1 rounded-full font-black text-[11px] whitespace-nowrap inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-sm">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>Đang mở bán</span>
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full font-black text-[11px] whitespace-nowrap inline-flex items-center gap-1.5 bg-amber-50 text-amber-800 border border-amber-300 shadow-sm">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            <span>Bản nháp</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('movies.show', $event->slug) }}" target="_blank" class="p-2 rounded-xl border border-black/10 hover:bg-[#FAF9F6] text-gray-700 hover:text-black transition-all" title="Xem trên web">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        <a href="{{ route('admin.events.edit', $event) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#FAF9F6] border border-black/10 hover:bg-black hover:text-white transition-all">Sửa</a>
                                        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Xóa sự kiện này? Toàn bộ suất diễn chưa bán vé sẽ bị xóa theo.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-[#CC0000] border border-[#CC0000]/30 hover:bg-[#CC0000] hover:text-white transition-all">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-500">
                                    <div class="w-12 h-12 rounded-2xl bg-[#FAF9F6] border border-black/10 text-gray-400 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </div>
                                    <span class="font-bold text-black block">Không tìm thấy sự kiện nào</span>
                                    <span class="text-xs text-gray-400 mt-1 block">Hãy thử tìm với từ khóa khác hoặc xóa bộ lọc.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($events->hasPages())
                <div class="p-4 border-t border-black/5 bg-[#FAF9F6]">
                    {{ $events->links() }}
                </div>
            @endif
        </div>

    </div>
</x-admin-layout>
