@props(['event' => null, 'movie' => null, 'featured' => false])

@php
    $item = $event ?? $movie;
@endphp

@if ($item)
<a href="{{ route('events.show', $item->slug) }}" class="group relative flex flex-col bg-white rounded-3xl overflow-hidden p-3 sm:p-3.5 transition-all duration-300 border border-black/[0.08] shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_20px_40px_-15px_rgba(192,132,151,0.28)] hover:border-rose-taupe/40 hover:-translate-y-1.5">
    <!-- Poster with luxury overlay -->
    <div class="relative aspect-[16/11] sm:aspect-[4/3] w-full rounded-2xl overflow-hidden bg-[#FAF9F6]">
        <img 
            src="{{ $item->poster_url }}" 
            alt="{{ $item->title }}" 
            class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-700 ease-out"
            loading="lazy"
        >
        
        <!-- Ambient Vignette Gradient -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-85 group-hover:opacity-70 transition-opacity"></div>
        
        <!-- Top Left: Category Badge -->
        <div class="absolute top-3 left-3 z-10 flex items-center gap-1.5">
            <span class="px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-md border border-white/20 text-white text-[10px] font-black uppercase tracking-wider shadow-sm">
                {{ $item->category->name ?? 'Sự Kiện' }}
            </span>
        </div>

        <!-- Top Right: Live/Availability Status & Favorite Heart -->
        <div class="absolute top-3 right-3 z-10 flex items-center gap-1.5">
            @php
                $isFav = auth()->check() && $item->isFavoritedBy(auth()->user());
            @endphp
            <button 
                type="button"
                onclick="event.preventDefault(); event.stopPropagation(); toggleFavoriteCard({{ $item->id }}, this);"
                data-event-id="{{ $item->id }}"
                class="favorite-heart-btn w-7 h-7 rounded-full bg-white/95 backdrop-blur-md flex items-center justify-center transition-all hover:scale-110 shadow-md {{ $isFav ? 'text-crimson' : 'text-gray-400 hover:text-crimson' }}"
                title="{{ $isFav ? 'Đã yêu thích' : 'Lưu vào yêu thích' }}"
                aria-label="{{ $isFav ? 'Đã yêu thích' : 'Lưu vào yêu thích' }}"
            >
                <svg class="w-4 h-4 {{ $isFav ? 'fill-current' : 'fill-none stroke-current' }}" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>
            <span class="px-2.5 py-1 rounded-full bg-white/95 backdrop-blur-md text-emerald-800 text-[10px] font-extrabold flex items-center gap-1.5 shadow-md">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Đang mở bán
            </span>
        </div>

        <!-- Bottom details inside poster -->
        <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between text-[11px] text-white/90 font-medium z-10">
            <div class="flex items-center gap-1.5 bg-black/50 backdrop-blur-md px-2.5 py-1 rounded-lg border border-white/15">
                <svg class="w-3.5 h-3.5 text-gold-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-[11px] font-semibold">Khán phòng VIP</span>
            </div>

            @if ($item->duration_minutes)
                <div class="flex items-center gap-1 bg-black/50 backdrop-blur-md px-2.5 py-1 rounded-lg border border-white/15">
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-[11px] font-semibold">{{ $item->duration_minutes }} phút</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Title & Content -->
    <div class="mt-3.5 px-1.5 flex flex-col flex-1 justify-between">
        <div>
            <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-wider text-gold-dark mb-1">
                <span>Show Diễn 2026</span>
                <span>•</span>
                <span class="text-gray-400 font-medium">Bảo đảm vé thật 100%</span>
            </div>

            <h3 class="font-serif font-black text-black text-lg leading-snug group-hover:text-rose-taupe transition-colors line-clamp-1">
                {{ $item->title }}
            </h3>

            <p class="text-xs text-gray-500 mt-1.5 line-clamp-2 leading-relaxed font-normal">
                {{ $item->description }}
            </p>

            <!-- Prominent Participants / Guests Preview -->
            @if (!empty($item->participants_summary) || !empty($item->lineup))
                <div class="mt-2.5 flex items-center gap-2 text-[11px] text-gray-700 bg-[#FAF9F6] px-2.5 py-1.5 rounded-xl border border-black/5">
                    <span class="text-gold-dark text-xs shrink-0">⭐</span>
                    <span class="line-clamp-1 font-semibold text-gray-800">
                        {{ $item->participants_summary ?? collect($item->lineup)->pluck('name')->take(3)->implode(', ') }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Footer action -->
        <div class="mt-4 pt-3.5 border-t border-black/8 flex items-center justify-end">
            <span class="btn-rose px-3.5 py-2 rounded-xl text-xs font-black flex items-center gap-1.5 shadow-sm group-hover:shadow-md group-hover:gap-2 transition-all">
                <span>Chọn vé</span>
                <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </span>
        </div>
    </div>
</a>
@endif
