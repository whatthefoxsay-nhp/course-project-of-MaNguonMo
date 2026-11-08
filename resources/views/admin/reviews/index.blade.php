<x-admin-layout title="Đánh giá sự kiện" header="Đánh Giá & Bình Luận" subtitle="Theo dõi điểm sao và xử lý nội dung vi phạm.">
    <div class="grid sm:grid-cols-3 gap-4 mb-6">
        @foreach ([['Tổng bình luận', $stats['total']], ['Có chấm sao', $stats['rated']], ['Điểm trung bình', $stats['rated'] ? $stats['average'].' / 5' : 'Chưa có']] as [$label, $value])
            <div class="bg-white border border-stone-200 rounded-2xl p-5"><p class="text-sm text-stone-500">{{ $label }}</p><p class="text-2xl font-bold mt-1">{{ $value }}</p></div>
        @endforeach
    </div>
    <form method="GET" class="bg-white border border-stone-200 rounded-2xl p-4 mb-5 flex flex-wrap gap-3">
        <input name="search" value="{{ request('search') }}" placeholder="Tìm nội dung, sự kiện, người dùng" class="flex-1 min-w-56 rounded-xl border-stone-300 text-sm">
        <select name="rating" class="rounded-xl border-stone-300 text-sm"><option value="">Mọi mức sao</option>@for ($star = 5; $star >= 1; $star--)<option value="{{ $star }}" @selected((string) request('rating') === (string) $star)>{{ $star }} sao</option>@endfor</select>
        <button class="px-4 py-2 rounded-xl bg-stone-900 text-white text-sm font-semibold">Lọc</button>
    </form>
    <div class="space-y-3">
        @forelse ($reviews as $review)
            <article class="bg-white border border-stone-200 rounded-2xl p-5">
                <div class="flex flex-wrap justify-between gap-2"><div><h2 class="font-bold">{{ $review->event?->title ?? 'Sự kiện đã xóa' }}</h2><p class="text-xs text-stone-500 mt-1">{{ $review->user?->name ?? 'Tài khoản đã xóa' }} · {{ $review->created_at->format('d/m/Y H:i') }}</p></div><span class="text-amber-600 font-bold">{{ $review->rating ? str_repeat('★', $review->rating).str_repeat('☆', 5 - $review->rating).' · '.$review->rating.'/5' : 'Chưa chấm sao' }}</span></div>
                <p class="text-sm text-stone-700 whitespace-pre-line mt-3">{{ $review->content }}</p>
                <form method="POST" action="{{ route('admin.reviews.reply', $review) }}" class="mt-4 rounded-xl border border-stone-200 bg-stone-50 p-4 space-y-3">
                    @csrf @method('PATCH')
                    <label for="reply-{{ $review->id }}" class="block text-xs font-bold text-stone-700">{{ $review->admin_replied_at ? 'Sửa phản hồi đã gửi' : 'Trả lời khách' }}</label>
                    <textarea id="reply-{{ $review->id }}" name="reply" rows="2" maxlength="1000" required class="w-full rounded-xl border-stone-300 text-sm" placeholder="Nhập phản hồi công khai dưới bình luận này">{{ old('reply', $review->admin_reply) }}</textarea>
                    @error('reply')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] text-stone-500">Khách sẽ thấy phản hồi trên trang sự kiện và nhận thông báo.</span>
                        <button class="shrink-0 rounded-lg bg-stone-900 px-3 py-2 text-xs font-bold text-white">{{ $review->admin_replied_at ? 'Cập nhật phản hồi' : 'Gửi phản hồi' }}</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('comments.destroy', $review) }}" class="mt-4" onsubmit="return confirm('Xóa bình luận này?')">@csrf @method('DELETE')<button class="text-xs font-bold text-red-700 hover:underline">Xóa bình luận</button></form>
            </article>
        @empty
            <div class="bg-white border border-stone-200 rounded-2xl p-8 text-center text-stone-500">Chưa có bình luận phù hợp.</div>
        @endforelse
    </div>
    <div class="mt-5">{{ $reviews->links() }}</div>
</x-admin-layout>
