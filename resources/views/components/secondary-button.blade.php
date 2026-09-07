<button {{ $attributes->merge(['type' => 'button', 'class' => 'glass-btn-secondary inline-flex items-center px-4 py-2 font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
