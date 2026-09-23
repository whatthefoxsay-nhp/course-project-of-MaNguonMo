@php
    $flashStyles = [
        'success' => 'bg-[#3A5A40]/10 border-[#3A5A40]/30 text-[#3A5A40]',
        'warning' => 'bg-[#D4AF37]/10 border-[#D4AF37]/40 text-[#8A6D1D]',
        'error' => 'bg-[#CC0000]/10 border-[#CC0000]/30 text-[#CC0000]',
    ];
@endphp

@foreach ($flashStyles as $key => $classes)
    @if (session($key))
        <div class="mb-6 rounded-2xl border px-5 py-3 text-xs font-bold {{ $classes }}" role="alert">
            {{ session($key) }}
        </div>
    @endif
@endforeach
