@props(['zones'])

{{-- Sự kiện không có sơ đồ: mỗi khu là một hạng vé, khách chọn số lượng, server tự gán chỗ --}}
<div class="bg-white rounded-3xl p-6 sm:p-8 border border-black/10 shadow-lg space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-black/10 pb-4">
        <div>
            <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider">Đặt vé theo khu vực</span>
            <h3 class="font-display font-black text-2xl text-black mt-1">Chọn Khu Vực &amp; Số Lượng</h3>
            <p class="text-xs text-gray-500 mt-1">Sự kiện không chọn số ghế cụ thể · Hệ thống tự xếp chỗ, bạn nhận vé điện tử để check-in.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($zones as $zone)
            <div class="bg-[#FAF9F6] rounded-3xl p-6 border-2 border-black/10 hover:shadow-xl transition-all flex flex-col justify-between space-y-5"
                 style="border-top: 6px solid {{ $zone['color'] }}">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-black text-black">{{ $zone['name'] }}</span>
                        <span class="text-[10px] font-mono text-gray-500 font-bold uppercase whitespace-nowrap">{{ $zone['gate'] ?? 'Cổng chính' }}</span>
                    </div>
                    <div>
                        <span class="font-display font-black text-2xl sm:text-3xl text-black block">{{ number_format($zone['price']) }}₫</span>
                        <span class="text-[11px] text-gray-500 font-medium">/ 1 vé · còn {{ $zone['available'] }} chỗ</span>
                    </div>
                    @if ($zone['description'])
                        <p class="text-xs text-gray-600 leading-relaxed">{{ $zone['description'] }}</p>
                    @endif
                    @if (! empty($zone['perks']))
                        <div class="pt-3 border-t border-black/10 space-y-1.5 text-xs text-gray-700">
                            @foreach ($zone['perks'] as $perk)
                                <div class="flex items-start gap-2"><span class="text-sage-forest font-bold">✓</span><span>{{ $perk }}</span></div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="pt-4 border-t border-black/10 flex items-center justify-between">
                    <span class="text-xs font-black text-black"
                          x-text="zoneCount({{ $zone['id'] }}) > 0 ? formatCurrency(zoneCount({{ $zone['id'] }}) * {{ $zone['price'] }}) : 'Chưa chọn'"></span>
                    <div class="flex items-center gap-2 bg-white rounded-2xl p-1 border border-black/15 shadow-sm">
                        <button type="button" @click="decrementZone({{ $zone['id'] }})" :disabled="zoneCount({{ $zone['id'] }}) <= 0"
                                class="w-8 h-8 rounded-xl bg-[#FAF9F6] text-gray-700 font-black disabled:opacity-30">&minus;</button>
                        <span class="w-8 text-center font-mono font-black text-sm text-black" x-text="zoneCount({{ $zone['id'] }})">0</span>
                        <button type="button" @click="incrementZone({{ $zone['id'] }})" @disabled($zone['available'] === 0)
                                class="w-8 h-8 rounded-xl btn-rose font-black disabled:opacity-30">&#43;</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <p class="bg-[#FAF9F6] rounded-2xl p-4 border border-black/10 text-xs text-gray-600">
        Mỗi đơn hàng được mua tối đa <strong>8 vé</strong>. Vé được giữ 10 phút sau khi xác nhận.
    </p>
</div>
