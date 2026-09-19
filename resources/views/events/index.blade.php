<x-site-layout :title="'Danh Sách Sự Kiện, Concert & Hội Thảo - TicketBox'">
    @php
        $eventList = $events ?? $movies ?? collect();
    @endphp

    <div class="max-w-[1440px] mx-auto px-4 sm:px-8 py-8 sm:py-12">
        
        <!-- Header Banner -->
        <div class="mb-8 space-y-2">
            <span class="text-xs font-bold uppercase tracking-wider text-gold-dark">Kho Tàng Sự Kiện Đỉnh Cao</span>
            <h1 class="font-serif text-3xl sm:text-4xl font-black text-black">Khám Phá Sự Kiện &amp; Show Diễn</h1>
            <p class="text-sm text-gray-600">Tìm kiếm và chọn vị trí ngồi đẹp nhất từ hệ thống khán phòng, nhà hát và sân vận động hàng đầu.</p>
        </div>

        <!-- Filter & Search Bar Container (Light Theme Luxury Box) -->
        <div class="bg-[#F5F5DC] rounded-3xl p-5 sm:p-6 border border-[#D8D8A8] mb-10 shadow-sm space-y-4">
            <form action="{{ route('events.index') }}" method="GET" class="flex flex-col md:flex-row gap-3">
                <!-- Search text -->
                <div class="relative flex-1">
                    <svg class="w-4 h-4 text-gray-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="search"
                        name="q"
                        value="{{ $query }}"
                        placeholder="Tìm theo tên sự kiện, concert, diễn giả, nghệ sĩ biểu diễn..."
                        class="glass-input pl-11 pr-4 py-3 rounded-2xl w-full text-sm text-black placeholder-gray-500 border border-black/15 focus:ring-2 focus:ring-gold-antique bg-white"
                    >
                </div>

                <!-- Category dropdown -->
                <div class="w-full md:w-64">
                    <select 
                        name="category" 
                        class="glass-input px-4 py-3 rounded-2xl w-full text-sm text-black font-semibold border border-black/15 focus:ring-2 focus:ring-gold-antique bg-white"
                    >
                        <option value="">Tất cả danh mục sự kiện</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-gold px-7 py-3 rounded-2xl text-sm font-black shrink-0 flex items-center justify-center gap-2 shadow-sm text-black">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <span>Lọc Sự Kiện</span>
                </button>
            </form>

            <!-- Quick Category Badges -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs pt-2 border-t border-black/10">
                <span class="text-gray-600 shrink-0 font-bold">Danh mục hot:</span>
                <a href="{{ route('events.index') }}" class="px-3.5 py-1.5 rounded-full {{ empty($categoryId) ? 'bg-black text-white font-bold' : 'bg-white text-gray-700 hover:text-black border border-black/10' }}">
                    Tất cả
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('events.index', ['category' => $category->id]) }}" class="px-3.5 py-1.5 rounded-full {{ (string)$categoryId === (string)$category->id ? 'bg-black text-white font-bold' : 'bg-white text-gray-700 hover:text-black border border-black/10' }} shrink-0">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Events Grid -->
        @if ($eventList->isEmpty())
            <div class="bg-white rounded-3xl p-12 text-center border border-black/10 max-w-lg mx-auto space-y-4 shadow-sm">
                <div class="w-12 h-12 rounded-2xl bg-[#FAF9F6] border border-black/10 text-gray-500 mx-auto flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="font-display font-black text-lg text-black">Không tìm thấy sự kiện phù hợp</h3>
                <p class="text-xs text-gray-500">Hãy thử thay đổi từ khóa tìm kiếm hoặc chọn một danh mục sự kiện khác.</p>
                <a href="{{ route('events.index') }}" class="btn-ghost-light inline-block px-5 py-2.5 rounded-2xl text-xs font-bold">
                    Xóa bộ lọc &amp; xem tất cả
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($eventList as $event)
                    <x-event-card :event="$event" />
                @endforeach
            </div>
        @endif

    </div>
</x-site-layout>
