<x-admin-layout
    :header="$isEdit ? 'Chỉnh Sửa Sơ Đồ Địa Điểm' : 'Tạo Mới & Thiết Kế Sơ Đồ Địa Điểm'"
    :subtitle="$isEdit ? 'Cấu hình khu vực và chỗ ngồi cho [' . $room->name . ']' : 'Chọn mẫu bố trí theo loại sự kiện, rồi chỉnh khu vực, giá và chỗ ngồi'"
>
    <x-slot:breadcrumb>
        <span class="text-gray-400">Tổng quan</span>
        <span class="text-gray-300">›</span>
        <a href="{{ route('admin.rooms.index') }}" class="text-gray-400 hover:text-black">Khán phòng</a>
        <span class="text-gray-300">›</span>
        <span class="text-[#123D22] font-black">{{ $isEdit ? 'Chỉnh sửa' : 'Tạo mới' }}</span>
    </x-slot:breadcrumb>

    @php
        // Gửi lỗi lên lại: giữ nguyên sơ đồ admin vừa vẽ thay vì quay về mẫu.
        $oldLayout = old('layout') ? json_decode(old('layout'), true) : null;
        $layoutForJs = is_array($oldLayout) && isset($oldLayout['zones'], $oldLayout['rows']) ? $oldLayout : $initialLayout;
    @endphp

    <div
        x-data="adminSeatMapBuilder({
            templates: @js($templates),
            preset: @js(old('layout_preset', $room->layout_preset ?? 'mega_concert')),
            initialLayout: @js($layoutForJs),
            locked: @js($hasShowtimes),
        })"
        class="space-y-6"
    >
        <!-- Header -->
        <div class="relative bg-gradient-to-r from-white via-white to-[#F1F8F2] p-6 rounded-3xl border border-[#E3EAE4] shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="text-[11px] font-black uppercase tracking-wider text-[#123D22] bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full inline-block mb-1">
                    Trình dựng sơ đồ theo khu vực
                </span>
                <h2 class="font-display font-black text-xl text-slate-900">
                    {{ $isEdit ? 'Cấu Hình Sơ Đồ: ' . $room->name : 'Thiết Kế Sơ Đồ Địa Điểm Mới' }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Mỗi địa điểm gồm nhiều khu: ghế đánh số, bàn tiệc hoặc khu đứng / vào cửa tự do. Giá vé = giá suất diễn + phụ thu của khu.
                </p>
            </div>
            <a href="{{ route('admin.rooms.index') }}" class="px-5 py-2.5 rounded-2xl text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 self-start md:self-auto">&larr; Quay lại danh sách</a>
        </div>

        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <div>⚠️ {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <template x-if="locked">
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900">
                Địa điểm đã có suất diễn: bạn chỉ sửa được <strong>tên, giá, màu, cổng, mô tả</strong> của các khu. Vị trí ghế giữ nguyên để không ảnh hưởng vé đã bán — dùng nút <strong>Nhân bản</strong> ở trang danh sách nếu muốn vẽ sơ đồ khác.
            </div>
        </template>

        <form action="{{ $isEdit ? route('admin.rooms.update', $room) : route('admin.rooms.store') }}" method="POST">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif
            <input type="hidden" name="layout_preset" :value="preset">
            <input type="hidden" name="layout" :value="layoutJson">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- CỘT TRÁI: mẫu, thông tin, khu vực -->
                <div class="lg:col-span-5 space-y-6">
                    <!-- 1. Mẫu bố trí -->
                    <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4" :class="locked ? 'opacity-60' : ''">
                        <div class="border-b border-black/10 pb-3">
                            <h3 class="font-display font-black text-base text-black">1. Chọn mẫu bố trí</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Mỗi loại sự kiện một kiểu chỗ ngồi. Chọn mẫu sẽ thay sơ đồ đang vẽ.</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($templates as $key => $template)
                                <button type="button" @click="selectPreset(@js($key))" :disabled="locked"
                                        class="p-3.5 rounded-2xl border-2 text-left flex items-start gap-3 transition-all"
                                        :class="preset === @js($key) ? 'border-[#123D22] bg-emerald-50/60 shadow-sm' : 'border-black/10 bg-[#FAF9F6] hover:border-black/25'">
                                    <span class="text-2xl">{{ $template['icon'] }}</span>
                                    <span>
                                        <span class="font-display font-black text-xs text-black block">{{ $template['label'] }}</span>
                                        <span class="text-[11px] text-gray-500 leading-tight block mt-0.5">{{ $template['subtitle'] }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        <div x-show="preset === 'custom_grid' && !locked" class="p-3 bg-[#FAF9F6] rounded-2xl border border-black/10 flex flex-wrap items-end gap-3 text-xs" style="display: none;">
                            <label class="flex-1 min-w-[6rem]">
                                <span class="block text-gray-500 text-[10px] font-bold mb-1">Số hàng (2–26)</span>
                                <input type="number" x-model="gridRows" min="2" max="26" class="glass-input w-full rounded-xl px-3 py-2 font-mono">
                            </label>
                            <label class="flex-1 min-w-[6rem]">
                                <span class="block text-gray-500 text-[10px] font-bold mb-1">Ghế mỗi hàng (4–40)</span>
                                <input type="number" x-model="gridCols" min="4" max="40" class="glass-input w-full rounded-xl px-3 py-2 font-mono">
                            </label>
                            <button type="button" @click="buildCustomGrid()" class="px-3 py-2 rounded-xl bg-black text-white font-bold">Tạo lưới</button>
                        </div>
                    </div>

                    <!-- 2. Thông tin địa điểm -->
                    <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4 text-xs">
                        <h3 class="font-display font-black text-base text-black border-b border-black/10 pb-3">2. Thông tin địa điểm</h3>
                        <label class="block">
                            <span class="block text-gray-700 font-bold uppercase tracking-wider mb-1.5">Tên địa điểm <span class="text-crimson">*</span></span>
                            <input type="text" name="name" value="{{ old('name', $room->name) }}" required
                                   placeholder="VD: Nhà Hát Thành Phố" class="glass-input w-full rounded-xl px-4 py-2.5 font-semibold">
                        </label>
                        <label class="block">
                            <span class="block text-gray-700 font-bold uppercase tracking-wider mb-1.5">Địa chỉ</span>
                            <input type="text" name="address" value="{{ old('address', $room->address) }}"
                                   placeholder="VD: 07 Công Trường Lam Sơn, Quận 1" class="glass-input w-full rounded-xl px-4 py-2.5">
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="block text-gray-700 font-bold uppercase tracking-wider mb-1.5">Vĩ độ (latitude)</span>
                                <input type="number" step="0.000001" name="latitude" value="{{ old('latitude', $room->latitude) }}"
                                       placeholder="10.8005" class="glass-input w-full rounded-xl px-4 py-2.5 font-mono">
                            </label>
                            <label class="block">
                                <span class="block text-gray-700 font-bold uppercase tracking-wider mb-1.5">Kinh độ (longitude)</span>
                                <input type="number" step="0.000001" name="longitude" value="{{ old('longitude', $room->longitude) }}"
                                       placeholder="106.6658" class="glass-input w-full rounded-xl px-4 py-2.5 font-mono">
                            </label>
                            <p class="col-span-2 text-[11px] text-gray-500">Lấy từ Google Maps: chuột phải vào địa điểm → bấm dòng tọa độ để sao chép. Dùng cho bản đồ và dự báo thời tiết.</p>
                        </div>
                        <label class="block">
                            <span class="block text-gray-700 font-bold uppercase tracking-wider mb-1.5">Sức chứa công bố <span class="text-crimson">*</span></span>
                            <input type="number" name="capacity" value="{{ old('capacity', $room->capacity) }}" min="10" max="50000" required
                                   class="glass-input w-full rounded-xl px-4 py-2.5 font-black font-mono">
                        </label>
                    </div>

                    <!-- 3. Khu vực -->
                    <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-black/10 pb-3">
                            <div>
                                <h3 class="font-display font-black text-base text-black">3. Khu vực &amp; giá</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Phụ thu cộng vào giá suất diễn (có thể âm). Tầng 1 gần sân khấu nhất.</p>
                            </div>
                            <div class="flex gap-1.5" x-show="!locked">
                                <button type="button" @click="addZone('seated')" class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-[#FAF9F6] border border-black/10 hover:border-black">+ Khu ghế</button>
                                <button type="button" @click="addZone('table')" class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-[#FAF9F6] border border-black/10 hover:border-black">+ Khu bàn</button>
                                <button type="button" @click="addZone('standing')" class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-[#FAF9F6] border border-black/10 hover:border-black">+ Khu đứng</button>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <template x-for="zone in zones" :key="zone.code">
                                <div class="p-3.5 rounded-2xl bg-[#FAF9F6] border border-black/10 space-y-2 text-xs" :style="'border-left: 5px solid ' + zone.color">
                                    <div class="flex items-center gap-2">
                                        <input type="color" x-model="zone.color" class="w-8 h-8 rounded-lg border border-black/10 cursor-pointer shrink-0">
                                        <input type="text" x-model="zone.name" maxlength="120" class="glass-input flex-1 rounded-xl px-3 py-1.5 font-bold" placeholder="Tên khu">
                                        <span class="text-[10px] font-mono text-gray-500 shrink-0" x-text="zone.code"></span>
                                        <button type="button" x-show="!locked && zones.length > 1" @click="removeZone(zone.code)" class="w-6 h-6 rounded-md bg-red-50 text-red-600 font-black shrink-0" title="Xóa khu">&times;</button>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <label>
                                            <span class="block text-[10px] text-gray-500 font-bold">Loại</span>
                                            <span class="block font-bold py-1.5" x-text="zone.kind === 'standing' ? 'Khu đứng (số lượng)' : (zone.kind === 'table' ? 'Bàn tiệc' : 'Ghế đánh số')"></span>
                                        </label>
                                        <label>
                                            <span class="block text-[10px] text-gray-500 font-bold">Phụ thu (₫)</span>
                                            <input type="number" x-model="zone.surcharge" step="10000" class="glass-input w-full rounded-lg px-2 py-1.5 font-mono">
                                        </label>
                                        <label>
                                            <span class="block text-[10px] text-gray-500 font-bold">Tầng</span>
                                            <input type="number" x-model="zone.level" min="1" max="6" :disabled="locked" class="glass-input w-full rounded-lg px-2 py-1.5 font-mono">
                                        </label>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label>
                                            <span class="block text-[10px] text-gray-500 font-bold">Cổng / lối vào</span>
                                            <input type="text" x-model="zone.gate" maxlength="80" class="glass-input w-full rounded-lg px-2 py-1.5">
                                        </label>
                                        <label x-show="zone.kind === 'standing'">
                                            <span class="block text-[10px] text-gray-500 font-bold">Sức chứa (chỗ)</span>
                                            <input type="number" x-model="zone.capacity" min="1" max="2000" :disabled="locked" class="glass-input w-full rounded-lg px-2 py-1.5 font-mono">
                                        </label>
                                    </div>
                                    <input type="text" x-model="zone.description" maxlength="500" class="glass-input w-full rounded-lg px-2 py-1.5" placeholder="Mô tả ngắn cho khách">
                                    <input type="text" x-model="zone.perksText" class="glass-input w-full rounded-lg px-2 py-1.5" placeholder="Quyền lợi, cách nhau bằng dấu phẩy">
                                </div>
                            </template>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-4 rounded-2xl font-black text-sm text-white shadow-xl hover:scale-[1.01] transition-all"
                            style="background: linear-gradient(135deg, #123D22 0%, #1e662e 100%);">
                        {{ $isEdit ? 'Lưu thay đổi sơ đồ' : 'Lưu địa điểm & sinh sơ đồ' }}
                    </button>
                </div>

                <!-- CỘT PHẢI: vẽ sơ đồ -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-white rounded-3xl p-6 border-2 border-gold-antique/60 shadow-xl space-y-5 lg:sticky lg:top-16">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-black/10 pb-4">
                            <h4 class="font-display font-black text-lg text-black">Bản vẽ sơ đồ</h4>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="text-amber-900 bg-amber-50 border border-amber-200 px-3 py-1 rounded-full font-black font-mono" x-text="stats.total + ' chỗ'"></span>
                                <div class="flex items-center bg-[#FAF9F6] border border-black/10 rounded-full p-1">
                                    <button type="button" @click="zoomOut()" class="w-7 h-7 rounded-full font-bold">&minus;</button>
                                    <span class="px-2 font-mono font-bold text-gray-600" x-text="Math.round(zoomScale * 100) + '%'"></span>
                                    <button type="button" @click="zoomIn()" class="w-7 h-7 rounded-full font-bold">&#43;</button>
                                    <button type="button" @click="resetZoom()" class="px-2 text-[10px] font-bold text-gray-500 border-l border-black/10">Reset</button>
                                </div>
                            </div>
                        </div>

                        <!-- Bộ cọ: tô ô bằng khu hoặc khoét lối đi -->
                        <div class="p-4 bg-[#FAF9F6] rounded-2xl border border-black/10 space-y-3" x-show="!locked">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-[11px] font-black uppercase tracking-wider text-gray-700">🖌️ Cọ vẽ (bấm vào ô để tô)</span>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="addRow()" class="px-3 py-1 rounded-xl text-[11px] font-bold bg-white border border-black/15 hover:border-black">+ Thêm hàng</button>
                                    <button type="button" @click="resetToTemplate()" class="px-2.5 py-1 rounded-xl text-[11px] font-semibold text-gray-500 hover:text-rose-taupe">🔄 Về mẫu gốc</button>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="zone in pickableZones" :key="zone.code">
                                    <button type="button" @click="selectedTool = zone.code"
                                            :class="selectedTool === zone.code ? 'ring-2 ring-black bg-white shadow-md' : 'bg-white/70 border border-black/5'"
                                            class="px-3 py-1.5 rounded-xl text-[11px] font-black flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-full" :style="'background-color: ' + zone.color"></span>
                                        <span x-text="zone.name"></span>
                                    </button>
                                </template>
                                <button type="button" @click="selectedTool = 'aisle'"
                                        :class="selectedTool === 'aisle' ? 'ring-2 ring-black bg-white shadow-md' : 'bg-white/70 border border-black/5'"
                                        class="px-3 py-1.5 rounded-xl text-[11px] font-black">🚫 Lối đi / ô trống</button>
                            </div>
                            <p class="text-[11px] text-gray-500 italic">Bấm nhãn hàng bên trái để tô cả hàng. Với khu bàn, mỗi hàng là một bàn và mỗi ô là một ghế quanh bàn.</p>
                        </div>

                        <div class="overflow-x-auto p-4 bg-gradient-to-b from-[#0F172A] via-[#111827] to-[#020617] rounded-3xl text-white min-h-[320px]">
                            <div class="transition-transform duration-200 origin-top min-w-max mx-auto space-y-2" :style="'transform: scale(' + zoomScale + ')'">
                                <div class="w-full max-w-md mx-auto p-2.5 text-center rounded-2xl border-2 border-gold-antique/80 text-[11px] font-black tracking-widest uppercase text-gold-light mb-4">★ Sân khấu ★</div>

                                <template x-for="(row, rIdx) in rows" :key="rIdx">
                                    <div class="flex items-center gap-2 group">
                                        <button type="button" @click="paintRow(rIdx)" x-show="!locked" class="w-6 text-[11px] hover:scale-125 shrink-0" title="Tô cả hàng bằng cọ đang chọn">🖌️</button>
                                        <input type="text" x-model="row.label" :readonly="locked" maxlength="12" class="w-16 bg-white/10 border border-white/10 rounded px-1 py-0.5 text-[10px] font-mono text-white" title="Nhãn hàng (sửa được)">
                                        <div class="flex items-center gap-1">
                                            <template x-for="(cell, cIdx) in row.cells" :key="cIdx">
                                                <button type="button" @click="paintCell(rIdx, cIdx)"
                                                        @mouseenter="hoveredCell = cell.aisle ? 'Lối đi' : (row.label + '-' + seatNumber(rIdx, cIdx) + ' · ' + zoneName(cell.zone))"
                                                        :style="cell.aisle ? '' : 'background-color: ' + zoneColor(cell.zone)"
                                                        :class="cell.aisle ? 'border border-dashed border-gray-600 text-gray-600' : 'border border-white/20 text-white hover:scale-125'"
                                                        class="w-6 h-6 rounded-md text-[9px] font-black flex items-center justify-center transition-transform select-none">
                                                    <span x-text="cell.aisle ? '·' : seatNumber(rIdx, cIdx)"></span>
                                                </button>
                                            </template>
                                        </div>
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 pl-1 shrink-0" x-show="!locked">
                                            <button type="button" @click="addCell(rIdx)" class="w-5 h-5 rounded bg-white/10 text-[10px] font-bold" title="Thêm 1 ô">+</button>
                                            <button type="button" @click="removeCell(rIdx)" class="w-5 h-5 rounded bg-white/10 text-[10px] font-bold" title="Bớt 1 ô">&minus;</button>
                                            <button type="button" @click="removeRow(rIdx)" class="w-5 h-5 rounded bg-red-500/30 text-[10px] font-bold" title="Xóa hàng">&times;</button>
                                        </div>
                                    </div>
                                </template>

                                <div class="pt-4 flex flex-wrap justify-center gap-3">
                                    <template x-for="zone in standingZones" :key="zone.code">
                                        <div class="px-4 py-3 rounded-2xl border-2 border-dashed text-center" :style="'border-color: ' + zone.color">
                                            <span class="text-xs font-black block" x-text="zone.name"></span>
                                            <span class="text-[10px] text-gray-300 font-mono" x-text="'Khu đứng · ' + (zone.capacity || 0) + ' chỗ'"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-[#FAF9F6] rounded-2xl border border-black/10 text-xs flex items-center justify-between">
                            <span class="font-bold text-gray-500">Đang trỏ vào:</span>
                            <span class="font-black text-black" x-text="hoveredCell || 'Rê chuột vào ô bất kỳ'"></span>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-xs font-semibold pt-2 border-t border-black/10">
                            <template x-for="zone in zones" :key="zone.code">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-full" :style="'background-color: ' + zone.color"></span>
                                    <span x-text="zone.name + ': '"></span><strong x-text="stats.counts[zone.code] || 0"></strong>
                                </span>
                            </template>
                            <span class="text-gray-500">Lối đi: <strong x-text="stats.aisles"></strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-admin-layout>
