<x-admin-layout
    :header="$isEdit ? 'Chỉnh Sửa Khán Phòng Theo Khu' : 'Thiết Kế Khán Phòng Theo Khu'"
    :subtitle="$isEdit ? 'Quản lý sức chứa và sơ đồ từng khu vực' : 'Chọn loại địa điểm, sau đó cấu hình từng khu bán vé'"
>
    <x-slot:breadcrumb>
        <a href="{{ route('admin.rooms.index') }}" class="text-gray-500 hover:text-black">Khán phòng</a>
        <span class="text-gray-300">›</span>
        <span class="font-bold text-emerald-900">{{ $isEdit ? 'Chỉnh sửa khu vực' : 'Tạo mới' }}</span>
    </x-slot:breadcrumb>

    <div x-data="venueZoneBuilder({
        preset: @js($room->layout_preset ?: 'outdoor_concert'),
        zones: @js($initialZones),
        templates: @js(collect($presetOptions)->mapWithKeys(fn ($preset) => [$preset => \App\Support\VenuePresets::zones($preset)])),
    })" class="space-y-5" @pointermove.window="moveZoneCanvas($event)" @pointerup.window="endZoneCanvasDrag()" @pointercancel.window="endZoneCanvasDrag()">
        <h2 class="sr-only">Thiết Kế &amp; Sinh Sơ Đồ Khán Đài Mới</h2>
        <form method="POST" action="{{ $isEdit ? route('admin.rooms.update', $room) : route('admin.rooms.store') }}" class="space-y-5">
            @csrf
            @if($isEdit) @method('PUT') @endif
            <input type="hidden" name="diagram_mode" value="zones">
            <input type="hidden" name="layout_preset" :value="preset">
            <input type="hidden" name="zones" :value="JSON.stringify(zonesPayload)">

            <section class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
                <h2 class="font-black text-lg">Thông tin địa điểm</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-semibold">Tên khán phòng
                        <input name="name" required maxlength="255" value="{{ old('name', $room->name) }}" class="mt-1 w-full rounded-xl border-gray-300">
                    </label>
                    <label class="text-sm font-semibold">Địa chỉ
                        <input name="address" maxlength="500" value="{{ old('address', $room->address) }}" class="mt-1 w-full rounded-xl border-gray-300">
                    </label>
                    <label class="text-sm font-semibold">Mẫu khởi tạo
                        <select x-model="preset" @change="loadPreset()" class="mt-1 w-full rounded-xl border-gray-300">
                            <option value="outdoor_concert">Sân khấu ngoài trời</option>
                            <option value="theater">Nhà hát</option>
                            <option value="fixed_hall">Hội trường ghế cố định</option>
                            <option value="conference_tables">Hội nghị theo bàn</option>
                            <option value="admission_only">Vé vào cửa</option>
                        </select>
                    </label>
                    <div class="rounded-xl bg-emerald-50 p-3 text-sm">
                        <span class="text-emerald-900">Sức chứa tổng (tính tự động)</span>
                        <strong class="block text-2xl text-emerald-950" x-text="totalCapacity"></strong>
                    </div>
                </div>
                @error('zones') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-800">Xem trước mặt bằng</p>
                    <h2 class="mt-1 font-black text-lg">Bố cục các khu vé</h2>
                    <p class="text-sm text-gray-500">Kéo khu để đặt vị trí, kéo góc dưới để đổi kích thước. Chọn từng khu để chỉnh hình dạng chính xác.</p>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <input type="search" x-model="zoneSearch" placeholder="Tìm nhanh theo mã hoặc tên khu..." class="w-full rounded-xl border-gray-300 text-sm sm:max-w-sm">
                    <span class="text-xs font-semibold text-gray-500" x-text="`${filteredZones.length} / ${zones.length} khu`"></span>
                </div>
                <div x-ref="zoneCanvas" class="relative mx-auto aspect-[16/10] w-full max-w-5xl overflow-hidden rounded-3xl border border-stone-200 bg-[#F7F6F2] shadow-inner touch-none"
                    style="background-image:linear-gradient(rgba(100,116,139,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(100,116,139,.08) 1px,transparent 1px);background-size:4% 4%">
                    <div class="absolute z-10 flex items-center justify-center rounded-b-2xl border-b-4 border-amber-400 bg-slate-900 px-2 text-center text-[10px] font-black uppercase tracking-[0.3em] text-white shadow"
                        style="left:35%;top:3%;width:30%;height:11%;border-radius:50% 50% 12px 12px / 45% 45% 12px 12px">Sân khấu</div>
                    <template x-for="(zone, index) in filteredZones" :key="'preview-' + zone._key">
                        <div role="button" tabindex="0" @click="selectedZoneKey = zone._key" @keydown.enter="selectedZoneKey = zone._key"
                            @pointerdown.stop.prevent="startZoneMove(zone, $event)"
                            :style="zoneCanvasStyle(zone)"
                            class="absolute flex cursor-move select-none flex-col items-center justify-center overflow-hidden border-2 p-2 text-center shadow-md transition-colors touch-none"
                            :class="previewPalette(index) + ' ' + (selectedZoneKey === zone._key ? 'z-20 ring-4 ring-slate-900 ring-offset-2' : 'z-0 hover:brightness-95')"
                            :title="zone.name + ' · kéo để di chuyển · kéo góc để đổi kích thước'">
                            <span class="text-[10px] font-black uppercase tracking-wider" x-text="zone.code || ('KHU ' + (index + 1))"></span>
                            <strong class="mt-1 max-w-full text-xs leading-tight sm:text-sm" x-text="zone.name || ('Khu ' + (index + 1))"></strong>
                            <span class="mt-1 text-[10px] font-semibold" x-text="zoneSummary(zone)"></span>
                            <button type="button" @pointerdown.stop.prevent="startZoneResize(zone, $event)" @click.stop
                                class="absolute bottom-0 right-0 h-5 w-5 cursor-nwse-resize rounded-tl-lg bg-slate-900/80 text-xs font-black text-white" aria-label="Đổi kích thước khu">↘</button>
                            <template x-if="zone.canvas.shape === 'polygon' && selectedZoneKey === zone._key">
                                <template x-for="(point, pointIndex) in zone.canvas.points" :key="'handle-' + zone._key + '-' + pointIndex">
                                    <button type="button" @pointerdown.stop.prevent="startVertexMove(zone, pointIndex)" @click.stop
                                        :style="{left:point.x+'%',top:point.y+'%'}"
                                        class="absolute z-30 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-sky-950 bg-white shadow ring-2 ring-white/70" :aria-label="'Di chuyển đỉnh ' + (pointIndex + 1)"></button>
                                </template>
                            </template>
                        </div>
                        </template>
                    <p x-show="filteredZones.length === 0" class="absolute inset-x-4 bottom-4 rounded-xl bg-amber-50 p-4 text-center text-sm text-amber-900" x-text="zones.length ? 'Không tìm thấy khu phù hợp.' : 'Thêm khu để xem sơ đồ.'"></p>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 class="font-black text-lg">Chỉnh sửa khu đang chọn</h2><p class="text-sm text-gray-500">Chọn một khu trên mặt bằng để chỉnh; không cần cuộn qua toàn bộ danh sách.</p></div>
                    <button type="button" @click="addZone()" class="rounded-xl bg-emerald-800 px-4 py-2 text-sm font-bold text-white">+ Thêm khu</button>
                </div>
                <template x-if="selectedZone">
                    <article class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <h3 class="font-black" x-text="'Khu ' + (selectedZoneIndex + 1) + ' / ' + zones.length"></h3>
                                <select x-model="selectedZoneKey" class="rounded-lg border-gray-300 py-1.5 text-xs font-bold" aria-label="Chuyển nhanh đến khu">
                                    <template x-for="(zone, index) in zones" :key="'select-' + zone._key"><option :value="zone._key" x-text="zone.code + ' · ' + zone.name"></option></template>
                                </select>
                            </div>
                            <button type="button" @click="removeSelectedZone()" class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-bold text-red-700">Xóa khu này</button>
                        </div>
                        <div class="grid gap-3 md:grid-cols-4">
                            <label class="text-xs font-bold">Mã khu<input x-model="selectedZone.code" maxlength="6" class="mt-1 w-full rounded-lg border-gray-300"></label>
                            <label class="text-xs font-bold">Tên khu<input x-model="selectedZone.name" maxlength="100" class="mt-1 w-full rounded-lg border-gray-300"></label>
                            <label class="text-xs font-bold">Cách bán vé
                                <select x-model="selectedZone.kind" class="mt-1 w-full rounded-lg border-gray-300">
                                    <option value="seated">Chọn ghế</option><option value="table">Theo bàn</option>
                                    <option value="standing">Vé đứng theo số lượng</option><option value="admission">Vé vào cửa theo số lượng</option>
                                </select>
                            </label>
                            <label class="text-xs font-bold">Hạng ghế / vé
                                <select x-model="selectedZone.seat_type" class="mt-1 w-full rounded-lg border-gray-300">
                                    <option value="svip_diamond">SVIP</option><option value="vip_gold">VIP</option>
                                    <option value="cat1_stand">Cat 1</option><option value="cat2_wings">Cat 2</option>
                                    <option value="skybox_suite">Skybox</option><option value="standing_pit">Vé đứng</option>
                                </select>
                            </label>
                            <label class="text-xs font-bold">Hình dạng
                                <select x-model="selectedZone.canvas.shape" @change="ensurePolygonPoints(selectedZone)" class="mt-1 w-full rounded-lg border-gray-300">
                                    <option value="rectangle">Chữ nhật</option><option value="rounded">Bo góc</option>
                                    <option value="ellipse">Oval</option><option value="trapezoid">Hình thang</option><option value="polygon">Đa giác tự do</option>
                                </select>
                            </label>
                        </div>
                        <div x-show="selectedZone.canvas.shape === 'polygon'" class="rounded-xl border border-sky-200 bg-sky-50 p-3 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-bold text-sky-950">Kéo các nút tròn trên khu để nắn theo địa hình, hoặc nhập tọa độ đỉnh (%).</p>
                                <button type="button" @click="addPolygonPoint(selectedZone)" :disabled="selectedZone.canvas.points.length >= 12" class="shrink-0 rounded-lg bg-sky-900 px-3 py-1.5 text-xs font-bold text-white disabled:opacity-40">+ Thêm đỉnh</button>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <template x-for="(point, pointIndex) in selectedZone.canvas.points" :key="'point-' + selectedZone._key + '-' + pointIndex">
                                    <div class="flex items-center gap-2 rounded-lg bg-white p-2 text-xs">
                                        <strong class="shrink-0" x-text="'Đỉnh ' + (pointIndex + 1)"></strong>
                                        <label class="flex items-center gap-1">X<input type="number" min="0" max="100" x-model.number="point.x" @change="clampPolygonPoint(point)" class="w-16 rounded border-gray-300 px-1 py-1"></label>
                                        <label class="flex items-center gap-1">Y<input type="number" min="0" max="100" x-model.number="point.y" @change="clampPolygonPoint(point)" class="w-16 rounded border-gray-300 px-1 py-1"></label>
                                        <button type="button" @click="removePolygonPoint(selectedZone, pointIndex)" :disabled="selectedZone.canvas.points.length <= 3" class="ml-auto text-red-700 disabled:opacity-30" aria-label="Xóa đỉnh">×</button>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 rounded-xl bg-white p-3 sm:grid-cols-4">
                            <label class="text-xs font-bold">Vị trí ngang (%)<input type="number" min="0" max="100" step="0.5" x-model.number="selectedZone.canvas.x" @change="clampZoneCanvas(selectedZone)" class="mt-1 w-full rounded-lg border-gray-300"></label>
                            <label class="text-xs font-bold">Vị trí dọc (%)<input type="number" min="0" max="100" step="0.5" x-model.number="selectedZone.canvas.y" @change="clampZoneCanvas(selectedZone)" class="mt-1 w-full rounded-lg border-gray-300"></label>
                            <label class="text-xs font-bold">Chiều rộng (%)<input type="number" min="5" max="100" step="0.5" x-model.number="selectedZone.canvas.width" @change="clampZoneCanvas(selectedZone)" class="mt-1 w-full rounded-lg border-gray-300"></label>
                            <label class="text-xs font-bold">Chiều cao (%)<input type="number" min="5" max="100" step="0.5" x-model.number="selectedZone.canvas.height" @change="clampZoneCanvas(selectedZone)" class="mt-1 w-full rounded-lg border-gray-300"></label>
                        </div>
                        <div x-show="selectedZone.kind === 'seated' || selectedZone.kind === 'table'" class="rounded-xl bg-gray-50 p-3 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-xs font-bold text-gray-700" x-text="selectedZone.kind === 'table' ? 'Sơ đồ bàn — chỉnh số chỗ cho từng bàn.' : 'Sơ đồ ghế — thêm hoặc bớt ghế riêng theo từng hàng.'"></p>
                                    <p class="mt-1 text-xs text-gray-500">Sức chứa đang chỉnh: <strong x-text="seatCapacity(selectedZone)"></strong> chỗ</p>
                                </div>
                                <button type="button" @click="addSeatRow()" :disabled="selectedZone.layout.length >= (selectedZone.kind === 'table' ? 100 : 26)" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white disabled:opacity-40" x-text="selectedZone.kind === 'table' ? '+ Thêm bàn' : '+ Thêm hàng'"></button>
                            </div>
                            <div class="max-h-[55vh] space-y-2 overflow-auto rounded-xl bg-slate-950 p-3 text-white">
                                <template x-for="(row, rowIndex) in selectedZone.layout" :key="row.row">
                                    <div class="flex items-center gap-2 rounded-lg bg-white/5 p-2">
                                        <span class="w-12 shrink-0 text-right text-[10px] font-black text-amber-300" x-text="row.row"></span>
                                        <div class="flex min-h-8 flex-1 flex-wrap items-center gap-1">
                                            <template x-for="seat in row.seats" :key="seat.number">
                                                <span class="flex h-7 min-w-7 items-center justify-center rounded-md bg-emerald-700 px-1 text-[10px] font-black" x-text="seat.number"></span>
                                            </template>
                                            <span x-show="row.seats.length === 0" class="text-[10px] text-white/50">Chưa có ghế</span>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1">
                                            <button type="button" @click="addSeatToRow(rowIndex)" class="h-7 w-7 rounded-md bg-white/15 text-sm font-black hover:bg-white/25" :aria-label="selectedZone.kind === 'table' ? 'Thêm chỗ vào bàn' : 'Thêm ghế vào hàng'">+</button>
                                            <button type="button" @click="removeSeatFromRow(rowIndex)" :disabled="row.seats.length <= 1" class="h-7 w-7 rounded-md bg-white/15 text-sm font-black hover:bg-white/25 disabled:opacity-30" :aria-label="selectedZone.kind === 'table' ? 'Bớt chỗ khỏi bàn' : 'Bớt ghế khỏi hàng'">−</button>
                                            <button type="button" @click="removeSeatRow(rowIndex)" :disabled="selectedZone.layout.length <= 1" class="h-7 w-7 rounded-md bg-red-500/20 text-sm font-black text-red-200 hover:bg-red-500/40 disabled:opacity-30" :aria-label="selectedZone.kind === 'table' ? 'Xóa bàn' : 'Xóa hàng'">×</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div x-show="selectedZone.kind === 'standing' || selectedZone.kind === 'admission'" class="rounded-xl bg-amber-50 p-3">
                            <label class="text-xs font-bold">Sức chứa tối đa<input type="number" min="1" x-model.number="selectedZone.capacity" class="mt-1 w-full rounded-lg border-gray-300 md:max-w-xs"></label>
                            <p class="mt-1 text-xs text-gray-600">Vé được quản lý theo số lượng; không sinh ghế ảo.</p>
                        </div>
                    </article>
                </template>
                <p x-show="zones.length === 0" class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Thêm ít nhất một khu để tiếp tục.</p>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.rooms.index') }}" class="rounded-xl border px-5 py-3 text-sm font-bold">Hủy</a>
                <button type="submit" class="rounded-xl bg-emerald-800 px-6 py-3 text-sm font-bold text-white">Lưu khán phòng</button>
            </div>
        </form>
    </div>
    <script>
        function venueZoneBuilder(config) {
            const defaultCanvas = (index, total) => {
                total = Math.max(1, total);
                const columns = Math.max(1, Math.min(10, Math.ceil(Math.sqrt(total * 1.4))));
                const rows = Math.ceil(total / columns);
                const horizontalGap = 1.2, verticalGap = 1.5;
                const width = (90 - (columns - 1) * horizontalGap) / columns;
                const height = Math.max(5, Math.min(19, (77 - (rows - 1) * verticalGap) / rows));
                return { x: 5 + (index % columns) * (width + horizontalGap), y: 18 + Math.floor(index / columns) * (height + verticalGap), width, height, shape: 'rounded' };
            };
            const normalize = (zone, index, total = config.zones?.length || 1) => {
                const kind = zone.kind ?? 'seated';
                const rows = Number(zone.rows ?? 6), cols = Number(zone.cols ?? 10);
                const tables = Number(zone.tables ?? 10), seatsPerTable = Number(zone.seats_per_table ?? 8);
                const layout = Array.isArray(zone.layout) && zone.layout.length
                    ? zone.layout.map((row, rowIndex) => ({
                        row: row.row ?? (kind === 'table' ? `T${String(rowIndex + 1).padStart(2, '0')}` : String.fromCharCode(65 + rowIndex)),
                        seats: (row.seats ?? []).map((seat, seatIndex) => ({ number: Number(seat.number ?? seatIndex + 1), type: seat.type ?? zone.seat_type ?? 'cat1_stand' })),
                    }))
                    : Array.from({ length: Number(kind === 'table' ? tables : rows) }, (_, rowIndex) => ({
                        row: kind === 'table' ? `T${String(rowIndex + 1).padStart(2, '0')}` : String.fromCharCode(65 + rowIndex),
                        seats: Array.from({ length: Number(kind === 'table' ? seatsPerTable : cols) }, (_, seatIndex) => ({ number: seatIndex + 1, type: zone.seat_type ?? 'cat1_stand' })),
                    }));
                const canvas = { ...defaultCanvas(index, total), ...(zone.meta?.canvas ?? {}) };
                canvas.points = Array.isArray(canvas.points) && canvas.points.length >= 3
                    ? canvas.points.slice(0, 12).map(point => ({ x: Number(point.x) || 0, y: Number(point.y) || 0 }))
                    : [];
                return {
                    _key: `${Date.now()}-${index}-${Math.random()}`,
                    code: zone.code ?? `K${index + 1}`, name: zone.name ?? `Khu ${index + 1}`,
                    kind, seat_type: zone.seat_type ?? 'cat1_stand',
                    ticket_tier: zone.ticket_tier ?? '', capacity: Number(zone.capacity ?? 0),
                    rows, cols, tables, seats_per_table: seatsPerTable, layout,
                    meta: zone.meta ?? {}, canvas,
                    sort_order: index,
                };
            };
            return {
                preset: config.preset,
                zones: (config.zones ?? []).map((zone, index, zones) => normalize(zone, index, zones.length)),
                selectedZoneKey: null,
                draggedZoneKey: null,
                dragOverZoneKey: null,
                zonePointer: null,
                zoneSearch: '',
                templates: config.templates,
                init() {
                    if (!this.zones.length) this.loadPreset();
                    else this.selectedZoneKey = this.zones[0]._key;
                    this.syncSortOrder();
                    this.zones.forEach(zone => this.clampZoneCanvas(zone));
                },
                loadPreset() {
                    const templateZones = this.templates[this.preset] ?? [];
                    this.zones = templateZones.map((zone, index) => normalize(zone, index, templateZones.length));
                    this.selectedZoneKey = this.zones[0]?._key ?? null;
                    this.syncSortOrder();
                },
                addZone() {
                    const zone = normalize({}, this.zones.length, this.zones.length + 1);
                    this.zones.push(zone);
                    this.selectedZoneKey = zone._key;
                    this.syncSortOrder();
                },
                removeSelectedZone() {
                    const index = this.selectedZoneIndex;
                    if (index < 0) return;
                    this.zones.splice(index, 1);
                    this.selectedZoneKey = this.zones[Math.min(index, this.zones.length - 1)]?._key ?? null;
                    this.syncSortOrder();
                },
                addSeatRow() {
                    const layout = this.selectedZone?.layout;
                    if (!layout || layout.length >= (this.selectedZone.kind === 'table' ? 100 : 26)) return;
                    const labels = layout.map(row => row.row);
                    let label;
                    if (this.selectedZone.kind === 'table') {
                        let number = 1;
                        while (labels.includes(`T${String(number).padStart(2, '0')}`)) number++;
                        label = `T${String(number).padStart(2, '0')}`;
                    } else {
                        label = [...'ABCDEFGHIJKLMNOPQRSTUVWXYZ'].find(candidate => !labels.includes(candidate));
                    }
                    if (!label) return;
                    const template = layout[layout.length - 1];
                    const count = Math.max(1, template?.seats.length ?? 10);
                    layout.push({
                        row: label,
                        seats: Array.from({ length: count }, (_, index) => ({ number: index + 1, type: this.selectedZone.seat_type })),
                    });
                },
                removeSeatRow(rowIndex) {
                    if (!this.selectedZone || this.selectedZone.layout.length <= 1) return;
                    this.selectedZone.layout.splice(rowIndex, 1);
                },
                addSeatToRow(rowIndex) {
                    const row = this.selectedZone?.layout[rowIndex];
                    if (!row) return;
                    const nextNumber = Math.max(0, ...row.seats.map(seat => Number(seat.number) || 0)) + 1;
                    row.seats.push({ number: nextNumber, type: this.selectedZone.seat_type });
                },
                removeSeatFromRow(rowIndex) {
                    const seats = this.selectedZone?.layout[rowIndex]?.seats;
                    if (seats?.length > 1) seats.pop();
                },
                startZoneMove(zone, event) {
                    const rect = this.$refs.zoneCanvas.getBoundingClientRect();
                    this.selectedZoneKey = zone._key;
                    this.zonePointer = {
                        key: zone._key,
                        mode: 'move',
                        offsetX: (event.clientX - rect.left) / rect.width * 100 - zone.canvas.x,
                        offsetY: (event.clientY - rect.top) / rect.height * 100 - zone.canvas.y,
                    };
                },
                startZoneResize(zone, event) {
                    const rect = this.$refs.zoneCanvas.getBoundingClientRect();
                    this.selectedZoneKey = zone._key;
                    this.zonePointer = { key: zone._key, mode: 'resize', rect };
                },
                startVertexMove(zone, pointIndex) {
                    this.selectedZoneKey = zone._key;
                    this.zonePointer = { key: zone._key, mode: 'vertex', pointIndex };
                },
                ensurePolygonPoints(zone) {
                    if (zone.canvas.shape === 'polygon' && zone.canvas.points.length < 3) {
                        zone.canvas.points = [{ x: 12, y: 0 }, { x: 88, y: 0 }, { x: 100, y: 100 }, { x: 0, y: 100 }];
                    }
                },
                addPolygonPoint(zone) {
                    if (zone.canvas.points.length >= 12) return;
                    const points = zone.canvas.points;
                    const last = points[points.length - 1] ?? { x: 50, y: 50 };
                    const first = points[0] ?? { x: 50, y: 50 };
                    points.push({ x: Math.round((last.x + first.x) / 2), y: Math.round((last.y + first.y) / 2) });
                },
                removePolygonPoint(zone, pointIndex) {
                    if (zone.canvas.points.length > 3) zone.canvas.points.splice(pointIndex, 1);
                },
                clampPolygonPoint(point) {
                    point.x = Math.max(0, Math.min(100, Number(point.x) || 0));
                    point.y = Math.max(0, Math.min(100, Number(point.y) || 0));
                },
                moveZoneCanvas(event) {
                    if (!this.zonePointer) return;
                    const zone = this.zones.find(item => item._key === this.zonePointer.key);
                    const rect = this.$refs.zoneCanvas.getBoundingClientRect();
                    if (!zone || !rect.width || !rect.height) return;
                    const x = (event.clientX - rect.left) / rect.width * 100;
                    const y = (event.clientY - rect.top) / rect.height * 100;
                    if (this.zonePointer.mode === 'vertex') {
                        const point = zone.canvas.points[this.zonePointer.pointIndex];
                        if (!point) return;
                        point.x = Math.max(0, Math.min(100, (x - zone.canvas.x) / zone.canvas.width * 100));
                        point.y = Math.max(0, Math.min(100, (y - zone.canvas.y) / zone.canvas.height * 100));
                    } else if (this.zonePointer.mode === 'resize') {
                        zone.canvas.width = x - zone.canvas.x;
                        zone.canvas.height = y - zone.canvas.y;
                    } else {
                        zone.canvas.x = x - this.zonePointer.offsetX;
                        zone.canvas.y = y - this.zonePointer.offsetY;
                    }
                    this.clampZoneCanvas(zone);
                },
                endZoneCanvasDrag() { this.zonePointer = null; },
                clampZoneCanvas(zone) {
                    zone.canvas.x = Math.max(0, Math.min(95, Number(zone.canvas.x) || 0));
                    zone.canvas.y = Math.max(0, Math.min(95, Number(zone.canvas.y) || 0));
                    zone.canvas.width = Math.max(5, Math.min(100 - zone.canvas.x, Number(zone.canvas.width) || 5));
                    zone.canvas.height = Math.max(5, Math.min(100 - zone.canvas.y, Number(zone.canvas.height) || 5));
                },
                zoneCanvasStyle(zone) {
                    const style = {
                        left: `${zone.canvas.x}%`, top: `${zone.canvas.y}%`,
                        width: `${zone.canvas.width}%`, height: `${zone.canvas.height}%`,
                        borderRadius: zone.canvas.shape === 'ellipse' ? '50%' : (zone.canvas.shape === 'rounded' ? '1rem' : '0.25rem'),
                        clipPath: zone.canvas.shape === 'trapezoid' ? 'polygon(12% 0, 88% 0, 100% 100%, 0 100%)' : (zone.canvas.shape === 'polygon' ? `polygon(${zone.canvas.points.map(point => `${point.x}% ${point.y}%`).join(',')})` : 'none'),
                    };
                    return style;
                },
                syncSortOrder() { this.zones.forEach((zone, index) => { zone.sort_order = index; }); },
                get selectedZone() { return this.zones.find(zone => zone._key === this.selectedZoneKey) ?? null; },
                get selectedZoneIndex() { return this.zones.findIndex(zone => zone._key === this.selectedZoneKey); },
                get filteredZones() {
                    const query = this.zoneSearch.trim().toLocaleLowerCase();
                    return query ? this.zones.filter(zone => `${zone.code} ${zone.name}`.toLocaleLowerCase().includes(query)) : this.zones;
                },
                get zonesPayload() {
                    return this.zones.map(zone => {
                        const { _key, canvas, ...data } = zone;
                        return { ...data, meta: { ...(data.meta ?? {}), canvas } };
                    });
                },
                seatCapacity(zone) {
                    if (Array.isArray(zone.layout)) return zone.layout.reduce((count, row) => count + row.seats.length, 0);
                    return Math.max(0, Number(zone.kind === 'table' ? zone.tables * zone.seats_per_table : zone.rows * zone.cols) || 0);
                },
                previewPalette(index) {
                    return ['border-rose-400 bg-rose-100 text-rose-950', 'border-amber-400 bg-amber-100 text-amber-950', 'border-sky-400 bg-sky-100 text-sky-950', 'border-emerald-400 bg-emerald-100 text-emerald-950', 'border-violet-400 bg-violet-100 text-violet-950', 'border-orange-400 bg-orange-100 text-orange-950'][index % 6];
                },
                zoneSummary(zone) {
                    if (['seated', 'table'].includes(zone.kind)) return `${this.seatCapacity(zone)} ghế`;
                    return `${Math.max(0, Number(zone.capacity) || 0)} vé`;
                },
                get totalCapacity() {
                    return this.zones.reduce((total, zone) => total + (['seated', 'table'].includes(zone.kind) ? this.seatCapacity(zone) : Math.max(0, Number(zone.capacity) || 0)), 0);
                },
            };
        }
    </script>
</x-admin-layout>
