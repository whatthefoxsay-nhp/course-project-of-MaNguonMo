<x-site-layout :title="'Vé Điện Tử E-Ticket #' . $booking->booking_code . ' - TicketBox'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8" x-data="{
        emailSent: false,
        emailSending: false,
        driveSynced: false,
        driveSyncing: false,
        toastMsg: '',
        showToast: false,
        notify(msg) {
            this.toastMsg = msg;
            this.showToast = true;
            setTimeout(() => { this.showToast = false; }, 3500);
        },
        async sendEmail() {
            this.emailSending = true;
            try {
                const res = await fetch('{{ route('bookings.ticket.email', $booking->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    }
                });
                const data = await res.json();
                this.notify(data.message || (res.ok ? 'Đã gửi email!' : 'Lỗi gửi mail'));
                if (res.ok) this.emailSent = true;
            } catch (e) {
                this.notify('Lỗi kết nối khi gửi email');
            } finally {
                this.emailSending = false;
            }
        },
        async syncDrive() {
            this.driveSyncing = true;
            try {
                const res = await fetch('{{ route('bookings.ticket.sync-drive', $booking->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    }
                });
                const data = await res.json();
                this.notify(data.message || (res.ok ? 'Đã đồng bộ Drive!' : 'Lỗi đồng bộ'));
                if (res.ok) this.driveSynced = true;
            } catch (e) {
                this.notify('Lỗi kết nối khi đồng bộ Google Drive');
            } finally {
                this.driveSyncing = false;
            }
        }
    }">
        <!-- Toast Notification (Hiển thị góc trên bên phải, tránh va chạm với TicketBot AI ở góc dưới) -->
        <div x-show="showToast" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
             class="fixed top-20 right-5 sm:top-24 sm:right-8 z-50 bg-slate-900/95 text-white px-5 py-3.5 rounded-2xl shadow-xl border border-slate-700 text-xs font-bold pointer-events-none backdrop-blur-md" 
             style="display: none;" 
             x-text="toastMsg"></div>

        <!-- Navigation Breadcrumbs & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <a href="{{ route('bookings.history') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:text-black transition-all shadow-xs">
                &larr; Quay lại lịch sử vé
            </a>
            
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Nút Tải PDF -->
                <a 
                    href="{{ route('bookings.ticket.pdf', $booking->id) }}" 
                    class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-black text-white text-xs font-bold shadow-xs flex items-center gap-1.5 transition-all"
                >
                    <svg class="w-4 h-4 text-gold-antique" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Tải file PDF</span>
                </a>

                <!-- Nút Gửi Email -->
                <button 
                    type="button" 
                    @click="sendEmail()" 
                    :disabled="emailSending"
                    class="px-4 py-2 rounded-xl bg-white border border-slate-300 hover:border-black text-slate-700 text-xs font-bold shadow-xs flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-50"
                >
                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span x-text="emailSending ? 'Đang gửi...' : (emailSent ? 'Đã gửi mail ✓' : 'Gửi qua Email')">Gửi qua Email</span>
                </button>

                <!-- Nút Đồng bộ Google Drive -->
                <button 
                    type="button" 
                    @click="syncDrive()" 
                    :disabled="driveSyncing"
                    class="px-4 py-2 rounded-xl bg-[#FAF9F6] border border-amber-300 hover:border-amber-500 text-amber-900 text-xs font-bold shadow-xs flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-50"
                >
                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span x-text="driveSyncing ? 'Đang đồng bộ...' : (driveSynced ? 'Đã lên Drive ✓' : 'Lưu Google Drive')">Lưu Google Drive</span>
                </button>
            </div>
        </div>

        @php
            $firstItem = $booking->items->first();
            $showtime = $firstItem?->showtimeSeat?->showtime;
            $event = $showtime?->event;
            $room = $showtime?->room;
        @endphp

        <!-- Event Overview Banner -->
        <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <img src="{{ $event?->poster_url }}" alt="{{ $event?->title }}" class="w-16 h-24 object-cover rounded-xl bg-slate-100 border border-slate-200 shrink-0">
                <div>
                    <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider">
                        {{ $event?->category?->name ?? 'Sự Kiện' }}
                    </span>
                    <h1 class="font-display font-black text-xl sm:text-2xl text-slate-900 mt-1">{{ $event?->title }}</h1>
                    <div class="text-xs text-slate-600 space-y-0.5 mt-2">
                        <p><strong>Thời gian:</strong> {{ $showtime?->start_time->format('H:i - d/m/Y') }}</p>
                        <p><strong>Địa điểm:</strong> {{ $room?->name }} ({{ $room?->address }})</p>
                    </div>
                </div>
            </div>

            <div class="text-right sm:border-l sm:border-slate-100 sm:pl-6 shrink-0 w-full sm:w-auto">
                <span class="text-[10px] uppercase font-bold text-slate-500 block">Mã đơn hàng</span>
                <span class="font-mono text-base font-black text-slate-900">{{ $booking->booking_code }}</span>
                <span class="badge-sage text-[10px] px-2 py-0.5 rounded-full font-black uppercase block mt-1">
                    {{ count($booking->items) }} Vé Đã Xác Nhận
                </span>
            </div>
        </div>

        <!-- Individual E-Ticket Passes List (Hỗ trợ đơn hàng nhiều vé) -->
        <div class="space-y-6">
            <h2 class="font-display font-black text-lg text-slate-900">Danh Sách Vé Điện Tử ({{ count($booking->items) }} Vé)</h2>

            @foreach ($booking->items as $index => $item)
                @php
                    $seat = $item->showtimeSeat?->seat;
                    $zone = $seat?->zone;
                    $ticketNumber = $index + 1;
                    $uniqueTicketCode = $booking->booking_code . '-' . str_pad((string) $ticketNumber, 2, '0', STR_PAD_LEFT);
                    $qrContent = 'TICKETBOX_AUTH_PASS_' . $booking->booking_code . '_' . $item->id;
                    $itemQrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                        ->size(300)
                        ->margin(2)
                        ->generate($qrContent);
                @endphp

                <!-- Single Boarding Pass Card -->
                <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-md relative">
                    <div class="h-1.5 bg-gradient-to-r from-rose-500 via-amber-400 to-emerald-500"></div>

                    <div class="grid grid-cols-1 md:grid-cols-12">
                        <!-- Left Details (7 cols) -->
                        <div class="md:col-span-8 p-6 sm:p-7 flex flex-col justify-between border-b md:border-b-0 md:border-r border-dashed border-slate-200">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-mono text-xs font-black text-rose-600 bg-rose-50 px-2.5 py-0.5 rounded-md border border-rose-200">
                                        VÉ SỐ #{{ $ticketNumber }} &bull; {{ $uniqueTicketCode }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider hidden sm:inline">XUẤT TRÌNH TẠI CỔNG KIỂM SOÁT</span>
                                        <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                            ✓ Hợp Lệ
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 my-4">
                                    <!-- Cửa vào (Gate) -->
                                    <div class="bg-[#FAF9F6] p-3 rounded-xl border border-slate-100">
                                        <span class="text-[9px] uppercase font-bold text-slate-500 block">Cổng soát vé (Gate)</span>
                                        <strong class="text-slate-900 font-black text-xs sm:text-sm">{{ $zone?->gate ?? 'Cổng Chính' }}</strong>
                                    </div>

                                    <!-- Khu vực (Zone) -->
                                    <div class="bg-[#FAF9F6] p-3 rounded-xl border border-slate-100">
                                        <span class="text-[9px] uppercase font-bold text-slate-500 block">Khu vực (Zone)</span>
                                        <strong class="text-slate-900 font-black text-xs sm:text-sm" style="color: {{ $zone?->color ?? '#000000' }}">
                                            {{ $zone?->name ?? 'Khu Tiêu Chuẩn' }}
                                        </strong>
                                    </div>

                                    <!-- Số ghế / Vị trí -->
                                    <div class="bg-[#FAF9F6] p-3 rounded-xl border border-slate-100">
                                        <span class="text-[9px] uppercase font-bold text-slate-500 block">Vị trí chỗ ngồi</span>
                                        <strong class="text-rose-600 font-black text-xs sm:text-sm">
                                            @if ($zone && $zone->isStanding())
                                                Khu Đứng GA #{{ $seat?->seat_number ?? $ticketNumber }}
                                            @else
                                                Ghế {{ $seat?->full_code ?? 'A-01' }}
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            <div class="text-[11px] text-slate-500 pt-3 border-t border-slate-100 flex items-center justify-between">
                                <span>Giá vé: <strong class="text-slate-900">{{ number_format($item->price) }}₫</strong></span>
                                <span class="text-slate-400">Xuất trình tại cửa kiểm soát an ninh</span>
                            </div>
                        </div>

                        <!-- Right QR Pass Stub (4 cols) -->
                        <div class="md:col-span-4 p-6 bg-gradient-to-br from-slate-50 to-amber-50/30 flex flex-col items-center justify-center text-center space-y-2">
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 font-mono">
                                CHECK-IN QR PASS
                            </span>

                            <div class="w-32 h-32 bg-white p-2 rounded-xl shadow-xs border border-slate-200">
                                <img src="{{ 'data:image/svg+xml;base64,'.base64_encode($itemQrSvg) }}" alt="Mã QR vé #{{ $ticketNumber }}" class="w-full h-full object-contain">
                            </div>

                            <span class="font-mono text-[11px] font-black text-slate-900 tracking-wider">{{ $uniqueTicketCode }}</span>
                            <span class="text-[9px] font-bold text-emerald-700">MÃ XÁC THỰC CHECK-IN</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-site-layout>
