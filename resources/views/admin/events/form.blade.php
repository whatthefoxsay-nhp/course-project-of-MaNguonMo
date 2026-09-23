@php
    $isEdit = $event->exists;
    $lineupText = collect($event->lineup ?? [])->map(fn ($row) => implode(' | ', array_values($row)))->implode("\n");
    $timelineText = collect($event->timeline ?? [])->map(fn ($row) => implode(' | ', array_values($row)))->implode("\n");
@endphp

<x-admin-layout :header="$isEdit ? 'Sửa Sự Kiện' : 'Thêm Sự Kiện'">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $isEdit ? route('admin.events.update', $event) : route('admin.events.store') }}"
          class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-black/10 shadow-sm space-y-5">
            <h3 class="font-display font-black text-lg">Thông tin chính</h3>

            <x-admin.field label="Tên sự kiện" name="title" :value="$event->title" required />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="category_id" class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1.5">Danh mục <span class="text-[#CC0000]">*</span></label>
                    <select id="category_id" name="category_id" class="admin-input" required>
                        <option value="">— Chọn danh mục —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $event->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="mt-1 text-[11px] font-bold text-[#CC0000]">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="status" class="block text-[11px] font-bold uppercase tracking-wider text-gray-700 mb-1.5">Trạng thái <span class="text-[#CC0000]">*</span></label>
                    <select id="status" name="status" class="admin-input" required>
                        @foreach (['draft' => 'Nháp (ẩn)', 'published' => 'Đang mở bán', 'archived' => 'Lưu trữ (ẩn)'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $event->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="mt-1 text-[11px] font-bold text-[#CC0000]">{{ $message }}</p>@enderror
                </div>
                <x-admin.field label="Thời lượng (phút)" name="duration_minutes" type="number" :value="$event->duration_minutes" min="15" max="1440" />
                <x-admin.field label="Ngày mở bán" name="release_date" type="date" :value="$event->release_date?->format('Y-m-d')" />
            </div>

            <label class="flex items-start gap-3 bg-[#F5F5DC] p-4 rounded-2xl border border-[#D8D8A8] text-xs">
                <input type="hidden" name="is_seated" value="0">
                <input type="checkbox" name="is_seated" value="1" class="mt-0.5" @checked(old('is_seated', $event->is_seated ?? true))>
                <span>
                    <strong class="block text-black">Chọn ghế trên sơ đồ</strong>
                    Bỏ chọn nếu là hội thảo / triển lãm / workshop: khách chỉ chọn hạng vé + số lượng, hệ thống tự gán chỗ.
                </span>
            </label>

            <x-admin.field label="Mô tả" name="description" type="textarea" :value="$event->description" rows="5" />

            <h3 class="font-display font-black text-lg pt-2">Chi tiết hiển thị</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-admin.field label="Tên địa điểm" name="details[venue_name]" :value="$event->venue_name" />
                <x-admin.field label="Địa chỉ địa điểm" name="details[venue_address]" :value="$event->venue_address" />
                <x-admin.field label="MC / Người dẫn" name="details[host_mc]" :value="$event->host_mc" />
                <x-admin.field label="Khách mời đặc biệt" name="details[special_guests]" :value="$event->special_guests" />
            </div>
            <x-admin.field label="Tóm tắt thành phần tham gia" name="details[participants_summary]" :value="$event->participants_summary" />
            <x-admin.field label="Lineup (mỗi dòng: Tên | Vai trò | Nhóm | Huy hiệu)" name="lineup_text" type="textarea" :value="$lineupText" rows="5" />
            <x-admin.field label="Lịch trình (mỗi dòng: Giờ | Tiêu đề | Mô tả)" name="timeline_text" type="textarea" :value="$timelineText" rows="5" />
        </div>

        <div class="space-y-6">
            <div class="bg-white p-6 rounded-3xl border border-black/10 shadow-sm space-y-4">
                <h3 class="font-display font-black text-lg">Poster</h3>
                @if ($isEdit)
                    <img src="{{ $event->poster_url }}" alt="{{ $event->title }}" class="w-full aspect-[2/3] object-cover rounded-2xl border border-black/10">
                @endif
                <x-admin.field label="Tải ảnh mới" name="poster" type="file" accept="image/jpeg,image/png,image/webp" hint="JPG/PNG/WEBP, tối đa 2MB." />
            </div>

            <div class="bg-white p-6 rounded-3xl border border-black/10 shadow-sm flex flex-col gap-3">
                <button type="submit" class="btn-rose px-6 py-3 rounded-2xl text-xs font-black">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo sự kiện' }}</button>
                <a href="{{ route('admin.events.index') }}" class="text-center px-6 py-3 rounded-2xl text-xs font-bold border border-black/10">Hủy</a>
            </div>
        </div>
    </form>
</x-admin-layout>
