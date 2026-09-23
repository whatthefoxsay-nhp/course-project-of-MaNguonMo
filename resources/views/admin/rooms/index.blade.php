<x-admin-layout :header="'Khán Phòng & Sơ Đồ Ghế'">
    <div 
        x-data="{
            showModal: false,
            activeRoom: null,
            zoomScale: 1,
            hoveredSeat: null,
            openBlueprint(roomData) {
                this.activeRoom = roomData;
                this.zoomScale = 1;
                this.hoveredSeat = null;
                this.showModal = true;
            },
            closeBlueprint() {
                this.showModal = false;
                this.activeRoom = null;
                this.hoveredSeat = null;
            },
            zoomIn() {
                if (this.zoomScale < 1.6) this.zoomScale += 0.15;
            },
            zoomOut() {
                if (this.zoomScale > 0.6) this.zoomScale -= 0.15;
            },
            resetZoom() {
                this.zoomScale = 1;
            }
        }"
        class="space-y-6"
    >

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
                    $blueprintJson = json_encode($room->blueprint_data ?? []);
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

                        <!-- Action Buttons: Xem Sơ Đồ + Sửa & Sơ Đồ + Xóa -->
                        <div class="flex items-center gap-2 pt-2">
                            <button 
                                type="button" 
                                @click='openBlueprint({{ $blueprintJson }})'
                                class="flex-1 py-2.5 rounded-2xl text-xs font-black bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white shadow-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                                title="Xem sơ đồ khán đài thực tế"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Xem Sơ Đồ</span>
                            </button>

                            <a href="{{ route('admin.rooms.builder', $room) }}" class="px-4 py-2.5 rounded-2xl text-xs font-bold bg-[#FAF9F6] border border-black/10 hover:bg-neutral-100 text-black transition-all text-center">
                                Sửa
                            </a>

                            <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" onsubmit="return confirm('Bạn có chắc muốn xóa khán phòng [{{ addslashes($room->name) }}]?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2.5 rounded-2xl text-xs font-bold text-[#CC0000] border border-[#CC0000]/30 hover:bg-[#CC0000] hover:text-white transition-all flex items-center justify-center" title="Xóa khán phòng">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
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

        <!-- ========================================================================= -->
        <!-- LIVE BLUEPRINT INTERACTIVE MODAL (MODAL XEM NHANH SƠ ĐỒ THỰC TẾ TỪ DB)    -->
        <!-- ========================================================================= -->
        <div 
            x-show="showModal" 
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
            @keydown.escape.window="closeBlueprint()"
        >
            <!-- Backdrop -->
            <div 
                x-show="showModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="closeBlueprint()"
                class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity"
            ></div>

            <!-- Modal Dialog Container -->
            <div class="flex min-h-screen items-center justify-center p-4 sm:p-6 text-center">
                <div 
                    x-show="showModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative w-full max-w-5xl transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all border border-black/10 flex flex-col max-h-[90vh]"
                >
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-black/10 bg-[#FAF9F6]">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-700 border border-amber-300 flex items-center justify-center font-black text-lg">
                                🏟️
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase" x-text="activeRoom?.preset_label"></span>
                                    <span class="text-xs text-gray-500 font-mono" x-text="'Mã phòng: #' + activeRoom?.id"></span>
                                </div>
                                <h3 class="font-display font-black text-lg text-black mt-0.5" x-text="activeRoom?.name"></h3>
                                <p class="text-xs text-gray-500 flex items-center gap-1">
                                    <span>📍</span>
                                    <span x-text="activeRoom?.address"></span>
                                </p>
                            </div>
                        </div>

                        <!-- Zoom & Close Controls -->
                        <div class="flex items-center gap-2">
                            <div class="flex items-center bg-white border border-black/10 rounded-full p-1 shadow-xs text-xs">
                                <button type="button" @click="zoomOut()" class="w-7 h-7 rounded-full flex items-center justify-center text-gray-700 hover:bg-[#FAF9F6] font-bold">&minus;</button>
                                <span class="px-2 font-mono font-bold text-gray-600" x-text="Math.round(zoomScale * 100) + '%'"></span>
                                <button type="button" @click="zoomIn()" class="w-7 h-7 rounded-full flex items-center justify-center text-gray-700 hover:bg-[#FAF9F6] font-bold">&#43;</button>
                                <button type="button" @click="resetZoom()" class="px-2 text-[10px] font-bold text-gray-500 hover:text-black border-l border-black/10">Reset</button>
                            </div>

                            <button 
                                type="button" 
                                @click="closeBlueprint()" 
                                class="w-9 h-9 rounded-2xl bg-white border border-black/10 text-gray-500 hover:text-black flex items-center justify-center hover:bg-neutral-100 transition-all"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body: Blueprint Viewport & Seat Matrix -->
                    <div class="p-6 overflow-y-auto flex-1 bg-gradient-to-b from-[#0F172A] via-[#111827] to-[#020617] text-white">
                        
                        <!-- STAGE BANNER -->
                        <div class="w-full max-w-md mx-auto p-3 bg-gradient-to-r from-obsidian-950 via-obsidian-800 to-obsidian-950 text-white text-center rounded-2xl shadow-md border-2 border-gold-antique/80 relative overflow-hidden mb-8">
                            <div class="font-display font-black text-xs tracking-widest uppercase text-gold-light">
                                ★ SÂN KHẤU CHÍNH (MAIN STAGE) ★
                            </div>
                            <span class="text-[10px] text-gray-400 font-mono">HỆ THỐNG ÂM THANH ÁNH SÁNG &amp; MÀN HÌNH LED</span>
                        </div>

                        <!-- ZOOM CANVAS CONTAINER -->
                        <div class="overflow-x-auto pb-6">
                            <div 
                                class="transition-transform duration-200 origin-top min-w-[500px] max-w-4xl mx-auto space-y-3"
                                :style="'transform: scale(' + zoomScale + ')'"
                            >
                                <template x-for="r in activeRoom?.rows || []" :key="r.row">
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        <!-- Row Label -->
                                        <span class="w-14 text-right pr-2 text-[11px] font-mono font-bold text-gray-400 shrink-0" x-text="r.row"></span>
                                        
                                        <!-- Seats in Row -->
                                        <div class="flex items-center gap-1 flex-wrap justify-center">
                                            <template x-for="s in r.seats" :key="s.id">
                                                <button 
                                                    type="button" 
                                                    @mouseenter="hoveredSeat = s"
                                                    @click="hoveredSeat = s"
                                                    :style="'background-color: ' + s.color"
                                                    class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[9px] font-black text-white flex items-center justify-center shadow-xs hover:scale-125 transition-transform cursor-pointer border border-white/20"
                                                    :title="r.row + '-' + s.number + ' (' + s.type_name + ')'"
                                                >
                                                    <span x-text="s.number"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Standing GA Zone if applicable -->
                                <template x-if="activeRoom?.has_standing">
                                    <div class="mt-6 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/40 text-center max-w-md mx-auto">
                                        <span class="text-xs font-black text-amber-300 block uppercase">Khu Vực Vé Đứng (GA Standing Pit)</span>
                                        <span class="text-[11px] text-gray-300 font-mono" x-text="'Sức chứa vé đứng: ' + activeRoom?.standing_count + ' Khán giả (Gán tự động)'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- LIVE HOVER INSPECTOR BAR -->
                        <div class="mt-6 p-3 bg-black/80 rounded-2xl border border-white/10 max-w-lg mx-auto flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full" :style="'background-color: ' + (hoveredSeat?.color || '#D4AF37')"></span>
                                <span class="font-bold text-gray-400">Ghế đang xem:</span>
                                <span class="font-black text-white" x-text="hoveredSeat ? (hoveredSeat.type_name + ' #' + hoveredSeat.number) : 'Rê chuột vào ghế bất kỳ'"></span>
                            </div>
                            <span class="text-[10px] font-mono text-gold-antique" x-text="hoveredSeat ? ('Mã ID: ' + hoveredSeat.id) : 'Sơ đồ thực tế'"></span>
                        </div>

                    </div>

                    <!-- Modal Footer: Statistics & Quick Actions -->
                    <div class="p-6 bg-white border-t border-black/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <!-- Legend & Counts -->
                        <div class="flex flex-wrap items-center gap-4 text-xs font-semibold">
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#D4AF37]"></span>
                                <span>SVIP: <strong x-text="activeRoom?.summary?.svip || 0"></strong></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#C08497]"></span>
                                <span>VIP: <strong x-text="activeRoom?.summary?.vip || 0"></strong></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#3A5A40]"></span>
                                <span>Cat 1: <strong x-text="activeRoom?.summary?.cat1 || 0"></strong></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#1E293B]"></span>
                                <span>Cat 2: <strong x-text="activeRoom?.summary?.cat2 || 0"></strong></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#E11D48]"></span>
                                <span>Skybox: <strong x-text="activeRoom?.summary?.skybox || 0"></strong></span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <a 
                                :href="activeRoom?.builder_url" 
                                class="btn-dark flex-1 sm:flex-initial px-5 py-2.5 rounded-2xl text-xs font-black flex items-center justify-center gap-1.5"
                            >
                                <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Mở Trình Sửa &amp; Tái Tạo Sơ Đồ</span>
                            </a>
                            <button 
                                type="button" 
                                @click="closeBlueprint()" 
                                class="btn-ghost-light px-4 py-2.5 rounded-2xl text-xs font-bold"
                            >
                                Đóng
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
