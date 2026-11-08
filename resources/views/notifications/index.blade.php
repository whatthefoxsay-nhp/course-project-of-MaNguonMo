<x-site-layout title="Lịch sử thông báo - TicketBox">
    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
        <div class="flex items-end justify-between gap-4 mb-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-rose-taupe">Tài khoản của bạn</p>
                <h1 class="font-display font-black text-2xl sm:text-3xl text-black mt-1">Lịch sử thông báo</h1>
                <p class="text-sm text-gray-600 mt-2">Thông báo vẫn được lưu tại đây sau khi bạn đã mở và đọc.</p>
            </div>
            <span class="hidden sm:inline-flex rounded-full bg-[#FAF9F6] border border-black/10 px-3 py-1.5 text-xs font-bold text-gray-700">{{ $notifications->total() }} thông báo</span>
        </div>

        <div class="space-y-3">
            @forelse ($notifications as $notification)
                @php
                    $kind = $notification->data['kind'] ?? '';
                    $title = $notification->data['title'] ?? 'Thông báo';
                    $message = $notification->data['message'] ?? '';
                @endphp
                <article class="rounded-2xl border p-4 sm:p-5 {{ $notification->read_at ? 'bg-white border-black/10' : 'bg-sky-50/70 border-sky-200' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="text-[10px] font-black uppercase tracking-wider text-rose-taupe">
                                    {{ match($kind) { 'announcement' => 'Thông báo hệ thống', 'comment_reply' => 'Phản hồi bình luận', 'showtime_reminder' => 'Nhắc suất diễn', default => 'Thông báo' } }}
                                </span>
                                @if (!$notification->read_at)
                                    <span class="rounded-full bg-sky-700 px-2 py-0.5 text-[9px] font-bold text-white">CHƯA ĐỌC</span>
                                @else
                                    <span class="text-[10px] text-gray-400">Đã đọc</span>
                                @endif
                            </div>
                            <h2 class="font-display font-black text-base text-black">{{ $title }}</h2>
                            <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $message }}</p>
                            <time class="block text-[11px] text-gray-400 mt-3" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->locale('vi')->format('d/m/Y · H:i') }}</time>
                        </div>
                        <a href="{{ route('notifications.open', $notification) }}" class="shrink-0 rounded-xl bg-black px-3.5 py-2 text-xs font-bold text-white hover:bg-rose-taupe transition-colors">Mở</a>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-black/20 bg-white p-10 text-center">
                    <p class="font-bold text-gray-800">Bạn chưa có thông báo nào.</p>
                    <p class="text-sm text-gray-500 mt-1">Thông báo từ hệ thống và phản hồi bình luận sẽ được lưu tại đây.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>
    </main>
</x-site-layout>
