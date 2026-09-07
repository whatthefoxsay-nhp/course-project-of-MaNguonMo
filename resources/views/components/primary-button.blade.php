<button {{ $attributes->merge(['type' => 'submit', 'class' => 'glass-btn-primary inline-flex items-center px-4 py-2 font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
