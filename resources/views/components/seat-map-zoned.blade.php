<section class="space-y-5">
    <div class="rounded-3xl bg-slate-950 p-6 text-white shadow-xl">
        <span class="text-xs font-black uppercase tracking-widest text-amber-300">Sơ đồ khán phòng</span>
        <h2 class="mt-2 text-2xl font-black">{{ $showtime->room->name }}</h2>
        <p class="mt-1 text-sm text-white/70">{{ $showtime->room->address }}</p>
        <div class="mt-4 h-2 rounded-full bg-gradient-to-r from-amber-400 via-rose-400 to-amber-400"></div>
        <p class="mt-2 text-center text-[10px] font-black uppercase tracking-[0.35em] text-white/70">Sân khấu / khu vực sự kiện</p>
    </div>

    <article class="overflow-hidden rounded-3xl border border-black/10 bg-white p-5 shadow-sm sm:p-7">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-800">Sơ đồ chọn khu</p>
                <h3 class="mt-1 text-xl font-black">Chọn khu vé trên sơ đồ</h3>
            </div>
            <p class="text-xs text-gray-500">Chọn một khu để xem ghế hoặc số vé còn</p>
        </div>
        <div class="mx-auto mt-6 max-w-4xl rounded-[2rem] bg-[#F7F6F2] p-3 sm:p-6">
            <div class="mx-auto mb-5 max-w-sm rounded-t-[50%] border-b-4 border-amber-400 bg-slate-900 px-8 py-3 text-center text-xs font-black uppercase tracking-[0.3em] text-white shadow-lg">Sân khấu</div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3">
                @foreach($venueZones as $index => $zone)
                    @php
                        $zonePalette = [
                            ['bg' => 'bg-rose-100', 'border' => 'border-rose-400', 'text' => 'text-rose-950', 'dot' => 'bg-rose-500'],
                            ['bg' => 'bg-amber-100', 'border' => 'border-amber-400', 'text' => 'text-amber-950', 'dot' => 'bg-amber-500'],
                            ['bg' => 'bg-sky-100', 'border' => 'border-sky-400', 'text' => 'text-sky-950', 'dot' => 'bg-sky-500'],
                            ['bg' => 'bg-emerald-100', 'border' => 'border-emerald-400', 'text' => 'text-emerald-950', 'dot' => 'bg-emerald-500'],
                            ['bg' => 'bg-violet-100', 'border' => 'border-violet-400', 'text' => 'text-violet-950', 'dot' => 'bg-violet-500'],
                            ['bg' => 'bg-orange-100', 'border' => 'border-orange-400', 'text' => 'text-orange-950', 'dot' => 'bg-orange-500'],
                        ][$index % 6];
                    @endphp
                    <button type="button"
                        @click="document.getElementById('zone-card-{{ $zone['id'] }}')?.scrollIntoView({ behavior: 'smooth', block: 'center' })"
                        class="group min-h-24 rounded-2xl border-2 {{ $zonePalette['border'] }} {{ $zonePalette['bg'] }} p-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-700 sm:min-h-28 sm:p-4">
                        <span class="flex items-center gap-2 text-[10px] font-black uppercase tracking-wider {{ $zonePalette['text'] }}"><span class="h-2.5 w-2.5 rounded-full {{ $zonePalette['dot'] }}"></span>{{ $zone['code'] }}</span>
                        <strong class="mt-2 block text-sm font-black leading-tight {{ $zonePalette['text'] }} sm:text-base">{{ $zone['name'] }}</strong>
                        <span class="mt-2 block text-[11px] font-semibold {{ $zonePalette['text'] }}">{{ number_format($zone['price']) }}₫ · còn {{ number_format($zone['available']) }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        <div class="mt-4 flex flex-wrap justify-center gap-x-5 gap-y-2 text-[11px] font-semibold text-gray-600">
            <span>Chạm vào khu để xem chi tiết</span><span>·</span><span>Ghế ngồi chọn từng ghế</span><span>·</span><span>Khu đứng chọn số lượng</span>
        </div>
    </article>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach($venueZones as $zone)
            @php
                $zoneSeats = $seatsCollection->where('zone_id', $zone['id']);
                $zoneRows = $zoneSeats->groupBy('row_label');
            @endphp
            <article id="zone-card-{{ $zone['id'] }}" class="scroll-mt-24 rounded-3xl border border-black/10 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4 border-b border-black/10 pb-4">
                    <div>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase text-emerald-900">{{ $zone['code'] }}</span>
                        <h3 class="mt-2 text-lg font-black">{{ $zone['name'] }}</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            @if(in_array($zone['kind'], ['seated', 'table'], true))
                                {{ $zone['capacity'] }} ghế · còn {{ $zone['available'] }}
                            @else
                                Sức chứa {{ $zone['capacity'] }} vé · còn {{ $zone['available'] }}
                            @endif
                        </p>
                    </div>
                    <div class="text-right">
                        <strong class="block text-lg">{{ number_format($zone['price']) }}₫</strong>
                        <span class="text-[10px] font-bold uppercase text-gray-500">{{ in_array($zone['kind'], ['seated', 'table'], true) ? 'Chọn chỗ' : 'Theo số lượng' }}</span>
                    </div>
                </div>

                @if(in_array($zone['kind'], ['seated', 'table'], true))
                    <div class="mt-4 space-y-3">
                        @forelse($zoneRows as $rowLabel => $rowSeats)
                            <div class="rounded-xl bg-[#FAF9F6] p-3">
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <span class="text-xs font-black">{{ $zone['kind'] === 'table' ? 'Bàn ' : 'Hàng ' }}{{ str_replace($zone['code'].'-', '', $rowLabel) }}</span>
                                    @if($zone['kind'] === 'table')
                                        <button type="button" @click="toggleTable({{ $zone['id'] }}, @js($rowLabel))" class="rounded-lg border border-emerald-800 px-2.5 py-1 text-[10px] font-bold text-emerald-900 hover:bg-emerald-50">Chọn cả bàn</button>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($rowSeats as $seat)
                                        @php($unavailable = $seat->status !== 'available')
                                        <button type="button"
                                            @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js($seat->row_label), @js($seat->type), @js($seat->type_name), @js($seat->status), {{ $seat->price }}, @js($seat->perks ?? []))"
                                            :class="isSeatSelected({{ $seat->id }}) ? 'bg-emerald-700 text-white ring-2 ring-emerald-300' : (@js($unavailable) || unavailableIds.includes({{ $seat->id }}) ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-white border border-gray-300 hover:border-emerald-700 hover:bg-emerald-50')"
                                            @disabled($unavailable)
                                            class="min-w-8 rounded-lg px-2 py-1.5 text-[10px] font-black transition-colors"
                                            title="{{ $seat->code }} · {{ number_format($seat->price) }}₫">{{ $seat->seat_number }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="rounded-xl bg-amber-50 p-3 text-xs text-amber-900">Khu chưa có sơ đồ ghế.</p>
                        @endforelse
                    </div>
                @else
                    <div class="mt-4 flex items-center justify-between rounded-2xl bg-[#FAF9F6] p-3">
                        <span class="text-sm font-bold">Số vé</span>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="decrementZone({{ $zone['id'] }})" :disabled="getZoneSelectedCount({{ $zone['id'] }}) === 0" class="h-9 w-9 rounded-xl border font-black disabled:opacity-30">−</button>
                            <span class="w-6 text-center font-mono font-black" x-text="getZoneSelectedCount({{ $zone['id'] }})">0</span>
                            <button type="button" @click="incrementZone({{ $zone['id'] }})" :disabled="getZoneSelectedCount({{ $zone['id'] }}) >= {{ $zone['available'] }} || totalTicketCount >= 8" class="h-9 w-9 rounded-xl bg-slate-950 text-white font-black disabled:opacity-30">+</button>
                        </div>
                    </div>
                @endif
            </article>
        @endforeach
    </div>

    <div class="sticky bottom-3 flex flex-col gap-4 rounded-3xl border border-black/10 bg-white/95 p-4 shadow-xl backdrop-blur sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-xs text-gray-500">{{ $showtime->start_time->format('H:i - d/m/Y') }} · tối đa 8 vé</p><strong class="text-2xl" x-text="formattedTotalPrice">0 ₫</strong></div>
        <button type="button" @click="confirmHold()" :disabled="totalTicketCount === 0 || isSubmitting" class="rounded-full bg-emerald-800 px-7 py-3.5 font-black text-white disabled:opacity-40">
            <span x-text="isSubmitting ? 'Đang giữ vé...' : 'Giữ vé'">Giữ vé</span>
            <span x-show="totalTicketCount > 0" x-text="' (' + totalTicketCount + ')' "></span>
        </button>
    </div>
</section>
