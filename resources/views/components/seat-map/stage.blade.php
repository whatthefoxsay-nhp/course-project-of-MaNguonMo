@props(['preset'])

{{-- Sân khấu / điểm nhìn chính, vẽ theo kiểu địa điểm --}}
@switch($preset)
    @case('mega_concert')
        <div class="relative w-full max-w-3xl mx-auto text-center">
            <div class="bg-gradient-to-r from-[#1F2937] via-[#111827] to-[#1F2937] border-2 border-gold-antique/80 rounded-3xl p-4 shadow-[0_0_50px_rgba(212,175,55,0.4)] flex flex-col items-center">
                <div class="w-full flex items-center justify-between px-4 pb-2 border-b border-white/10 text-[10px] font-mono text-gold-light">
                    <span>🔊 LINE ARRAY (L)</span>
                    <span class="px-3 py-0.5 rounded-full bg-rose-900/60 border border-rose-500/40 text-rose-300 font-bold uppercase tracking-widest text-[9px]">Màn hình LED</span>
                    <span>LINE ARRAY (R) 🔊</span>
                </div>
                <div class="py-3 font-display font-black text-base sm:text-lg uppercase tracking-[0.3em] text-white">Sân khấu chính</div>
                <div class="w-20 h-16 bg-gradient-to-b from-black to-[#334155] border-x-2 border-b-2 border-gold-antique/70 flex items-end justify-center pb-1 -mb-8">
                    <span class="text-[9px] font-black uppercase text-gold-light tracking-widest">Catwalk</span>
                </div>
            </div>
        </div>
        @break
    @case('theater_hall')
        <div class="w-full max-w-lg mx-auto p-4 bg-gradient-to-r from-red-950 via-rose-950 to-red-950 text-center rounded-b-[3rem] rounded-t-xl shadow-xl border-2 border-gold-antique">
            <div class="font-display font-black text-xs tracking-widest uppercase text-gold-light">🎭 Sân khấu nhà hát 🎭</div>
            <span class="text-[10px] text-gray-300 font-mono">Hố dàn nhạc</span>
        </div>
        @break
    @case('convention_center')
        <div class="w-full max-w-lg mx-auto p-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-center rounded-2xl shadow-xl border-2 border-cyan-400/60">
            <div class="font-display font-black text-xs tracking-widest uppercase text-cyan-300">🏢 Bục diễn giả &amp; màn chiếu 🏢</div>
        </div>
        @break
    @case('banquet_hall')
        <div class="w-full max-w-md mx-auto p-3 bg-gradient-to-r from-amber-950 via-stone-900 to-amber-950 text-center rounded-2xl shadow-xl border-2 border-gold-antique/70">
            <div class="font-display font-black text-xs tracking-widest uppercase text-gold-light">🍽️ Sân khấu &amp; bàn giảng viên</div>
        </div>
        @break
    @case('open_space')
        <div class="w-full max-w-md mx-auto p-3 bg-gradient-to-r from-emerald-950 via-teal-900 to-emerald-950 text-center rounded-2xl shadow-xl border-2 border-emerald-400/60">
            <div class="font-display font-black text-xs tracking-widest uppercase text-emerald-300">🎪 Cổng vào khu sự kiện</div>
        </div>
        @break
    @default
        <div class="mx-auto w-2/3 py-3 rounded-b-[2rem] bg-gradient-to-b from-gold-antique/40 to-transparent border-t-4 border-gold-antique text-center text-[11px] font-black tracking-[0.3em] text-gold-light">
            SÂN KHẤU
        </div>
@endswitch
