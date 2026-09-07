<x-site-layout :title="'Giỏ vé'">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold mb-6">Giỏ vé của bạn</h1>

        @if (empty($items))
            <div class="glass-panel p-6 text-gray-500">
                Giỏ vé đang trống. <a href="{{ route('movies.index') }}" class="text-rose-600 font-medium">Chọn phim/sự kiện</a>
            </div>
        @else
            <div class="glass-panel divide-y divide-white/50 overflow-hidden">
                @foreach ($items as $item)
                    <div class="flex items-center gap-4 p-4">
                        <img src="{{ $item->movie->poster_path }}" alt="" class="w-14 h-20 object-cover rounded-xl">
                        <div class="flex-1">
                            <p class="font-semibold">{{ $item->movie->title }}</p>
                            <p class="text-sm text-gray-500">
                                {{ $item->showtime->start_time->format('H:i d/m') }} · {{ $item->showtime->room->name }}
                                · Ghế {{ $item->seat->row_label }}{{ $item->seat->seat_number }}
                            </p>
                        </div>
                        <p class="font-semibold text-rose-600">{{ number_format($item->price) }}đ</p>
                        <button type="button" title="Xoá (AJAX sẽ nối ở Phase 2)" class="text-gray-400 hover:text-red-500 text-sm">
                            Xoá
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="glass-panel !rounded-2xl flex items-center justify-between mt-6 p-4">
                <span class="text-gray-600">Tổng cộng ({{ count($items) }} vé)</span>
                <span class="text-xl font-bold text-rose-600">{{ number_format($total) }}đ</span>
            </div>

            <button type="button" class="glass-btn-primary mt-6 w-full font-semibold py-3">
                Xác nhận đặt vé (mock — Phase 2 sẽ nối logic thật)
            </button>
        @endif
    </div>
</x-site-layout>
