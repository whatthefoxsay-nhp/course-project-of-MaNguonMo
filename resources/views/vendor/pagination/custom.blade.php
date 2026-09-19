@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full text-xs">
        <div class="text-gray-600 font-medium">
            Hiển thị từ <span class="font-bold text-black">{{ $paginator->firstItem() }}</span> đến <span class="font-bold text-black">{{ $paginator->lastItem() }}</span> trong <span class="font-bold text-black">{{ $paginator->total() }}</span> kết quả
        </div>

        <div class="flex items-center gap-1.5 flex-wrap justify-center">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="px-3 py-2 rounded-xl bg-[#FAF9F6] border border-black/10 text-gray-400 cursor-not-allowed flex items-center gap-1 font-bold select-none">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Trước</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-3 py-2 rounded-xl bg-white border border-black/15 text-black hover:bg-[#FAF9F6] hover:border-black/30 transition-all font-bold flex items-center gap-1 shadow-sm">
                    <svg class="w-3.5 h-3.5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Trước</span>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="px-3 py-2 text-gray-400 font-bold select-none">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="w-9 h-9 rounded-xl bg-gold-antique text-black font-black flex items-center justify-center shadow-md border border-[#C5A028] select-none">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="w-9 h-9 rounded-xl bg-white border border-black/15 text-black hover:bg-[#FAF9F6] hover:border-black/30 transition-all font-bold flex items-center justify-center shadow-sm">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-3 py-2 rounded-xl bg-white border border-black/15 text-black hover:bg-[#FAF9F6] hover:border-black/30 transition-all font-bold flex items-center gap-1 shadow-sm">
                    <span>Sau</span>
                    <svg class="w-3.5 h-3.5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span class="px-3 py-2 rounded-xl bg-[#FAF9F6] border border-black/10 text-gray-400 cursor-not-allowed flex items-center gap-1 font-bold select-none">
                    <span>Sau</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
