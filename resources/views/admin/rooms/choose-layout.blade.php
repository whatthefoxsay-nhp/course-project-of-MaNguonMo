<x-admin-layout header="Tạo sơ đồ khán phòng" subtitle="Chọn một kiểu sơ đồ độc lập để bắt đầu thiết kế">
    <x-slot:breadcrumb>
        <a href="{{ route('admin.rooms.index') }}" class="text-gray-500 hover:text-black">Khán phòng</a>
        <span class="text-gray-300">›</span>
        <span class="font-bold text-emerald-900">Tạo sơ đồ</span>
    </x-slot:breadcrumb>

    <div class="mx-auto grid max-w-5xl gap-5 md:grid-cols-2">
        <a href="{{ route('admin.rooms.create', ['mode' => 'seats']) }}" class="group rounded-3xl border-2 border-gray-200 bg-white p-7 shadow-sm transition hover:border-emerald-700 hover:shadow-lg">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-3xl">💺</div>
            <h2 class="mt-5 text-xl font-black">Sơ đồ ghế ngồi</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600">Tạo từng hàng ghế, lối đi và phân loại ghế. Mỗi ghế là một chỗ riêng để khách chọn trực tiếp.</p>
            <span class="mt-6 inline-flex rounded-full bg-emerald-800 px-5 py-2.5 text-sm font-bold text-white">Thiết kế sơ đồ ghế →</span>
        </a>

        <a href="{{ route('admin.rooms.create', ['mode' => 'zones']) }}" class="group rounded-3xl border-2 border-gray-200 bg-white p-7 shadow-sm transition hover:border-amber-500 hover:shadow-lg">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-3xl">🗺️</div>
            <h2 class="mt-5 text-xl font-black">Sơ đồ khu</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600">Chia mặt bằng thành các khu độc lập. Khu ghế có sơ đồ ghế riêng; khu đứng hoặc vé vào cửa bán theo số lượng.</p>
            <span class="mt-6 inline-flex rounded-full bg-slate-900 px-5 py-2.5 text-sm font-bold text-white">Thiết kế sơ đồ khu →</span>
        </a>
    </div>
</x-admin-layout>
