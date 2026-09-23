@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null])

@php
    // "details[venue_name]" -> "details.venue_name" để dùng với old() và @error
    $dotKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $inputId = str_replace('.', '_', $dotKey);
@endphp

<div>
    <label for="{{ $inputId }}" class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1.5">
        {{ $label }}
        @if ($required)
            <span class="text-[#CC0000]">*</span>
        @endif
    </label>

    @if ($type === 'textarea')
        <textarea id="{{ $inputId }}" name="{{ $name }}" @required($required) {{ $attributes->merge(['class' => 'admin-input', 'rows' => 4]) }}>{{ old($dotKey, $value) }}</textarea>
    @elseif ($type === 'file')
        <input id="{{ $inputId }}" name="{{ $name }}" type="file" @required($required) {{ $attributes->merge(['class' => 'admin-input']) }}>
    @else
        <input id="{{ $inputId }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($dotKey, $value) }}" @required($required) {{ $attributes->merge(['class' => 'admin-input']) }}>
    @endif

    @if ($hint)
        <p class="mt-1 text-[11px] text-gray-500">{{ $hint }}</p>
    @endif

    @error($dotKey)
        <p class="mt-1 text-[11px] font-bold text-[#CC0000]">{{ $message }}</p>
    @enderror
</div>
