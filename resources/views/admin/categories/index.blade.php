<x-admin-layout :header="'Quản Lý Danh Mục'">
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-black/10 shadow-sm">
            <div>
                <h2 class="font-display font-black text-xl text-black">Danh Mục Sự Kiện</h2>
                <p class="text-xs text-gray-500 mt-0.5">Phân loại concert, hòa nhạc, hội thảo, triển lãm…</p>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="btn-rose px-5 py-3 rounded-2xl text-xs font-black shadow-md">+ Thêm Danh Mục</a>
        </div>

        <form method="GET" action="{{ route('admin.categories.index') }}" class="bg-white p-5 rounded-3xl border border-black/10 shadow-sm flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên danh mục…" class="admin-input">
            <button type="submit" class="btn-dark px-5 rounded-2xl text-xs font-black">Lọc</button>
        </form>

        <div class="bg-white rounded-3xl border border-black/10 shadow-sm overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#FAF9F6] text-[11px] font-black uppercase tracking-wider text-gray-600">
                    <tr>
                        <th class="py-3 px-6">Tên</th>
                        <th class="py-3 px-6">Slug</th>
                        <th class="py-3 px-6">Số sự kiện</th>
                        <th class="py-3 px-6 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/5">
                    @forelse ($categories as $category)
                        <tr>
                            <td class="py-4 px-6">
                                <div class="font-bold text-black">{{ $category->name }}</div>
                                <div class="text-gray-500 line-clamp-1">{{ $category->description }}</div>
                            </td>
                            <td class="py-4 px-6 font-mono text-gray-600">{{ $category->slug }}</td>
                            <td class="py-4 px-6 font-bold">{{ $category->events_count }}</td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="px-3.5 py-1.5 rounded-xl font-bold bg-[#FAF9F6] border border-black/10 hover:bg-black hover:text-white">Sửa</a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Xóa danh mục này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3.5 py-1.5 rounded-xl font-bold text-[#CC0000] border border-[#CC0000]/30 hover:bg-[#CC0000] hover:text-white">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-12 text-center text-gray-500">Chưa có danh mục nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $categories->links() }}</div>
    </div>
</x-admin-layout>
