@props(['zone'])

{{-- Khu đứng / vào cửa tự do: không vẽ ghế, khách chọn số lượng --}}
<div class="rounded-3xl p-4 border-2 border-dashed text-center space-y-2 min-w-[11rem] bg-black/40" style="border-color: {{ $zone['color'] }}">
    <h5 class="font-display font-black text-sm text-white">{{ $zone['name'] }}</h5>
    @if ($zone['description'])
        <p class="text-[10px] text-gray-300 leading-tight max-w-[14rem] mx-auto">{{ $zone['description'] }}</p>
    @endif
    <div class="text-xs font-mono text-gold-light font-bold">{{ number_format($zone['price']) }}₫</div>
    <div class="text-[10px] text-gray-400">còn {{ $zone['available'] }} chỗ</div>

    <div class="flex items-center justify-center gap-2 pt-1">
        <button type="button" @click="decrementZone({{ $zone['id'] }})" :disabled="zoneCount({{ $zone['id'] }}) <= 0"
                class="w-7 h-7 rounded-lg bg-white/10 text-white font-black disabled:opacity-30">&minus;</button>
        <span class="w-6 font-mono font-black text-sm text-white" x-text="zoneCount({{ $zone['id'] }})">0</span>
        <button type="button" @click="incrementZone({{ $zone['id'] }})" @disabled($zone['available'] === 0)
                class="w-7 h-7 rounded-lg btn-rose font-black disabled:opacity-30">&#43;</button>
    </div>
</div>
