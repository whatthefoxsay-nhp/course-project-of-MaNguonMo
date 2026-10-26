@props(['seat', 'zone', 'rowLabel', 'style' => ''])

@php
    $payload = [
        'id' => $seat['id'],
        'code' => $seat['code'],
        'zoneId' => $zone['id'],
        'zoneName' => $zone['name'],
        'price' => $seat['price'],
        'color' => $zone['color'],
        'status' => $seat['status'],
    ];
    $hover = [
        'code' => $seat['code'],
        'row' => $rowLabel,
        'number' => (string) $seat['number'],
        'sector' => $zone['name'],
        'gate' => $zone['gate'] ?? '',
        'type' => $zone['name'],
        'price' => $seat['price'],
        'status' => $seat['status'],
        'perks' => $zone['perks'],
    ];
    $isBooked = $seat['status'] === 'booked';
    $isHeld = $seat['status'] === 'held';
@endphp

<button
    type="button"
    @disabled($isBooked || $isHeld)
    @click="toggleSeat(@js($payload))"
    @mouseenter="setHoveredSeat(@js($hover))"
    @mouseleave="clearHoveredSeat()"
    style="--zone: {{ $zone['color'] }}; {{ $style }}"
    @if ($isBooked)
        class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[10px] font-black flex items-center justify-center bg-gray-700/50 text-gray-500 opacity-30 cursor-not-allowed"
    @elseif ($isHeld)
        class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[10px] font-black flex items-center justify-center bg-amber-900/60 border border-amber-500 text-amber-300 animate-pulse cursor-not-allowed"
    @else
        class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[10px] font-black flex items-center justify-center transition-all border border-white/20"
        :class="unavailableIds.includes({{ $seat['id'] }})
            ? 'bg-amber-900/60 text-amber-300 opacity-60 cursor-not-allowed'
            : (isSeatSelected({{ $seat['id'] }}) ? 'bg-white text-black ring-2 ring-gold-antique scale-110 z-10' : 'bg-[var(--zone)] text-white hover:scale-110 cursor-pointer')"
    @endif
    title="{{ $zone['name'] }} · {{ $seat['code'] }} · {{ number_format($seat['price']) }}₫"
>{{ $seat['number'] }}</button>
