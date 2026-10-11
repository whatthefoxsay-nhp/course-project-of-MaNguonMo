<x-admin-layout 
    :header="$isEdit ? 'Chỉnh Sửa & Tái Tạo Sơ Đồ Khán Phòng' : 'Tạo Mới & Thiết Kế Sơ Đồ Khán Đài'"
    :subtitle="$isEdit ? 'Cấu hình và tái tạo sơ đồ ghế ngồi cho [' . $room->name . ']' : 'Thiết kế sơ đồ khán đài và cấu hình phân vùng giá vé cho khán phòng mới'"
>
    <x-slot:breadcrumb>
        <span class="text-gray-400">Tổng quan</span>
        <span class="text-gray-300">›</span>
        <a href="{{ route('admin.rooms.index') }}" class="text-gray-400 hover:text-black">Khán phòng</a>
        <span class="text-gray-300">›</span>
        <span class="text-[#123D22] font-black">{{ $isEdit ? 'Chỉnh sửa' : 'Tạo mới' }}</span>
    </x-slot:breadcrumb>

    @php
        $preset = $room->layout_preset ?? 'mega_concert';
        $config = $room->seat_config ?? [];
        $basePrice = $config['base_price'] ?? 250000;
        $rows = $config['rows'] ?? 10;
        $cols = $config['cols'] ?? 14;
        $vipRatio = $config['vip_ratio'] ?? 30;
        $svipRatio = $config['svip_ratio'] ?? 15;
        $stageType = $config['stage_type'] ?? ($preset === 'sayhi_concert_d2' ? 'runway_y' : (($preset === 'sayhi_concert' || $preset === 'mega_concert') ? 'runway_t' : 'standard'));
        $standingZones = $config['standing_zones'] ?? ($preset === 'sayhi_concert_d2' ? [
            ['code' => 'FZ-A', 'name' => 'Super Fan Fanzone A', 'capacity' => 30],
            ['code' => 'FZ-B', 'name' => 'Super Fan Fanzone B', 'capacity' => 30],
        ] : (($preset === 'sayhi_concert' || $preset === 'mega_concert') ? [
            ['code' => 'GA-A', 'name' => 'Khu Đứng Fanzone A', 'capacity' => 20],
            ['code' => 'GA-B', 'name' => 'Khu Đứng Fanzone B', 'capacity' => 20],
        ] : []));
    @endphp

    <div 
        x-data="adminSeatMapBuilder({
            preset: '{{ $preset }}',
            roomName: '{{ addslashes($room->name ?? '') }}',
            roomAddress: '{{ addslashes($room->address ?? '') }}',
            capacity: {{ $room->capacity ?? 252 }},
            basePrice: {{ $basePrice }},
            rows: {{ $rows }},
            cols: {{ $cols }},
            vipRatio: {{ $vipRatio }},
            svipRatio: {{ $svipRatio }},
            stageType: '{{ $stageType }}',
            standingZones: @js($standingZones),
            initialRows: @js($initialRows ?? [])
        })"
        class="space-y-6"
    >
        <!-- Header Banner -->
        <div class="relative bg-gradient-to-r from-white via-white to-[#F1F8F2] p-6 rounded-3xl border border-[#E3EAE4] shadow-sm overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Decorative SVG Watermark -->
            <svg class="absolute right-4 -bottom-8 w-44 h-44 text-[#123D22] opacity-[0.06] pointer-events-none transform -rotate-12" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>

            <div class="relative z-10 flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-[#123D22] flex items-center justify-center shadow-xs shrink-0">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <span class="text-[11px] font-black uppercase tracking-wider text-[#123D22] bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full inline-block mb-1">
                        Trình Sinh Sơ Đồ &amp; Hạng Vé Tự Động
                    </span>
                    <h2 class="font-display font-black text-xl text-slate-900">
                        {{ $isEdit ? 'Cấu Hình & Tái Tạo Sơ Đồ: ' . $room->name : 'Thiết Kế & Sinh Sơ Đồ Khán Đài Mới' }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Lựa chọn mẫu khán đài hoặc ma trận tùy chỉnh. Hệ thống sẽ tự động tạo sơ đồ trực quan và đồng bộ sang giao diện đặt vé người dùng.
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.rooms.index') }}" class="relative z-10 px-5 py-2.5 rounded-2xl text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors flex items-center gap-1.5 self-start md:self-auto">
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
            <input type="hidden" name="stage_type" :value="stageType">
            <input type="hidden" name="standing_zones" :value="JSON.stringify(standingZones)">
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
                            <!-- Preset 0: Anh Trai Say Hi Concert Đêm 2 -->
                            <div 
                                @click="selectPreset('sayhi_concert_d2')"
                                class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5 relative overflow-hidden"
                                :class="preset === 'sayhi_concert_d2' ? 'border-amber-400 bg-amber-50/70 shadow-md ring-2 ring-amber-300' : 'border-black/10 bg-[#FAF9F6] hover:border-black/20'"
                            >
                                <span class="text-2xl">🌟</span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-display font-black text-xs text-black flex items-center gap-1.5">
                                            <span>Live Concert Anh Trai Say Hi - Đêm 2</span>
                                            <span class="text-[9px] bg-red-600 text-white font-black px-1.5 py-0.2 rounded uppercase">Vạn Phúc City</span>
                                        </h4>
                                        <span class="badge-gold text-[9px] px-2 py-0.5 rounded-full font-bold">Y-Stage Runway</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Sân khấu Runway chữ Y (Y-Stage Catwalk), 2 cụm Super Fanzone A/B ôm sát cánh gà, SVIP Diamond ôm trọn ngã ba sân khấu, VIP Gold, CAT 1-3, Sky Lounge.</p>
                                </div>
                            </div>

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

                            <!-- Preset 2: Anh Trai Say Hi Concert -->
                            <div 
                                @click="selectPreset('sayhi_concert')"
                                class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                :class="preset === 'sayhi_concert' ? 'border-rose-400 bg-rose-50/60 shadow-sm ring-2 ring-rose-300' : 'border-black/10 bg-[#FAF9F6] hover:border-black/20'"
                            >
                                <span class="text-2xl">🎤</span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-display font-black text-xs text-black">Live Concert Anh Trai "Say Hi" - Đêm 1</h4>
                                        <span class="badge-rose text-[9px] px-2 py-0.5 rounded-full font-bold">SVĐ QK7</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Sân khấu Runway T-Stage mở rộng, Super Fan Fanzone Say Hi A/B, SVIP Diamond, VIP Gold, Khán đài CAT 1-3, Sky Lounge.</p>
                                </div>
                            </div>

                            <!-- Preset 3: Theater Hall -->
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

                            <!-- Preset 4: Convention Center -->
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

                            <!-- Preset 5: Custom Grid -->
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

                    <!-- 1.5 Custom Stage Blueprint & Standing Fanzone Card -->
                    <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4">
                        <div class="border-b border-black/10 pb-3 flex items-center justify-between">
                            <div>
                                <h3 class="font-display font-black text-base text-black flex items-center gap-2">
                                    <span>2. Kiểu Dáng Sân Khấu &amp; Khu Đứng Fanzone</span>
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">Tùy biến cấu trúc sàn catwalk &amp; số lượng vé đứng thực tế:</p>
                            </div>
                            <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-bold">Customizable</span>
                        </div>

                        <!-- Stage Blueprint Selector -->
                        <div class="space-y-2">
                            <label class="block text-gray-700 font-bold uppercase tracking-wider text-[11px]">
                                Kiểu Sân Khấu Catwalk (Stage Blueprint)
                            </label>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <button 
                                    type="button" 
                                    @click="setStageType('runway_y')"
                                    :class="stageType === 'runway_y' ? 'border-amber-400 bg-amber-50 text-amber-950 ring-2 ring-amber-300 font-black' : 'border-black/10 bg-white hover:border-black/30 text-gray-700 font-medium'"
                                    class="p-2.5 rounded-xl border flex items-center gap-2 transition-all cursor-pointer text-left"
                                >
                                    <span class="text-lg">🔱</span>
                                    <div>
                                        <div class="font-bold">Runway Chữ Y</div>
                                        <div class="text-[10px] text-gray-500 leading-tight">Y-Catwalk (Anh Trai Đêm 2)</div>
                                    </div>
                                </button>

                                <button 
                                    type="button" 
                                    @click="setStageType('runway_t')"
                                    :class="stageType === 'runway_t' ? 'border-amber-400 bg-amber-50 text-amber-950 ring-2 ring-amber-300 font-black' : 'border-black/10 bg-white hover:border-black/30 text-gray-700 font-medium'"
                                    class="p-2.5 rounded-xl border flex items-center gap-2 transition-all cursor-pointer text-left"
                                >
                                    <span class="text-lg">⚡</span>
                                    <div>
                                        <div class="font-bold">Runway Chữ T</div>
                                        <div class="text-[10px] text-gray-500 leading-tight">T-Stage Runway nối B-Stage</div>
                                    </div>
                                </button>

                                <button 
                                    type="button" 
                                    @click="setStageType('dual_runway')"
                                    :class="stageType === 'dual_runway' ? 'border-amber-400 bg-amber-50 text-amber-950 ring-2 ring-amber-300 font-black' : 'border-black/10 bg-white hover:border-black/30 text-gray-700 font-medium'"
                                    class="p-2.5 rounded-xl border flex items-center gap-2 transition-all cursor-pointer text-left"
                                >
                                    <span class="text-lg">🛤️</span>
                                    <div>
                                        <div class="font-bold">Hai Cánh Song Song</div>
                                        <div class="text-[10px] text-gray-500 leading-tight">Dual Catwalk 2 bên</div>
                                    </div>
                                </button>

                                <button 
                                    type="button" 
                                    @click="setStageType('standard')"
                                    :class="stageType === 'standard' ? 'border-amber-400 bg-amber-50 text-amber-950 ring-2 ring-amber-300 font-black' : 'border-black/10 bg-white hover:border-black/30 text-gray-700 font-medium'"
                                    class="p-2.5 rounded-xl border flex items-center gap-2 transition-all cursor-pointer text-left"
                                >
                                    <span class="text-lg">🏛️</span>
                                    <div>
                                        <div class="font-bold">Sân Khấu Chuẩn</div>
                                        <div class="text-[10px] text-gray-500 leading-tight">Proscenium cổ điển</div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Standing Fanzone / Pit Manager -->
                        <div class="space-y-3 pt-2 border-t border-black/10">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="block text-gray-700 font-bold uppercase tracking-wider text-[11px]">
                                        Khu Vực Vé Đứng (Standing Pits / Fanzones)
                                    </label>
                                    <span class="text-[10px] text-gray-500">Tổng vé đứng: <strong class="text-amber-800" x-text="totalStandingCapacity"></strong> vé</span>
                                </div>
                                <button 
                                    type="button" 
                                    @click="addStandingZone()"
                                    class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-amber-50 border border-amber-300 text-amber-900 hover:bg-amber-100 transition-all flex items-center gap-1 cursor-pointer"
                                >
                                    <span>+ Thêm Khu Đứng</span>
                                </button>
                            </div>

                            <div class="space-y-2">
                                <template x-for="(zone, zIdx) in standingZones" :key="zIdx">
                                    <div class="p-2.5 rounded-xl bg-[#FAF9F6] border border-black/10 flex items-center gap-2">
                                        <input 
                                            type="text" 
                                            x-model="zone.code" 
                                            placeholder="Mã (FZ-A)" 
                                            class="w-20 px-2 py-1.5 text-xs font-mono font-bold rounded-lg border border-black/15 bg-white"
                                        >
                                        <input 
                                            type="text" 
                                            x-model="zone.name" 
                                            placeholder="Tên khu (Fanzone A)" 
                                            class="flex-1 px-2.5 py-1.5 text-xs rounded-lg border border-black/15 bg-white font-medium"
                                        >
                                        <div class="flex items-center gap-1">
                                            <input 
                                                type="number" 
                                                x-model.number="zone.capacity" 
                                                @input="syncCapacity()"
                                                min="0" 
                                                max="500" 
                                                placeholder="SL" 
                                                class="w-16 px-2 py-1.5 text-xs font-mono font-bold text-center rounded-lg border border-black/15 bg-amber-50/50 text-amber-950"
                                            >
                                            <span class="text-[10px] text-gray-400 font-bold">chỗ</span>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="removeStandingZone(zIdx)"
                                            class="w-7 h-7 rounded-lg text-red-500 hover:bg-red-50 flex items-center justify-center font-black text-xs transition-colors cursor-pointer"
                                            title="Xóa khu đứng này"
                                        >
                                            &times;
                                        </button>
                                    </div>
                                </template>
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
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-gray-700 font-bold uppercase tracking-wider text-[11px]">
                                            Sức Chứa Khán Giả <span class="text-crimson">*</span>
                                        </label>
                                        <span class="text-[9px] text-emerald-800 font-bold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                            Khớp 100% sơ đồ
                                        </span>
                                    </div>
                                    <input 
                                        type="number" 
                                        name="capacity" 
                                        x-model="capacity" 
                                        class="glass-input w-full rounded-xl px-4 py-2.5 font-black text-xs font-mono bg-amber-50/30"
                                        required
                                    >
                                    <p class="text-[10px] text-gray-500 mt-1 leading-tight">
                                        <span x-text="activeSeatCount"></span> ghế khán đài<span x-show="totalStandingCapacity > 0"> + <span x-text="totalStandingCapacity"></span> chỗ đứng Fanzone</span> (Tự động cập nhật khi vẽ ghế).
                                    </p>
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
                            class="w-full py-4 rounded-2xl font-black text-sm text-white shadow-xl flex items-center justify-center gap-2 hover:scale-[1.01] hover:shadow-2xl transition-all cursor-pointer"
                            style="background: linear-gradient(135deg, #123D22 0%, #1e662e 100%);"
                        >
                            <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
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

                        <!-- LARGE EVENT ZONE-BASED BOOKING MODE BANNER -->
                        <div x-show="['mega_concert', 'sayhi_concert', 'sayhi_concert_d2'].includes(preset)" class="p-4 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent rounded-2xl border border-amber-300/60 flex items-start gap-3">
                            <span class="text-2xl">🏟️</span>
                            <div class="flex-1 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-display font-black text-amber-950 text-sm">Mô Hình Quản Lý Theo Phân Khu (Zone &amp; Quota Mode)</span>
                                    <span class="badge-gold text-[9px] px-2 py-0.2 rounded-full font-bold">Sự kiện quy mô lớn</span>
                                </div>
                                <p class="text-gray-600 mt-1 leading-relaxed">
                                    Đối với sự kiện quy mô lớn (Live Concert / Sân vận động), khán giả sẽ <strong>chọn khu vực ngồi / khu vực đứng (Zone)</strong> và <strong>số lượng vé</strong> thay vì chọn từng ghế chi tiết để tối ưu hiệu năng và tránh nghẽn vé. Toàn bộ cấu hình sàn catwalk, số lượng vé đứng Fanzone và phân bổ hạng vé được quản lý tại bảng bên trái.
                                </p>
                            </div>
                        </div>

                        <!-- THEATER & CONVENTION DETAILED SEAT GRID BANNER -->
                        <div x-show="['theater_hall', 'convention_center', 'custom_grid'].includes(preset)" class="p-4 bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent rounded-2xl border border-emerald-300/60 flex items-start gap-3">
                            <span class="text-2xl">🎭</span>
                            <div class="flex-1 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-display font-black text-emerald-950 text-sm">Chế Độ Chỉnh Sửa Ghế Chi Tiết (Detailed Seat Matrix)</span>
                                    <span class="badge-sage text-[9px] px-2 py-0.2 rounded-full font-bold">Hòa nhạc &amp; Hội thảo</span>
                                </div>
                                <p class="text-gray-600 mt-1 leading-relaxed">
                                    Dành cho Hòa nhạc thính phòng, Nhà hát nghệ thuật &amp; Hội nghị: Sự kiện yêu cầu khán giả <strong>chọn chính xác từng số ghế cụ thể</strong> (Hàng - Số ghế). Bạn có toàn quyền dùng cọ vẽ hạng vé, thêm/bớt hàng, chỉnh từng ghế, đục lối đi hoặc khóa ghế kỹ thuật bên dưới.
                                </p>
                            </div>
                        </div>

                        <!-- ZONE SUMMARY CARDS FOR LARGE CONCERTS -->
                        <div x-show="['mega_concert', 'sayhi_concert', 'sayhi_concert_d2'].includes(preset)" class="p-4 bg-[#FAF9F6] rounded-2xl border border-black/10 space-y-3">
                            <div class="flex items-center justify-between border-b border-black/10 pb-2">
                                <span class="text-[11px] font-black uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                    <span>🎫</span>
                                    <span>Danh Sách Phân Khu Ngồi &amp; Vé Đứng (Khách Hàng Chọn Theo Khu)</span>
                                </span>
                                <span class="text-[10px] text-amber-800 font-bold bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                    Bán theo Zone Quota
                                </span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
                                <template x-for="tier in generatedTiers" :key="tier.key">
                                    <div class="p-3 rounded-xl bg-white border border-black/10 shadow-xs flex flex-col justify-between space-y-1.5">
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="font-black text-[11px] truncate text-black" x-text="tier.name"></span>
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="'background-color: ' + tier.color"></span>
                                        </div>
                                        <div class="flex items-center justify-between text-[10px]">
                                            <span class="font-bold text-gray-500" x-text="tier.gate"></span>
                                            <span class="font-mono font-black text-crimson" x-text="formatCurrency(tier.price)"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- INTERACTIVE TOOL PALETTE (CHỈ DÀNH CHO HÒA NHẠC, HỘI THẢO & GRID TÙY CHỈNH) -->
                        <div x-show="['theater_hall', 'convention_center', 'custom_grid'].includes(preset)" class="p-4 bg-[#FAF9F6] rounded-2xl border border-black/10 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <span class="text-[11px] font-black uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                    <span>🖌️</span>
                                    <span>Bộ Cọ Vẽ Ghế (Click vào ghế để áp dụng):</span>
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

                            <!-- QUICK SMART GENERATORS (CÔNG CỤ TẠO NHANH BỐ CỤC) -->
                            <div class="pt-2 border-t border-black/10 flex flex-wrap items-center gap-1.5">
                                <span class="text-[10px] font-black uppercase text-gray-500 mr-1">Tạo Nhanh Lối Đi:</span>
                                <button 
                                    type="button" 
                                    @click="generateCenterAisle()"
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-white hover:bg-gray-100 text-gray-700 border border-black/15 transition-all flex items-center gap-1 cursor-pointer"
                                    title="Mở lối đi giữa xuyên suốt các hàng"
                                >
                                    <span>🛣️ Mở Lối Đi Giữa</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="generateSideAisles()"
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-white hover:bg-gray-100 text-gray-700 border border-black/15 transition-all flex items-center gap-1 cursor-pointer"
                                    title="Mở 2 lối đi phân chia 3 block khán đài"
                                >
                                    <span>↔️ Mở 2 Lối Đi Hông</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="restoreAllSeats()"
                                    class="px-2 py-1 rounded-lg text-[10px] font-bold bg-gray-50 hover:bg-gray-100 text-gray-500 border border-black/10 transition-all flex items-center gap-1 cursor-pointer"
                                    title="Hủy mọi lối đi và khôi phục ghế ngồi"
                                >
                                    <span>🔄 Khôi Phục Ghế</span>
                                </button>
                            </div>
                        </div>

                        <!-- SEAT MATRIX CANVAS (KHUNG SOẠN THẢO TRỰC QUAN) -->
                        <div class="overflow-x-auto p-4 bg-gradient-to-b from-[#0F172A] via-[#111827] to-[#020617] rounded-3xl border border-black/20 text-white min-h-[380px]">
                            
                            <div 
                                class="transition-transform duration-200 origin-top min-w-[540px] max-w-4xl mx-auto space-y-4"
                                :style="'transform: scale(' + zoomScale + ')'"
                            >
                                <!-- DYNAMIC STAGE BLUEPRINT VISUAL -->
                                <div class="w-full max-w-xl mx-auto space-y-2 mb-6">
                                    <!-- Main Stage Header -->
                                    <div class="p-3 bg-gradient-to-r from-obsidian-950 via-obsidian-800 to-obsidian-950 text-white text-center rounded-2xl shadow-lg border-2 border-amber-400 relative overflow-hidden">
                                        <div class="font-display font-black text-xs tracking-widest uppercase text-amber-300 flex items-center justify-center gap-2">
                                            <span>★</span>
                                            <span>SÂN KHẤU CHÍNH (MAIN STAGE)</span>
                                            <span x-show="stageType === 'runway_y'" class="text-[9px] px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-300 border border-amber-500/40 font-mono">Y-STAGE CATWALK</span>
                                            <span x-show="stageType === 'runway_t'" class="text-[9px] px-1.5 py-0.2 rounded bg-rose-500/20 text-rose-300 border border-rose-500/40 font-mono">T-STAGE RUNWAY</span>
                                            <span>★</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-mono flex items-center justify-center gap-3 mt-0.5">
                                            <span>HỆ THỐNG MÀN HÌNH LED NGHỆ THUẬT</span>
                                            <span>•</span>
                                            <span>DÀN ÂM THANH ÁNH SÁNG 360°</span>
                                        </div>
                                    </div>

                                    <!-- Extended Runway & Fanzone Pits Visuals -->
                                    <div x-show="stageType === 'runway_y'" class="flex items-center justify-center gap-2 pt-1">
                                        <!-- Left Fanzone -->
                                        <div class="px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/40 text-amber-300 text-center text-[10px] font-bold shadow-xs flex-1 max-w-[130px]">
                                            <div class="text-[9px] text-amber-400/80 uppercase">SUPER FANZONE</div>
                                            <div class="font-black text-xs text-amber-200">KHU A (TẢ)</div>
                                            <div class="text-[9px] font-mono text-gray-300" x-text="(standingZones[0]?.capacity || 30) + ' VÉ ĐỨNG'"></div>
                                        </div>

                                        <!-- Y-Runway Catwalk Spine -->
                                        <div class="flex flex-col items-center justify-center px-4 py-1.5 bg-gradient-to-b from-amber-500/30 to-amber-600/10 border border-amber-400/60 rounded-xl">
                                            <span class="text-xs">🔱</span>
                                            <span class="text-[9px] font-black tracking-widest text-amber-300 uppercase">RUNWAY CHỮ Y</span>
                                            <span class="text-[8px] text-gray-400 font-mono">Cầu nối ngã 3 sàn diễn</span>
                                        </div>

                                        <!-- Right Fanzone -->
                                        <div class="px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/40 text-amber-300 text-center text-[10px] font-bold shadow-xs flex-1 max-w-[130px]">
                                            <div class="text-[9px] text-amber-400/80 uppercase">SUPER FANZONE</div>
                                            <div class="font-black text-xs text-amber-200">KHU B (HỮU)</div>
                                            <div class="text-[9px] font-mono text-gray-300" x-text="(standingZones[1]?.capacity || 30) + ' VÉ ĐỨNG'"></div>
                                        </div>
                                    </div>

                                    <div x-show="stageType === 'runway_t'" class="flex items-center justify-center gap-3 pt-1">
                                        <!-- Left Fanzone GA -->
                                        <div class="px-3 py-1.5 rounded-xl bg-rose-500/10 border border-rose-500/40 text-rose-300 text-center text-[10px] font-bold flex-1 max-w-[120px]">
                                            <div class="text-[9px] text-rose-400 uppercase">FANZONE A</div>
                                            <div class="text-[9px] font-mono text-gray-300" x-text="(standingZones[0]?.capacity || 20) + ' vé đứng'"></div>
                                        </div>

                                        <!-- T-Runway -->
                                        <div class="flex flex-col items-center justify-center px-4 py-1 bg-gradient-to-b from-rose-500/30 to-rose-600/10 border border-rose-400/60 rounded-xl">
                                            <span class="text-xs">⚡</span>
                                            <span class="text-[9px] font-black tracking-widest text-rose-300 uppercase">CATWALK T-RUNWAY ➔ B-STAGE</span>
                                        </div>

                                        <!-- Right Fanzone GA -->
                                        <div class="px-3 py-1.5 rounded-xl bg-rose-500/10 border border-rose-500/40 text-rose-300 text-center text-[10px] font-bold flex-1 max-w-[120px]">
                                            <div class="text-[9px] text-rose-400 uppercase">FANZONE B</div>
                                            <div class="text-[9px] font-mono text-gray-300" x-text="(standingZones[1]?.capacity || 20) + ' vé đứng'"></div>
                                        </div>
                                    </div>

                                    <div x-show="stageType === 'dual_runway'" class="flex items-center justify-between px-10 pt-1">
                                        <div class="px-3 py-1 rounded-xl bg-amber-500/10 border border-amber-400/40 text-amber-300 text-[10px] font-bold">
                                            🛤️ SÀN CATWALK TẢ
                                        </div>
                                        <div class="px-3 py-1 rounded-xl bg-amber-500/10 border border-amber-400/40 text-amber-300 text-[10px] font-bold">
                                            🛤️ SÀN CATWALK HỮU
                                        </div>
                                    </div>
                                </div>

                                <!-- MATRIX ROWS -->
                                <div class="space-y-2">
                                    <template x-for="(r, rIdx) in matrixRows" :key="rIdx">
                                        <div class="flex items-center justify-center gap-2 group">
                                            
                                            <!-- Row Label Clickable (Paint Whole Row) & Rename -->
                                            <div class="w-16 flex items-center justify-end gap-1 shrink-0">
                                                <button 
                                                    type="button" 
                                                    @click="paintRow(rIdx)"
                                                    class="text-right text-[10px] font-mono font-bold text-gray-300 hover:text-amber-400 hover:scale-105 transition-all cursor-pointer"
                                                    title="Bấm để đổi toàn bộ hàng này thành cọ đang chọn"
                                                >
                                                    <span x-text="r.row"></span>
                                                    <span class="text-[9px] text-amber-400 opacity-0 group-hover:opacity-100">🖌️</span>
                                                </button>
                                                <button 
                                                    type="button" 
                                                    @click.stop="promptRenameRow(rIdx)"
                                                    class="text-[9px] text-gray-500 hover:text-amber-300 opacity-0 group-hover:opacity-100 transition-opacity p-0.5 cursor-pointer"
                                                    title="Đổi tên nhãn hàng ghế này (VD: SVIP-D2-1, CAT1-A...)"
                                                >
                                                    ✏️
                                                </button>
                                            </div>

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
