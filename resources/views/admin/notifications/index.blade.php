<x-admin-layout title="Thông báo hệ thống" header="Thông Báo Hệ Thống" subtitle="Soạn thông báo và gửi đến tài khoản khách hàng đang hoạt động.">
    <div class="max-w-3xl space-y-6">
        <form method="POST" action="{{ route('admin.notifications.store') }}" class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4">
            @csrf
            <div><label for="title" class="block text-sm font-semibold mb-1">Tiêu đề</label><input id="title" name="title" maxlength="255" required value="{{ old('title') }}" class="w-full rounded-xl border-stone-300" placeholder="Ví dụ: Thay đổi lịch diễn"></div>
            <div><label for="message" class="block text-sm font-semibold mb-1">Nội dung</label><textarea id="message" name="message" rows="5" maxlength="5000" required class="w-full rounded-xl border-stone-300" placeholder="Nội dung thông báo gửi đến khách hàng">{{ old('message') }}</textarea></div>
            @error('title')<p class="text-sm text-red-700">{{ $message }}</p>@enderror @error('message')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
            <p class="text-xs text-stone-500">Thông báo được lưu trong chuông của người nhận. Tài khoản Admin và tài khoản bị khóa không nhận thông báo.</p>
            <button class="rounded-xl bg-stone-900 px-5 py-3 text-sm font-bold text-white" onclick="return confirm('Gửi thông báo này đến tất cả khách hàng đang hoạt động?')">Gửi thông báo</button>
        </form>
        <section class="bg-white border border-stone-200 rounded-2xl p-6">
            <h2 class="font-bold mb-4">Thông báo đã gửi gần đây</h2>
            <div class="divide-y divide-stone-100">@forelse ($announcements as $announcement) @php($data = json_decode($announcement->data, true) ?? [])<article class="py-3"><p class="font-semibold">{{ $data['title'] ?? 'Thông báo' }}</p><p class="text-sm text-stone-600 mt-1">{{ $data['message'] ?? '' }}</p><p class="text-xs text-stone-400 mt-1">{{ \Illuminate\Support\Carbon::parse($announcement->created_at)->format('d/m/Y H:i') }}</p></article>@empty<p class="text-sm text-stone-500">Chưa gửi thông báo nào.</p>@endforelse</div>
        </section>
    </div>
</x-admin-layout>
