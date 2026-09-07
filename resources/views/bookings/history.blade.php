<x-site-layout :title="'Lịch sử đặt vé'">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold mb-6">Lịch sử đặt vé</h1>

        @if (empty($bookings))
            <div class="glass-panel p-6 text-gray-500">Bạn chưa có vé nào.</div>
        @else
            <div class="space-y-4">
                @foreach ($bookings as $booking)
                    @php
                        $statusClasses = $booking->status === 'confirmed'
                            ? 'bg-green-100/70 text-green-700'
                            : 'bg-gray-200/70 text-gray-500';
                        $statusLabel = $booking->status === 'confirmed' ? 'Đã xác nhận' : 'Đã huỷ';
                    @endphp
                    <div class="glass-panel !rounded-2xl p-4 flex items-center gap-4">
                        <img src="{{ $booking->movie->poster_path }}" alt="" class="w-14 h-20 object-cover rounded-xl">
                        <div class="flex-1">
                            <p class="font-semibold">{{ $booking->movie->title }}</p>
                            <p class="text-sm text-gray-500">
                                {{ $booking->showtime->start_time->format('H:i d/m/Y') }} ·
                                Ghế {{ implode(', ', $booking->seats) }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">Mã vé: {{ $booking->booking_code }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block text-xs font-medium px-2 py-1 rounded-full {{ $statusClasses }}">
                                {{ $statusLabel }}
                            </span>
                            <p class="font-semibold text-rose-600 mt-1">{{ number_format($booking->total_price) }}đ</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-site-layout>
