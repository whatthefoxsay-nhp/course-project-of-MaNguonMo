<x-site-layout :title="'Sự Kiện Yêu Thích Của Tôi - TicketBox'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        
        <!-- Breadcrumb & Header -->
        <div class="mb-8 sm:mb-10">
            <div class="flex flex-wrap items-center gap-2 mb-3 text-xs">
                <a href="{{ route('home') }}" class="text-gray-500 hover:text-black font-semibold transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Trang chủ</span>
                </a>
                <span class="text-gray-300">/</span>
                <a href="{{ route('profile.edit') }}" class="text-gray-500 hover:text-black font-semibold transition-colors">
                    Hồ sơ cá nhân
                </a>
                <span class="text-gray-300">/</span>
                <span class="text-gold-dark font-bold">Sự kiện yêu thích</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 text-crimson border border-rose-200 text-[10px] font-black uppercase tracking-wider mb-2">
                        <svg class="w-3 h-3 text-crimson" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        Danh Sách Quan Tâm (Wishlist)
                    </span>
                    <h1 class="font-serif text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">Sự Kiện Yêu Thích Của Tôi</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1.5 font-medium max-w-2xl">
                        Nơi lưu trữ những concert, đêm nhạc và hội thảo bạn quan tâm để dễ dàng theo dõi và săn vé.
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('events.index') }}" class="btn-ghost-light text-xs font-bold px-4 py-2.5 rounded-2xl flex items-center gap-2 shadow-2xs">
                        <svg class="w-4 h-4 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                        <span>Khám phá thêm</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Favorites List Grid -->
        @if ($favorites->isEmpty())
            <!-- Empty State Interface -->
            <div class="bg-white rounded-3xl p-10 sm:p-14 text-center border border-black/10 max-w-md mx-auto space-y-4 shadow-sm">
                <div class="w-16 h-16 rounded-full bg-rose-50 border border-rose-100 text-rose-500 mx-auto flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </div>
                <h3 class="font-display font-black text-lg text-black">Chưa có sự kiện yêu thích nào</h3>
                <p class="text-xs text-gray-500 leading-relaxed">
                    Bạn chưa lưu sự kiện nào vào danh sách yêu thích. Hãy bấm vào biểu tượng Trái tim trên các sự kiện để lưu lại nhé!
                </p>
                <div class="pt-2">
                    <a href="{{ route('events.index') }}" class="btn-rose inline-flex items-center gap-2 px-6 py-3 rounded-2xl text-xs font-bold shadow-sm">
                        <span>Khám phá sự kiện ngay</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($favorites as $event)
                    <div class="bg-white rounded-3xl p-4 border border-black/10 shadow-sm flex flex-col justify-between group hover:border-gold-antique transition-all relative">
                        <!-- Poster Thumbnail -->
                        <div class="relative aspect-[16/10] rounded-2xl overflow-hidden bg-[#FAF9F6] mb-3.5">
                            <img src="{{ $event->poster_url }}" alt="{{ $event->title }}" class="w-full h-full object-cover">
                            
                            <!-- Badges -->
                            <div class="absolute top-2.5 left-2.5">
                                <span class="px-2.5 py-0.5 rounded-full bg-black/70 backdrop-blur-md text-white text-[10px] font-bold">
                                    {{ $event->category->name ?? 'Sự kiện' }}
                                </span>
                            </div>

                            <div class="absolute top-2.5 right-2.5">
                                @if ($event->status === 'published')
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                                        Đang mở bán
                                    </span>
                                @elseif ($event->status === 'archived')
                                    <span class="px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-600 text-[10px] font-bold border border-gray-200">
                                        Đã lưu trữ / Ngừng mở bán
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-bold border border-amber-200">
                                        Bản nháp / Tạm ngưng
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="space-y-2 flex-1">
                            <h3 class="font-serif font-black text-slate-900 text-base line-clamp-1 group-hover:text-gold-dark transition-colors">
                                {{ $event->title }}
                            </h3>
                            <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed">
                                {{ $event->description }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 pt-3.5 border-t border-black/8 flex items-center justify-between gap-2">
                            <!-- Nút Bỏ lưu -->
                            <form action="{{ route('favorites.destroy', $event->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn bỏ lưu sự kiện này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800 hover:underline flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-rose-500 fill-current" viewBox="0 0 24 24">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                    <span>Bỏ lưu</span>
                                </button>
                            </form>

                            <!-- Nút xem chi tiết -->
                            @if ($event->status === 'published')
                                <a href="{{ route('events.show', $event->slug) }}" class="btn-rose px-3.5 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 shadow-2xs">
                                    <span>Xem chi tiết</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @else
                                <span class="text-xs text-gray-400 font-medium">Ngừng mở bán</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $favorites->links() }}
            </div>
        @endif

    </div>
</x-site-layout>
