<x-admin-layout 
    :header="'Danh Mục Sự Kiện'" 
    :subtitle="'Phân loại và quản lý các nhóm sự kiện: concert, hòa nhạc, hội thảo, triển lãm…'"
>
    <x-slot:breadcrumb>
        <a href="{{ route('admin.dashboard') }}" class="hover:text-black transition-colors">Tổng quan</a>
        <span class="text-gray-300">›</span>
        <span class="text-gray-400">Nội dung</span>
        <span class="text-gray-300">›</span>
        <span class="text-[#123D22] font-black">Danh mục</span>
    </x-slot:breadcrumb>

    @php
        if (! function_exists('getCategoryVisual')) {
            function getCategoryVisual($name) {
                $lower = mb_strtolower($name);
                if (str_contains($lower, 'concert') || str_contains($lower, 'ca nhạc')) {
                    return ['bg' => 'bg-purple-50 text-purple-700 border-purple-200/80', 'chip' => 'bg-purple-100 text-purple-800', 'accent' => '#8B5CF6', 'icon' => 'music'];
                } elseif (str_contains($lower, 'fan') || str_contains($lower, 'meeting')) {
                    return ['bg' => 'bg-pink-50 text-pink-700 border-pink-200/80', 'chip' => 'bg-pink-100 text-pink-800', 'accent' => '#EC4899', 'icon' => 'heart'];
                } elseif (str_contains($lower, 'festival') || str_contains($lower, 'lễ hội')) {
                    return ['bg' => 'bg-orange-50 text-orange-700 border-orange-200/80', 'chip' => 'bg-orange-100 text-orange-800', 'accent' => '#F97316', 'icon' => 'sparkles'];
                } elseif (str_contains($lower, 'hòa nhạc') || str_contains($lower, 'giao hưởng')) {
                    return ['bg' => 'bg-blue-50 text-blue-700 border-blue-200/80', 'chip' => 'bg-blue-100 text-blue-800', 'accent' => '#3B82F6', 'icon' => 'disc'];
                } elseif (str_contains($lower, 'hội thảo') || str_contains($lower, 'workshop') || str_contains($lower, 'talkshow')) {
                    return ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80', 'chip' => 'bg-emerald-100 text-emerald-800', 'accent' => '#10B981', 'icon' => 'presentation'];
                } elseif (str_contains($lower, 'triển lãm') || str_contains($lower, 'nghệ thuật')) {
                    return ['bg' => 'bg-amber-50 text-amber-700 border-amber-200/80', 'chip' => 'bg-amber-100 text-amber-800', 'accent' => '#F59E0B', 'icon' => 'palette'];
                } elseif (str_contains($lower, 'thể thao') || str_contains($lower, 'sport') || str_contains($lower, 'giải đấu')) {
                    return ['bg' => 'bg-cyan-50 text-cyan-700 border-cyan-200/80', 'chip' => 'bg-cyan-100 text-cyan-800', 'accent' => '#06B6D4', 'icon' => 'trophy'];
                } else {
                    return ['bg' => 'bg-teal-50 text-teal-700 border-teal-200/80', 'chip' => 'bg-teal-100 text-teal-800', 'accent' => '#14B8A6', 'icon' => 'tag'];
                }
            }
        }
    @endphp

    <div 
        x-data="{
            viewMode: localStorage.getItem('category_view_mode') || 'table',
            setViewMode(mode) {
                this.viewMode = mode;
                localStorage.setItem('category_view_mode', mode);
            },
            searchQuery: '{{ request('search') }}',
            selectedIds: [],
            selectAll: false,
            toggleSelectAll(items) {
                if (this.selectAll) {
                    this.selectedIds = items.map(i => i.id);
                } else {
                    this.selectedIds = [];
                }
            },
            // Toast notification
            toast: {
                show: false,
                message: '',
                type: 'success',
                timer: null,
            },
            showToast(message, type = 'success') {
                this.toast.message = message;
                this.toast.type = type;
                this.toast.show = true;
                clearTimeout(this.toast.timer);
                this.toast.timer = setTimeout(() => { this.toast.show = false; }, 3200);
            },
            copyToClipboard(text) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                    this.showToast('Đã chép slug: ' + text);
                }
            },
            // Modal Add / Edit
            formModal: {
                open: false,
                isEdit: false,
                actionUrl: '',
                name: '',
                slugPreview: '',
                description: '',
            },
            openCreateModal() {
                this.formModal.isEdit = false;
                this.formModal.actionUrl = '{{ route('admin.categories.store') }}';
                this.formModal.name = '';
                this.formModal.slugPreview = '';
                this.formModal.description = '';
                this.formModal.open = true;
            },
            openEditModal(category) {
                this.formModal.isEdit = true;
                this.formModal.actionUrl = '/admin/categories/' + category.id;
                this.formModal.name = category.name;
                this.formModal.slugPreview = category.slug;
                this.formModal.description = category.description || '';
                this.formModal.open = true;
            },
            generateSlug(text) {
                return text.toString().toLowerCase().trim()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[đĐ]/g, 'd')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            },
            // Delete confirm modal
            deleteModal: {
                open: false,
                category: null,
                actionUrl: '',
            },
            openDeleteModal(category) {
                this.deleteModal.category = category;
                this.deleteModal.actionUrl = '/admin/categories/' + category.id;
                this.deleteModal.open = true;
            }
        }"
        class="space-y-6"
    >
        <!-- Flash session message bridge to toast -->
        @if (session('success'))
            <div x-init="showToast('{{ addslashes(session('success')) }}', 'success')"></div>
        @endif
        @if (session('error'))
            <div x-init="showToast('{{ addslashes(session('error')) }}', 'error')"></div>
        @endif

        <!-- 1. Stats Cards Grid (4 KPI Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Tổng danh mục -->
            <div class="bg-white p-5 rounded-3xl border border-[#E3EAE4] shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tổng danh mục</span>
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="font-display font-black text-3xl text-slate-900">{{ $stats['total_categories'] ?? 0 }}</span>
                    <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Hoạt động</span>
                </div>
                <p class="text-[11px] text-gray-400 mt-1">Phân loại đang được công khai</p>
            </div>

            <!-- Card 2: Tổng sự kiện trực thuộc -->
            <div class="bg-white p-5 rounded-3xl border border-[#E3EAE4] shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tổng sự kiện</span>
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-700 border border-amber-200/80 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="font-display font-black text-3xl text-slate-900">{{ $stats['total_events'] ?? 0 }}</span>
                    <span class="text-xs font-semibold text-gray-500">sự kiện</span>
                </div>
                <p class="text-[11px] text-gray-400 mt-1">Đã gắn phân loại danh mục</p>
            </div>

            <!-- Card 3: Danh mục nhiều sự kiện nhất -->
            <div class="bg-white p-5 rounded-3xl border border-[#E3EAE4] shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Sôi động nhất</span>
                    <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-700 border border-purple-200/80 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="font-display font-black text-xl text-slate-900 truncate">
                        {{ $stats['top_category']->name ?? 'Chưa có' }}
                    </div>
                    <div class="text-[11px] text-purple-700 font-bold mt-1 flex items-center gap-1">
                        <span>🔥 {{ $stats['top_category']->events_count ?? 0 }} sự kiện liên kết</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: Danh mục chưa có sự kiện -->
            <div class="bg-white p-5 rounded-3xl border border-[#E3EAE4] shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Chưa có show</span>
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-700 border border-rose-200/80 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="font-display font-black text-3xl {{ ($stats['empty_categories_count'] ?? 0) > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                        {{ $stats['empty_categories_count'] ?? 0 }}
                    </span>
                    <span class="text-xs font-semibold text-gray-500">danh mục</span>
                </div>
                <p class="text-[11px] text-gray-400 mt-1">Cần bổ sung sự kiện hoặc xóa an toàn</p>
            </div>
        </div>

        <!-- 2. Header Banner + Create Action Button -->
        <div class="relative bg-gradient-to-r from-white via-white to-[#F1F8F2] p-6 rounded-3xl border border-[#E3EAE4] shadow-sm overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Decorative ticket/tag watermark SVG -->
            <svg class="absolute right-4 -bottom-8 w-44 h-44 text-[#123D22] opacity-[0.06] pointer-events-none transform -rotate-12" fill="currentColor" viewBox="0 0 24 24">
                <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>
            </svg>

            <div class="relative z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200/80 text-[#123D22] text-xs font-bold mb-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Phân Loại Danh Mục Hệ Thống</span>
                </div>
                <h2 class="font-display font-black text-2xl text-slate-900 tracking-tight">Quản Lý Danh Mục Sự Kiện</h2>
                <p class="text-xs text-gray-500 mt-1 max-w-xl">
                    Tổ chức cây danh mục trực quan giúp khách hàng tìm kiếm và lọc vé nhanh chóng theo từng gu thưởng thức.
                </p>
            </div>

            <div class="relative z-10 flex items-center gap-3 shrink-0">
                <button 
                    @click="openCreateModal()"
                    type="button" 
                    class="px-5 py-3 rounded-2xl text-xs font-black text-white flex items-center gap-2 shadow-lg shadow-emerald-950/20 hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200"
                    style="background: linear-gradient(135deg, #123D22 0%, #1e662e 100%);"
                >
                    <div class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center font-bold text-sm leading-none">+</div>
                    <span>Thêm Danh Mục Mới</span>
                </button>
            </div>
        </div>

        <!-- 3. Toolbar: Search, Filter, Sort, View Toggle -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-[#E3EAE4] shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3.5">
            <!-- Search Form -->
            <form method="GET" action="{{ route('admin.categories.index') }}" class="flex-1 flex items-center gap-2">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>

                    <input 
                        type="text" 
                        name="search" 
                        x-model="searchQuery"
                        placeholder="Tìm theo tên, slug hoặc mô tả danh mục…" 
                        class="w-full pl-10 pr-9 py-2.5 rounded-2xl border border-gray-200 text-xs font-medium placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-400/50 focus:border-amber-400 transition-all bg-[#FAF9F6]"
                    >

                    <!-- Clear Search Button -->
                    <button 
                        type="button" 
                        x-show="searchQuery && searchQuery.length > 0" 
                        @click="searchQuery = ''; $el.form.submit()" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                        title="Xóa tìm kiếm"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Hidden inputs to preserve sorting & pagination count -->
                <input type="hidden" name="sort" value="{{ request('sort', 'name_asc') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">

                <button 
                    type="submit" 
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold text-white transition-all duration-200 shrink-0 shadow-sm"
                    style="background: #123D22;"
                >
                    Lọc
                </button>

                @if(request('search') || request('sort'))
                    <a 
                        href="{{ route('admin.categories.index') }}" 
                        class="text-xs font-bold text-gray-500 hover:text-rose-600 px-2 py-2 transition-colors shrink-0"
                    >
                        Đặt lại
                    </a>
                @endif
            </form>

            <!-- Sorting & View Mode Switcher -->
            <div class="flex items-center gap-2.5 justify-end shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-gray-100">
                <!-- Sắp xếp dropdown -->
                <form method="GET" action="{{ route('admin.categories.index') }}" class="flex items-center gap-1.5">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                    <label class="text-[11px] font-bold text-gray-400 uppercase tracking-wider hidden lg:inline">Sắp xếp:</label>
                    <select 
                        name="sort" 
                        onchange="this.form.submit()" 
                        class="py-2 pl-3 pr-8 rounded-2xl border border-gray-200 text-xs font-bold text-slate-700 bg-[#FAF9F6] focus:outline-none focus:ring-2 focus:ring-amber-400/50 cursor-pointer"
                    >
                        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Tên A – Z</option>
                        <option value="events_desc" {{ request('sort') == 'events_desc' ? 'selected' : '' }}>Nhiều sự kiện nhất</option>
                        <option value="events_asc" {{ request('sort') == 'events_asc' ? 'selected' : '' }}>Ít sự kiện nhất</option>
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Mới nhất</option>
                    </select>
                </form>

                <!-- Toggle Table / Grid -->
                <div class="bg-[#FAF9F6] p-1 rounded-2xl border border-gray-200 flex items-center gap-1">
                    <button 
                        @click="setViewMode('table')" 
                        type="button" 
                        class="p-2 rounded-xl transition-all"
                        :class="viewMode === 'table' ? 'bg-[#123D22] text-white shadow-sm' : 'text-gray-400 hover:text-gray-700'"
                        title="Chế độ xem bảng"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </button>
                    <button 
                        @click="setViewMode('grid')" 
                        type="button" 
                        class="p-2 rounded-xl transition-all"
                        :class="viewMode === 'grid' ? 'bg-[#123D22] text-white shadow-sm' : 'text-gray-400 hover:text-gray-700'"
                        title="Chế độ xem lưới thẻ"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. Bulk Actions Floating Banner -->
        <div 
            x-show="selectedIds.length > 0" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="bg-slate-900 text-white px-5 py-3 rounded-2xl flex items-center justify-between shadow-xl"
        >
            <div class="flex items-center gap-3">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                <span class="text-xs font-bold">Đã chọn <span x-text="selectedIds.length" class="text-amber-400 font-black"></span> danh mục</span>
            </div>
            <div class="flex items-center gap-2">
                <button 
                    @click="selectedIds = []; selectAll = false" 
                    type="button" 
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white transition-colors"
                >
                    Bỏ chọn
                </button>
                <form method="POST" action="{{ route('admin.categories.bulk-destroy') }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa các danh mục đã chọn? Các danh mục còn sự kiện sẽ được bảo vệ.')">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <button 
                        type="submit" 
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black bg-rose-600 hover:bg-rose-500 text-white shadow-sm transition-all"
                    >
                        Xóa các mục đã chọn
                    </button>
                </form>
            </div>
        </div>

        <!-- 5. Data View: Table Mode -->
        <div x-show="viewMode === 'table'" class="bg-white rounded-3xl border border-[#E3EAE4] shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F6F8F6] text-[11px] font-black uppercase tracking-[0.06em] text-gray-500 border-b border-gray-100">
                        <tr>
                            <th scope="col" class="py-3.5 pl-6 pr-3 w-10">
                                <input 
                                    type="checkbox" 
                                    x-model="selectAll" 
                                    @change="toggleSelectAll({{ json_encode($categories->items()) }})" 
                                    class="w-4 h-4 rounded border-gray-300 text-[#123D22] focus:ring-amber-400 cursor-pointer"
                                >
                            </th>
                            <th scope="col" class="py-3.5 px-4">Tên danh mục</th>
                            <th scope="col" class="py-3.5 px-4 hidden md:table-cell">Slug đường dẫn</th>
                            <th scope="col" class="py-3.5 px-4">Số sự kiện</th>
                            <th scope="col" class="py-3.5 pl-4 pr-6 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($categories as $category)
                            @php
                                $visual = getCategoryVisual($category->name);
                                $pct = ($totalEvents ?? 0) > 0 ? round(($category->events_count / $totalEvents) * 100) : 0;
                            @endphp
                            <tr class="group hover:bg-[#F2F8F4] transition-colors border-l-[3px] border-l-transparent hover:border-l-amber-500">
                                <!-- Checkbox -->
                                <td class="py-4 pl-6 pr-3">
                                    <input 
                                        type="checkbox" 
                                        value="{{ $category->id }}" 
                                        x-model="selectedIds" 
                                        class="w-4 h-4 rounded border-gray-300 text-[#123D22] focus:ring-amber-400 cursor-pointer"
                                    >
                                </td>

                                <!-- Category Name & Description -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 border {{ $visual['bg'] }} shadow-xs">
                                            @if($visual['icon'] === 'music')
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" /></svg>
                                            @elseif($visual['icon'] === 'heart')
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                            @elseif($visual['icon'] === 'sparkles')
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                            @elseif($visual['icon'] === 'disc')
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            @elseif($visual['icon'] === 'presentation')
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 13v-1m4 1v-3m4 3V8M12 21l9-9-9-9-9 9 9 9z" /></svg>
                                            @elseif($visual['icon'] === 'palette')
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>
                                            @elseif($visual['icon'] === 'trophy')
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 group-hover:text-[#123D22] transition-colors truncate text-sm">
                                                {{ $category->name }}
                                            </div>
                                            <div class="text-gray-500 text-xs line-clamp-1 mt-0.5" title="{{ $category->description ?? 'Không có mô tả' }}">
                                                {{ $category->description ?: 'Chưa có thông tin mô tả chi tiết.' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Slug pill with quick copy button -->
                                <td class="py-4 px-4 hidden md:table-cell">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100/90 border border-slate-200/80 font-mono text-[11px] text-slate-700">
                                        <span>{{ $category->slug }}</span>
                                        <button 
                                            @click="copyToClipboard('{{ $category->slug }}')"
                                            type="button" 
                                            class="text-gray-400 hover:text-black transition-colors" 
                                            title="Sao chép slug"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                                <!-- Events Count & Progress Ratio -->
                                <td class="py-4 px-4">
                                    <div class="w-36">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-black {{ $category->events_count > 0 ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-gray-100 text-gray-500' }}">
                                                {{ $category->events_count }} sự kiện
                                            </span>
                                            <span class="text-[10px] text-gray-400 font-bold">{{ $pct }}%</span>
                                        </div>
                                        <!-- Mini progress bar -->
                                        <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full rounded-full transition-all duration-500" 
                                                style="width: {{ max($pct, 3) }}%; background-color: {{ $visual['accent'] }};"
                                            ></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 pl-4 pr-6">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Edit button -->
                                        <button 
                                            @click="openEditModal({{ json_encode($category) }})" 
                                            type="button" 
                                            class="w-8 h-8 rounded-xl bg-[#FAF9F6] border border-gray-200 hover:bg-[#123D22] hover:border-[#123D22] hover:text-white text-gray-600 flex items-center justify-center transition-all duration-150 shadow-2xs" 
                                            title="Sửa danh mục"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <!-- Delete button with safe modal trigger -->
                                        <button 
                                            @click="openDeleteModal({{ json_encode($category) }})" 
                                            type="button" 
                                            class="w-8 h-8 rounded-xl bg-[#FAF9F6] border border-gray-200 hover:bg-[#EF5350] hover:border-[#EF5350] hover:text-white text-gray-600 flex items-center justify-center transition-all duration-150 shadow-2xs" 
                                            title="Xóa danh mục"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-14 text-center">
                                    <div class="max-w-sm mx-auto flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-[#123D22] flex items-center justify-center mb-3">
                                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </div>
                                        <h3 class="font-bold text-slate-800 text-sm">Chưa có danh mục nào</h3>
                                        <p class="text-xs text-gray-400 mt-1 mb-4">
                                            {{ request('search') ? 'Không tìm thấy kết quả nào với từ khóa "' . request('search') . '"' : 'Hãy bắt đầu tạo danh mục đầu tiên cho hệ thống bán vé.' }}
                                        </p>
                                        <button 
                                            @click="openCreateModal()" 
                                            type="button" 
                                            class="px-4 py-2 rounded-xl text-xs font-bold text-white shadow-sm"
                                            style="background: #123D22;"
                                        >
                                            + Tạo danh mục đầu tiên
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 6. Data View: Grid Cards Mode -->
        <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($categories as $category)
                @php
                    $visual = getCategoryVisual($category->name);
                    $pct = ($totalEvents ?? 0) > 0 ? round(($category->events_count / $totalEvents) * 100) : 0;
                @endphp
                <div class="bg-white p-5 rounded-3xl border border-[#E3EAE4] shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group">
                    <div>
                        <!-- Top Header in Card -->
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center border {{ $visual['bg'] }} shrink-0">
                                @if($visual['icon'] === 'music')
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" /></svg>
                                @elseif($visual['icon'] === 'heart')
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                @elseif($visual['icon'] === 'sparkles')
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                @elseif($visual['icon'] === 'disc')
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                @else
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                @endif
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-black {{ $category->events_count > 0 ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-gray-100 text-gray-500' }}">
                                {{ $category->events_count }} sự kiện
                            </span>
                        </div>

                        <!-- Title & Description -->
                        <h4 class="font-display font-black text-base text-slate-900 group-hover:text-[#123D22] transition-colors">
                            {{ $category->name }}
                        </h4>
                        <div class="mt-1 inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-100 font-mono text-[10px] text-slate-600">
                            <span>{{ $category->slug }}</span>
                            <button @click="copyToClipboard('{{ $category->slug }}')" type="button" class="text-gray-400 hover:text-black">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2.5 line-clamp-2 leading-relaxed">
                            {{ $category->description ?: 'Chưa có thông tin mô tả chi tiết cho nhóm sự kiện này.' }}
                        </p>
                    </div>

                    <!-- Card Footer: Progress bar and action buttons -->
                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                        <div class="w-24">
                            <div class="text-[10px] text-gray-400 font-bold mb-1">Tỷ lệ: {{ $pct }}%</div>
                            <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ max($pct, 4) }}%; background-color: {{ $visual['accent'] }};"></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button 
                                @click="openEditModal({{ json_encode($category) }})" 
                                type="button" 
                                class="w-8 h-8 rounded-xl bg-[#FAF9F6] border border-gray-200 hover:bg-[#123D22] hover:text-white flex items-center justify-center transition-all text-gray-600"
                                title="Chỉnh sửa"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button 
                                @click="openDeleteModal({{ json_encode($category) }})" 
                                type="button" 
                                class="w-8 h-8 rounded-xl bg-[#FAF9F6] border border-gray-200 hover:bg-[#EF5350] hover:border-[#EF5350] hover:text-white flex items-center justify-center transition-all text-gray-600"
                                title="Xóa"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white rounded-3xl border border-[#E3EAE4] p-8">
                    <p class="text-sm font-bold text-gray-600">Chưa có danh mục nào để hiển thị dạng lưới.</p>
                </div>
            @endforelse
        </div>

        <!-- 7. Pagination & Rows Per Page -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 sm:p-5 rounded-3xl border border-[#E3EAE4] shadow-sm">
            <div class="text-xs text-gray-500 font-medium">
                @if($categories->total() > 0)
                    Hiển thị 
                    <span class="font-bold text-slate-800">{{ $categories->firstItem() }}</span> – 
                    <span class="font-bold text-slate-800">{{ $categories->lastItem() }}</span> / 
                    <span class="font-bold text-slate-800">{{ $categories->total() }}</span> danh mục
                @else
                    Không có danh mục nào
                @endif
            </div>

            <div class="flex items-center gap-3">
                <!-- Per page selector -->
                <form method="GET" action="{{ route('admin.categories.index') }}" class="flex items-center gap-1.5 text-xs">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="sort" value="{{ request('sort', 'name_asc') }}">
                    <label class="text-gray-400 font-bold hidden sm:inline">Dòng/trang:</label>
                    <select 
                        name="per_page" 
                        onchange="this.form.submit()" 
                        class="py-1 px-2.5 rounded-xl border border-gray-200 text-xs font-bold text-slate-700 bg-[#FAF9F6] cursor-pointer"
                    >
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    </select>
                </form>

                <div>
                    {{ $categories->links() }}
                </div>
            </div>
        </div>

        <!-- 8. Modal Thêm / Sửa Danh Mục (Alpine.js Modal) -->
        <div 
            x-show="formModal.open" 
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
        >
            <!-- Backdrop -->
            <div 
                x-show="formModal.open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="formModal.open = false"
                class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity"
            ></div>

            <div class="flex min-h-screen items-center justify-center p-4">
                <div 
                    x-show="formModal.open"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                    class="relative w-full max-w-lg bg-white rounded-3xl p-6 sm:p-7 shadow-2xl border border-gray-100 overflow-hidden"
                >
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <div>
                            <h3 class="font-display font-black text-lg text-slate-900" x-text="formModal.isEdit ? 'Chỉnh Sửa Danh Mục' : 'Thêm Danh Mục Sự Kiện Mới'"></h3>
                            <p class="text-xs text-gray-500 mt-0.5">Điền tên danh mục và thông tin mô tả chi tiết</p>
                        </div>
                        <button 
                            @click="formModal.open = false" 
                            type="button" 
                            class="w-8 h-8 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-black flex items-center justify-center transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form :action="formModal.actionUrl" method="POST" class="space-y-4 pt-4">
                        @csrf
                        <template x-if="formModal.isEdit">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <!-- Category Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Tên danh mục <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="name" 
                                x-model="formModal.name" 
                                @input="formModal.slugPreview = generateSlug(formModal.name)"
                                required 
                                placeholder="Ví dụ: Concert & Âm Nhạc, Fan Meeting..." 
                                class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/50 focus:border-amber-400 font-semibold bg-[#FAF9F6]"
                            >
                            <!-- Real-time slug preview -->
                            <div class="mt-1.5 flex items-center gap-1.5 text-[11px] text-gray-400">
                                <span>Slug tự tạo:</span>
                                <span class="font-mono text-slate-700 bg-gray-100 px-2 py-0.5 rounded-md" x-text="formModal.slugPreview || 'chua-co-slug'"></span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Mô tả phân loại
                            </label>
                            <textarea 
                                name="description" 
                                x-model="formModal.description" 
                                rows="3" 
                                placeholder="Mô tả ngắn gọn về đặc trưng các sự kiện thuộc danh mục này..." 
                                class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/50 focus:border-amber-400 bg-[#FAF9F6]"
                            ></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                            <button 
                                @click="formModal.open = false" 
                                type="button" 
                                class="px-5 py-2.5 rounded-2xl text-xs font-bold text-gray-600 hover:text-black bg-gray-100 hover:bg-gray-200 transition-colors"
                            >
                                Hủy bỏ
                            </button>
                            <button 
                                type="submit" 
                                class="px-6 py-2.5 rounded-2xl text-xs font-black text-white shadow-md transition-all hover:shadow-lg hover:-translate-y-0.5"
                                style="background: linear-gradient(135deg, #123D22 0%, #1e662e 100%);"
                                x-text="formModal.isEdit ? 'Lưu cập nhật' : 'Tạo danh mục'"
                            ></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 9. Modal Xác Nhận Xóa An Toàn (Safe Delete Confirmation Modal) -->
        <div 
            x-show="deleteModal.open" 
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
        >
            <div 
                x-show="deleteModal.open"
                @click="deleteModal.open = false"
                class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity"
            ></div>

            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-3xl p-6 sm:p-7 shadow-2xl border border-gray-100 overflow-hidden text-center">
                    <template x-if="deleteModal.category && deleteModal.category.events_count > 0">
                        <div>
                            <div class="w-14 h-14 rounded-3xl bg-amber-50 text-amber-600 border border-amber-200 mx-auto flex items-center justify-center mb-3.5">
                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="font-display font-black text-lg text-slate-900">Không Thể Xóa Danh Mục</h3>
                            <div class="mt-2 text-xs text-gray-500 leading-relaxed">
                                Danh mục <span class="font-bold text-slate-800" x-text="'[' + deleteModal.category.name + ']'"></span> hiện đang có 
                                <span class="font-black text-amber-700" x-text="deleteModal.category.events_count + ' sự kiện'"></span> liên kết. 
                                Để bảo vệ dữ liệu vé và show diễn, bạn không thể xóa danh mục này.
                            </div>
                            <div class="mt-5">
                                <button 
                                    @click="deleteModal.open = false" 
                                    type="button" 
                                    class="w-full py-2.5 rounded-2xl text-xs font-bold text-white shadow-sm transition-all"
                                    style="background: #123D22;"
                                >
                                    Đã hiểu, quay lại
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="deleteModal.category && deleteModal.category.events_count === 0">
                        <div>
                            <div class="w-14 h-14 rounded-3xl bg-rose-50 text-rose-600 border border-rose-200 mx-auto flex items-center justify-center mb-3.5">
                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>
                            <h3 class="font-display font-black text-lg text-slate-900">Xác Nhận Xóa Danh Mục</h3>
                            <p class="mt-2 text-xs text-gray-500 leading-relaxed">
                                Bạn có chắc chắn muốn xóa danh mục <span class="font-bold text-slate-800" x-text="'[' + deleteModal.category.name + ']'"></span>? Thao tác này không thể hoàn tác.
                            </p>
                            <form :action="deleteModal.actionUrl" method="POST" class="mt-5 flex items-center justify-center gap-3">
                                @csrf
                                <input type="hidden" name="_method" value="DELETE">
                                <button 
                                    @click="deleteModal.open = false" 
                                    type="button" 
                                    class="flex-1 py-2.5 rounded-2xl text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors"
                                >
                                    Hủy
                                </button>
                                <button 
                                    type="submit" 
                                    class="flex-1 py-2.5 rounded-2xl text-xs font-black text-white bg-rose-600 hover:bg-rose-500 shadow-md transition-all"
                                >
                                    Xóa vĩnh viễn
                                </button>
                            </form>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- 10. Global Toast Popup (Top-Right) -->
        <div 
            x-show="toast.show" 
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="fixed top-24 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl border text-xs font-bold"
            :class="toast.type === 'error' ? 'bg-rose-900 text-white border-rose-700' : 'bg-slate-900 text-white border-slate-700'"
        >
            <template x-if="toast.type !== 'error'">
                <div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </template>
            <template x-if="toast.type === 'error'">
                <div class="w-5 h-5 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
            </template>
            <span x-text="toast.message"></span>
            <button @click="toast.show = false" class="text-gray-400 hover:text-white ml-2">×</button>
        </div>

    </div>
</x-admin-layout>
