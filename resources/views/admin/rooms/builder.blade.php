<x-admin-layout :header="$isEdit ? 'Chỉnh Sửa & Tái Tạo Sơ Đồ Khán Phòng' : 'Tạo Mới & Thiết Kế Sơ Đồ Khán Đài'">
    @php
        $preset = $room->layout_preset ?? 'mega_concert';
        $config = $room->seat_config ?? [];
        $basePrice = $config['base_price'] ?? 250000;
        $rows = $config['rows'] ?? 10;
        $cols = $config['cols'] ?? 14;
        $vipRatio = $config['vip_ratio'] ?? 30;
        $svipRatio = $config['svip_ratio'] ?? 15;
    @endphp

    <div 
        x-data="adminSeatMapBuilder({
            preset: '{{ $preset }}',
            roomName: '{{ addslashes($room->name ?? '') }}',
            roomAddress: '{{ addslashes($room->address ?? '') }}',
            capacity: {{ $room->capacity ?? 20000 }},
            basePrice: {{ $basePrice }},
            rows: {{ $rows }},
            cols: {{ $cols }},
            vipRatio: {{ $vipRatio }},
            svipRatio: {{ $svipRatio }},
            initialRows: @js($initialRows ?? [])
        })"
        class="space-y-6"
    >
        <!-- Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-black/10 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-rose-600 text-white flex items-center justify-center shadow-md shadow-amber-500/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-black uppercase tracking-wider text-gold-dark bg-[#FFF8E1] border border-[#FFE082] px-2.5 py-0.5 rounded-full inline-block mb-1">
                        Trình Sinh Sơ Đồ &amp; Hạng Vé Tự Động
                    </span>
                    <h2 class="font-display font-black text-xl text-black">
                        {{ $isEdit ? 'Cấu Hình & Tái Tạo Sơ Đồ: ' . $room->name : 'Thiết Kế & Sinh Sơ Đồ Khán Đài Mới' }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Lựa chọn mẫu khán đài hoặc ma trận tùy chỉnh. Hệ thống sẽ tự động tạo sơ đồ trực quan và đồng bộ sang giao diện đặt vé người dùng.
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.rooms.index') }}" class="btn-ghost-light px-5 py-2.5 rounded-2xl text-xs font-bold flex items-center gap-1.5 self-start md:self-auto">
                <span>&larr;</span>
                <span>Quay lại danh sách</span>
            </a>
        </div>

        <form action="{{ $isEdit ? route('admin.rooms.update', $room) : route('admin.rooms.store') }}" method="POST">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <input type="hidden" name="layout_preset" :value="preset">
            <input type="hidden" name="custom_layout_matrix" :value="JSON.stringify(matrixRows)">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- ========================================================= -->
                <!-- LEFT COLUMN: CONFIGURATION & TICKET TIERS (5 COLS)        -->
                <!-- ========================================================= -->
                <div class="lg:col-span-5 space-y-6">
                    
                    <!-- 1. Preset Selector Card -->
                    <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4">
                        <div class="border-b border-black/10 pb-3">
                            <h3 class="font-display font-black text-base text-black flex items-center gap-2">
                                <span>1. Chọn Mẫu Sơ Đồ Khán Đài (Layout Preset)</span>
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Chọn bố cục sân khấu tương thích với loại hình sự kiện:</p>
                        </div>

                        <div class="space-y-3">
                            <!-- Preset 1: Mega Concert -->
                            <div 
                                @click="selectPreset('mega_concert')"
                                class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                :class="preset === 'mega_concert' ? 'border-gold-antique bg-[#FFF8E1]/40 shadow-sm' : 'border-black/10 bg-[#FAF9F6] hover:border-black/20'"
                            >
                                <span class="text-2xl">🏟️</span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-display font-black text-xs text-black">Mega Concert Arena</h4>
                                        <span class="badge-gold text-[9px] px-2 py-0.5 rounded-full font-bold">20.000+ chỗ</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Sân khấu chính, Catwalk Runway, B-Stage, GA Standing, SVIP Diamond, Khán đài A/B, Skybox Suites.</p>
                                </div>
                            </div>

                            <!-- Preset 2: Theater Hall -->
                            <div 
                                @click="selectPreset('theater_hall')"
                                class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                :class="preset === 'theater_hall' ? 'border-rose-taupe bg-[#FDE8EE]/40 shadow-sm' : 'border-black/10 bg-[#FAF9F6] hover:border-black/20'"
                            >
                                <span class="text-2xl">🎭</span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-display font-black text-xs text-black">Nhà Hát &amp; Giao Hưởng (Theater)</h4>
                                        <span class="badge-rose text-[9px] px-2 py-0.5 rounded-full font-bold">1.200 chỗ</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Tầng Trệt VIP Stalls, Khán đài Dress Circle, Ban công Upper Gallery, Hộp VIP Boxes.</p>
                                </div>
                            </div>

                            <!-- Preset 3: Convention Center -->
                            <div 
                                @click="selectPreset('convention_center')"
                                class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                :class="preset === 'convention_center' ? 'border-sage-forest bg-emerald-50/50 shadow-sm' : 'border-black/10 bg-[#FAF9F6] hover:border-black/20'"
                            >
                                <span class="text-2xl">🏢</span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-display font-black text-xs text-black">Trung Tâm Hội Nghị &amp; Expo</h4>
                                        <span class="badge-sage text-[9px] px-2 py-0.5 rounded-full font-bold">800 chỗ</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Hàng ghế Keynote VIP trước sân khấu, Khu Tiêu chuẩn &amp; Bàn tròn VIP Business.</p>
                                </div>
                            </div>

                            <!-- Preset 4: Custom Grid -->
                            <div 
                                @click="selectPreset('custom_grid')"
                                class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                :class="preset === 'custom_grid' ? 'border-black bg-gray-100 shadow-sm' : 'border-black/10 bg-[#FAF9F6] hover:border-black/20'"
                            >
                                <span class="text-2xl">⚡</span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-display font-black text-xs text-black">Ma Trận Tùy Chỉnh (Custom Grid)</h4>
                                        <span class="badge-dark text-[9px] px-2 py-0.5 rounded-full font-bold">Linh hoạt</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Tự do nhập số hàng, số cột, tỷ lệ phân chia SVIP / VIP / Standard.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Room Basic Info & Pricing Card -->
                    <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4">
                        <div class="border-b border-black/10 pb-3">
                            <h3 class="font-display font-black text-base text-black flex items-center gap-2">
                                <span>2. Thông Tin Địa Điểm &amp; Giá Vé Cơ Sở</span>
                            </h3>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div>
                                <label class="block text-gray-700 font-bold uppercase tracking-wider mb-1.5">
                                    Tên Khán Phòng / Sân Vận Động <span class="text-crimson">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="name" 
                                    x-model="roomName" 
                                    placeholder="VD: Sân Vận Động Quân Khu 7 (SVĐ QK7 Arena)"
                                    class="glass-input w-full rounded-xl px-4 py-2.5 font-semibold text-xs"
                                    required
                                >
                            </div>

                            <div>
                                <label class="block text-gray-700 font-bold uppercase tracking-wider mb-1.5">
                                    Địa chỉ chi tiết
                                </label>
                                <input 
                                    type="text" 
                                    name="address" 
                                    x-model="roomAddress" 
                                    placeholder="VD: Số 202 Hoàng Văn Thụ, Phường 9, Phú Nhuận, TP.HCM"
                                    class="glass-input w-full rounded-xl px-4 py-2.5 text-xs"
                                >
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 font-bold uppercase tracking-wider mb-1.5">
                                        Sức Chứa Khán Giả <span class="text-crimson">*</span>
                                    </label>
                                    <input 
                                        type="number" 
                                        name="capacity" 
                                        x-model="capacity" 
                                        class="glass-input w-full rounded-xl px-4 py-2.5 font-black text-xs font-mono"
                                        required
                                    >
                                </div>

                                <div>
                                    <label class="block text-gray-700 font-bold uppercase tracking-wider mb-1.5">
                                        Giá Vé Cơ Sở (Base Price) <span class="text-crimson">*</span>
                                    </label>
                                    <input 
                                        type="number" 
                                        name="base_price" 
                                        x-model="basePrice" 
                                        step="10000"
                                        class="glass-input w-full rounded-xl px-4 py-2.5 font-black text-xs font-mono text-crimson"
                                        required
                                    >
                                </div>
                            </div>

                            <!-- Custom Grid Specific Options -->
                            <div x-show="preset === 'custom_grid'" class="p-4 bg-[#FAF9F6] rounded-2xl border border-black/10 space-y-3" style="display: none;">
                                <span class="font-bold text-[11px] text-gray-700 uppercase block">Cấu Hình Ma Trận Hàng / Cột:</span>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-gray-500 text-[10px] font-bold mb-1">Số Hàng Ghế (Rows):</label>
                                        <input type="number" name="rows" x-model="rows" min="2" max="26" class="glass-input w-full rounded-xl px-3 py-2 text-xs font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-gray-500 text-[10px] font-bold mb-1">Số Ghế Mỗi Hàng (Cols):</label>
                                        <input type="number" name="cols" x-model="cols" min="4" max="40" class="glass-input w-full rounded-xl px-3 py-2 text-xs font-mono">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Auto-Generated Ticket Tiers Table -->
                    <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4">
                        <div class="border-b border-black/10 pb-3 flex items-center justify-between">
                            <div>
                                <h3 class="font-display font-black text-base text-black">
                                    3. Bảng Hạng Vé Tự Động Sinh
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">Giá vé được tự động tính toán theo hệ số nhân từ Giá Vé Cơ Sở:</p>
                            </div>
                            <span class="badge-sage text-[10px] px-2.5 py-1 rounded-full font-bold">Auto-Calculated</span>
                        </div>

                        <div class="space-y-3">
                            <template x-for="tier in generatedTiers" :key="tier.key">
                                <div class="p-3.5 rounded-2xl bg-[#FAF9F6] border border-black/5 flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full" :style="'background-color:' + tier.color"></span>
                                            <span class="font-display font-black text-xs text-black" x-text="tier.name"></span>
                                            <span class="text-[10px] font-mono font-bold text-gray-500" x-text="'(' + tier.multiplier + ')'"></span>
                                        </div>
                                        <div class="flex flex-wrap gap-1 pt-1">
                                            <template x-for="perk in tier.perks" :key="perk">
                                                <span class="text-[9px] px-2 py-0.5 rounded-md bg-white border border-black/10 text-gray-600 font-medium" x-text="perk"></span>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="text-right shrink-0">
                                        <span class="font-display font-black text-sm text-black" x-text="formatCurrency(tier.price)"></span>
                                        <span class="text-[10px] text-gray-500 block font-bold" x-text="tier.gate"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Submit CTA -->
                    <div class="pt-2">
                        <button 
                            type="submit" 
                            class="btn-rose w-full py-4 rounded-2xl font-black text-sm shadow-xl flex items-center justify-center gap-2 hover:scale-[1.01] transition-all cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            <span>{{ $isEdit ? 'Cập Nhật & Tái Tạo Sơ Đồ Khán Đài' : 'Lưu Khán Phòng & Tự Động Sinh Sơ Đồ Ghế' }}</span>
                        </button>
                    </div>

                </div>


                <!-- ========================================================= -->
                <!-- RIGHT COLUMN: LIVE INTERACTIVE SEAT MATRIX EDITOR (7 COLS)-->
                <!-- ========================================================= -->
                <div class="lg:col-span-7 space-y-6">
                    
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border-2 border-gold-antique/60 shadow-xl space-y-6 sticky top-16">
                        
                        <!-- Top Header & Live Counter -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-black/10 pb-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="badge-gold text-[10px] px-3 py-0.5 rounded-full font-black uppercase tracking-wider">
                                        Trình Biên Tập Ghế Trực Quan (Live Matrix Editor)
                                    </span>
                                    <span class="badge-sage text-[10px] px-2.5 py-0.5 rounded-full font-bold">
                                        Bộ Cọ Vẽ &amp; Phân Khu
                                    </span>
                                </div>
                                <h4 class="font-display font-black text-lg text-black mt-1" x-text="roomName || 'Thiết kế sơ đồ khán đài'"></h4>
                            </div>

                            <!-- Actions & Zoom buttons -->
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-amber-900 bg-amber-50 border border-amber-200 px-3 py-1 rounded-full font-black font-mono" x-text="activeSeatCount + ' Ghế thực tế'"></span>
                                <div class="flex items-center bg-[#FAF9F6] border border-black/10 rounded-full p-1 shadow-sm text-xs">
                                    <button type="button" @click="zoomOut()" class="w-7 h-7 rounded-full flex items-center justify-center text-gray-700 hover:bg-white font-bold">&minus;</button>
                                    <span class="px-2 font-mono font-bold text-gray-600" x-text="Math.round(zoomScale * 100) + '%'">100%</span>
                                    <button type="button" @click="zoomIn()" class="w-7 h-7 rounded-full flex items-center justify-center text-gray-700 hover:bg-white font-bold">&#43;</button>
                                    <button type="button" @click="resetZoom()" class="px-2 text-[10px] font-bold text-gray-500 hover:text-black border-l border-black/10">Reset</button>
                                </div>
                            </div>
                        </div>

                        <!-- INTERACTIVE TOOL PALETTE (BỘ CỌ VẼ HẠNG VÉ & LỐI ĐI) -->
                        <div class="p-4 bg-[#FAF9F6] rounded-2xl border border-black/10 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <span class="text-[11px] font-black uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                    <span>🖌️</span>
                                    <span>Chọn Cọ Vẽ (Click vào ghế để áp dụng):</span>
                                </span>
                                <div class="flex items-center gap-2">
                                    <button 
                                        type="button" 
                                        @click="addRow()"
                                        class="px-3 py-1 rounded-xl text-[11px] font-bold bg-white border border-black/15 hover:border-black text-black transition-all flex items-center gap-1 shadow-xs cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5 text-sage-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        <span>+ Thêm Hàng Ghế</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="buildDefaultMatrix(preset)"
                                        class="px-2.5 py-1 rounded-xl text-[11px] font-semibold text-gray-500 hover:text-rose-taupe hover:bg-rose-50 transition-all cursor-pointer"
                                        title="Khôi phục lại sơ đồ mẫu ban đầu của preset"
                                    >
                                        🔄 Đặt lại mẫu
                                    </button>
                                </div>
                            </div>

                            <!-- Tool buttons -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
                                <template x-for="tool in tools" :key="tool.id">
                                    <button 
                                        type="button"
                                        @click="selectedTool = tool.id"
                                        :class="selectedTool === tool.id ? 'ring-2 ring-black bg-white shadow-md scale-105' : 'bg-white/70 hover:bg-white border border-black/5 opacity-80 hover:opacity-100'"
                                        class="p-2 rounded-xl text-center flex flex-col items-center justify-center gap-1 transition-all cursor-pointer"
                                    >
                                        <span class="text-base" x-text="tool.icon"></span>
                                        <span class="text-[10px] font-black leading-tight truncate w-full" x-text="tool.name"></span>
                                        <span class="w-3 h-1.5 rounded-full" :style="'background-color: ' + tool.color"></span>
                                    </button>
                                </template>
                            </div>

                            <p class="text-[11px] text-gray-500 italic pt-1">
                                💡 <strong>Mẹo:</strong> Click vào tên hàng (nhãn chữ cái bên trái) để đổi màu toàn bộ hàng. Click vào cọ <strong>Lối Đi (Aisle)</strong> để khoét khoảng trống đi lại.
                            </p>
                        </div>

                        <!-- SEAT MATRIX CANVAS (KHUNG SOẠN THẢO TRỰC QUAN) -->
                        <div class="overflow-x-auto p-4 bg-gradient-to-b from-[#0F172A] via-[#111827] to-[#020617] rounded-3xl border border-black/20 text-white min-h-[380px]">
                            
                            <div 
                                class="transition-transform duration-200 origin-top min-w-[540px] max-w-4xl mx-auto space-y-4"
                                :style="'transform: scale(' + zoomScale + ')'"
                            >
                                <!-- STAGE BANNER -->
                                <div class="w-full max-w-md mx-auto p-3 bg-gradient-to-r from-obsidian-950 via-obsidian-800 to-obsidian-950 text-white text-center rounded-2xl shadow-md border-2 border-gold-antique/80 relative overflow-hidden mb-6">
                                    <div class="font-display font-black text-xs tracking-widest uppercase text-gold-light">
                                        ★ SÂN KHẤU CHÍNH (MAIN STAGE) ★
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-mono">DÀN ÂM THANH ÁNH SÁNG 360°</span>
                                </div>

                                <!-- MATRIX ROWS -->
                                <div class="space-y-2">
                                    <template x-for="(r, rIdx) in matrixRows" :key="rIdx">
                                        <div class="flex items-center justify-center gap-2 group">
                                            
                                            <!-- Row Label Clickable (Paint Whole Row) -->
                                            <button 
                                                type="button" 
                                                @click="paintRow(rIdx)"
                                                class="w-14 text-right pr-2 text-[10px] font-mono font-bold text-gray-300 hover:text-amber-400 hover:scale-105 transition-all shrink-0 cursor-pointer"
                                                title="Bấm để đổi toàn bộ hàng này thành cọ đang chọn"
                                            >
                                                <span x-text="r.row"></span>
                                                <span class="text-[9px] text-amber-400 opacity-0 group-hover:opacity-100">🖌️</span>
                                            </button>

                                            <!-- Seats in Row -->
                                            <div class="flex items-center gap-1 flex-wrap justify-center">
                                                <template x-for="(s, sIdx) in r.seats" :key="sIdx">
                                                    <button 
                                                        type="button"
                                                        @click="paintSeat(rIdx, sIdx)"
                                                        @mouseenter="hoveredSeat = { row: r.row, number: s.number, type: s.type, is_aisle: s.is_aisle, is_blocked: s.is_blocked }"
                                                        :style="s.is_aisle ? 'background-color: transparent' : ('background-color: ' + getSeatColor(s))"
                                                        :class="{
                                                            'border-dashed border-gray-600 text-gray-500 opacity-30 hover:opacity-100': s.is_aisle,
                                                            'border-2 border-red-500 text-red-200': s.is_blocked,
                                                            'border border-white/20 text-white shadow-xs hover:scale-125 hover:z-10': !s.is_aisle && !s.is_blocked
                                                        }"
                                                        class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[9px] font-black flex items-center justify-center transition-all cursor-pointer select-none"
                                                        :title="s.is_aisle ? 'Lối đi trống (Click để khôi phục ghế)' : (s.is_blocked ? 'Ghế kỹ thuật / Khóa' : (r.row + '-' + s.number + ' · ' + s.type))"
                                                    >
                                                        <span x-show="!s.is_aisle && !s.is_blocked" x-text="s.number"></span>
                                                        <span x-show="s.is_aisle" class="text-[8px] text-gray-500">·</span>
                                                        <span x-show="s.is_blocked" class="text-[8px]">🔒</span>
                                                    </button>
                                                </template>
                                            </div>

                                            <!-- Row Controls (+ seat, - seat, remove row) -->
                                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity pl-2 shrink-0">
                                                <button type="button" @click="addSeatToRow(rIdx)" class="w-5 h-5 rounded-md bg-white/10 hover:bg-white/20 text-white text-[10px] font-bold flex items-center justify-center cursor-pointer" title="Thêm 1 ghế vào hàng này">+</button>
                                                <button type="button" @click="removeSeatFromRow(rIdx)" class="w-5 h-5 rounded-md bg-white/10 hover:bg-white/20 text-white text-[10px] font-bold flex items-center justify-center cursor-pointer" title="Bớt 1 ghế">&minus;</button>
                                                <button type="button" @click="removeRow(rIdx)" class="w-5 h-5 rounded-md bg-red-500/20 hover:bg-red-500 text-red-300 hover:text-white text-[10px] font-bold flex items-center justify-center cursor-pointer" title="Xóa toàn bộ hàng này">&times;</button>
                                            </div>

                                        </div>
                                    </template>
                                </div>

                            </div>
                        </div>

                        <!-- LIVE HOVER INSPECTOR BAR -->
                        <div class="p-3 bg-[#FAF9F6] rounded-2xl border border-black/10 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-500">Đang trỏ vào:</span>
                                <span class="font-black text-black" x-text="hoveredSeat ? (hoveredSeat.is_aisle ? '🚫 Khoảng trống Lối đi' : (hoveredSeat.is_blocked ? '🔒 Ghế Khóa / Kỹ thuật' : (hoveredSeat.row + '-' + hoveredSeat.number + ' (' + hoveredSeat.type + ')'))) : 'Rê chuột vào bất kỳ ghế nào'"></span>
                            </div>
                            <span class="text-[11px] text-gold-dark font-mono font-bold" x-text="'Cọ đang dùng: ' + selectedTool"></span>
                        </div>

                        <!-- REALTIME MATRIX STATISTICS -->
                        <div class="flex flex-wrap items-center justify-between gap-4 text-xs font-semibold pt-2 border-t border-black/10">
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#D4AF37]"></span>
                                <span>SVIP: <strong x-text="matrixStats.svip"></strong></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#C08497]"></span>
                                <span>VIP: <strong x-text="matrixStats.vip"></strong></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#3A5A40]"></span>
                                <span>Cat 1: <strong x-text="matrixStats.cat1"></strong></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#1E293B]"></span>
                                <span>Cat 2: <strong x-text="matrixStats.cat2"></strong></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#E11D48]"></span>
                                <span>Skybox: <strong x-text="matrixStats.skybox"></strong></span>
                            </div>
                            <div class="flex items-center gap-1.5 text-gray-500">
                                <span>Lối đi: <strong x-text="matrixStats.aisles"></strong></span>
                            </div>
                            <div class="flex items-center gap-1.5 text-red-600">
                                <span>Khóa: <strong x-text="matrixStats.blocked"></strong></span>
                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </form>

    </div>
</x-admin-layout>
