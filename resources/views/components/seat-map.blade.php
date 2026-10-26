@props(['showtime', 'movie', 'seatMap'])

@php
    $isSeated = $seatMap['is_seated'];
    $levels = $seatMap['levels'];
    $allZones = collect($levels)->flatten(1)->values();
    // Thông tin khu cho JS (giá, sức chứa còn lại) — khu ảo của ghế cũ không có id nên bỏ qua.
    $zoneMeta = $allZones->filter(fn ($zone) => $zone['id'] !== null)->mapWithKeys(fn ($zone) => [
        $zone['id'] => ['name' => $zone['name'], 'price' => $zone['price'], 'kind' => $zone['kind'], 'color' => $zone['color'], 'available' => $zone['available']],
    ]);
@endphp

<div
    x-data="seatBookingManager({
        zones: @js($zoneMeta),
        holdUrl: @js(route('showtimes.holds.store', $showtime->id)),
        statusUrl: @js(route('showtimes.seat-status', $showtime->id)),
        loginUrl: @js(route('login')),
        pollStatus: @js($isSeated),
    })"
    class="w-full space-y-8"
>
    @if ($isSeated)
        <!-- Header: địa điểm, zoom, lọc khu -->
        <div class="bg-white rounded-3xl p-4 sm:p-6 border border-black/10 shadow-sm space-y-4">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider">
                            Sơ đồ {{ $showtime->room->preset_label }}
                        </span>
                        <span class="text-xs text-gray-500 font-semibold">
                            {{ $showtime->room->name }} · còn {{ number_format($seatMap['available_total']) }} chỗ
                        </span>
                    </div>
                    <h3 class="font-display font-black text-xl text-black mt-1">Chọn Khu Vực &amp; Vị Trí Trực Quan</h3>
                </div>

                <div class="flex items-center gap-2 self-end lg:self-auto text-xs">
                    <div class="flex items-center bg-[#FAF9F6] border border-black/10 rounded-full p-1 shadow-sm">
                        <button type="button" @click="zoomOut()" class="w-7 h-7 rounded-full flex items-center justify-center text-gray-700 hover:bg-white" title="Thu nhỏ">&minus;</button>
                        <span class="px-2 font-mono font-bold text-[11px] text-gray-600" x-text="Math.round(zoomScale * 100) + '%'">100%</span>
                        <button type="button" @click="zoomIn()" class="w-7 h-7 rounded-full flex items-center justify-center text-gray-700 hover:bg-white" title="Phóng to">&#43;</button>
                        <button type="button" @click="resetZoom()" class="px-2.5 py-0.5 text-[10px] font-bold text-gray-500 hover:text-black border-l border-black/10">Reset</button>
                    </div>
                    <button type="button" x-show="totalTicketCount > 0" @click="clearAll()"
                            class="px-3.5 py-1.5 rounded-full text-rose-taupe hover:bg-rose-50 text-xs font-bold border border-rose-200">
                        Xóa chọn
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto pb-2 border-t border-black/5 pt-3">
                <button type="button" @click="setZoneFilter('all')"
                        :class="activeZoneFilter === 'all' ? 'bg-black text-white shadow-md' : 'bg-[#FAF9F6] text-gray-700 border border-black/5'"
                        class="px-4 py-2 rounded-full text-xs font-bold whitespace-nowrap">Tất cả khu</button>
                @foreach ($allZones as $zone)
                    <button type="button" @click="setZoneFilter(@js($zone['code']))"
                            :class="activeZoneFilter === @js($zone['code']) ? 'bg-black text-white shadow-md' : 'bg-[#FAF9F6] text-gray-700 border border-black/5'"
                            class="px-3.5 py-2 rounded-full text-xs font-bold whitespace-nowrap flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $zone['color'] }}"></span>
                        <span>{{ $zone['name'] }}</span>
                        <span class="text-[10px] font-mono opacity-80">({{ number_format($zone['price']) }}₫)</span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Sơ đồ: sân khấu trên cùng, các khu xếp theo tầng; khu cùng tầng nằm cạnh nhau -->
        <div class="relative bg-gradient-to-b from-[#111827] via-[#0F172A] to-[#020617] rounded-3xl p-4 sm:p-10 border-2 border-black/40 shadow-2xl text-white">
            <div class="absolute inset-0 pointer-events-none opacity-20 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:24px_24px] rounded-3xl"></div>

            <div class="relative overflow-x-auto">
                <div :style="`transform: scale(${zoomScale}); transform-origin: top center; transition: transform 0.25s;`"
                     class="pb-8 space-y-8 min-w-max mx-auto flex flex-col items-center">
                    <x-seat-map.stage :preset="$seatMap['preset']" />

                    @foreach ($levels as $level => $zones)
                        <div class="w-full flex flex-wrap justify-center items-start gap-4 sm:gap-6 {{ $loop->first ? 'pt-6' : '' }}">
                            @foreach ($zones as $zone)
                                <div class="transition-opacity duration-300" :class="isZoneDimmed(@js($zone['code'])) ? 'opacity-20' : ''">
                                    @if ($zone['kind'] === 'standing')
                                        <x-seat-map.zone-standing :zone="$zone" />
                                    @elseif ($zone['kind'] === 'table')
                                        <x-seat-map.zone-table :zone="$zone" />
                                    @else
                                        <x-seat-map.zone-seated :zone="$zone" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Chú thích trạng thái + thông tin ghế khi rê chuột -->
            <div class="relative w-full max-w-2xl mx-auto mt-6 pt-4 border-t border-white/10 pointer-events-none">
                <div class="bg-black/85 rounded-2xl border border-white/15 p-3.5 text-xs min-h-[82px] flex items-center">
                    <div x-show="hoveredSeat === null" class="flex flex-wrap items-center gap-4 w-full text-gray-300">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-600"></span> Còn trống (màu theo khu)</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-white"></span> Đang chọn</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-700"></span> Người khác đang giữ</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-gray-600 opacity-40"></span> Đã bán</span>
                    </div>
                    <div x-show="hoveredSeat !== null" style="display: none;" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="font-display font-black text-white text-sm" x-text="hoveredSeat?.code"></span>
                                <span class="badge-gold text-[9px] px-2 rounded-full font-bold" x-text="hoveredSeat?.gate" x-show="hoveredSeat?.gate"></span>
                            </div>
                            <p class="text-[11px] text-gray-300">Khu: <strong class="text-gold-light" x-text="hoveredSeat?.sector"></strong> · Hàng/Bàn: <strong class="text-white" x-text="hoveredSeat?.row"></strong> · Ghế: <strong class="text-white" x-text="hoveredSeat?.number"></strong></p>
                            <p class="text-[10px] text-emerald-400" x-show="hoveredSeat?.perks?.length" x-text="'✓ ' + (hoveredSeat?.perks || []).join(' · ')"></p>
                        </div>
                        <span class="font-display font-black text-lg text-gold-antique" x-text="formatCurrency(hoveredSeat?.price || 0)"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bảng giá từng khu -->
        <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4">
            <h4 class="font-display font-black text-sm text-black border-b border-black/10 pb-3">Bảng Giá &amp; Quyền Lợi Từng Khu</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 text-xs">
                @foreach ($allZones as $zone)
                    <div class="p-3.5 rounded-2xl bg-[#FAF9F6] border border-black/10 space-y-2" style="border-left: 5px solid {{ $zone['color'] }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-black text-black">{{ $zone['name'] }}</span>
                            <span class="font-display font-black text-sm text-black">{{ number_format($zone['price']) }}₫</span>
                        </div>
                        @if ($zone['description'])
                            <p class="text-[11px] text-gray-600 leading-tight">{{ $zone['description'] }}</p>
                        @endif
                        <div class="flex items-center justify-between pt-1 border-t border-black/5 text-[10px] text-gray-500">
                            <span>Lối vào: <strong class="text-black">{{ $zone['gate'] ?? 'Cổng chính' }}</strong></span>
                            <span>{{ $zone['kind'] === 'standing' ? 'Chọn số lượng' : 'Chọn ghế' }} · còn {{ $zone['available'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <x-seat-map.zone-cards :zones="$allZones" />
    @endif

    <!-- Ngăn kéo vé đang chọn (dùng chung) -->
    <div class="sticky bottom-6 z-40 bg-white/95 backdrop-blur-xl rounded-3xl p-5 sm:p-7 border border-black/15 shadow-[0_20px_50px_-10px_rgba(0,0,0,0.25)] flex flex-col lg:flex-row items-center justify-between gap-6">
        <div class="flex-1 w-full space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black text-rose-taupe uppercase tracking-wider">Vé đang chọn (<span x-text="totalTicketCount">0</span>/8 vé)</span>
                <span class="text-xs text-gray-500 font-medium hidden sm:inline">Giữ chỗ an toàn trong 10 phút sau khi xác nhận</span>
            </div>
            <div class="flex flex-wrap gap-2 items-center min-h-[36px]">
                <template x-if="totalTicketCount === 0">
                    <span class="text-xs sm:text-sm text-gray-400 italic">
                        {{ $isSeated ? 'Bấm vào ghế trên sơ đồ (hoặc chọn số lượng ở khu đứng) để chọn vé.' : 'Chọn số lượng vé ở khu bạn muốn phía trên.' }}
                    </span>
                </template>
                <template x-for="pill in zonePills" :key="'z' + pill.id">
                    <span class="badge-sage px-3.5 py-1.5 rounded-full text-xs font-black flex items-center gap-2">
                        <span x-text="pill.name + ' x' + pill.count"></span>
                        <span class="text-[10px] text-gray-700" x-text="formatCurrency(pill.price)"></span>
                        <button type="button" @click="zoneQuantities[pill.id] = 0" class="text-sm font-black ml-1">&times;</button>
                    </span>
                </template>
                <template x-for="seat in selectedSeats" :key="seat.id">
                    <span class="px-3.5 py-1.5 rounded-full text-xs font-black flex items-center gap-2 bg-[#FAF9F6] border" :style="`border-color: ${seat.color}`">
                        <span class="w-2 h-2 rounded-full" :style="`background-color: ${seat.color}`"></span>
                        <span x-text="seat.code"></span>
                        <span class="text-[10px] opacity-80" x-text="'(' + seat.zoneName + ')'"></span>
                        <span class="text-[10px] font-mono" x-text="formatCurrency(seat.price)"></span>
                        <button type="button" @click="removeSeat(seat.id)" class="text-sm font-black ml-0.5" title="Bỏ ghế này">&times;</button>
                    </span>
                </template>
            </div>
        </div>

        <div class="flex items-center justify-between lg:justify-end gap-6 w-full lg:w-auto shrink-0 pt-4 lg:pt-0 border-t lg:border-t-0 border-black/10">
            <div class="text-left lg:text-right">
                <span class="text-xs text-gray-500 font-bold block">Tổng tạm tính</span>
                <span class="font-display font-black text-2xl sm:text-3xl text-black" x-text="formattedTotalPrice">0 ₫</span>
            </div>
            <button type="button" @click="confirmHold()" :disabled="totalTicketCount === 0 || isSubmitting"
                    class="btn-rose px-8 py-3.5 rounded-full font-black text-sm flex items-center gap-2.5 shadow-lg"
                    :class="{ 'opacity-50 pointer-events-none grayscale': totalTicketCount === 0 || isSubmitting }">
                <span x-text="isSubmitting ? 'Đang giữ chỗ...' : 'Xác Nhận Giữ Chỗ'">Xác Nhận Giữ Chỗ</span>
                <span x-show="totalTicketCount > 0 && !isSubmitting" x-text="'(' + totalTicketCount + ' vé)'"></span>
            </button>
        </div>
    </div>

    <!-- Thông báo -->
    <div x-show="showToast" x-transition style="display: none;"
         class="fixed bottom-8 right-8 z-50 bg-black text-white border border-white/20 px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 max-w-sm">
        <div class="w-3 h-3 rounded-full shrink-0"
             :class="{ 'bg-emerald-400': notificationType === 'success', 'bg-amber-400': notificationType === 'warning', 'bg-red-400': notificationType === 'error', 'bg-rose-taupe': notificationType === 'info' }"></div>
        <span class="text-xs sm:text-sm font-bold leading-tight" x-text="notificationMessage"></span>
    </div>
</div>
