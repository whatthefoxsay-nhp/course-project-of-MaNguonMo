@props(['zone'])

<div class="flex items-center justify-between gap-3 px-1">
    <span class="flex items-center gap-2 text-[11px] font-black text-white">
        <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $zone['color'] }}"></span>
        {{ $zone['name'] }}
    </span>
    <span class="text-[10px] font-mono text-gold-light font-bold whitespace-nowrap">
        {{ number_format($zone['price']) }}₫ · còn {{ $zone['available'] }} chỗ
    </span>
</div>
