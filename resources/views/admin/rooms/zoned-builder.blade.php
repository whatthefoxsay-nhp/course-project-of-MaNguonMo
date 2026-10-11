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
    })" class="space-y-5">
        <h2 class="sr-only">Thiết Kế &amp; Sinh Sơ Đồ Khán Đài Mới</h2>
        <form method="POST" action="{{ $isEdit ? route('admin.rooms.update', $room) : route('admin.rooms.store') }}" class="space-y-5">
            @csrf
            @if($isEdit) @method('PUT') @endif
            <input type="hidden" name="layout_preset" :value="preset">
            <input type="hidden" name="zones" :value="JSON.stringify(zones)">

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
                <div class="flex items-center justify-between gap-3">
                    <div><h2 class="font-black text-lg">Các khu trong khán phòng</h2><p class="text-sm text-gray-500">Mỗi khu có sơ đồ ghế hoặc tồn kho vé riêng.</p></div>
                    <button type="button" @click="addZone()" class="rounded-xl bg-emerald-800 px-4 py-2 text-sm font-bold text-white">+ Thêm khu</button>
                </div>
                <template x-for="(zone, index) in zones" :key="zone._key">
                    <article class="rounded-2xl border border-gray-200 p-4 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-black" x-text="'Khu ' + (index + 1)"></h3>
                            <div class="flex gap-2">
                                <button type="button" @click="move(index, -1)" :disabled="index === 0" class="rounded-lg border px-3 py-1 text-sm disabled:opacity-30" aria-label="Di chuyển lên">↑</button>
                                <button type="button" @click="move(index, 1)" :disabled="index === zones.length - 1" class="rounded-lg border px-3 py-1 text-sm disabled:opacity-30" aria-label="Di chuyển xuống">↓</button>
                                <button type="button" @click="removeZone(index)" class="rounded-lg border border-red-200 px-3 py-1 text-sm text-red-700">Xóa khu</button>
                            </div>
                        </div>
                        <div class="grid gap-3 md:grid-cols-4">
                            <label class="text-xs font-bold">Mã khu<input x-model="zone.code" maxlength="6" class="mt-1 w-full rounded-lg border-gray-300"></label>
                            <label class="text-xs font-bold">Tên khu<input x-model="zone.name" maxlength="100" class="mt-1 w-full rounded-lg border-gray-300"></label>
                            <label class="text-xs font-bold">Cách bán vé
                                <select x-model="zone.kind" class="mt-1 w-full rounded-lg border-gray-300">
                                    <option value="seated">Chọn ghế</option><option value="table">Theo bàn</option>
                                    <option value="standing">Vé đứng theo số lượng</option><option value="admission">Vé vào cửa theo số lượng</option>
                                </select>
                            </label>
                            <label class="text-xs font-bold">Hạng ghế / vé
                                <select x-model="zone.seat_type" class="mt-1 w-full rounded-lg border-gray-300">
                                    <option value="svip_diamond">SVIP</option><option value="vip_gold">VIP</option>
                                    <option value="cat1_stand">Cat 1</option><option value="cat2_wings">Cat 2</option>
                                    <option value="skybox_suite">Skybox</option><option value="standing_pit">Vé đứng</option>
                                </select>
                            </label>
                        </div>
                        <div x-show="zone.kind === 'seated'" class="grid gap-3 md:grid-cols-3 rounded-xl bg-gray-50 p-3">
                            <p class="md:col-span-3 text-xs font-bold text-gray-600">Sơ đồ ghế ngồi — có thể chỉnh số hàng và số ghế mỗi hàng.</p>
                            <label class="text-xs font-bold">Số hàng<input type="number" min="1" max="26" x-model.number="zone.rows" class="mt-1 w-full rounded-lg border-gray-300"></label>
                            <label class="text-xs font-bold">Ghế mỗi hàng<input type="number" min="1" max="40" x-model.number="zone.cols" class="mt-1 w-full rounded-lg border-gray-300"></label>
                            <p class="self-end text-sm">Số ghế: <strong x-text="seatCapacity(zone)"></strong></p>
                        </div>
                        <div x-show="zone.kind === 'table'" class="grid gap-3 md:grid-cols-3 rounded-xl bg-gray-50 p-3">
                            <label class="text-xs font-bold">Số bàn<input type="number" min="1" max="100" x-model.number="zone.tables" class="mt-1 w-full rounded-lg border-gray-300"></label>
                            <label class="text-xs font-bold">Chỗ mỗi bàn<input type="number" min="1" max="20" x-model.number="zone.seats_per_table" class="mt-1 w-full rounded-lg border-gray-300"></label>
                            <p class="self-end text-sm">Số chỗ: <strong x-text="seatCapacity(zone)"></strong></p>
                        </div>
                        <div x-show="zone.kind === 'standing' || zone.kind === 'admission'" class="rounded-xl bg-amber-50 p-3">
                            <label class="text-xs font-bold">Sức chứa tối đa<input type="number" min="1" x-model.number="zone.capacity" class="mt-1 w-full rounded-lg border-gray-300 md:max-w-xs"></label>
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
            const normalize = (zone, index) => ({
                _key: `${Date.now()}-${index}-${Math.random()}`,
                code: zone.code ?? `K${index + 1}`, name: zone.name ?? `Khu ${index + 1}`,
                kind: zone.kind ?? 'seated', seat_type: zone.seat_type ?? 'cat1_stand',
                ticket_tier: zone.ticket_tier ?? '', capacity: Number(zone.capacity ?? 0),
                rows: Number(zone.rows ?? 6), cols: Number(zone.cols ?? 10),
                tables: Number(zone.tables ?? 10), seats_per_table: Number(zone.seats_per_table ?? 8),
                sort_order: index,
            });
            return {
                preset: config.preset,
                zones: (config.zones ?? []).map(normalize),
                templates: config.templates,
                init() { if (!this.zones.length) this.loadPreset(); },
                loadPreset() { this.zones = (this.templates[this.preset] ?? []).map(normalize); },
                addZone() { this.zones.push(normalize({}, this.zones.length)); },
                removeZone(index) { this.zones.splice(index, 1); },
                move(index, direction) {
                    const next = index + direction;
                    if (next < 0 || next >= this.zones.length) return;
                    [this.zones[index], this.zones[next]] = [this.zones[next], this.zones[index]];
                },
                seatCapacity(zone) { return Math.max(0, Number(zone.kind === 'table' ? zone.tables * zone.seats_per_table : zone.rows * zone.cols) || 0); },
                get totalCapacity() {
                    return this.zones.reduce((total, zone) => total + (['seated', 'table'].includes(zone.kind) ? this.seatCapacity(zone) : Math.max(0, Number(zone.capacity) || 0)), 0);
                },
            };
        }
    </script>
</x-admin-layout>
