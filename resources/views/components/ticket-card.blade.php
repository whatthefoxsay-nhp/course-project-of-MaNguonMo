@props(['movie', 'showtime' => null])

<div class="relative group bg-white rounded-3xl p-4 transition-all duration-300 border border-black/10 hover:border-gold-antique hover:shadow-lg shadow-sm">
    <div class="flex flex-col sm:flex-row gap-4 items-stretch">
        <!-- Poster Part -->
        <div class="w-full sm:w-28 h-36 shrink-0 rounded-2xl overflow-hidden bg-[#FAF9F6] relative">
            <img src="{{ $movie->poster_path }}" alt="{{ $movie->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            <span class="absolute top-2 left-2 badge-gold text-[9px] px-2 py-0.5 rounded-full uppercase font-black shadow-sm">
                Sự Kiện Hot
            </span>
        </div>

        <!-- Details Part -->
        <div class="flex-1 flex flex-col justify-between py-1">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs text-rose-taupe font-bold tracking-wider uppercase">
                        {{ $movie->category->name ?? 'Đặc Sắc' }}
                    </span>
                    <span class="badge-sage text-[10px] px-2.5 py-0.5 rounded-full font-bold">
                        Đang mở bán vé
                    </span>
                </div>
                <h4 class="font-display text-black text-lg font-black mt-1 group-hover:text-gold-dark transition-colors">
                    {{ $movie->title }}
                </h4>
                <p class="text-xs text-gray-500 mt-1 line-clamp-1">
                    {{ $movie->description }}
                </p>
            </div>

            <div class="mt-3 flex items-center justify-between pt-2.5 border-t border-dashed border-black/10">
                <div class="text-xs">
                    <span class="text-gray-500 block text-[10px] uppercase font-bold tracking-wider">Giá khởi điểm</span>
                    <span class="text-black font-display font-black text-sm">180.000₫</span>
                </div>

                <a href="{{ route('movies.show', $movie->slug) }}" class="btn-rose px-4 py-1.5 rounded-xl text-xs font-bold shadow-sm">
                    Xem suất diễn &rarr;
                </a>
            </div>
        </div>
    </div>
</div>
