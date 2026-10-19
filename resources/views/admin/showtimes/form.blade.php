@php $isEdit = $showtime->exists; @endphp

<x-admin-layout 
    :header="$isEdit ? 'Sửa Suất Diễn' : 'Thêm Suất Diễn'"
    :subtitle="$isEdit ? 'Cập nhật thời gian bắt đầu, kết thúc và giá vé cho suất diễn #' . $showtime->id : 'Thiết lập khung giờ và khán phòng mở bán vé cho sự kiện'"
>
    <x-slot:breadcrumb>
        <span class="text-gray-400">Tổng quan</span>
        <span class="text-gray-300">›</span>
        <a href="{{ route('admin.showtimes.index') }}" class="text-gray-400 hover:text-black">Lịch diễn</a>
        <span class="text-gray-300">›</span>
        <span class="text-[#123D22] font-black">{{ $isEdit ? 'Chỉnh sửa' : 'Thêm mới' }}</span>
    </x-slot:breadcrumb>

    <form method="POST"
          action="{{ $isEdit ? route('admin.showtimes.update', $showtime) : route('admin.showtimes.store') }}"
          class="max-w-3xl bg-white p-6 sm:p-7 rounded-3xl border border-[#E3EAE4] shadow-sm space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div>
            <label for="event_id" class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1.5">Sự kiện <span class="text-[#CC0000]">*</span></label>
            <select id="event_id" name="event_id" class="admin-input" required>
                <option value="">— Chọn sự kiện —</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected(old('event_id', $showtime->event_id) == $event->id)>{{ $event->title }}</option>
                @endforeach
            </select>
            @error('event_id')<p class="mt-1 text-[11px] font-bold text-[#CC0000]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="room_id" class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1.5">Khán phòng <span class="text-[#CC0000]">*</span></label>
            <select id="room_id" name="room_id" class="admin-input" required>
                <option value="">— Chọn khán phòng —</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->id }}" @selected(old('room_id', $showtime->room_id) == $room->id)>{{ $room->name }} ({{ $room->seats_count }} chỗ)</option>
                @endforeach
            </select>
            @error('room_id')<p class="mt-1 text-[11px] font-bold text-[#CC0000]">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-admin.field label="Bắt đầu" name="start_time" type="datetime-local" :value="$showtime->start_time?->format('Y-m-d\TH:i')" required />
            <x-admin.field label="Kết thúc" name="end_time" type="datetime-local" :value="$showtime->end_time?->format('Y-m-d\TH:i')" required />
            <x-admin.field label="Giá vé cơ bản (₫)" name="base_price" type="number" :value="$showtime->base_price" min="10000" step="1000" required hint="Giá từng hạng ghế = giá cơ bản + phụ thu hạng." />
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button 
                type="submit" 
                class="px-6 py-3 rounded-2xl text-xs font-black text-white shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all"
                style="background: linear-gradient(135deg, #123D22 0%, #1e662e 100%);"
            >
                {{ $isEdit ? 'Lưu thay đổi' : 'Tạo suất diễn' }}
            </button>
            <a href="{{ route('admin.showtimes.index') }}" class="px-6 py-3 rounded-2xl text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">Hủy</a>
        </div>
    </form>
</x-admin-layout>
