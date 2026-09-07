<x-site-layout :title="$movie->title">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid md:grid-cols-[280px_1fr] gap-8">
            <img src="{{ $movie->poster_path }}" alt="{{ $movie->title }}" class="w-full rounded-lg shadow-sm">

            <div>
                <span class="inline-block text-xs font-semibold uppercase tracking-wide text-rose-600">
                    {{ $movie->type === 'event' ? 'Sự kiện' : 'Phim' }} · {{ $movie->category->name }}
                </span>
                <h1 class="text-2xl font-bold mt-1">{{ $movie->title }}</h1>
                @if ($movie->duration_minutes)
                    <p class="text-sm text-gray-500 mt-1">{{ $movie->duration_minutes }} phút</p>
                @endif
                <p class="mt-4 text-gray-700 leading-relaxed">{{ $movie->description }}</p>

                <h2 class="text-lg font-semibold mt-8 mb-3">Suất chiếu</h2>
                <div class="flex flex-wrap gap-3">
                    @foreach ($showtimes as $showtime)
                        <a href="{{ route('showtimes.seats', $showtime->id) }}"
                           class="block border border-gray-300 rounded-lg px-4 py-2 text-sm hover:border-rose-500 hover:bg-rose-50">
                            <span class="block font-semibold">{{ $showtime->start_time->format('H:i') }}</span>
                            <span class="block text-gray-500">{{ $showtime->start_time->format('d/m') }} · {{ $showtime->room->name }}</span>
                            <span class="block text-rose-600 font-medium">{{ number_format($showtime->base_price) }}đ</span>
                        </a>
                    @endforeach
                </div>

                @if ($movie->type === 'event')
                    <div class="mt-8 grid sm:grid-cols-2 gap-4">
                        <div class="border rounded-lg p-4">
                            <h3 class="font-semibold text-sm mb-2">Vị trí (Google Maps)</h3>
                            <div class="h-40 bg-gray-100 rounded flex items-center justify-center text-gray-400 text-sm">
                                Bản đồ sẽ nhúng ở Phase 5 (Google Maps Embed API)
                            </div>
                        </div>
                        <div class="border rounded-lg p-4">
                            <h3 class="font-semibold text-sm mb-2">Thời tiết dự kiến</h3>
                            <div class="h-40 bg-gray-100 rounded flex items-center justify-center text-gray-400 text-sm">
                                Widget OpenWeatherMap sẽ thêm ở Phase 5
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mt-8">
                    <h2 class="text-lg font-semibold mb-3">Bình luận</h2>
                    <div class="border rounded-lg p-4 text-sm text-gray-400">
                        Form bình luận AJAX sẽ thêm ở Phase 3.
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-site-layout>
