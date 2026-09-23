@php $isEdit = $category->exists; @endphp

<x-admin-layout :header="$isEdit ? 'Sửa Danh Mục' : 'Thêm Danh Mục'">
    <form method="POST"
          action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
          class="max-w-2xl bg-white p-6 rounded-3xl border border-black/10 shadow-sm space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <x-admin.field label="Tên danh mục" name="name" :value="$category->name" required />
        <x-admin.field label="Mô tả" name="description" type="textarea" :value="$category->description" />

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn-rose px-6 py-3 rounded-2xl text-xs font-black">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo danh mục' }}</button>
            <a href="{{ route('admin.categories.index') }}" class="px-6 py-3 rounded-2xl text-xs font-bold border border-black/10">Hủy</a>
        </div>
    </form>
</x-admin-layout>
