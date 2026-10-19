@if ($movies->isNotEmpty())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
        @foreach ($movies as $event)
            <div>
                <x-movie-card :movie="$event" />
            </div>
        @endforeach
    </div>
@else
    <div class="py-14 sm:py-16 text-center bg-white rounded-3xl border border-black/10 p-8 shadow-xs">
        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-amber-50 text-gold-dark flex items-center justify-center border border-gold-light/40">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
        </div>
        <h3 class="font-serif font-black text-lg text-black mb-1">Chưa có sự kiện trong danh mục này</h3>
        <p class="text-xs sm:text-sm text-gray-500 max-w-md mx-auto mb-5">Hiện tại chưa có sự kiện nào thuộc danh mục này đang mở bán. Ban tổ chức sẽ sớm cập nhật các suất diễn mới!</p>
        <button 
            type="button" 
            @click="setTab('all')" 
            class="px-5 py-2.5 rounded-full bg-black hover:bg-gold-dark text-white text-xs font-bold transition-all shadow-sm cursor-pointer"
        >
            &larr; Xem tất cả sự kiện
        </button>
    </div>
@endif
