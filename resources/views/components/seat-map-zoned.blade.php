<section class="space-y-5">
    <div class="rounded-3xl bg-slate-950 p-6 text-white shadow-xl">
        <span class="text-xs font-black uppercase tracking-widest text-amber-300">Sơ đồ khán phòng</span>
        <h2 class="mt-2 text-2xl font-black">{{ $showtime->room->name }}</h2>
        <p class="mt-1 text-sm text-white/70">{{ $showtime->room->address }}</p>
        <div class="mt-4 h-2 rounded-full bg-gradient-to-r from-amber-400 via-rose-400 to-amber-400"></div>
        <p class="mt-2 text-center text-[10px] font-black uppercase tracking-[0.35em] text-white/70">Sân khấu / khu vực sự kiện</p>
    </div>

    <div class="grid items-start gap-5 lg:grid-cols-12">
    <article class="overflow-hidden rounded-3xl border border-black/10 bg-white p-5 shadow-sm sm:p-7 lg:sticky lg:top-24 lg:col-span-7">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-800">Sơ đồ chọn khu</p>
                <h3 class="mt-1 text-xl font-black">Chọn khu vé trên sơ đồ</h3>
            </div>
            <p class="text-xs text-gray-500">Chọn một khu để xem ghế hoặc số vé còn</p>
        </div>
        <div class="mx-auto mt-6 max-w-4xl rounded-[2rem] bg-[#F7F6F2] p-3 sm:p-6">
            <div class="relative mx-auto aspect-[16/10] min-h-[220px] overflow-hidden rounded-2xl border border-black/5 bg-white/40">
                <div class="absolute z-10 flex items-center justify-center border-b-4 border-amber-400 bg-slate-900 px-2 text-center text-[9px] font-black uppercase tracking-[0.2em] text-white shadow sm:text-xs"
                    style="left:35%;top:3%;width:30%;height:11%;border-radius:50% 50% 12px 12px / 45% 45% 12px 12px">Sân khấu</div>
                @foreach($venueZones as $index => $zone)
                    @php
                        $zoneColors = ['#fda4af', '#fbbf24', '#38bdf8', '#34d399', '#a78bfa', '#fb923c'];
                        $zoneColor = $zoneColors[$index % count($zoneColors)];
                        $canvas = $zone['canvas'] ?? ['x' => 5 + ($index % 4) * 23, 'y' => 20 + intdiv($index, 4) * 23, 'width' => 21, 'height' => 19, 'shape' => 'rounded'];
                        $shapeStyle = match ($canvas['shape'] ?? 'rounded') {
                            'ellipse' => 'border-radius:50%;',
                            'trapezoid' => 'clip-path:polygon(12% 0,88% 0,100% 100%,0 100%);',
                            'polygon' => 'clip-path:polygon('.collect($canvas['points'] ?? [])->map(fn ($point) => $point['x'].'% '.$point['y'].'%')->join(',').');',
                            'rectangle' => 'border-radius:4px;',
                            default => 'border-radius:1rem;',
                        };
                    @endphp
                    <button type="button"
                        @click="focusVenueZone({{ $zone['id'] }})"
                        :class="activeVenueZone === {{ $zone['id'] }} ? 'ring-4 ring-slate-900 ring-offset-2' : ''"
                        class="group absolute z-0 flex flex-col items-center justify-center overflow-hidden border-2 p-1 text-center shadow-sm transition hover:z-20 hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-emerald-700 sm:p-3"
                        style="left:{{ $canvas['x'] }}%;top:{{ $canvas['y'] }}%;width:{{ $canvas['width'] }}%;height:{{ $canvas['height'] }}%;border-color:{{ $zoneColor }};background-color:{{ $zoneColor }}33;{{ $shapeStyle }}">
                        <span class="text-[8px] font-black uppercase tracking-wider text-slate-900 sm:text-[10px]">{{ $zone['code'] }}</span>
                        <strong class="mt-0.5 block max-w-full text-[9px] font-black leading-tight text-slate-950 sm:mt-1 sm:text-sm">{{ $zone['name'] }}</strong>
                        <span class="mt-0.5 block text-[8px] font-semibold text-slate-800 sm:mt-1 sm:text-[10px]">{{ number_format($zone['price']) }}₫ · còn {{ number_format($zone['available']) }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        <div class="mt-4 flex flex-wrap justify-center gap-x-5 gap-y-2 text-[11px] font-semibold text-gray-600">
            <span>Chọn khu ghế để mở sơ đồ riêng</span><span>·</span><span>Khu đứng chọn số lượng</span>
        </div>
    </article>

    <aside class="space-y-3 lg:col-span-5">
        <div class="rounded-2xl border border-black/10 bg-white p-4 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-800">Hạng vé</p>
            <h3 class="mt-1 text-xl font-black">Chọn khu và loại vé</h3>
            <p class="mt-1 text-xs text-gray-500">Chọn khu trên sơ đồ hoặc xem từng hạng vé bên dưới.</p>
        </div>
        @foreach($venueZones as $zone)
            @php
                $zoneSeats = $seatsCollection->where('zone_id', $zone['id']);
                $zoneRows = $zoneSeats->groupBy('row_label');
            @endphp
            <article id="zone-card-{{ $zone['id'] }}" :class="activeVenueZone === {{ $zone['id'] }} ? 'border-slate-900 ring-2 ring-slate-900/10' : 'border-black/10'" class="scroll-mt-24 rounded-3xl border bg-white p-4 shadow-sm transition sm:p-5">
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
                        @if(in_array($zone['kind'], ['seated', 'table'], true))
                            <button type="button" @click="focusVenueZone({{ $zone['id'] }})" class="mt-2 block rounded-full bg-slate-900 px-3 py-1.5 text-[10px] font-black text-white">Chọn ghế khu này</button>
                        @endif
                    </div>
                </div>

                @if(in_array($zone['kind'], ['seated', 'table'], true))
                    <div x-show="activeVenueZone === {{ $zone['id'] }}" x-cloak x-transition class="mt-4 max-h-[48vh] space-y-3 overflow-y-auto pr-1">
                        <div class="sticky top-0 z-10 flex items-center justify-between gap-2 rounded-xl bg-slate-950 px-3 py-2 text-white shadow">
                            <span class="text-xs font-black">Sơ đồ ghế · {{ $zone['name'] }}</span>
                            <button type="button" @click="activeVenueZone = null" class="rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold hover:bg-white/25">Đóng sơ đồ</button>
                        </div>
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
    </aside>
    </div>

    <div class="sticky bottom-3 flex flex-col gap-4 rounded-3xl border border-black/10 bg-white/95 p-4 shadow-xl backdrop-blur sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-xs text-gray-500">{{ $showtime->start_time->format('H:i - d/m/Y') }} · tối đa 8 vé</p><strong class="text-2xl" x-text="formattedTotalPrice">0 ₫</strong></div>
        <button type="button" @click="confirmHold()" :disabled="totalTicketCount === 0 || isSubmitting" class="rounded-full bg-emerald-800 px-7 py-3.5 font-black text-white disabled:opacity-40">
            <span x-text="isSubmitting ? 'Đang giữ vé...' : 'Giữ vé'">Giữ vé</span>
            <span x-show="totalTicketCount > 0" x-text="' (' + totalTicketCount + ')' "></span>
        </button>
    </div>
</section>
