<x-admin-layout :header="'Khán Phòng & Sơ Đồ Ghế'">
    <div class="space-y-6">

        <!-- Top Action Bar & Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-black/10 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-black uppercase tracking-wider text-indigo-800 bg-indigo-50 border border-indigo-200 px-2.5 py-0.5 rounded-full inline-block mb-1">Địa Điểm &amp; Sân Khấu</span>
                    <h2 class="font-display font-black text-xl text-black">Quản Lý Khán Phòng &amp; Sơ Đồ Khán Đài</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Hệ thống tự động sinh sơ đồ ghế trực quan và phân vùng giá vé cho từng địa điểm tổ chức sự kiện.</p>
                </div>
            </div>
            <div>
                <a href="{{ route('admin.rooms.create') }}" class="btn-dark px-5 py-3 rounded-2xl text-xs font-black flex items-center gap-2 shadow-md hover:scale-[1.02] transition-all">
                    <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>Thêm &amp; Thiết Kế Sơ Đồ Mới</span>
                </a>
            </div>
        </div>

        <!-- Rooms Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($rooms as $room)
                @php
                    $presetLabel = match($room->layout_preset ?? 'mega_concert') {
                        'theater_hall' => '🎭 Nhà Hát & Giao Hưởng',
                        'convention_center' => '🏢 Trung Tâm Hội Nghị',
                        'custom_grid' => '⚡ Ma Trận Tùy Chỉnh',
                        default => '🏟️ Mega Concert Arena'
                    };
                @endphp

                <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm flex flex-col justify-between space-y-6 group hover:border-indigo-400 hover:shadow-md transition-all">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] px-3 py-1 rounded-full font-black bg-indigo-50 text-indigo-900 border border-indigo-200 inline-flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                                Khán phòng #{{ $room->id }}
                            </span>
                            <span class="text-xs font-black px-2.5 py-1 rounded-full bg-amber-50 text-amber-900 border border-amber-200">
                                {{ $room->showtimes_count ?? 0 }} Suất diễn
                            </span>
                        </div>

                        <h3 class="font-display font-black text-lg text-black mt-4 group-hover:text-indigo-600 transition-colors">{{ $room->name }}</h3>
                        <p class="text-xs text-gray-500 mt-1 flex items-start gap-1.5">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>{{ $room->address ?? 'Trung tâm tổ chức sự kiện' }}</span>
                        </p>

                        <div class="mt-3">
                            <span class="badge-gold text-[10px] px-2.5 py-1 rounded-xl font-bold inline-block">
                                {{ $presetLabel }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-3 pt-4 border-t border-black/5">
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-3 bg-sky-50/60 rounded-2xl border border-sky-200/70">
                                <span class="text-sky-800 block text-[10px] uppercase font-black">Sức Chứa</span>
                                <span class="font-black text-base text-sky-950 mt-0.5 block">{{ number_format($room->capacity) }} Khán giả</span>
                            </div>
                            <div class="p-3 bg-amber-50/60 rounded-2xl border border-amber-200/70">
                                <span class="text-amber-800 block text-[10px] uppercase font-black">Ghế Đã Sinh</span>
                                <span class="font-black text-base text-amber-950 mt-0.5 block">{{ $room->seats_count ?? $room->seats->count() }} Ghế</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <a href="{{ route('admin.rooms.builder', $room) }}" class="flex-1 py-2.5 rounded-2xl text-xs font-bold bg-[#FAF9F6] border border-black/10 hover:bg-neutral-100 text-black transition-all text-center">
                                Chỉnh Sửa
                            </a>
                            <a href="{{ route('admin.rooms.builder', $room) }}" class="flex-1 py-2.5 rounded-2xl text-xs font-black btn-dark shadow-sm flex items-center justify-center gap-1.5 text-center">
                                <span class="text-amber-400">Sơ Đồ Ghế</span>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-12 text-gray-500 bg-white rounded-3xl border border-black/10">
                    <div class="w-12 h-12 rounded-2xl bg-[#FAF9F6] border border-black/10 text-gray-400 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <span class="font-bold text-black block">Chưa có khán phòng nào</span>
                    <span class="text-xs text-gray-400 mt-1 block">Nhấn nút thêm bên trên để tạo khán phòng mới.</span>
                </div>
            @endforelse
        </div>

        @if($rooms->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-black/10 shadow-sm">
                {{ $rooms->links() }}
            </div>
        @endif

    </div>
</x-admin-layout>
