@php $isEdit = $category->exists; @endphp

<x-admin-layout 
    :header="$isEdit ? 'Sửa Danh Mục' : 'Thêm Danh Mục'"
    :subtitle="$isEdit ? 'Cập nhật tên và thông tin phân loại cho danh mục [' . $category->name . ']' : 'Tạo mới danh mục phân loại sự kiện cho hệ thống'"
>
    <x-slot:breadcrumb>
        <a href="{{ route('admin.dashboard') }}" class="hover:text-black transition-colors">Tổng quan</a>
        <span class="text-gray-300">›</span>
        <a href="{{ route('admin.categories.index') }}" class="hover:text-black transition-colors">Danh mục</a>
        <span class="text-gray-300">›</span>
        <span class="text-[#123D22] font-black">{{ $isEdit ? 'Chỉnh sửa' : 'Thêm mới' }}</span>
    </x-slot:breadcrumb>

    <div class="max-w-2xl">
        <form method="POST"
              action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
              class="bg-white p-7 rounded-3xl border border-[#E3EAE4] shadow-sm space-y-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="border-b border-gray-100 pb-4">
                <h3 class="font-display font-black text-lg text-slate-900">
                    {{ $isEdit ? 'Thông Tin Danh Mục' : 'Tạo Danh Mục Mới' }}
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Các danh mục giúp phân loại concert, kịch, hội thảo để khách hàng dễ dàng tìm kiếm.
                </p>
            </div>

            <x-admin.field label="Tên danh mục" name="name" :value="$category->name" required />
            <x-admin.field label="Mô tả" name="description" type="textarea" :value="$category->description" />

            <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                <button 
                    type="submit" 
                    class="px-6 py-3 rounded-2xl text-xs font-black text-white shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200"
                    style="background: linear-gradient(135deg, #123D22 0%, #1e662e 100%);"
                >
                    {{ $isEdit ? 'Lưu thay đổi' : 'Tạo danh mục' }}
                </button>
                <a 
                    href="{{ route('admin.categories.index') }}" 
                    class="px-6 py-3 rounded-2xl text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors"
                >
                    Hủy bỏ
                </a>
            </div>
        </form>
    </div>
</x-admin-layout>
