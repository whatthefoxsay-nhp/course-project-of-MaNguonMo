@php
    $isEdit = $event->exists;
    $lineupRows = is_array($event->lineup) ? $event->lineup : ($event->lineup ? [$event->lineup] : []);
    $timelineRows = is_array($event->timeline) ? $event->timeline : ($event->timeline ? [$event->timeline] : []);
    $lineupText = collect($lineupRows)->map(fn ($row) => is_array($row) ? implode(' | ', array_values($row)) : (string) $row)->implode("\n");
    $timelineText = collect($timelineRows)->map(fn ($row) => is_array($row) ? implode(' | ', array_values($row)) : (string) $row)->implode("\n");
@endphp

<x-admin-layout 
    :header="$isEdit ? 'Sửa Sự Kiện' : 'Thêm Sự Kiện'"
    :subtitle="$isEdit ? 'Cập nhật thông tin chi tiết, nghệ sĩ và hình ảnh cho [' . $event->title . ']' : 'Tạo mới chương trình sự kiện / liveshow trên hệ thống TicketAdmin'"
>
    <x-slot:breadcrumb>
        <span class="text-gray-400">Tổng quan</span>
        <span class="text-gray-300">›</span>
        <a href="{{ route('admin.events.index') }}" class="text-gray-400 hover:text-black">Sự kiện</a>
        <span class="text-gray-300">›</span>
        <span class="text-[#123D22] font-black">{{ $isEdit ? 'Chỉnh sửa' : 'Thêm mới' }}</span>
    </x-slot:breadcrumb>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $isEdit ? route('admin.events.update', $event) : route('admin.events.store') }}"
          class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="lg:col-span-2 bg-white p-6 sm:p-7 rounded-3xl border border-[#E3EAE4] shadow-sm space-y-5">
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

                @if ($isEdit)
                    <div class="sm:col-span-2 bg-[#F6F8F6] p-3.5 rounded-2xl border border-emerald-200/80">
                        <label class="flex items-start gap-2.5 text-xs text-slate-800 font-semibold cursor-pointer">
                            <input type="checkbox" name="sync_showtimes" value="1" checked class="mt-0.5 rounded text-[#123D22] focus:ring-[#123D22]">
                            <div>
                                <strong class="text-[#123D22] block font-bold">Tự động dời ngày các suất diễn theo Ngày mở bán mới</strong>
                                <span class="text-[11px] text-gray-500 font-normal">Khi thay đổi ngày mở bán sang ngày mới, các suất diễn sẽ tự động dời ngày theo (giữ nguyên khung giờ biểu diễn) để tiếp tục mở bán và không bị báo hết hạn.</span>
                            </div>
                        </label>
                    </div>
                @endif
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

            @if ($isEdit)
                <!-- Danh sách suất diễn của sự kiện -->
                <div class="pt-4 border-t border-black/10 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-display font-black text-lg text-black">Lịch Diễn &amp; Suất Mở Bán Của Sự Kiện</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Quản lý trực tiếp các khung giờ và trạng thái mở bán</p>
                        </div>
                        <a 
                            href="{{ route('admin.showtimes.create', ['movie_id' => $event->id]) }}" 
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-[#123D22] bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition-colors inline-flex items-center gap-1 shadow-xs"
                            target="_blank"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Thêm Suất Mới</span>
                        </a>
                    </div>

                    @if ($event->showtimes && $event->showtimes->isNotEmpty())
                        <div class="overflow-x-auto rounded-2xl border border-black/10">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-[#F6F8F6] text-gray-600 font-bold border-b border-black/10">
                                    <tr>
                                        <th class="py-2.5 px-3">Suất</th>
                                        <th class="py-2.5 px-3">Khán Phòng</th>
                                        <th class="py-2.5 px-3">Thời Gian</th>
                                        <th class="py-2.5 px-3">Giá Vé</th>
                                        <th class="py-2.5 px-3">Trạng Thái</th>
                                        <th class="py-2.5 px-3 text-right">Thao Tác</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-black/5">
                                    @foreach ($event->showtimes->sortBy('start_time') as $st)
                                        @php
                                            $isPast = $st->start_time <= now();
                                        @endphp
                                        <tr class="hover:bg-black/[0.02]">
                                            <td class="py-2 px-3 font-mono font-bold text-gray-800">#ST-{{ $st->id }}</td>
                                            <td class="py-2 px-3 font-medium text-gray-800">{{ $st->room?->name ?? 'N/A' }}</td>
                                            <td class="py-2 px-3">
                                                <div class="font-bold text-black">{{ $st->start_time->format('H:i') }} - {{ $st->end_time?->format('H:i') }}</div>
                                                <div class="text-[11px] text-gray-500">{{ $st->start_time->format('d/m/Y') }}</div>
                                            </td>
                                            <td class="py-2 px-3 font-bold text-amber-900">{{ number_format($st->base_price) }}₫</td>
                                            <td class="py-2 px-3">
                                                @if ($isPast)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                        Đã qua giờ (Hết hạn)
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-800 border border-emerald-300">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                        Đang mở bán
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3 text-right">
                                                <a 
                                                    href="{{ route('admin.showtimes.edit', $st) }}" 
                                                    class="text-indigo-600 hover:text-indigo-900 font-bold hover:underline"
                                                    target="_blank"
                                                >
                                                    Sửa suất &rarr;
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-800 font-medium">
                            Sự kiện này chưa có suất diễn nào. Hãy bấm "Thêm Suất Mới" ở góc trên để mở bán vé.
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white p-6 rounded-3xl border border-black/10 shadow-sm space-y-4">
                <h3 class="font-display font-black text-lg">Poster</h3>
                @if ($isEdit)
                    <img src="{{ $event->poster_url }}" alt="{{ $event->title }}" class="w-full aspect-[2/3] object-cover rounded-2xl border border-black/10">
                @endif
                <x-admin.field label="Tải ảnh mới" name="poster" type="file" accept="image/jpeg,image/png,image/webp" hint="JPG/PNG/WEBP, tối đa 2MB." />
            </div>

            <div class="bg-white p-6 rounded-3xl border border-[#E3EAE4] shadow-sm flex flex-col gap-3">
                <button 
                    type="submit" 
                    class="w-full py-3.5 rounded-2xl text-xs font-black text-white shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200"
                    style="background: linear-gradient(135deg, #123D22 0%, #1e662e 100%);"
                >
                    {{ $isEdit ? 'Lưu thay đổi' : 'Tạo sự kiện' }}
                </button>
                <a href="{{ route('admin.events.index') }}" class="text-center py-3 rounded-2xl text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">Hủy</a>
            </div>
        </div>
    </form>
</x-admin-layout>
