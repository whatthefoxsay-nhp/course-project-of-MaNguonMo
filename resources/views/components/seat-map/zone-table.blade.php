@props(['zone'])

{{-- Khu bàn tiệc: mỗi bàn (row) là hình tròn, ghế xếp đều quanh bàn --}}
<div class="rounded-3xl p-3 sm:p-4 bg-black/40 border border-white/10 space-y-3" style="border-color: {{ $zone['color'] }}66">
    <x-seat-map.zone-header :zone="$zone" />

    <div class="flex flex-wrap justify-center gap-4">
        @foreach ($zone['rows'] as $table)
            @php
                $count = count($table['seats']);
                $tablePayload = collect($table['seats'])->map(fn ($seat) => [
                    'id' => $seat['id'], 'code' => $seat['code'], 'zoneId' => $zone['id'], 'zoneName' => $zone['name'],
                    'price' => $seat['price'], 'color' => $zone['color'], 'status' => $seat['status'],
                ])->all();
            @endphp
            <div class="flex flex-col items-center gap-1.5">
                <div class="relative w-36 h-36">
                    <div class="absolute inset-0 m-auto w-16 h-16 rounded-full flex items-center justify-center text-[11px] font-black text-white shadow-lg"
                         style="background-color: {{ $zone['color'] }}55; border: 2px solid {{ $zone['color'] }}">
                        {{ $table['label'] }}
                    </div>
                    @foreach ($table['seats'] as $index => $seat)
                        @php
                            $angle = 2 * M_PI * $index / max(1, $count) - M_PI / 2;
                            $left = 50 + 40 * cos($angle);
                            $top = 50 + 40 * sin($angle);
                        @endphp
                        <x-seat-map.seat-button
                            :seat="$seat" :zone="$zone" :row-label="$table['label']"
                            :style="sprintf('position:absolute; left:%.1f%%; top:%.1f%%; transform:translate(-50%%,-50%%)', $left, $top)" />
                    @endforeach
                </div>
                <button type="button" @click="toggleTable(@js($tablePayload))"
                        class="text-[10px] font-bold px-2.5 py-1 rounded-full border border-white/20 text-gray-200 hover:bg-white/10">
                    Chọn cả bàn
                </button>
            </div>
        @endforeach
    </div>
</div>
