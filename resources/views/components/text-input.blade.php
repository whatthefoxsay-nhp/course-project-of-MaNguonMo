@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-3 px-4 text-xs sm:text-sm text-slate-900 placeholder-slate-400 transition-all font-semibold shadow-2xs']) }}>
