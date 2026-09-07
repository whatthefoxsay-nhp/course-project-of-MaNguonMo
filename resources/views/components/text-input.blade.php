@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'glass-input border-0 focus:ring-2 focus:ring-rose-400']) }}>
