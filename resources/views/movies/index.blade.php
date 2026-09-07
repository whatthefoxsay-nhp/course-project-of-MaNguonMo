<x-site-layout :title="'Phim & Sự kiện'">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold mb-4">Phim &amp; Sự kiện</h1>

        <form action="{{ route('movies.index') }}" method="GET" class="glass-panel flex flex-wrap gap-3 p-4 mb-8 !rounded-2xl">
            <input
                type="search"
                name="q"
                value="{{ $query }}"
                placeholder="Tìm theo tên..."
                class="glass-input border-0 text-sm flex-1 min-w-[180px] focus:ring-2 focus:ring-rose-400"
            >
            <select name="category" class="glass-input border-0 text-sm focus:ring-2 focus:ring-rose-400">
                <option value="">Tất cả thể loại</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="glass-btn-primary text-sm font-medium px-5 py-2">
                Tìm kiếm
            </button>
        </form>

        @if ($movies->isEmpty())
            <p class="text-gray-500">Không tìm thấy phim/sự kiện phù hợp.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-6">
                @foreach ($movies as $movie)
                    <x-movie-card :movie="$movie" />
                @endforeach
            </div>
        @endif

        {{-- Phase 1 BE sẽ thay bằng Laravel pagination thật (LengthAwarePaginator) --}}
        <nav class="mt-10 flex justify-center gap-2 text-sm text-gray-500" aria-disabled="true">
            <span class="glass-pill px-3 py-1">« Trước</span>
            <span class="glass-btn-primary px-3 py-1">1</span>
            <span class="glass-pill px-3 py-1">2</span>
            <span class="glass-pill px-3 py-1">Sau »</span>
        </nav>
    </div>
</x-site-layout>
