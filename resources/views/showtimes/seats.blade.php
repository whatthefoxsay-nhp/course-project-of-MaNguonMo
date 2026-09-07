@php
    $rows = collect($seats)->groupBy('row_label');
@endphp

<x-site-layout :title="'Chọn ghế - '.$movie->title">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <a href="{{ route('movies.show', $movie->slug) }}" class="text-sm text-gray-500 hover:text-rose-600">&larr; {{ $movie->title }}</a>
        <h1 class="text-xl font-bold mt-1">
            Chọn ghế · {{ $showtime->start_time->format('H:i d/m') }} · {{ $showtime->room->name }}
        </h1>

        <div class="glass-panel mt-6 p-6 sm:p-8">
            <div class="mb-6">
                <div class="h-2 bg-white/60 backdrop-blur-sm rounded-full mx-12 mb-1 shadow-inner"></div>
                <p class="text-center text-xs text-gray-400 uppercase tracking-widest">Màn hình</p>
            </div>

            <div class="space-y-2">
                @foreach ($rows as $rowLabel => $rowSeats)
                    <div class="flex items-center gap-3">
                        <span class="w-4 text-xs text-gray-400 font-medium">{{ $rowLabel }}</span>
                        <div class="flex gap-2 flex-wrap">
                            @foreach ($rowSeats as $seat)
                                @php
                                    $classes = match ($seat->status) {
                                        'booked' => 'bg-gray-300/50 border-gray-300/50 text-gray-400 cursor-not-allowed',
                                        'held' => 'bg-amber-200/60 border-amber-300/60 text-amber-700 cursor-not-allowed',
                                        default => $seat->type === 'vip'
                                            ? 'bg-white/40 border-amber-400/70 text-amber-600 hover:bg-amber-50/60 cursor-pointer'
                                            : 'bg-white/40 border-white/70 text-gray-600 hover:border-rose-400 hover:text-rose-600 cursor-pointer',
                                    };
                                @endphp
                                <button
                                    type="button"
                                    @disabled(in_array($seat->status, ['booked', 'held'], true))
                                    title="{{ $seat->row_label }}{{ $seat->seat_number }} ({{ $seat->status }})"
                                    class="glass-seat w-8 h-8 border text-[11px] font-semibold flex items-center justify-center {{ $classes }}"
                                >{{ $seat->seat_number }}</button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-5 mt-8 text-xs text-gray-500">
                <span class="flex items-center gap-2"><span class="w-4 h-4 rounded border border-white/70 bg-white/40 inline-block"></span> Còn trống</span>
                <span class="flex items-center gap-2"><span class="w-4 h-4 rounded border border-amber-400/70 bg-white/40 inline-block"></span> VIP</span>
                <span class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-amber-200/60 inline-block"></span> Đang được giữ</span>
                <span class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-gray-300/50 inline-block"></span> Đã đặt</span>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between">
            <p class="text-sm text-gray-500 max-w-sm">
                Chọn ghế và bấm "Thêm vào giỏ" (AJAX giữ ghế sẽ nối ở Phase 2 — hiện đang xem giao diện).
            </p>
            <a href="{{ route('cart.index') }}" class="glass-btn-primary text-sm font-semibold px-6 py-2.5">
                Thêm vào giỏ
            </a>
        </div>
    </div>
</x-site-layout>
