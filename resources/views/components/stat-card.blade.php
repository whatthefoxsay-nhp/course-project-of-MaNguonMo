@props([
    'title',
    'value',
    'change' => null,
    'isPositive' => true,
    'icon' => null,
    'accent' => 'gold' // gold, rose, sage, crimson
])

@php
    $accentBorder = match($accent) {
        'rose' => 'hover:border-rose-taupe hover:shadow-md',
        'sage' => 'hover:border-sage-forest hover:shadow-md',
        'crimson' => 'hover:border-crimson hover:shadow-md',
        default => 'hover:border-gold-antique hover:shadow-md',
    };

    $accentBg = match($accent) {
        'rose' => 'bg-[#FDE8EE] text-[#99334D] border-[#F3B3C4]',
        'sage' => 'bg-[#EAF3EC] text-sage-forest border-[#A3C9A8]',
        'crimson' => 'bg-[#FEE2E2] text-crimson border-[#FCA5A5]',
        default => 'bg-[#FFF8E1] text-gold-dark border-[#FFE082]',
    };
@endphp

<div class="bg-white rounded-3xl p-6 transition-all duration-300 border border-black/10 shadow-sm {{ $accentBorder }}">
    <div class="flex items-center justify-between">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ $title }}</span>
        @if ($icon)
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center border font-bold text-lg {{ $accentBg }}">
                {!! $icon !!}
            </div>
        @endif
    </div>

    <div class="mt-4 flex items-baseline justify-between">
        <span class="font-display font-black text-2xl sm:text-3xl text-black tracking-tight">
            {{ $value }}
        </span>

        @if ($change)
            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full flex items-center gap-1 {{ $isPositive ? 'bg-emerald-50 text-sage-forest border border-emerald-200' : 'bg-red-50 text-crimson border border-red-200' }}">
                <span>{{ $isPositive ? '↑' : '↓' }}</span>
                <span>{{ $change }}</span>
            </span>
        @endif
    </div>
</div>
