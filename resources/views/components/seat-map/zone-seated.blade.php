@props(['zone'])

{{-- Khu ghế đánh số: mỗi hàng là 1 lưới, ghế nằm đúng cột (pos_col) nên lối đi thành khoảng trống --}}
<div class="rounded-3xl p-3 sm:p-4 bg-black/40 border border-white/10 space-y-2" style="border-color: {{ $zone['color'] }}66">
    <x-seat-map.zone-header :zone="$zone" />

    <div class="space-y-1.5">
        @foreach ($zone['rows'] as $row)
            <div class="flex items-center gap-2">
                <span class="w-12 shrink-0 text-right text-[10px] font-mono text-gray-400 font-bold">{{ $row['label'] }}</span>
                <div class="grid gap-1" style="grid-template-columns: repeat({{ max(1, $zone['max_col']) }}, minmax(0, 1.75rem));">
                    @foreach ($row['seats'] as $seat)
                        <x-seat-map.seat-button :seat="$seat" :zone="$zone" :row-label="$row['label']" :style="'grid-column: '.$seat['col']" />
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
