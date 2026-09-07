<x-site-layout :title="'Trang chủ'">
    <section class="bg-gradient-to-r from-rose-600 to-rose-500 text-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <h1 class="text-3xl sm:text-4xl font-bold">Đặt vé phim &amp; sự kiện nhanh chóng</h1>
            <p class="mt-3 text-rose-100 max-w-xl">Chọn phim, chọn suất, chọn ghế — chỉ vài bước để có vé trong tay.</p>
            <a href="{{ route('movies.index') }}" class="inline-block mt-6 bg-white text-rose-600 font-semibold px-6 py-3 rounded-full hover:bg-rose-50">
                Xem tất cả phim &amp; sự kiện
            </a>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h2 class="text-xl font-bold mb-5">Đang chiếu / sắp diễn ra</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-6">
            @foreach ($movies as $movie)
                <x-movie-card :movie="$movie" />
            @endforeach
        </div>
    </section>
</x-site-layout>
