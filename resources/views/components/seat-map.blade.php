@props(['showtime', 'movie', 'seats', 'ticketTiers' => [], 'venueZones' => [], 'useZoneMap' => false])

@php
    $basePrice = $showtime->base_price ?? 180000;
    $isSeatedConcert = $movie->is_seated_concert ?? true;
    if (empty($ticketTiers)) {
        $ticketTiers = \App\Support\TicketTiers::for($basePrice, $isSeatedConcert);
    }
    $seatsCollection = collect($seats);
    $isZonedVenue = $useZoneMap && $showtime->room->isZoned();
    $totalAvailable = $seatsCollection->where('status', 'available')->count();
    $totalBooked = $seatsCollection->where('status', 'booked')->count();
    $totalHeld = $seatsCollection->where('status', 'held')->count();

    // Sân vận động (preset mega_concert): nhóm theo khu
    $svipSeats = $seatsCollection->where('sector', 'floor')->where('type', 'svip_diamond');
    $vipFloorSeats = $seatsCollection->where('sector', 'floor')->where('type', 'vip_gold')->groupBy('row_label');
    $standASeats = $seatsCollection->where('sector', 'center_stand')->groupBy('row_label');
    $standBSeats = $seatsCollection->where('sector', 'upper_stand')->groupBy('row_label');
    $standCSeats = $seatsCollection->where('sector', 'left_stand');
    $standDSeats = $seatsCollection->where('sector', 'right_stand');
    $skyboxSeats = $seatsCollection->where('sector', 'skybox');

    // Khán phòng thường (nhà hát / hội nghị / lưới tùy chỉnh): vẽ lưới theo hàng
    $generalSeats = $seatsCollection->where('sector', 'general')->groupBy('row_label');
    $isSayHi = ($showtime->room->layout_preset ?? '') === 'sayhi_concert' || str_contains($movie->slug ?? '', 'say-hi');
    $isStadiumLayout = $generalSeats->isEmpty() || in_array($showtime->room->layout_preset ?? '', ['mega_concert', 'sayhi_concert'], true);

    // Thống kê chi tiết theo phân khu phục vụ Sơ đồ SVĐ phân khu thật (Ticketbox)
    $zoneStats = [
        'svip_diamond' => [
            'name' => $isSayHi ? 'SVIP Say Hi Diamond B-Stage' : 'SVIP Sân Khấu Kim Cương',
            'label' => $isSayHi ? 'SVIP Say Hi Diamond' : 'SVIP B-Stage Diamond',
            'code' => 'ZONE-SVIP',
            'gate' => 'Cổng VIP 1',
            'price' => $ticketTiers['svip_diamond']['price'] ?? 400000,
            'color' => '#D4AF37',
            'accent' => 'amber',
            'total' => $svipSeats->count(),
            'available' => $svipSeats->where('status', 'available')->count(),
            'booked' => $svipSeats->where('status', 'booked')->count(),
            'icon' => '💎',
            'perks' => $ticketTiers['svip_diamond']['perks'] ?? ['Lối VIP Check-in riêng', 'Soundcheck rehearsal', 'Set quà độc quyền'],
            'desc' => 'Khu VIP sát mép Catwalk & B-Stage kim cương, tầm nhìn đỉnh nhất toàn bộ sân vận động.',
        ],
        'standing_pit' => [
            'name' => 'GA Standing Fanzone (Pit Trái & Phải)',
            'label' => 'GA Standing Pits',
            'code' => 'PIT-GA',
            'gate' => 'Cổng Fanzone 1 & 2',
            'price' => $ticketTiers['standing_pit']['price'] ?? 230000,
            'color' => '#F43F5E',
            'accent' => 'rose',
            'total' => 40,
            'available' => 40,
            'booked' => 0,
            'icon' => '🔥',
            'perks' => $ticketTiers['standing_pit']['perks'] ?? ['Khu đứng sát sàn catwalk', 'Hòa mình fanzone cuồng nhiệt'],
            'desc' => 'Khu đứng sát mép sàn diễn Catwalk, vị trí gần nghệ sĩ nhất để "quẩy" hết mình.',
        ],
        'vip_gold' => [
            'name' => 'VIP Mặt Sàn Trực Diện',
            'label' => 'VIP Floor A & B',
            'code' => 'ZONE-VIP',
            'gate' => 'Cổng VIP 2',
            'price' => $ticketTiers['vip_gold']['price'] ?? 330000,
            'color' => '#E5A93C',
            'accent' => 'yellow',
            'total' => $vipFloorSeats->flatten()->count(),
            'available' => $vipFloorSeats->flatten()->where('status', 'available')->count(),
            'booked' => $vipFloorSeats->flatten()->where('status', 'booked')->count(),
            'icon' => '⭐',
            'perks' => $ticketTiers['vip_gold']['perks'] ?? ['Ghế VIP mặt sàn chính', 'Âm thanh Studio Master', 'Lanyard VIP đeo cổ'],
            'desc' => 'Khu ghế ngồi VIP mặt sàn trung tâm, trực diện sân khấu với hệ thống âm thanh vòm hoàn hảo.',
        ],
        'cat1_stand' => [
            'name' => 'Khán Đài A & B - Trung Tâm Tầng 1 & 2',
            'label' => 'West Stand Cat 1',
            'code' => 'STAND-A',
            'gate' => 'Cổng Khán Đài A/B',
            'price' => $ticketTiers['cat1_stand']['price'] ?? 290000,
            'color' => '#10B981',
            'accent' => 'emerald',
            'total' => $standASeats->flatten()->count() + ($standBSeats ? $standBSeats->flatten()->count() : 0),
            'available' => $standASeats->flatten()->where('status', 'available')->count() + ($standBSeats ? $standBSeats->flatten()->where('status', 'available')->count() : 0),
            'booked' => $standASeats->flatten()->where('status', 'booked')->count() + ($standBSeats ? $standBSeats->flatten()->where('status', 'booked')->count() : 0),
            'icon' => '✨',
            'perks' => $ticketTiers['cat1_stand']['perks'] ?? ['Góc nhìn toàn cảnh sân khấu', 'Ghế đệm êm có tựa tay'],
            'desc' => 'Khán đài trung tâm đối diện sân khấu, tầm nhìn bao quát trọn vẹn toàn bộ laser & visual visual.',
        ],
        'cat2_wings' => [
            'name' => 'Khán Đài C & D - Cánh Khán Giả',
            'label' => 'Wing Stands Cat 2',
            'code' => 'STAND-CD',
            'gate' => 'Cổng Khán Đài C & D',
            'price' => $ticketTiers['cat2_wings']['price'] ?? 250000,
            'color' => '#3B82F6',
            'accent' => 'blue',
            'total' => $standCSeats->count() + $standDSeats->count(),
            'available' => $standCSeats->where('status', 'available')->count() + $standDSeats->where('status', 'available')->count(),
            'booked' => $standCSeats->where('status', 'booked')->count() + $standDSeats->where('status', 'booked')->count(),
            'icon' => '🏟️',
            'perks' => $ticketTiers['cat2_wings']['perks'] ?? ['Chỗ ngồi thoáng mát', 'Giá vé tối ưu'],
            'desc' => 'Khán đài cánh trái & cánh phải sân vận động, vị trí thoáng mát với giá vé tiết kiệm.',
        ],
        'skybox_suite' => [
            'name' => 'Skybox VIP Suites / Hoàng Gia',
            'label' => 'Royal Skybox Lounge',
            'code' => 'SKYBOX',
            'gate' => 'Thang Máy VIP 3',
            'price' => $ticketTiers['skybox_suite']['price'] ?? 700000,
            'color' => '#C08497',
            'accent' => 'pink',
            'total' => $skyboxSeats->count(),
            'available' => $skyboxSeats->where('status', 'available')->count(),
            'booked' => $skyboxSeats->where('status', 'booked')->count(),
            'icon' => '🥂',
            'perks' => $ticketTiers['skybox_suite']['perks'] ?? ['Sofa đôi phòng riêng VIP', 'Bao gồm 2 vé + Buffet cocktail', 'Ban công kính riêng'],
            'desc' => 'Phòng VIP riêng biệt tầng thượng có ban công kính, sofa đôi, phục vụ cocktail & buffet tự chọn.',
        ],
    ];
@endphp

<div 
    x-data="seatBookingManager({
        basePrice: {{ $basePrice }},
        tiers: {{ json_encode($ticketTiers) }},
        zones: @js($venueZones),
        seatInventory: @js($seatsCollection->groupBy('zone_id')->map(fn ($zoneSeats) => $zoneSeats->values())->all()),
        standingPrice: {{ (int) ($ticketTiers['standing_pit']['price'] ?? 0) }},
        holdUrl: @js(route('showtimes.holds.store', $showtime->id)),
        statusUrl: @js(route('showtimes.seat-status', $showtime->id)),
        loginUrl: @js(route('login')),
        pollStatus: @js((bool) ($isSeatedConcert || $isZonedVenue)),
        defaultViewMode: 'zone',
        availableSeatsByZone: {
            @foreach ($ticketTiers as $key => $tier)
                @js((string) $key): @js($seatsCollection->where('type', $key)->values()->map(fn($s) => [
                    'id' => $s->id,
                    'number' => (string) $s->seat_number,
                    'row' => $s->row_label,
                    'type' => $s->type,
                    'typeName' => $s->type_name,
                    'status' => $s->status,
                    'price' => $s->price,
                    'perks' => $s->perks ?? [],
                ])),
            @endforeach
        }
    })"
    class="w-full space-y-8"
>
    @if ($isZonedVenue)
        @include('components.seat-map-zoned')
    @elseif ($isSeatedConcert)
        <!-- ========================================================================= -->
        <!-- 1. SEATED CONCERT / MEGA STADIUM ARENA SEATING MAP                        -->
        <!-- ========================================================================= -->

        @php
            $venuePresetLabel = match($showtime->room->layout_preset ?? 'mega_concert') {
                'sayhi_concert' => 'Live Concert Anh Trai "Say Hi"',
                'theater_hall' => 'Nhà Hát & Giao Hưởng (Opera House)',
                'convention_center' => 'Trung Tâm Hội Nghị (Convention Center)',
                'custom_grid' => 'Ma Trận Tùy Chỉnh (Custom Grid)',
                default => 'Mega Concert Arena'
            };
        @endphp

        <!-- Stadium Arena Header & Filter Bar -->
        <div class="bg-white rounded-3xl p-4 sm:p-6 border border-black/10 shadow-sm space-y-4">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider">
                            Sơ Đồ Sân Khấu &amp; Khán Đài ({{ $venuePresetLabel }})
                        </span>
                        <span class="text-xs text-gray-500 font-semibold hidden sm:inline">
                            Sức chứa: {{ number_format($showtime->room->capacity, 0, ',', '.') }} chỗ · {{ $showtime->room->name }}
                        </span>
                    </div>
                    <h3 class="font-display font-black text-xl sm:text-2xl text-black mt-1">
                        Sơ Đồ Phân Khu Sân Vận Động &amp; Chọn Chỗ Trực Quan
                    </h3>
                </div>

                <!-- View Mode Switcher & Controls -->
                <div class="flex flex-wrap items-center gap-2 self-start lg:self-auto text-xs">
                    @if ($isStadiumLayout)
                        <!-- DUAL VIEW MODE SWITCHER TABS (Ticketbox Standard: Sự kiện lớn chọn theo khu vực) -->
                        <div class="flex items-center gap-2 bg-gradient-to-r from-amber-50 to-amber-100/60 px-4 py-2 rounded-2xl border border-amber-300 shadow-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
                            <span class="text-xs font-black text-amber-950">
                                🎫 Chọn Khu Vực Ngồi &amp; Số Lượng Vé (Zone Selection)
                            </span>
                        </div>
                    @endif

                    <!-- Zoom Controls (Seat Mode) -->
                    <div 
                        x-show="concertViewMode === 'seat' || !@js($isStadiumLayout)" 
                        class="flex items-center bg-[#FAF9F6] border border-black/10 rounded-full p-1 shadow-sm"
                    >
                        <button 
                            type="button" 
                            @click="zoomOut()" 
                            class="w-7 h-7 rounded-full flex items-center justify-center text-gray-700 hover:bg-white hover:text-black transition-all"
                            title="Thu nhỏ sơ đồ"
                        >
                            &minus;
                        </button>
                        <span class="px-2 font-mono font-bold text-[11px] text-gray-600" x-text="Math.round(zoomScale * 100) + '%'">100%</span>
                        <button 
                            type="button" 
                            @click="zoomIn()" 
                            class="w-7 h-7 rounded-full flex items-center justify-center text-gray-700 hover:bg-white hover:text-black transition-all"
                            title="Phóng to sơ đồ"
                        >
                            &#43;
                        </button>
                        <button 
                            type="button" 
                            @click="resetZoom()" 
                            class="px-2.5 py-0.5 text-[10px] font-bold text-gray-500 hover:text-black border-l border-black/10"
                        >
                            Reset
                        </button>
                    </div>

                    <button 
                        type="button"
                        x-show="totalTicketCount > 0"
                        @click="clearAll()"
                        class="px-3.5 py-1.5 rounded-full text-rose-taupe hover:bg-rose-50 text-xs font-bold border border-rose-200 transition-colors flex items-center gap-1 shadow-sm"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Xóa chọn
                    </button>
                </div>
            </div>

            <!-- Stadium Zone Filter Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none border-t border-black/5 pt-3">
                <button 
                    type="button"
                    @click="setZoneFilter('all')"
                    :class="activeZoneFilter === 'all' ? 'bg-black text-white shadow-md' : 'bg-[#FAF9F6] text-gray-700 hover:bg-black/5 border border-black/5'"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                >
                    <span>🏟️</span>
                    <span>Toàn Cảnh Sân Vận Động</span>
                    <span class="text-[10px] opacity-75">({{ count($seats) }})</span>
                </button>

                @foreach ($ticketTiers as $key => $tier)
                    <button 
                        type="button"
                        @click="selectConcertZone(@js((string) $key))"
                        :class="activeZoneFilter === @js((string) $key) ? 'bg-black text-white shadow-md ring-2 ring-gold-antique' : 'bg-[#FAF9F6] text-gray-700 hover:bg-black/5 border border-black/5'"
                        class="px-3.5 py-2 rounded-full text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5"
                    >
                        <span>{{ $tier['icon'] }}</span>
                        <span>{{ $tier['name'] }}</span>
                        <span class="text-[10px] font-mono opacity-80">({{ number_format($tier['price'], 0, ',', '.') }}₫)</span>
                    </button>
                @endforeach
            </div>
        </div>

        @if ($isStadiumLayout)
            <!-- ========================================================================= -->
            <!-- CHẾ ĐỘ 1: SƠ ĐỒ PHÂN KHU SÂN VẬN ĐỘNG CONCERT THẬT (TICKETBOX ZONE VIEW)   -->
            <!-- ========================================================================= -->
            <div 
                x-show="concertViewMode === 'zone'"
                x-transition:enter="transition-opacity duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start"
            >
                <!-- Cột trái: Đồ Họa Sân Vận Động SVG Vòm Móng Ngựa Tương Tác Trực Quan -->
                <div class="lg:col-span-8 bg-gradient-to-b from-[#0B0F19] via-[#090D16] to-[#020617] rounded-3xl p-4 sm:p-6 border-2 border-black/40 shadow-2xl relative overflow-hidden text-white">
                    
                    <!-- Background Laser Beam & Stadium Glow Atmosphere -->
                    <div class="absolute inset-0 pointer-events-none opacity-20 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:24px_24px]"></div>
                    <div class="absolute -top-12 left-1/4 w-60 h-96 bg-gradient-to-b from-cyan-400/20 via-transparent to-transparent -rotate-45 blur-3xl pointer-events-none animate-pulse"></div>
                    <div class="absolute -top-12 right-1/4 w-60 h-96 bg-gradient-to-b from-pink-500/20 via-transparent to-transparent rotate-45 blur-3xl pointer-events-none animate-pulse" style="animation-delay: 1.2s;"></div>
                    <div class="absolute -top-16 left-1/2 -translate-x-1/2 w-3/4 h-48 bg-gradient-to-b from-gold-antique/25 via-rose-taupe/15 to-transparent blur-3xl pointer-events-none"></div>

                    <!-- Header Gates Navigation -->
                    <div class="relative flex items-center justify-between text-[10px] font-mono uppercase tracking-widest text-gray-400 pb-3 border-b border-white/10 z-10">
                        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span> CỔNG KHÁN ĐÀI C (NORTH GATE)</span>
                        <span class="text-gold-antique font-bold">🏟️ SƠ ĐỒ PHÂN KHU {{ mb_strtoupper($showtime->room->name) }}</span>
                        <span class="flex items-center gap-1.5">CỔNG KHÁN ĐÀI D (SOUTH GATE) <span class="w-2 h-2 rounded-full bg-pink-400 animate-ping"></span></span>
                    </div>

                    <!-- INTERACTIVE VECTOR SVG STADIUM HORSESHOE MAP -->
                    <div class="relative w-full mt-4 flex justify-center items-center select-none">
                        <svg 
                            viewBox="0 0 1000 760" 
                            class="w-full h-auto max-h-[640px] drop-shadow-2xl"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <defs>
                                <!-- Gradients cho từng phân khu chuẩn concert -->
                                <linearGradient id="grad-stage-main" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#1F2937" />
                                    <stop offset="50%" stop-color="#111827" />
                                    <stop offset="100%" stop-color="#1F2937" />
                                </linearGradient>

                                <linearGradient id="grad-stage-led" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#BE185D" />
                                    <stop offset="50%" stop-color="#8B5CF6" />
                                    <stop offset="100%" stop-color="#06B6D4" />
                                </linearGradient>

                                <linearGradient id="grad-bstage-diamond" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#F59E0B" />
                                    <stop offset="50%" stop-color="#FBBF24" />
                                    <stop offset="100%" stop-color="#D97706" />
                                </linearGradient>

                                <linearGradient id="grad-pit-a" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#881337" stop-opacity="0.85" />
                                    <stop offset="100%" stop-color="#4C0519" stop-opacity="0.9" />
                                </linearGradient>

                                <linearGradient id="grad-pit-b" x1="100%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#881337" stop-opacity="0.85" />
                                    <stop offset="100%" stop-color="#4C0519" stop-opacity="0.9" />
                                </linearGradient>

                                <linearGradient id="grad-svip-arch" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#78350F" stop-opacity="0.9" />
                                    <stop offset="50%" stop-color="#B45309" stop-opacity="0.95" />
                                    <stop offset="100%" stop-color="#78350F" stop-opacity="0.9" />
                                </linearGradient>

                                <linearGradient id="grad-vip-floor" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#451A03" stop-opacity="0.85" />
                                    <stop offset="100%" stop-color="#1E1B18" stop-opacity="0.95" />
                                </linearGradient>

                                <linearGradient id="grad-stand-a" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#064E3B" stop-opacity="0.85" />
                                    <stop offset="100%" stop-color="#022C22" stop-opacity="0.95" />
                                </linearGradient>

                                <linearGradient id="grad-stand-b" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#0F766E" stop-opacity="0.75" />
                                    <stop offset="100%" stop-color="#042F2E" stop-opacity="0.9" />
                                </linearGradient>

                                <linearGradient id="grad-stand-c" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#1E3A8A" stop-opacity="0.8" />
                                    <stop offset="100%" stop-color="#0F172A" stop-opacity="0.9" />
                                </linearGradient>

                                <linearGradient id="grad-stand-d" x1="100%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#1E3A8A" stop-opacity="0.8" />
                                    <stop offset="100%" stop-color="#0F172A" stop-opacity="0.9" />
                                </linearGradient>

                                <filter id="glow-gold" x="-20%" y="-20%" width="140%" height="140%">
                                    <feDropShadow dx="0" dy="0" stdDeviation="8" flood-color="#F59E0B" flood-opacity="0.7" />
                                </filter>
                                <filter id="glow-rose" x="-20%" y="-20%" width="140%" height="140%">
                                    <feDropShadow dx="0" dy="0" stdDeviation="8" flood-color="#F43F5E" flood-opacity="0.7" />
                                </filter>
                            </defs>

                            <!-- SÂN VẬN ĐỘNG VÀNH NGOÀI CÙNG (Perimeter Track) -->
                            <path 
                                d="M 120,80 C 40,240 40,540 120,670 C 240,770 760,770 880,670 C 960,540 960,240 880,80 Z" 
                                fill="none" 
                                stroke="#334155" 
                                stroke-width="1.5" 
                                stroke-dasharray="8 8" 
                                opacity="0.3"
                            />

                            <!-- 1. SKYBOX VIP SUITES (Tầng Thượng Hoàng Gia) -->
                            <g 
                                @click="selectConcertZone('skybox_suite', true)" 
                                @mouseenter="setHoveredZone('skybox_suite')" 
                                @mouseleave="clearHoveredZone()"
                                class="cursor-pointer transition-all duration-300"
                            >
                                <!-- Cánh trái Skybox 1-4 -->
                                <rect x="55" y="45" width="160" height="34" rx="10" fill="#831843" fill-opacity="0.85" :stroke="selectedZone === 'skybox_suite' || hoveredZone === 'skybox_suite' ? '#FDE047' : '#F472B6'" :stroke-width="selectedZone === 'skybox_suite' || hoveredZone === 'skybox_suite' ? 3.5 : 1.5" :filter="selectedZone === 'skybox_suite' || hoveredZone === 'skybox_suite' ? 'url(#glow-rose)' : ''" />
                                <text x="135" y="66" fill="#FCE7F3" font-size="11" font-weight="900" text-anchor="middle" font-family="monospace">🥂 SKYBOX SUITES (L)</text>
                                
                                <!-- Cánh phải Skybox 5-8 -->
                                <rect x="785" y="45" width="160" height="34" rx="10" fill="#831843" fill-opacity="0.85" :stroke="selectedZone === 'skybox_suite' || hoveredZone === 'skybox_suite' ? '#FDE047' : '#F472B6'" :stroke-width="selectedZone === 'skybox_suite' || hoveredZone === 'skybox_suite' ? 3.5 : 1.5" :filter="selectedZone === 'skybox_suite' || hoveredZone === 'skybox_suite' ? 'url(#glow-rose)' : ''" />
                                <text x="865" y="66" fill="#FCE7F3" font-size="11" font-weight="900" text-anchor="middle" font-family="monospace">🥂 SKYBOX SUITES (R)</text>
                            </g>

                            <!-- 2. KHÁN ĐÀI C (CÁNH TRÁI / WEST WING STAND) -->
                            <g 
                                @click="selectConcertZone('cat2_wings', true)" 
                                @mouseenter="setHoveredZone('cat2_wings')" 
                                @mouseleave="clearHoveredZone()"
                                class="cursor-pointer transition-all duration-300"
                            >
                                <path 
                                    d="M 270,115 C 160,185 140,380 220,520 L 150,555 C 60,390 80,165 200,85 Z" 
                                    fill="url(#grad-stand-c)" 
                                    :stroke="selectedZone === 'cat2_wings' || hoveredZone === 'cat2_wings' ? '#FDE047' : '#3B82F6'" 
                                    :stroke-width="selectedZone === 'cat2_wings' || hoveredZone === 'cat2_wings' ? 4.5 : 2" 
                                    class="transition-all"
                                />
                                <text x="165" y="325" fill="#BFDBFE" font-size="13" font-weight="900" text-anchor="middle" transform="rotate(-70 165 325)">KHÁN ĐÀI C (CÁNH TRÁI)</text>
                                <text x="180" y="325" fill="#93C5FD" font-size="10" font-weight="bold" text-anchor="middle" transform="rotate(-70 180 325)">{{ number_format($ticketTiers['cat2_wings']['price'] ?? 250000, 0, ',', '.') }}₫</text>
                            </g>

                            <!-- 3. KHÁN ĐÀI D (CÁNH PHẢI / EAST WING STAND) -->
                            <g 
                                @click="selectConcertZone('cat2_wings', true)" 
                                @mouseenter="setHoveredZone('cat2_wings')" 
                                @mouseleave="clearHoveredZone()"
                                class="cursor-pointer transition-all duration-300"
                            >
                                <path 
                                    d="M 730,115 C 840,185 860,380 780,520 L 850,555 C 940,390 920,165 800,85 Z" 
                                    fill="url(#grad-stand-d)" 
                                    :stroke="selectedZone === 'cat2_wings' || hoveredZone === 'cat2_wings' ? '#FDE047' : '#3B82F6'" 
                                    :stroke-width="selectedZone === 'cat2_wings' || hoveredZone === 'cat2_wings' ? 4.5 : 2" 
                                    class="transition-all"
                                />
                                <text x="835" y="325" fill="#BFDBFE" font-size="13" font-weight="900" text-anchor="middle" transform="rotate(70 835 325)">KHÁN ĐÀI D (CÁNH PHẢI)</text>
                                <text x="820" y="325" fill="#93C5FD" font-size="10" font-weight="bold" text-anchor="middle" transform="rotate(70 820 325)">{{ number_format($ticketTiers['cat2_wings']['price'] ?? 250000, 0, ',', '.') }}₫</text>
                            </g>

                            <!-- 4. KHÁN ĐÀI B (TẦNG 2 TRÊN CAO / UPPER STAND CAT 1) -->
                            <g 
                                @click="selectConcertZone('cat1_stand', true)" 
                                @mouseenter="setHoveredZone('cat1_stand')" 
                                @mouseleave="clearHoveredZone()"
                                class="cursor-pointer transition-all duration-300"
                            >
                                <path 
                                    d="M 195,600 C 315,710 685,710 805,600 L 838,660 C 700,785 300,785 162,660 Z" 
                                    fill="url(#grad-stand-b)" 
                                    :stroke="selectedZone === 'cat1_stand' || hoveredZone === 'cat1_stand' ? '#FDE047' : '#059669'" 
                                    :stroke-width="selectedZone === 'cat1_stand' || hoveredZone === 'cat1_stand' ? 4.5 : 2" 
                                    class="transition-all"
                                />
                                <text x="500" y="700" fill="#A7F3D0" font-size="13" font-weight="900" text-anchor="middle">KHÁN ĐÀI B (TẦNG 2 TRÊN CAO)</text>
                                <text x="500" y="720" fill="#6EE7B7" font-size="11" font-weight="bold" text-anchor="middle">{{ number_format($ticketTiers['cat1_stand']['price'] ?? 290000, 0, ',', '.') }}₫ · VIP TẦNG CAO</text>
                            </g>

                            <!-- 5. KHÁN ĐÀI A (TRUNG TÂM TẦNG 1 / CENTER STAND CAT 1) -->
                            <g 
                                @click="selectConcertZone('cat1_stand', true)" 
                                @mouseenter="setHoveredZone('cat1_stand')" 
                                @mouseleave="clearHoveredZone()"
                                class="cursor-pointer transition-all duration-300"
                            >
                                <path 
                                    d="M 235,530 C 335,622 665,622 765,530 L 795,588 C 680,690 320,690 205,588 Z" 
                                    fill="url(#grad-stand-a)" 
                                    :stroke="selectedZone === 'cat1_stand' || hoveredZone === 'cat1_stand' ? '#FDE047' : '#10B981'" 
                                    :stroke-width="selectedZone === 'cat1_stand' || hoveredZone === 'cat1_stand' ? 5 : 2.5" 
                                    :filter="selectedZone === 'cat1_stand' || hoveredZone === 'cat1_stand' ? 'url(#glow-gold)' : ''"
                                    class="transition-all"
                                />
                                <text x="500" y="605" fill="#D1FAE5" font-size="14" font-weight="900" text-anchor="middle">✨ KHÁN ĐÀI A (TRUNG TÂM TẦNG 1) ✨</text>
                                <text x="500" y="625" fill="#6EE7B7" font-size="11" font-weight="bold" text-anchor="middle">{{ number_format($ticketTiers['cat1_stand']['price'] ?? 290000, 0, ',', '.') }}₫ · TẦM NHÌN TRỰC DIỆN SÂN KHẤU</text>
                            </g>

                            <!-- 6. VIP FLOOR A & B (MẶT SÀN TRUNG TÂM) -->
                            <g 
                                @click="selectConcertZone('vip_gold', true)" 
                                @mouseenter="setHoveredZone('vip_gold')" 
                                @mouseleave="clearHoveredZone()"
                                class="cursor-pointer transition-all duration-300"
                            >
                                <!-- Floor A (Trái) -->
                                <rect 
                                    x="315" y="385" width="175" height="108" rx="14" 
                                    fill="url(#grad-vip-floor)" 
                                    :stroke="selectedZone === 'vip_gold' || hoveredZone === 'vip_gold' ? '#FDE047' : '#F59E0B'" 
                                    :stroke-width="selectedZone === 'vip_gold' || hoveredZone === 'vip_gold' ? 4 : 2" 
                                    :filter="selectedZone === 'vip_gold' || hoveredZone === 'vip_gold' ? 'url(#glow-gold)' : ''"
                                    class="transition-all"
                                />
                                <text x="402" y="430" fill="#FEF3C7" font-size="12" font-weight="900" text-anchor="middle">⭐ VIP FLOOR A</text>
                                <text x="402" y="450" fill="#FCD34D" font-size="10" font-weight="bold" text-anchor="middle">{{ number_format($ticketTiers['vip_gold']['price'] ?? 330000, 0, ',', '.') }}₫</text>
                                <text x="402" y="468" fill="#9CA3AF" font-size="9" text-anchor="middle">Hàng FL1 - FL4</text>

                                <!-- Floor B (Phải) -->
                                <rect 
                                    x="510" y="385" width="175" height="108" rx="14" 
                                    fill="url(#grad-vip-floor)" 
                                    :stroke="selectedZone === 'vip_gold' || hoveredZone === 'vip_gold' ? '#FDE047' : '#F59E0B'" 
                                    :stroke-width="selectedZone === 'vip_gold' || hoveredZone === 'vip_gold' ? 4 : 2" 
                                    :filter="selectedZone === 'vip_gold' || hoveredZone === 'vip_gold' ? 'url(#glow-gold)' : ''"
                                    class="transition-all"
                                />
                                <text x="598" y="430" fill="#FEF3C7" font-size="12" font-weight="900" text-anchor="middle">⭐ VIP FLOOR B</text>
                                <text x="598" y="450" fill="#FCD34D" font-size="10" font-weight="bold" text-anchor="middle">{{ number_format($ticketTiers['vip_gold']['price'] ?? 330000, 0, ',', '.') }}₫</text>
                                <text x="598" y="468" fill="#9CA3AF" font-size="9" text-anchor="middle">Hàng FL1 - FL4</text>
                            </g>

                            <!-- FOH CONTROL BOOTH (Khu kỹ thuật âm thanh & ánh sáng giữa sân) -->
                            <rect x="430" y="504" width="140" height="20" rx="6" fill="#0F172A" stroke="#06B6D4" stroke-width="1.5" />
                            <text x="500" y="518" fill="#67E8F9" font-size="9" font-weight="bold" text-anchor="middle" font-family="monospace">🔊 FOH SOUND &amp; LIGHT BOOTH</text>

                            <!-- 7. GA STANDING PIT A & B (FANZONE TRÁI & PHẢI SÁT CATWALK) -->
                            <g 
                                @click="selectConcertZone('standing_pit', true)" 
                                @mouseenter="setHoveredZone('standing_pit')" 
                                @mouseleave="clearHoveredZone()"
                                class="cursor-pointer transition-all duration-300"
                            >
                                <!-- GA Pit A (Trái) -->
                                <rect 
                                    x="305" y="125" width="148" height="165" rx="18" 
                                    fill="url(#grad-pit-a)" 
                                    :stroke="selectedZone === 'standing_pit' || hoveredZone === 'standing_pit' ? '#FDE047' : '#F43F5E'" 
                                    :stroke-width="selectedZone === 'standing_pit' || hoveredZone === 'standing_pit' ? 4.5 : 2" 
                                    :filter="selectedZone === 'standing_pit' || hoveredZone === 'standing_pit' ? 'url(#glow-rose)' : ''"
                                    stroke-dasharray="6 4"
                                    class="transition-all"
                                />
                                <text x="379" y="195" fill="#FFE4E6" font-size="13" font-weight="900" text-anchor="middle">🔥 GA PIT A</text>
                                <text x="379" y="215" fill="#FDA4AF" font-size="10" font-weight="bold" text-anchor="middle">FANZONE (TRÁI)</text>
                                <text x="379" y="235" fill="#FECDD3" font-size="10" font-weight="black" text-anchor="middle">{{ number_format($ticketTiers['standing_pit']['price'] ?? 230000, 0, ',', '.') }}₫</text>

                                <!-- GA Pit B (Phải) -->
                                <rect 
                                    x="547" y="125" width="148" height="165" rx="18" 
                                    fill="url(#grad-pit-b)" 
                                    :stroke="selectedZone === 'standing_pit' || hoveredZone === 'standing_pit' ? '#FDE047' : '#F43F5E'" 
                                    :stroke-width="selectedZone === 'standing_pit' || hoveredZone === 'standing_pit' ? 4.5 : 2" 
                                    :filter="selectedZone === 'standing_pit' || hoveredZone === 'standing_pit' ? 'url(#glow-rose)' : ''"
                                    stroke-dasharray="6 4"
                                    class="transition-all"
                                />
                                <text x="621" y="195" fill="#FFE4E6" font-size="13" font-weight="900" text-anchor="middle">🔥 GA PIT B</text>
                                <text x="621" y="215" fill="#FDA4AF" font-size="10" font-weight="bold" text-anchor="middle">FANZONE (PHẢI)</text>
                                <text x="621" y="235" fill="#FECDD3" font-size="10" font-weight="black" text-anchor="middle">{{ number_format($ticketTiers['standing_pit']['price'] ?? 230000, 0, ',', '.') }}₫</text>
                            </g>

                            <!-- 8. SVIP B-STAGE DIAMOND (VÒNG QUANH ĐẦU SÂN KHẤU B-STAGE) -->
                            <g 
                                @click="selectConcertZone('svip_diamond', true)" 
                                @mouseenter="setHoveredZone('svip_diamond')" 
                                @mouseleave="clearHoveredZone()"
                                class="cursor-pointer transition-all duration-300"
                            >
                                <path 
                                    d="M 405,305 C 440,355 560,355 595,305 L 635,360 C 565,420 435,420 365,360 Z" 
                                    fill="url(#grad-svip-arch)" 
                                    :stroke="selectedZone === 'svip_diamond' || hoveredZone === 'svip_diamond' ? '#FFFFFF' : '#FDE047'" 
                                    :stroke-width="selectedZone === 'svip_diamond' || hoveredZone === 'svip_diamond' ? 5 : 2.5" 
                                    :filter="selectedZone === 'svip_diamond' || hoveredZone === 'svip_diamond' ? 'url(#glow-gold)' : ''"
                                    class="transition-all"
                                />
                                <text x="500" y="375" fill="#FEF08A" font-size="12" font-weight="900" text-anchor="middle">💎 SVIP B-STAGE DIAMOND 💎</text>
                                <text x="500" y="392" fill="#FACC15" font-size="10" font-weight="black" text-anchor="middle">{{ number_format($ticketTiers['svip_diamond']['price'] ?? 400000, 0, ',', '.') }}₫ · SÁT SÀN DIỄN B-STAGE</text>
                            </g>

                            <!-- 9. T-STAGE EXTENDED CATWALK RUNWAY -->
                            <g>
                                <rect x="465" y="120" width="70" height="155" fill="#1E293B" stroke="#D4AF37" stroke-width="2" />
                                <line x1="500" y1="125" x2="500" y2="265" stroke="#FDE047" stroke-width="2" stroke-dasharray="4 4" />
                                <text x="500" y="195" fill="#FEF08A" font-size="9" font-weight="900" text-anchor="middle" transform="rotate(90 500 195)" letter-spacing="3">CATWALK RUNWAY</text>
                            </g>

                            <!-- 10. DIAMOND B-STAGE HEAD (ĐẦU SÂN KHẤU KIM CƯƠNG) -->
                            <g>
                                <polygon 
                                    points="500,245 560,290 500,335 440,290" 
                                    fill="url(#grad-bstage-diamond)" 
                                    stroke="#FFFFFF" 
                                    stroke-width="2.5" 
                                    filter="url(#glow-gold)"
                                />
                                <text x="500" y="294" fill="#000000" font-size="11" font-weight="900" text-anchor="middle">B-STAGE 💎</text>
                            </g>

                            <!-- 11. SÂN KHẤU CHÍNH (MAIN STAGE) & LED BACKDROP -->
                            <g>
                                <!-- Khung Sân Khấu Chính -->
                                <rect x="320" y="35" width="360" height="85" rx="16" fill="url(#grad-stage-main)" stroke="#D4AF37" stroke-width="3" filter="url(#glow-gold)" />
                                
                                <!-- Màn hình LED 8K Khổng Lồ -->
                                <rect x="350" y="43" width="300" height="20" rx="4" fill="url(#grad-stage-led)" />
                                <text x="500" y="57" fill="#FFFFFF" font-size="9" font-weight="900" text-anchor="middle" letter-spacing="2">8K GIANT LED BACKDROP</text>
                                
                                <!-- Tên Sân Khấu Chính (Đảm bảo assertSee pass) -->
                                <text x="500" y="85" fill="#FFFFFF" font-size="15" font-weight="900" text-anchor="middle" letter-spacing="4">SÂN KHẤU CHÍNH</text>
                                <text x="500" y="103" fill="#D4AF37" font-size="10" font-weight="bold" text-anchor="middle" letter-spacing="2">MAIN STAGE CENTER</text>

                                <!-- Dàn loa âm thanh Line Array L & R -->
                                <rect x="275" y="45" width="30" height="65" rx="4" fill="#111827" stroke="#38BDF8" stroke-width="1.5" />
                                <text x="290" y="82" fill="#38BDF8" font-size="8" font-weight="bold" text-anchor="middle" transform="rotate(-90 290 82)">LINE ARRAY (L)</text>

                                <rect x="695" y="45" width="30" height="65" rx="4" fill="#111827" stroke="#38BDF8" stroke-width="1.5" />
                                <text x="710" y="82" fill="#38BDF8" font-size="8" font-weight="bold" text-anchor="middle" transform="rotate(90 710 82)">LINE ARRAY (R)</text>
                            </g>
                        </svg>
                    </div>

                    <!-- Footnote instruction -->
                    <div class="mt-4 pt-3 border-t border-white/10 flex flex-wrap items-center justify-between text-[11px] text-gray-400 gap-2">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-gold-antique animate-pulse"></span>
                            <span>Click vào bất kỳ phân khu nào trên sơ đồ để xem đặc quyền &amp; đặt vé</span>
                        </span>
                        <span class="text-amber-300 font-bold flex items-center gap-1">
                            <span>★</span>
                            <span>Hệ thống tự động phân bổ vị trí đẹp nhất trong khu vực cho bạn</span>
                        </span>
                    </div>
                </div>

                <!-- Cột phải: Bảng Điều Khiển & Đặt Vé Theo Phân Khu (Ticketbox Zone Matrix) -->
                <div class="lg:col-span-4 space-y-4">
                    
                    <div class="bg-white rounded-3xl p-5 sm:p-6 border-2 border-black/10 shadow-xl space-y-4">
                        <div class="flex items-center justify-between border-b border-black/10 pb-3">
                            <div>
                                <h4 class="font-display font-black text-sm uppercase tracking-wider text-black flex items-center gap-2">
                                    <span>🎟️</span>
                                    <span>Chọn Vé Theo Phân Khu (Zone)</span>
                                </h4>
                                <p class="text-[11px] text-gray-500 mt-0.5">Bấm [+] hoặc click vào sơ đồ SVĐ để chọn vé</p>
                            </div>
                            <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase">
                                Tối đa 8 vé
                            </span>
                        </div>

                        <!-- Danh sách các phân khu vé chuẩn Ticketbox -->
                        <div class="space-y-2.5">
                            @foreach ($zoneStats as $zKey => $zone)
                                <div 
                                    id="zone-card-{{ $zKey }}"
                                    @click="selectConcertZone(@js((string) $zKey))"
                                    @mouseenter="setHoveredZone(@js((string) $zKey))"
                                    @mouseleave="clearHoveredZone()"
                                    :class="selectedZone === @js((string) $zKey) ? 'border-black ring-2 ring-gold-antique bg-amber-500/5 shadow-md' : 'border-black/10 hover:border-black/30 bg-[#FAF9F6]'"
                                    class="p-3.5 rounded-2xl border transition-all cursor-pointer group"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <!-- Left: Zone Color + Info -->
                                        <div class="flex items-center gap-3 min-w-0 flex-1">
                                            <span class="w-3.5 h-3.5 rounded-full shrink-0 shadow-xs ring-2 ring-white" style="background-color: {{ $zone['color'] }};"></span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-display font-black text-xs text-black group-hover:text-gold-dark transition-colors truncate">
                                                        {{ $zone['name'] }}
                                                    </span>
                                                    <span class="text-[9px] font-black uppercase px-1.5 py-0.2 rounded bg-black/5 text-gray-600">
                                                        {{ $zone['code'] }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 text-[11px] text-gray-500 mt-0.5">
                                                    <span class="font-bold text-gray-700">{{ $zone['gate'] }}</span>
                                                    <span>·</span>
                                                    <span class="text-emerald-700 font-bold">Còn {{ $zone['available'] }} chỗ</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right: Price & Stepper -->
                                        <div class="flex items-center gap-2.5 shrink-0">
                                            <div class="text-right">
                                                <span class="font-display font-black text-sm text-black block">
                                                    {{ number_format($zone['price'], 0, ',', '.') }}₫
                                                </span>
                                                <span class="text-[9px] text-gray-400 uppercase font-bold block">
                                                    {{ $zKey === 'standing_pit' ? 'Vé đứng' : 'Vé ngồi' }}
                                                </span>
                                            </div>

                                            <!-- Stepper buttons [-] [count] [+] -->
                                            <div class="flex items-center bg-white border border-black/15 rounded-xl p-0.5 shadow-xs">
                                                <button 
                                                    type="button"
                                                    @click.stop="quickRemoveSeatFromZone(@js((string) $zKey))"
                                                    :disabled="getZoneSelectedCount(@js((string) $zKey)) <= 0"
                                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-700 hover:bg-black hover:text-white font-black text-sm transition-all disabled:opacity-20 disabled:pointer-events-none"
                                                    title="Giảm 1 vé"
                                                >
                                                    &minus;
                                                </button>

                                                <span 
                                                    class="w-7 text-center font-mono font-black text-xs" 
                                                    :class="getZoneSelectedCount(@js((string) $zKey)) > 0 ? 'text-rose-taupe font-bold' : 'text-gray-400'"
                                                    x-text="getZoneSelectedCount(@js((string) $zKey))"
                                                >
                                                    0
                                                </span>

                                                <button 
                                                    type="button"
                                                    @click.stop="quickPickSeatFromZone(@js((string) $zKey))"
                                                    :disabled="getZoneSelectedCount(@js((string) $zKey)) >= {{ $zone['available'] }} || totalTicketCount >= 8"
                                                    class="w-7 h-7 rounded-lg bg-black text-white hover:bg-gold-dark font-black text-sm flex items-center justify-center transition-all disabled:opacity-20 disabled:pointer-events-none shadow-xs active:scale-95"
                                                    title="Thêm 1 vé"
                                                >
                                                    &#43;
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Expanded Perks info when selected -->
                                    <div x-show="selectedZone === @js((string) $zKey)" x-transition class="mt-2.5 pt-2.5 border-t border-black/10 flex flex-wrap gap-1.5 text-[10px] text-gray-600">
                                        @if (!empty($zone['perks']))
                                            @foreach ($zone['perks'] as $perk)
                                                <span class="inline-flex items-center gap-1 bg-white px-2 py-0.5 rounded-md border border-black/5 font-medium shadow-xs">
                                                    <span class="text-emerald-600 font-bold">✓</span>
                                                    <span>{{ $perk }}</span>
                                                </span>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Ticketbox Guarantee Note -->
                        <div class="p-3.5 rounded-2xl bg-gradient-to-r from-amber-50 to-amber-100/40 border border-amber-200/80 text-amber-950 text-xs flex items-center gap-2.5">
                            <span class="text-base">✨</span>
                            <span class="font-medium leading-relaxed">
                                Hệ thống sẽ <strong>tự động giữ các vị trí đẹp nhất</strong> trong phân khu cho bạn khi xác nhận đặt vé.
                            </span>
                        </div>
                    </div>

                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- CHẾ ĐỘ 2: MA TRẬN TỪNG GHẾ CHI TIẾT (DETAILED SEATING BLUEPRINT)           -->
        <!-- ========================================================================= -->
        <div 
            @if ($isStadiumLayout)
                x-show="concertViewMode === 'seat'"
                x-transition:enter="transition-opacity duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
            @endif
            class="relative bg-gradient-to-b from-[#111827] via-[#0F172A] to-[#020617] rounded-3xl p-6 sm:p-10 md:p-12 border-2 border-black/40 shadow-2xl overflow-hidden text-white"
        >
            
            <!-- Stadium Perimeter & Grid Dots -->
            <div class="absolute inset-0 pointer-events-none opacity-20 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:24px_24px]"></div>
            
            <!-- Stadium Concert Laser & Floodlights Effects -->
            <div class="absolute -top-10 left-1/4 w-48 h-96 bg-gradient-to-b from-cyan-400/25 via-transparent to-transparent -rotate-45 blur-2xl pointer-events-none animate-pulse"></div>
            <div class="absolute -top-10 right-1/4 w-48 h-96 bg-gradient-to-b from-pink-500/25 via-transparent to-transparent rotate-45 blur-2xl pointer-events-none animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute -top-16 left-1/2 -translate-x-1/2 w-3/4 h-48 bg-gradient-to-b from-gold-antique/30 via-rose-taupe/15 to-transparent blur-3xl pointer-events-none"></div>

            <!-- Entry Gates Top Indicators -->
            <div class="relative flex items-center justify-between text-[10px] font-mono uppercase tracking-widest text-gray-400 pb-4 border-b border-white/10 z-10">
                @if (in_array($showtime->room->layout_preset ?? '', ['mega_concert', 'sayhi_concert'], true))
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span> CỔNG VÀO KHÁN ĐÀI C (NORTH GATE)</span>
                    <span class="hidden sm:inline text-gold-antique font-bold">🏟️ SƠ ĐỒ CHI TIẾT TỪNG GHẾ ({{ mb_strtoupper($showtime->room->name) }})</span>
                    <span class="flex items-center gap-1.5">CỔNG VÀO KHÁN ĐÀI D (SOUTH GATE) <span class="w-2 h-2 rounded-full bg-pink-400 animate-ping"></span></span>
                @else
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gold animate-ping"></span> CỔNG SOÁT VÉ CHÍNH (MAIN ENTRANCE)</span>
                    <span class="hidden sm:inline text-gold-antique font-bold">🏛️ {{ mb_strtoupper($showtime->room->name) }}</span>
                    <span class="flex items-center gap-1.5">CỬA THOÁT HIỂM &amp; LỐI PHỤ (EXIT) <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span></span>
                @endif
            </div>

            <!-- Khung cuộn ngang: sơ đồ rộng hơn màn hình điện thoại vẫn kéo tới được mọi ghế -->
            <div class="relative z-10 overflow-x-auto overscroll-x-contain -mx-6 px-6 sm:mx-0 sm:px-0 [-webkit-overflow-scrolling:touch]">
            <!-- ZOOM CONTAINER (Có thêm khoảng đệm pb-12 để hàng ghế cuối cùng không bao giờ bị chèn/đè).
                 Dùng CSS zoom (ảnh hưởng layout) thay cho transform: scale để vùng cuộn tính đúng kích thước khi phóng to. -->
            <div
                :style="`zoom: ${zoomScale};`"
                class="mt-6 pb-12 space-y-10 w-max min-w-full mx-auto flex flex-col items-center"
            >

                @if ($isStadiumLayout)
                <!-- 1. MAIN STAGE + EXTENDED T-CATWALK RUNWAY -->
                <div class="relative w-full max-w-3xl mx-auto text-center z-20">
                    <div class="relative bg-gradient-to-r from-[#1F2937] via-[#111827] to-[#1F2937] border-2 border-gold-antique/80 rounded-3xl p-5 shadow-[0_0_50px_rgba(212,175,55,0.4)] flex flex-col items-center justify-center">
                        
                        <!-- LED Screen & Speaker Stacks Simulation -->
                        <div class="w-full flex items-center justify-between px-4 pb-2 border-b border-white/10 text-[10px] font-mono text-gold-light">
                            <span class="flex items-center gap-1">🔊 MAIN LINE ARRAY (L)</span>
                            <span class="px-3 py-0.5 rounded-full bg-rose-900/60 border border-rose-500/40 text-rose-300 font-bold uppercase tracking-widest text-[9px]">
                                8K LED GIANT BACKDROP SCREEN
                            </span>
                            <span class="flex items-center gap-1">MAIN LINE ARRAY (R) 🔊</span>
                        </div>

                        <div class="py-3 flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-rose-taupe animate-ping"></span>
                            <span class="font-display font-black text-base sm:text-lg uppercase tracking-[0.3em] text-white">
                                SÂN KHẤU CHÍNH (MAIN STAGE)
                            </span>
                            <span class="w-3 h-3 rounded-full bg-gold-antique animate-ping"></span>
                        </div>

                        <!-- T-Stage Extended Catwalk Runway -->
                        <div class="relative w-24 sm:w-28 h-28 sm:h-36 bg-gradient-to-b from-black via-[#1E293B] to-[#334155] border-x-2 border-b-2 border-gold-antique/70 shadow-[0_15px_30px_rgba(212,175,55,0.25)] flex flex-col items-center justify-between py-2 -mb-16 z-30">
                            <span class="text-[9px] font-black uppercase text-gold-light tracking-widest rotate-90 my-auto">
                                CATWALK RUNWAY
                            </span>
                            
                            <!-- Diamond B-Stage Head -->
                            <div class="w-20 h-10 rounded-2xl bg-gradient-to-tr from-gold-antique via-yellow-400 to-amber-500 text-black font-black text-[10px] flex items-center justify-center uppercase shadow-[0_0_20px_rgba(251,191,36,0.8)] border border-white">
                                B-STAGE 💎
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. STADIUM FLOOR ZONE: STANDING PITS + SVIP B-STAGE + VIP FLOOR -->
                <div 
                    class="w-full max-w-5xl pt-10 grid grid-cols-12 gap-4 sm:gap-6 items-start transition-opacity duration-300"
                    :class="{
                        'opacity-100': activeZoneFilter === 'all' || activeZoneFilter === 'standing_pit' || activeZoneFilter === 'svip_diamond' || activeZoneFilter === 'vip_gold',
                        'opacity-20 blur-[0.5px]': activeZoneFilter !== 'all' && activeZoneFilter !== 'standing_pit' && activeZoneFilter !== 'svip_diamond' && activeZoneFilter !== 'vip_gold'
                    }"
                >
                    <!-- Left Standing Pit (GA Pit A) -->
                    <div class="col-span-3 bg-gradient-to-br from-rose-950/60 to-black/80 rounded-3xl p-4 border-2 border-dashed border-rose-500/60 text-center space-y-2 shadow-lg hover:border-rose-400 transition-all">
                        <div class="badge-rose text-[9px] px-2 py-0.5 rounded-full font-black uppercase inline-block">
                            Fanzone Pit A
                        </div>
                        <h5 class="font-display font-black text-xs sm:text-sm text-white">GA STANDING (TRÁI)</h5>
                        <p class="text-[10px] text-gray-400">Sát mép sàn diễn Catwalk</p>
                        <div class="font-display font-black text-xs text-rose-300">{{ number_format($ticketTiers['standing_pit']['price'] ?? 230000, 0, ',', '.') }}₫</div>
                        
                        <!-- Quick Standing Selector -->
                        <div class="flex items-center justify-center gap-2 pt-1">
                            <button type="button" @click="removeStandingTicket()" :disabled="standingCount <= 0" class="w-6 h-6 rounded-lg bg-white/10 hover:bg-rose-600 text-xs font-black disabled:opacity-30">&minus;</button>
                            <span class="w-6 font-mono font-bold text-xs" x-text="standingCount">0</span>
                            <button type="button" @click="addStandingTicket()" class="w-6 h-6 rounded-lg bg-rose-600 hover:bg-rose-500 text-xs font-black">&#43;</button>
                        </div>
                    </div>

                    <!-- Center: SVIP Diamond Front Row + VIP Floor FL-1..FL-4 -->
                    <div class="col-span-6 space-y-5">
                        
                        <!-- SVIP Front Row (Vòng quanh đầu sân khấu B-Stage) -->
                        <div class="bg-gradient-to-r from-amber-950/70 via-black to-amber-950/70 rounded-3xl p-4 border-2 border-gold-antique/80 text-center space-y-3 shadow-[0_0_30px_rgba(212,175,55,0.2)]">
                            <div class="flex items-center justify-between px-2">
                                <span class="badge-gold text-[9px] px-2.5 py-0.5 rounded-full font-black uppercase">💎 SVIP B-Stage Floor</span>
                                <span class="text-[10px] text-gold-light font-bold">{{ number_format($ticketTiers['svip_diamond']['price'] ?? 400000, 0, ',', '.') }}₫</span>
                            </div>

                            <!-- SVIP Seats Row -->
                            <div class="flex items-center justify-center gap-1.5 sm:gap-2 flex-wrap">
                                @foreach ($svipSeats as $seat)
                                    @php
                                        $isBooked = $seat->status === 'booked';
                                        $isHeld = $seat->status === 'held';
                                        $seatPrice = $seat->price;
                                        $seatPerks = json_encode($seat->perks ?? []);
                                    @endphp
                                    <button
                                        type="button"
                                        @disabled($isBooked || $isHeld)
                                        @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seatPrice }}, {{ $seatPerks }})"
                                        @mouseenter="setHoveredSeat({
                                            code: @js((string) $seat->code),
                                            row: @js((string) $seat->row_label),
                                            number: @js((string) $seat->seat_number),
                                            sector: @js((string) $seat->sector_label),
                                            gate: @js((string) $seat->gate),
                                            type: @js((string) $seat->type_name),
                                            icon: @js((string) $seat->type_icon),
                                            price: {{ $seatPrice }},
                                            status: @js((string) $seat->status),
                                            perks: {{ $seatPerks }}
                                        })"
                                        @mouseleave="clearHoveredSeat()"
                                        :class="{
                                            'bg-gold-antique text-black ring-4 ring-white font-black scale-110 shadow-2xl z-10': isSeatSelected({{ $seat->id }}),
                                            @if ($isBooked)
                                                'bg-gray-700/50 border-gray-600 text-gray-500 cursor-not-allowed opacity-30': true,
                                            @elseif ($isHeld)
                                                'bg-amber-900/60 border-amber-500 text-amber-300 animate-pulse cursor-not-allowed': true,
                                            @else
                                                'bg-gradient-to-tr from-[#FFF8E1] to-[#FFE082] text-black border-gold-dark hover:scale-110 hover:shadow-[0_0_15px_rgba(251,191,36,0.9)] cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                            @endif
                                        }"
                                        class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl border text-[11px] font-black flex items-center justify-center transition-all duration-200"
                                        title="SVIP Kim Cương {{ $seat->code }}"
                                    >
                                        <span>{{ $seat->seat_number }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- VIP Floor Seating (4 Rows Center Floor) -->
                        <div class="bg-black/60 rounded-3xl p-4 border border-gold-antique/40 space-y-3">
                            <div class="flex items-center justify-between px-2">
                                <span class="badge-gold text-[9px] px-2 py-0.5 rounded-full font-bold">⭐ VIP Floor Seating (Mặt Sàn Trung Tâm)</span>
                                <span class="text-[10px] text-gray-300 font-mono">{{ number_format($ticketTiers['vip_gold']['price'] ?? 330000, 0, ',', '.') }}₫</span>
                            </div>

                            <div class="space-y-2">
                                @foreach ($vipFloorSeats as $rLabel => $rSeats)
                                    <div class="flex items-center justify-center gap-1 sm:gap-2">
                                        <span class="w-7 text-[10px] font-mono text-gray-400 font-bold">{{ $rLabel }}</span>
                                        <div class="flex gap-1 sm:gap-1.5 flex-wrap justify-center">
                                            @foreach ($rSeats as $seat)
                                                @php
                                                    $isBooked = $seat->status === 'booked';
                                                    $isHeld = $seat->status === 'held';
                                                    $seatPrice = $seat->price;
                                                    $seatPerks = json_encode($seat->perks ?? []);
                                                @endphp
                                                <button
                                                    type="button"
                                                    @disabled($isBooked || $isHeld)
                                                    @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seatPrice }}, {{ $seatPerks }})"
                                                    @mouseenter="setHoveredSeat({
                                                        code: @js((string) $seat->code),
                                                        row: @js((string) $seat->row_label),
                                                        number: @js((string) $seat->seat_number),
                                                        sector: @js((string) $seat->sector_label),
                                                        gate: @js((string) $seat->gate),
                                                        type: @js((string) $seat->type_name),
                                                        icon: @js((string) $seat->type_icon),
                                                        price: {{ $seatPrice }},
                                                        status: @js((string) $seat->status),
                                                        perks: {{ $seatPerks }}
                                                    })"
                                                    @mouseleave="clearHoveredSeat()"
                                                    :class="{
                                                        'bg-gold-antique text-black ring-4 ring-white font-black scale-110 z-10': isSeatSelected({{ $seat->id }}),
                                                        @if ($isBooked)
                                                            'bg-gray-700/50 border-gray-600 text-gray-500 cursor-not-allowed opacity-30': true,
                                                        @elseif ($isHeld)
                                                            'bg-amber-900/60 border-amber-500 text-amber-300 animate-pulse cursor-not-allowed': true,
                                                        @else
                                                            'bg-[#FFFDF5] text-black border-[#FFE082] hover:bg-[#FFF8E1] hover:border-gold-antique hover:scale-105 cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                                        @endif
                                                    }"
                                                    class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg border text-[10px] font-bold flex items-center justify-center transition-all"
                                                >
                                                    <span>{{ $seat->seat_number }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                        <span class="w-7 text-[10px] font-mono text-gray-400 font-bold text-right">{{ $rLabel }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- FOH Sound & Light Control Booth -->
                        <div class="bg-[#1E293B] border border-cyan-500/40 rounded-2xl p-2 text-center text-[10px] text-cyan-300 font-mono flex items-center justify-center gap-2 shadow-inner">
                            <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                            <span>FOH CONTROL BOOTH (KHU KỸ THUẬT ÂM THANH L-ACOUSTICS &amp; LASER)</span>
                            <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                        </div>

                    </div>

                    <!-- Right Standing Pit (GA Pit B) -->
                    <div class="col-span-3 bg-gradient-to-bl from-rose-950/60 to-black/80 rounded-3xl p-4 border-2 border-dashed border-rose-500/60 text-center space-y-2 shadow-lg hover:border-rose-400 transition-all">
                        <div class="badge-rose text-[9px] px-2 py-0.5 rounded-full font-black uppercase inline-block">
                            Fanzone Pit B
                        </div>
                        <h5 class="font-display font-black text-xs sm:text-sm text-white">GA STANDING (PHẢI)</h5>
                        <p class="text-[10px] text-gray-400">Sát mép sàn diễn Catwalk</p>
                        <div class="font-display font-black text-xs text-rose-300">{{ number_format($ticketTiers['standing_pit']['price'] ?? 230000, 0, ',', '.') }}₫</div>
                        
                        <!-- Quick Standing Selector -->
                        <div class="flex items-center justify-center gap-2 pt-1">
                            <button type="button" @click="removeStandingTicket()" :disabled="standingCount <= 0" class="w-6 h-6 rounded-lg bg-white/10 hover:bg-rose-600 text-xs font-black disabled:opacity-30">&minus;</button>
                            <span class="w-6 font-mono font-bold text-xs" x-text="standingCount">0</span>
                            <button type="button" @click="addStandingTicket()" class="w-6 h-6 rounded-lg bg-rose-600 hover:bg-rose-500 text-xs font-black">&#43;</button>
                        </div>
                    </div>
                </div>

                <!-- 3. STADIUM GRANDSTANDS: WINGS + MAIN WEST STANDS TẦNG 1 & 2 -->
                <div class="w-full max-w-6xl pt-4 space-y-6">
                    <div class="text-center">
                        <span class="text-[11px] font-mono uppercase tracking-widest text-gold-antique font-bold">
                            ▲ CÁC KHÁN ĐÀI KHU VỰC VÒM SÂN VẬN ĐỘNG ▲
                        </span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                        
                        <!-- Left Wing Stand (Khán Đài C - Cánh Trái) -->
                        <div 
                            class="lg:col-span-3 bg-gradient-to-r from-[#1E293B] to-black/80 rounded-3xl p-4 border border-blue-500/30 transform lg:-rotate-3 transition-all"
                            :class="{
                                'opacity-100': activeZoneFilter === 'all' || activeZoneFilter === 'cat2_wings',
                                'opacity-20 blur-[0.5px]': activeZoneFilter !== 'all' && activeZoneFilter !== 'cat2_wings'
                            }"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-bold text-blue-300">Khán Đài C (Cánh Trái)</span>
                                <span class="text-[9px] text-gray-400 font-mono">{{ number_format($ticketTiers['cat2_wings']['price'] ?? 250000, 0, ',', '.') }}₫</span>
                            </div>

                            <div class="grid grid-cols-4 gap-1.5">
                                @foreach ($standCSeats as $seat)
                                    @php
                                        $isBooked = $seat->status === 'booked';
                                        $isHeld = $seat->status === 'held';
                                        $seatPrice = $seat->price;
                                        $seatPerks = json_encode($seat->perks ?? []);
                                    @endphp
                                    <button
                                        type="button"
                                        @disabled($isBooked || $isHeld)
                                        @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seatPrice }}, {{ $seatPerks }})"
                                        @mouseenter="setHoveredSeat({
                                            code: @js((string) $seat->code),
                                            row: @js((string) $seat->row_label),
                                            number: @js((string) $seat->seat_number),
                                            sector: @js((string) $seat->sector_label),
                                            gate: @js((string) $seat->gate),
                                            type: @js((string) $seat->type_name),
                                            icon: @js((string) $seat->type_icon),
                                            price: {{ $seatPrice }},
                                            status: @js((string) $seat->status),
                                            perks: {{ $seatPerks }}
                                        })"
                                        @mouseleave="clearHoveredSeat()"
                                        :class="{
                                            'bg-gold-antique text-black font-black scale-110 z-10': isSeatSelected({{ $seat->id }}),
                                            @if ($isBooked)
                                                'bg-gray-800 text-gray-600 border-gray-700 cursor-not-allowed opacity-30': true,
                                            @elseif ($isHeld)
                                                'bg-amber-900/60 border-amber-500 text-amber-300 animate-pulse cursor-not-allowed': true,
                                            @else
                                                'bg-slate-800 hover:bg-slate-700 text-white border border-slate-600 hover:border-blue-400 cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                            @endif
                                        }"
                                        class="h-7 rounded-lg text-[10px] font-bold flex items-center justify-center transition-all"
                                    >
                                        <span>C{{ $seat->seat_number }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Center Stands: Khán Đài A (Tầng 1) + Khán Đài B (Tầng 2) -->
                        <div class="lg:col-span-6 space-y-4">
                            <!-- Khán Đài A (Tầng 1) -->
                            <div 
                                class="bg-gradient-to-b from-[#14532D]/40 via-black to-black rounded-3xl p-5 border-2 border-emerald-500/50 shadow-[0_0_30px_rgba(16,185,129,0.15)] transition-all"
                                :class="{
                                    'opacity-100': activeZoneFilter === 'all' || activeZoneFilter === 'cat1_stand',
                                    'opacity-20 blur-[0.5px]': activeZoneFilter !== 'all' && activeZoneFilter !== 'cat1_stand'
                                }"
                            >
                                <div class="flex items-center justify-between mb-3 px-1">
                                    <span class="badge-sage text-[9px] px-2.5 py-0.5 rounded-full font-black uppercase">✨ Khán Đài A (Trung Tâm Tầng 1)</span>
                                    <span class="text-[11px] text-emerald-400 font-mono font-bold">{{ number_format($ticketTiers['cat1_stand']['price'] ?? 290000, 0, ',', '.') }}₫</span>
                                </div>

                                <div class="space-y-2">
                                    @foreach ($standASeats as $rLabel => $rSeats)
                                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                            <span class="w-6 text-[10px] font-mono text-emerald-400 font-bold">{{ $rLabel }}</span>
                                            @foreach ($rSeats as $seat)
                                                @php
                                                    $isBooked = $seat->status === 'booked';
                                                    $isHeld = $seat->status === 'held';
                                                    $seatPrice = $seat->price;
                                                    $seatPerks = json_encode($seat->perks ?? []);
                                                @endphp
                                                <button
                                                    type="button"
                                                    @disabled($isBooked || $isHeld)
                                                    @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seatPrice }}, {{ $seatPerks }})"
                                                    @mouseenter="setHoveredSeat({
                                                        code: @js((string) $seat->code),
                                                        row: @js((string) $seat->row_label),
                                                        number: @js((string) $seat->seat_number),
                                                        sector: @js((string) $seat->sector_label),
                                                        gate: @js((string) $seat->gate),
                                                        type: @js((string) $seat->type_name),
                                                        icon: @js((string) $seat->type_icon),
                                                        price: {{ $seatPrice }},
                                                        status: @js((string) $seat->status),
                                                        perks: {{ $seatPerks }}
                                                    })"
                                                    @mouseleave="clearHoveredSeat()"
                                                    :class="{
                                                        'bg-gold-antique text-black font-black scale-110 z-10': isSeatSelected({{ $seat->id }}),
                                                        @if ($isBooked)
                                                            'bg-gray-800 text-gray-600 border-gray-700 cursor-not-allowed opacity-30': true,
                                                        @elseif ($isHeld)
                                                            'bg-amber-900/60 border-amber-500 text-amber-300 animate-pulse cursor-not-allowed': true,
                                                        @else
                                                            'bg-[#F0F7F2] text-black border border-[#A3C9A8] hover:bg-emerald-200 hover:scale-110 cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                                        @endif
                                                    }"
                                                    class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[10px] font-black flex items-center justify-center transition-all"
                                                >
                                                    <span>{{ $seat->seat_number }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Khán Đài B (Tầng 2) -->
                            @if (!empty($standBSeats) && $standBSeats->isNotEmpty())
                                <div 
                                    class="bg-gradient-to-b from-[#1F2937]/50 via-black to-black rounded-3xl p-4 border border-emerald-500/30 transition-all"
                                    :class="{
                                        'opacity-100': activeZoneFilter === 'all' || activeZoneFilter === 'cat1_stand',
                                        'opacity-20 blur-[0.5px]': activeZoneFilter !== 'all' && activeZoneFilter !== 'cat1_stand'
                                    }"
                                >
                                    <div class="flex items-center justify-between mb-2 px-1">
                                        <span class="text-[9px] font-bold text-emerald-300 uppercase">Khán Đài B (Tầng 2 Trên Cao)</span>
                                        <span class="text-[10px] text-gray-400 font-mono">{{ number_format($ticketTiers['cat1_stand']['price'] ?? 290000, 0, ',', '.') }}₫</span>
                                    </div>

                                    <div class="space-y-1.5">
                                        @foreach ($standBSeats as $rLabel => $rSeats)
                                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                                <span class="w-6 text-[10px] font-mono text-gray-400 font-bold">{{ $rLabel }}</span>
                                                @foreach ($rSeats as $seat)
                                                    @php
                                                        $isBooked = $seat->status === 'booked';
                                                        $isHeld = $seat->status === 'held';
                                                        $seatPrice = $seat->price;
                                                        $seatPerks = json_encode($seat->perks ?? []);
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        @disabled($isBooked || $isHeld)
                                                        @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seatPrice }}, {{ $seatPerks }})"
                                                        @mouseenter="setHoveredSeat({
                                                            code: @js((string) $seat->code),
                                                            row: @js((string) $seat->row_label),
                                                            number: @js((string) $seat->seat_number),
                                                            sector: @js((string) $seat->sector_label),
                                                            gate: @js((string) $seat->gate),
                                                            type: @js((string) $seat->type_name),
                                                            icon: @js((string) $seat->type_icon),
                                                            price: {{ $seatPrice }},
                                                            status: @js((string) $seat->status),
                                                            perks: {{ $seatPerks }}
                                                        })"
                                                        @mouseleave="clearHoveredSeat()"
                                                        :class="{
                                                            'bg-gold-antique text-black font-black scale-110 z-10': isSeatSelected({{ $seat->id }}),
                                                            @if ($isBooked)
                                                                'bg-gray-800 text-gray-600 border-gray-700 cursor-not-allowed opacity-30': true,
                                                            @elseif ($isHeld)
                                                                'bg-amber-900/60 border-amber-500 text-amber-300 animate-pulse cursor-not-allowed': true,
                                                            @else
                                                                'bg-slate-700 text-gray-200 border border-slate-600 hover:bg-emerald-300 hover:text-black cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                                            @endif
                                                        }"
                                                        class="w-6 h-6 rounded-lg text-[9px] font-bold flex items-center justify-center transition-all"
                                                    >
                                                        <span>{{ $seat->seat_number }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Right Wing Stand (Khán Đài D - Cánh Phải) -->
                        <div 
                            class="lg:col-span-3 bg-gradient-to-l from-[#1E293B] to-black/80 rounded-3xl p-4 border border-blue-500/30 transform lg:rotate-3 transition-all"
                            :class="{
                                'opacity-100': activeZoneFilter === 'all' || activeZoneFilter === 'cat2_wings',
                                'opacity-20 blur-[0.5px]': activeZoneFilter !== 'all' && activeZoneFilter !== 'cat2_wings'
                            }"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-bold text-blue-300">Khán Đài D (Cánh Phải)</span>
                                <span class="text-[9px] text-gray-400 font-mono">{{ number_format($ticketTiers['cat2_wings']['price'] ?? 250000, 0, ',', '.') }}₫</span>
                            </div>

                            <div class="grid grid-cols-4 gap-1.5">
                                @foreach ($standDSeats as $seat)
                                    @php
                                        $isBooked = $seat->status === 'booked';
                                        $isHeld = $seat->status === 'held';
                                        $seatPrice = $seat->price;
                                        $seatPerks = json_encode($seat->perks ?? []);
                                    @endphp
                                    <button
                                        type="button"
                                        @disabled($isBooked || $isHeld)
                                        @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seatPrice }}, {{ $seatPerks }})"
                                        @mouseenter="setHoveredSeat({
                                            code: @js((string) $seat->code),
                                            row: @js((string) $seat->row_label),
                                            number: @js((string) $seat->seat_number),
                                            sector: @js((string) $seat->sector_label),
                                            gate: @js((string) $seat->gate),
                                            type: @js((string) $seat->type_name),
                                            icon: @js((string) $seat->type_icon),
                                            price: {{ $seatPrice }},
                                            status: @js((string) $seat->status),
                                            perks: {{ $seatPerks }}
                                        })"
                                        @mouseleave="clearHoveredSeat()"
                                        :class="{
                                            'bg-gold-antique text-black font-black scale-110 z-10': isSeatSelected({{ $seat->id }}),
                                            @if ($isBooked)
                                                'bg-gray-800 text-gray-600 border-gray-700 cursor-not-allowed opacity-30': true,
                                            @elseif ($isHeld)
                                                'bg-amber-900/60 border-amber-500 text-amber-300 animate-pulse cursor-not-allowed': true,
                                            @else
                                                'bg-slate-800 hover:bg-slate-700 text-white border border-slate-600 hover:border-blue-400 cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                            @endif
                                        }"
                                        class="h-7 rounded-lg text-[10px] font-bold flex items-center justify-center transition-all"
                                    >
                                        <span>D{{ $seat->seat_number }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 4. SKYBOX VIP SUITES (Tầng Thượng Ban Công Kính) -->
                <div 
                    class="w-full max-w-3xl bg-gradient-to-r from-rose-950/60 via-purple-950/40 to-rose-950/60 rounded-3xl p-5 border-2 border-rose-400/50 shadow-[0_0_35px_rgba(244,63,94,0.2)] text-center space-y-3 transition-all"
                    :class="{
                        'opacity-100': activeZoneFilter === 'all' || activeZoneFilter === 'skybox_suite',
                        'opacity-20 blur-[0.5px]': activeZoneFilter !== 'all' && activeZoneFilter !== 'skybox_suite'
                    }"
                >
                    <div class="flex items-center justify-between px-2">
                        <span class="badge-rose text-[9px] px-3 py-0.5 rounded-full font-black uppercase">🥂 Skybox VIP Suites (Tầng Thượng Hoàng Gia)</span>
                        <span class="text-[11px] text-pink-300 font-mono font-bold">{{ number_format($ticketTiers['skybox_suite']['price'] ?? 700000, 0, ',', '.') }}₫ (Bao gồm 2 vé + Buffet)</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-2 pt-1">
                        @foreach ($skyboxSeats as $seat)
                            @php
                                $isBooked = $seat->status === 'booked';
                                $isHeld = $seat->status === 'held';
                                $seatPrice = $seat->price;
                                $seatPerks = json_encode($seat->perks ?? []);
                            @endphp
                            <button
                                type="button"
                                @disabled($isBooked || $isHeld)
                                @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seatPrice }}, {{ $seatPerks }})"
                                @mouseenter="setHoveredSeat({
                                    code: @js((string) $seat->code),
                                    row: @js((string) $seat->row_label),
                                    number: @js((string) $seat->seat_number),
                                    sector: @js((string) $seat->sector_label),
                                    gate: @js((string) $seat->gate),
                                    type: @js((string) $seat->type_name),
                                    icon: @js((string) $seat->type_icon),
                                    price: {{ $seatPrice }},
                                    status: @js((string) $seat->status),
                                    perks: {{ $seatPerks }}
                                    })"
                                @mouseleave="clearHoveredSeat()"
                                :class="{
                                    'bg-gradient-to-r from-pink-500 to-rose-600 text-white ring-4 ring-white font-black scale-105 z-10 shadow-2xl': isSeatSelected({{ $seat->id }}),
                                    @if ($isBooked)
                                        'bg-gray-800 text-gray-600 border-gray-700 cursor-not-allowed opacity-30': true,
                                    @elseif ($isHeld)
                                        'bg-amber-900/60 border-amber-500 text-amber-300 animate-pulse cursor-not-allowed': true,
                                    @else
                                        'bg-gradient-to-r from-pink-900/60 to-purple-900/60 border border-pink-400/60 text-pink-200 hover:border-pink-300 hover:scale-105 cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                    @endif
                                }"
                                class="py-2 px-2 rounded-2xl border text-xs font-black flex items-center justify-center gap-1 transition-all"
                            >
                                <span>🥂</span>
                                <span>SB{{ $seat->seat_number }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                @else
                    @php
                        $roomPreset = $showtime->room->layout_preset ?? 'theater_hall';
                        $stallsRows = $generalSeats->filter(fn ($r, $k) => str_starts_with($k, 'ST'));
                        $circleRows = $generalSeats->filter(fn ($r, $k) => str_starts_with($k, 'DC'));
                        $galleryRows = $generalSeats->filter(fn ($r, $k) => str_starts_with($k, 'GL'));
                        $keynoteRows = $generalSeats->filter(fn ($r, $k) => str_starts_with($k, 'KN'));
                        $standardRows = $generalSeats->filter(fn ($r, $k) => str_starts_with($k, 'STD'));
                        $otherRows = $generalSeats->reject(fn ($r, $k) => 
                            str_starts_with($k, 'ST') || str_starts_with($k, 'DC') || str_starts_with($k, 'GL') ||
                            str_starts_with($k, 'KN') || str_starts_with($k, 'STD')
                        );
                    @endphp

                    @if ($roomPreset === 'theater_hall' && ($stallsRows->isNotEmpty() || $circleRows->isNotEmpty()))
                        <!-- ========================================================================= -->
                        <!-- 2. THEATER & OPERA HOUSE BLUEPRINT (NHÀ HÁT & GIAO HƯỞNG)                -->
                        <!-- ========================================================================= -->
                        <div class="w-full space-y-6">
                            <!-- OPERA PROSCENIUM STAGE -->
                            <div class="w-full max-w-lg mx-auto p-4 bg-gradient-to-r from-red-950 via-rose-950 to-red-950 text-white text-center rounded-2xl shadow-xl border-2 border-gold-antique relative overflow-hidden">
                                <div class="font-display font-black text-xs tracking-widest uppercase text-gold-light">
                                    🎭 SÂN KHẤU NHÀ HÁT &amp; GIAO HƯỞNG (OPERA STAGE) 🎭
                                </div>
                                <span class="text-[10px] text-gray-300 font-mono">DÀN NHẠC GIAO HƯỞNG &amp; HỆ THỐNG ÂM HỌC THÍNH PHÒNG</span>
                            </div>

                            <!-- SECTION 1: TẦNG TRỆT VIP STALLS -->
                            @if ($stallsRows->isNotEmpty())
                                <div class="p-5 bg-gradient-to-b from-[#1E1B18] to-black rounded-3xl border-2 border-gold-antique/60 shadow-lg max-w-4xl mx-auto space-y-3">
                                    <div class="flex items-center justify-between px-2 border-b border-gold-antique/20 pb-2">
                                        <span class="badge-gold text-[9px] px-3 py-0.5 rounded-full font-black uppercase">
                                            🎭 Tầng Trệt VIP Stalls (Trực Diện Sân Khấu)
                                        </span>
                                        <span class="text-[11px] font-bold text-gold-light font-mono">Âm thanh trung thực nhất</span>
                                    </div>
                                    <div class="space-y-2 pt-1">
                                        @foreach ($stallsRows as $rowLabel => $rowSeats)
                                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                                <span class="w-14 text-right pr-2 text-[10px] font-mono text-gold-antique font-bold">{{ $rowLabel }}</span>
                                                @foreach ($rowSeats as $seat)
                                                    @php
                                                        $isBooked = $seat->status === 'booked';
                                                        $isHeld = $seat->status === 'held';
                                                        $hoverData = [
                                                            'code' => $seat->code,
                                                            'row' => $seat->row_label,
                                                            'number' => (string) $seat->seat_number,
                                                            'sector' => $seat->sector_label,
                                                            'gate' => $seat->gate,
                                                            'type' => $seat->type_name,
                                                            'icon' => $seat->type_icon,
                                                            'price' => $seat->price,
                                                            'status' => $seat->status,
                                                            'perks' => $seat->perks,
                                                        ];
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        @disabled($isBooked || $isHeld)
                                                        @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seat->price }}, @js($seat->perks))"
                                                        @mouseenter="setHoveredSeat(@js($hoverData))"
                                                        @mouseleave="clearHoveredSeat()"
                                                        :class="{
                                                            'bg-gold-antique text-black font-black scale-110 z-10': isSeatSelected({{ $seat->id }}),
                                                            @if ($isBooked)
                                                                'bg-gray-800 text-gray-600 border-gray-700 cursor-not-allowed opacity-30': true,
                                                            @elseif ($isHeld)
                                                                'bg-amber-900/60 border-amber-500 text-amber-300 cursor-not-allowed': true,
                                                            @else
                                                                'bg-[#FFF8E1] text-black border border-gold-antique/50 hover:bg-gold hover:scale-110 cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                                            @endif
                                                        }"
                                                        class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[10px] font-black flex items-center justify-center transition-all"
                                                        title="{{ $seat->type_name }} · {{ number_format($seat->price, 0, ',', '.') }}₫"
                                                    >
                                                        <span>{{ $seat->seat_number }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- SECTION 2: TẦNG 1 DRESS CIRCLE -->
                            @if ($circleRows->isNotEmpty())
                                <div class="p-5 bg-gradient-to-b from-[#18181B] to-black rounded-3xl border border-gold-antique/40 shadow-lg max-w-4xl mx-auto space-y-3">
                                    <div class="flex items-center justify-between px-2 border-b border-gold-antique/20 pb-2">
                                        <span class="badge-gold text-[9px] px-3 py-0.5 rounded-full font-bold uppercase">
                                            👑 Tầng 1 Dress Circle (Khán Phòng Trên Cao)
                                        </span>
                                        <span class="text-[11px] text-gray-400 font-mono">Góc nhìn bao quát toàn rạp</span>
                                    </div>
                                    <div class="space-y-2 pt-1">
                                        @foreach ($circleRows as $rowLabel => $rowSeats)
                                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                                <span class="w-14 text-right pr-2 text-[10px] font-mono text-gold-antique font-bold">{{ $rowLabel }}</span>
                                                @foreach ($rowSeats as $seat)
                                                    @php
                                                        $isBooked = $seat->status === 'booked';
                                                        $isHeld = $seat->status === 'held';
                                                        $hoverData = [
                                                            'code' => $seat->code,
                                                            'row' => $seat->row_label,
                                                            'number' => (string) $seat->seat_number,
                                                            'sector' => $seat->sector_label,
                                                            'gate' => $seat->gate,
                                                            'type' => $seat->type_name,
                                                            'icon' => $seat->type_icon,
                                                            'price' => $seat->price,
                                                            'status' => $seat->status,
                                                            'perks' => $seat->perks,
                                                        ];
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        @disabled($isBooked || $isHeld)
                                                        @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seat->price }}, @js($seat->perks))"
                                                        @mouseenter="setHoveredSeat(@js($hoverData))"
                                                        @mouseleave="clearHoveredSeat()"
                                                        :class="{
                                                            'bg-gold-antique text-black font-black scale-110 z-10': isSeatSelected({{ $seat->id }}),
                                                            @if ($isBooked)
                                                                'bg-gray-800 text-gray-600 border-gray-700 cursor-not-allowed opacity-30': true,
                                                            @elseif ($isHeld)
                                                                'bg-amber-900/60 border-amber-500 text-amber-300 cursor-not-allowed': true,
                                                            @else
                                                                'bg-[#FFF8E1] text-black border border-gold-antique/50 hover:bg-gold hover:scale-110 cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                                            @endif
                                                        }"
                                                        class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[10px] font-black flex items-center justify-center transition-all"
                                                        title="{{ $seat->type_name }} · {{ number_format($seat->price, 0, ',', '.') }}₫"
                                                    >
                                                        <span>{{ $seat->seat_number }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- SECTION 3: TẦNG 2 UPPER GALLERY -->
                            @if ($galleryRows->isNotEmpty())
                                <div class="p-5 bg-gradient-to-b from-[#0F172A] to-black rounded-3xl border border-blue-500/30 shadow-md max-w-4xl mx-auto space-y-3">
                                    <div class="flex items-center justify-between px-2 border-b border-blue-500/20 pb-2">
                                        <span class="badge-blue text-[9px] px-3 py-0.5 rounded-full font-bold uppercase bg-blue-900/40 text-blue-300 border border-blue-500/30">
                                            🏛️ Tầng 2 Upper Gallery (Ban Công Khán Phòng)
                                        </span>
                                        <span class="text-[11px] text-gray-400 font-mono">Vé tiêu chuẩn acoustic</span>
                                    </div>
                                    <div class="space-y-2 pt-1">
                                        @foreach ($galleryRows as $rowLabel => $rowSeats)
                                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                                <span class="w-14 text-right pr-2 text-[10px] font-mono text-blue-300 font-bold">{{ $rowLabel }}</span>
                                                @foreach ($rowSeats as $seat)
                                                    @php
                                                        $isBooked = $seat->status === 'booked';
                                                        $isHeld = $seat->status === 'held';
                                                        $hoverData = [
                                                            'code' => $seat->code,
                                                            'row' => $seat->row_label,
                                                            'number' => (string) $seat->seat_number,
                                                            'sector' => $seat->sector_label,
                                                            'gate' => $seat->gate,
                                                            'type' => $seat->type_name,
                                                            'icon' => $seat->type_icon,
                                                            'price' => $seat->price,
                                                            'status' => $seat->status,
                                                            'perks' => $seat->perks,
                                                        ];
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        @disabled($isBooked || $isHeld)
                                                        @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seat->price }}, @js($seat->perks))"
                                                        @mouseenter="setHoveredSeat(@js($hoverData))"
                                                        @mouseleave="clearHoveredSeat()"
                                                        :class="{
                                                            'bg-gold-antique text-black font-black scale-110 z-10': isSeatSelected({{ $seat->id }}),
                                                            @if ($isBooked)
                                                                'bg-gray-800 text-gray-600 border-gray-700 cursor-not-allowed opacity-30': true,
                                                            @elseif ($isHeld)
                                                                'bg-amber-900/60 border-amber-500 text-amber-300 cursor-not-allowed': true,
                                                            @else
                                                                'bg-[#F0F7F2] text-black border border-[#A3C9A8] hover:bg-emerald-200 hover:scale-110 cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                                            @endif
                                                        }"
                                                        class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[10px] font-black flex items-center justify-center transition-all"
                                                        title="{{ $seat->type_name }} · {{ number_format($seat->price, 0, ',', '.') }}₫"
                                                    >
                                                        <span>{{ $seat->seat_number }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                    @elseif ($roomPreset === 'convention_center' && ($keynoteRows->isNotEmpty() || $standardRows->isNotEmpty()))
                        <!-- ========================================================================= -->
                        <!-- 3. CONVENTION CENTER BLUEPRINT (TRUNG TÂM HỘI NGHỊ & TRIỂN LÃM)          -->
                        <!-- ========================================================================= -->
                        <div class="w-full space-y-6">
                            <!-- KEYNOTE STAGE -->
                            <div class="w-full max-w-lg mx-auto p-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white text-center rounded-2xl shadow-xl border-2 border-cyan-400/60 relative overflow-hidden">
                                <div class="font-display font-black text-xs tracking-widest uppercase text-cyan-300">
                                    🏢 BỤC DIỄN GIẢ KEYNOTE &amp; MÀN HÌNH LED 8K 🏢
                                </div>
                                <span class="text-[10px] text-gray-400 font-mono">KHÔNG GIAN HỘI NGHỊ DOANH NGHIỆP QUỐC TẾ</span>
                            </div>

                            <!-- KEYNOTE VIP SECTION -->
                            @if ($keynoteRows->isNotEmpty())
                                <div class="p-5 bg-gradient-to-b from-[#1E293B] to-black rounded-3xl border-2 border-gold-antique/60 shadow-lg max-w-4xl mx-auto space-y-3">
                                    <div class="flex items-center justify-between px-2 border-b border-gold-antique/20 pb-2">
                                        <span class="badge-gold text-[9px] px-3 py-0.5 rounded-full font-black uppercase">
                                            👑 Khu Vực Keynote VIP (Doanh Nhân &amp; Diễn Giả)
                                        </span>
                                        <span class="text-[11px] font-bold text-gold-light font-mono">Bàn đại biểu hàng đầu</span>
                                    </div>
                                    <div class="space-y-2 pt-1">
                                        @foreach ($keynoteRows as $rowLabel => $rowSeats)
                                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                                <span class="w-14 text-right pr-2 text-[10px] font-mono text-gold-antique font-bold">{{ $rowLabel }}</span>
                                                @foreach ($rowSeats as $seat)
                                                    @php
                                                        $isBooked = $seat->status === 'booked';
                                                        $isHeld = $seat->status === 'held';
                                                        $hoverData = [
                                                            'code' => $seat->code,
                                                            'row' => $seat->row_label,
                                                            'number' => (string) $seat->seat_number,
                                                            'sector' => $seat->sector_label,
                                                            'gate' => $seat->gate,
                                                            'type' => $seat->type_name,
                                                            'icon' => $seat->type_icon,
                                                            'price' => $seat->price,
                                                            'status' => $seat->status,
                                                            'perks' => $seat->perks,
                                                        ];
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        @disabled($isBooked || $isHeld)
                                                        @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seat->price }}, @js($seat->perks))"
                                                        @mouseenter="setHoveredSeat(@js($hoverData))"
                                                        @mouseleave="clearHoveredSeat()"
                                                        :class="{
                                                            'bg-gold-antique text-black font-black scale-110 z-10': isSeatSelected({{ $seat->id }}),
                                                            @if ($isBooked)
                                                                'bg-gray-800 text-gray-600 border-gray-700 cursor-not-allowed opacity-30': true,
                                                            @elseif ($isHeld)
                                                                'bg-amber-900/60 border-amber-500 text-amber-300 cursor-not-allowed': true,
                                                            @else
                                                                'bg-[#FFF8E1] text-black border border-gold-antique/50 hover:bg-gold hover:scale-110 cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                                            @endif
                                                        }"
                                                        class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[10px] font-black flex items-center justify-center transition-all"
                                                        title="{{ $seat->type_name }} · {{ number_format($seat->price, 0, ',', '.') }}₫"
                                                    >
                                                        {{ $seat->seat_number }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- STANDARD CONFERENCE SECTION -->
                            @if ($standardRows->isNotEmpty())
                                <div class="p-5 bg-gradient-to-b from-[#0F172A] to-black rounded-3xl border border-cyan-500/30 shadow-md max-w-4xl mx-auto space-y-3">
                                    <div class="flex items-center justify-between px-2 border-b border-cyan-500/20 pb-2">
                                        <span class="badge-slate text-[9px] px-3 py-0.5 rounded-full font-black uppercase bg-cyan-900/30 text-cyan-300 border border-cyan-400/30">
                                            🏢 Khu Vực Hội Nghị Tiêu Chuẩn (Standard Expo)
                                        </span>
                                        <span class="text-[11px] font-bold text-gray-400 font-mono">Dãy ghế đại biểu &amp; khách mời</span>
                                    </div>
                                    <div class="space-y-2 pt-1">
                                        @foreach ($standardRows as $rowLabel => $rowSeats)
                                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                                <span class="w-14 text-right pr-2 text-[10px] font-mono text-cyan-300 font-bold">{{ $rowLabel }}</span>
                                                @foreach ($rowSeats as $seat)
                                                    @php
                                                        $isBooked = $seat->status === 'booked';
                                                        $isHeld = $seat->status === 'held';
                                                        $hoverData = [
                                                            'code' => $seat->code,
                                                            'row' => $seat->row_label,
                                                            'number' => (string) $seat->seat_number,
                                                            'sector' => $seat->sector_label,
                                                            'gate' => $seat->gate,
                                                            'type' => $seat->type_name,
                                                            'icon' => $seat->type_icon,
                                                            'price' => $seat->price,
                                                            'status' => $seat->status,
                                                            'perks' => $seat->perks,
                                                        ];
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        @disabled($isBooked || $isHeld)
                                                        @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seat->price }}, @js($seat->perks))"
                                                        @mouseenter="setHoveredSeat(@js($hoverData))"
                                                        @mouseleave="clearHoveredSeat()"
                                                        :class="{
                                                            'bg-gold-antique text-black font-black scale-110 z-10': isSeatSelected({{ $seat->id }}),
                                                            @if ($isBooked)
                                                                'bg-gray-800 text-gray-600 border-gray-700 cursor-not-allowed opacity-30': true,
                                                            @elseif ($isHeld)
                                                                'bg-amber-900/60 border-amber-500 text-amber-300 cursor-not-allowed': true,
                                                            @else
                                                                'bg-[#F0F7F2] text-black border border-[#A3C9A8] hover:bg-emerald-200 hover:scale-110 cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                                            @endif
                                                        }"
                                                        class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[10px] font-black flex items-center justify-center transition-all"
                                                        title="{{ $seat->type_name }} · {{ number_format($seat->price, 0, ',', '.') }}₫"
                                                    >
                                                        {{ $seat->seat_number }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                    @else
                        <!-- ========================================================================= -->
                        <!-- 4. CUSTOM GRID & GENERAL SEATING MATRIX                                  -->
                        <!-- ========================================================================= -->
                        <div class="w-full space-y-6">
                            <div class="mx-auto w-2/3 py-3 rounded-b-[2rem] bg-gradient-to-b from-gold-antique/40 to-transparent border-t-4 border-gold-antique text-center text-[11px] font-black tracking-[0.3em] text-gold-light">
                                SÂN KHẤU
                            </div>

                            <div class="space-y-2 max-w-4xl mx-auto">
                                @foreach ($generalSeats as $rowLabel => $rowSeats)
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        <span class="w-12 text-right pr-2 text-[10px] font-mono text-gray-400 font-bold">{{ $rowLabel }}</span>
                                        @foreach ($rowSeats as $seat)
                                            @php
                                                $isBooked = $seat->status === 'booked';
                                                $isHeld = $seat->status === 'held';
                                                $hoverData = [
                                                    'code' => $seat->code,
                                                    'row' => $seat->row_label,
                                                    'number' => (string) $seat->seat_number,
                                                    'sector' => $seat->sector_label,
                                                    'gate' => $seat->gate,
                                                    'type' => $seat->type_name,
                                                    'icon' => $seat->type_icon,
                                                    'price' => $seat->price,
                                                    'status' => $seat->status,
                                                    'perks' => $seat->perks,
                                                ];
                                            @endphp
                                            <button
                                                type="button"
                                                @disabled($isBooked || $isHeld)
                                                @click="toggleSeat({{ $seat->id }}, @js((string) $seat->seat_number), @js((string) $seat->row_label), @js((string) $seat->type), @js((string) $seat->type_name), @js((string) $seat->status), {{ $seat->price }}, @js($seat->perks))"
                                                @mouseenter="setHoveredSeat(@js($hoverData))"
                                                @mouseleave="clearHoveredSeat()"
                                                :class="{
                                                    'bg-gold-antique text-black font-black scale-110 z-10': isSeatSelected({{ $seat->id }}),
                                                    @if ($isBooked)
                                                        'bg-gray-800 text-gray-600 border-gray-700 cursor-not-allowed opacity-30': true,
                                                    @elseif ($isHeld)
                                                        'bg-amber-900/60 border-amber-500 text-amber-300 cursor-not-allowed': true,
                                                    @else
                                                        'bg-[#F0F7F2] text-black border border-[#A3C9A8] hover:bg-emerald-200 hover:scale-110 cursor-pointer': !isSeatSelected({{ $seat->id }}),
                                                    @endif
                                                }"
                                                class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg text-[10px] font-black flex items-center justify-center transition-all"
                                                title="{{ $seat->type_name }} · {{ number_format($seat->price, 0, ',', '.') }}₫"
                                            >
                                                {{ $seat->seat_number }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>
            </div>

            <!-- DEDICATED FIXED-HEIGHT SEAT & SECTOR DETAIL INSPECTOR STATION -->
            <div class="w-full max-w-2xl mx-auto mt-8 pt-4 border-t border-white/10 relative z-30 pointer-events-none">
                <div class="bg-black/85 backdrop-blur-md rounded-2xl border border-white/15 p-3.5 sm:p-4 text-xs shadow-2xl min-h-[92px] sm:min-h-[82px] flex items-center transition-all duration-150">
                    
                    <!-- 1. Idle Placeholder -->
                    <div 
                        x-show="hoveredSeat === null"
                        x-transition:enter="transition-opacity duration-150"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="flex items-center justify-between w-full text-gray-400 gap-3 select-none"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gold-antique shrink-0">
                                <svg class="w-5 h-5 animate-pulse text-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                </svg>
                            </div>
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black text-white uppercase tracking-wider">Thông Tin Hàng Ghế &amp; Hạng Vé</span>
                                    <span class="text-[9px] text-gold-light font-bold bg-gold-dark/20 px-2 py-0.5 rounded-full border border-gold-antique/30">Live Inspector</span>
                                </div>
                                <p class="text-[11px] text-gray-400 leading-tight">
                                    Rê chuột hoặc chạm vào ghế trên sơ đồ để xem vị trí hàng, cổng soát vé và giá vé chính thức.
                                </p>
                            </div>
                        </div>
                        <div class="hidden md:flex flex-col items-end text-right shrink-0">
                            <span class="text-[9px] text-gray-500 font-mono uppercase">Trạng Thái Sơ Đồ</span>
                            <span class="text-[11px] font-bold text-emerald-400 flex items-center gap-1.5 mt-0.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                Sẵn sàng chọn ghế
                            </span>
                        </div>
                    </div>

                    <!-- 2. Active Hover State -->
                    <div 
                        x-show="hoveredSeat !== null"
                        x-transition:enter="transition-opacity duration-150"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 w-full"
                        style="display: none;"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-gold-dark to-yellow-300 text-black flex items-center justify-center font-display font-black text-base shadow-md shrink-0">
                                <span x-text="hoveredSeat?.code">A1</span>
                            </div>
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <h5 class="font-display font-black text-white text-sm" x-text="hoveredSeat?.type">SVIP Sân Khấu</h5>
                                    <span class="badge-gold text-[9px] px-2 py-0.2 rounded-full font-bold uppercase" x-text="hoveredSeat?.gate">Cổng VIP 1</span>
                                    <template x-if="hoveredSeat?.status === 'booked'">
                                        <span class="bg-red-900/80 text-red-200 border border-red-500/50 text-[9px] px-2 py-0.5 rounded-full font-bold">Đã bán</span>
                                    </template>
                                    <template x-if="hoveredSeat?.status === 'held'">
                                        <span class="bg-amber-900/80 text-amber-200 border border-amber-500/50 text-[9px] px-2 py-0.5 rounded-full font-bold">Đang giữ chỗ</span>
                                    </template>
                                </div>
                                <p class="text-[11px] text-gray-300">
                                    Khu vực: <strong class="text-gold-light" x-text="hoveredSeat?.sector">Sân Khấu</strong> · Hàng: <strong class="text-white" x-text="hoveredSeat?.row">A</strong> · Ghế số: <strong class="text-white" x-text="hoveredSeat?.number">1</strong>
                                </p>
                                <template x-if="hoveredSeat?.perks && hoveredSeat?.perks.length > 0">
                                    <div class="text-[10px] text-emerald-400 font-semibold flex items-center gap-1">
                                        <span>✓</span>
                                        <span x-text="hoveredSeat?.perks.join(' · ')"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="text-left sm:text-right shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-white/10 w-full sm:w-auto">
                            <span class="text-[9px] text-gray-400 block uppercase font-bold tracking-wider">Giá vé chính thức</span>
                            <span class="font-display font-black text-base sm:text-lg text-gold-antique" x-text="formatCurrency(hoveredSeat?.price || 0)">0₫</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- STADIUM CONCERT TICKET TIERS & GATE INFO CARDS -->
        <div class="bg-white rounded-3xl p-6 border border-black/10 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-black/10 pb-3">
                <div>
                    <h4 class="font-display font-black text-sm text-black">Bảng Phân Hạng Vé &amp; Cổng Soát Vé Sân Vận Động</h4>
                    <p class="text-xs text-gray-500">Xem thông tin cổng đón khách và quyền lợi từng khu vực</p>
                </div>
                <span class="text-xs text-gray-500 font-semibold">
                    Còn trống <strong class="text-sage-forest font-black">{{ $totalAvailable }}</strong> chỗ ngồi
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 text-xs">
                @foreach ($ticketTiers as $key => $tier)
                    <div class="p-3.5 rounded-2xl bg-[#FAF9F6] border border-black/10 space-y-2 hover:border-gold-antique transition-all">
                        <div class="flex items-center justify-between">
                            <span class="{{ $tier['badge'] }} text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase flex items-center gap-1">
                                <span>{{ $tier['icon'] }}</span>
                                <span>{{ $tier['name'] }}</span>
                            </span>
                            <span class="font-display font-black text-sm text-black">
                                {{ number_format($tier['price'], 0, ',', '.') }}₫
                            </span>
                        </div>

                        <div class="text-[11px] text-gray-600 leading-tight">
                            {{ $tier['description'] }}
                        </div>

                        <div class="flex items-center justify-between pt-1 border-t border-black/5 text-[10px] text-gray-500">
                            <span>Lối vào: <strong class="text-black">{{ $tier['gate'] }}</strong></span>
                            <span class="text-emerald-700 font-bold">Mã QR Vé Điện Tử</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    @else
        <!-- ========================================================================= -->
        <!-- 2. NON-CONCERT EVENT DIRECT TICKET TIER & QUANTITY SELECTOR               -->
        <!-- ========================================================================= -->

        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-black/10 shadow-lg space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-black/10 pb-4">
                <div>
                    <span class="badge-gold text-[10px] px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider">
                        Đặt Vé Trực Tuyến Nhanh Chóng
                    </span>
                    <h3 class="font-display font-black text-2xl text-black mt-1">
                        Chọn Hạng Vé &amp; Số Lượng Tham Dự
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Sự kiện không áp dụng chọn số ghế cụ thể · Bạn sẽ nhận mã vé QR điện tử để check-in trực tiếp tại quầy tiếp đón.
                    </p>
                </div>

                <div class="flex items-center gap-2 text-xs text-sage-forest font-bold bg-[#EAF3EC] px-3.5 py-1.5 rounded-full border border-[#A3C9A8] shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>Vé E-Ticket Kích Hoạt Tức Thì</span>
                </div>
            </div>

            <!-- Ticket Tier Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($ticketTiers as $key => $tier)
                    <div class="bg-[#FAF9F6] rounded-3xl p-6 border-2 border-black/10 hover:border-gold-antique hover:shadow-xl transition-all flex flex-col justify-between space-y-5 relative overflow-hidden group">
                        
                        <!-- Top header -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="{{ $tier['badge'] }} text-xs px-3 py-1 rounded-full font-black uppercase flex items-center gap-1.5 shadow-sm">
                                    <span>{{ $tier['icon'] }}</span>
                                    <span>{{ $tier['name'] }}</span>
                                </span>
                                <span class="text-[10px] font-mono text-gray-500 font-bold uppercase">{{ $tier['gate'] ?? 'Cổng Chính' }}</span>
                            </div>

                            <div class="pt-2">
                                <span class="font-display font-black text-2xl sm:text-3xl text-black block">
                                    {{ number_format($tier['price'], 0, ',', '.') }}₫
                                </span>
                                <span class="text-[11px] text-gray-500 font-medium">/ 1 vé tham dự (đã gồm VAT)</span>
                            </div>

                            <p class="text-xs text-gray-600 leading-relaxed pt-1">
                                {{ $tier['description'] }}
                            </p>

                            <!-- Perks list -->
                            @if (!empty($tier['perks']))
                                <div class="pt-3 border-t border-black/10 space-y-1.5 text-xs text-gray-700">
                                    @foreach ($tier['perks'] as $perk)
                                        <div class="flex items-start gap-2">
                                            <span class="text-sage-forest font-bold shrink-0">✓</span>
                                            <span class="leading-tight">{{ $perk }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Bottom Quantity Stepper -->
                        <div class="pt-4 border-t border-black/10 flex items-center justify-between">
                            <div>
                                <span class="text-[11px] text-gray-500 font-bold block">Số lượng</span>
                                <span class="text-xs font-black text-black" x-text="getTierCount(@js((string) $key)) > 0 ? (getTierCount(@js((string) $key)) * {{ $tier['price'] }}).toLocaleString('vi-VN') + '₫' : 'Chưa chọn'"></span>
                            </div>

                            <div class="flex items-center gap-2 bg-white rounded-2xl p-1 border border-black/15 shadow-sm">
                                <button 
                                    type="button" 
                                    @click="decrementTier(@js((string) $key))" 
                                    :disabled="getTierCount(@js((string) $key)) <= 0"
                                    class="w-8 h-8 rounded-xl bg-[#FAF9F6] hover:bg-rose-50 text-gray-700 hover:text-rose-taupe flex items-center justify-center font-black text-sm disabled:opacity-30 disabled:pointer-events-none transition-colors"
                                >
                                    &minus;
                                </button>
                                
                                <span class="w-8 text-center font-mono font-black text-sm text-black" x-text="getTierCount(@js((string) $key))">0</span>
                                
                                <button 
                                    type="button" 
                                    @click="incrementTier(@js((string) $key), {{ $tier['price'] }}, @js((string) $tier['name']))" 
                                    class="w-8 h-8 rounded-xl btn-rose flex items-center justify-center font-black text-sm shadow-sm transition-transform active:scale-95"
                                >
                                    &#43;
                                </button>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            <!-- Terms and Event Policy Quick Note -->
            <div class="bg-[#FAF9F6] rounded-2xl p-4 border border-black/10 flex items-center justify-between text-xs text-gray-600">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>Mỗi đơn hàng được mua tối đa <strong>8 vé</strong>. Vé sẽ được gửi trực tiếp vào email và tài khoản của bạn.</span>
                </span>
                <span class="text-rose-taupe font-bold">Hỗ trợ 24/7</span>
            </div>
        </div>
    @endif

    <!-- STICKY LIVE BOOKING DRAWER (Dùng chung cho cả 2 chế độ) -->
    <div class="sticky bottom-6 z-40 bg-white/95 backdrop-blur-xl rounded-3xl p-6 sm:p-7 border border-black/15 shadow-[0_20px_50px_-10px_rgba(0,0,0,0.25)] flex flex-col lg:flex-row items-center justify-between gap-6">
        
        <!-- Left: Selected tickets list -->
        <div class="flex-1 w-full space-y-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs font-black text-rose-taupe uppercase tracking-wider">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-taupe animate-ping"></span>
                    <span>Vé Đang Chọn (<span x-text="totalTicketCount">0</span>/8 vé)</span>
                </div>

                <span class="text-xs text-gray-500 font-medium hidden sm:inline">
                    Giữ chỗ an toàn trong 10 phút sau khi xác nhận
                </span>
            </div>

            <div class="flex flex-wrap gap-2 items-center min-h-[36px]">
                <template x-if="totalTicketCount === 0">
                    <div class="flex items-center gap-2 text-xs sm:text-sm text-gray-400 italic">
                        <svg class="w-4 h-4 text-gold-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" /></svg>
                        @if ($isSeatedConcert)
                            Click trực tiếp vào các khu vực trên sơ đồ sân vận động để chọn vị trí mong muốn.
                        @else
                            Chọn số lượng vé của hạng vé mong muốn ở bảng phía trên.
                        @endif
                    </div>
                </template>

                <!-- Standing ticket pill (Concert) -->
                <template x-if="standingCount > 0">
                    <span class="badge-gold px-3.5 py-1.5 rounded-full text-xs font-black flex items-center gap-2 shadow-sm animate-fade-in">
                        <span>🔥 GA Standing Fanzone x<span x-text="standingCount"></span></span>
                        <span class="text-[10px] text-gray-700" x-text="formatCurrency(totalStandingPrice)"></span>
                        <button type="button" @click="standingCount = 0" class="hover:text-black text-sm font-black ml-1">&times;</button>
                    </span>
                </template>

                <!-- Direct Tier ticket pills (Non-Concert) -->
                <template x-for="(count, key) in tierQuantities" :key="key">
                    <template x-if="count > 0">
                        <span class="badge-sage px-3.5 py-1.5 rounded-full text-xs font-black flex items-center gap-2 shadow-sm animate-fade-in">
                            <span x-text="(tierMeta[key]?.name || key) + ' x' + count"></span>
                            <span class="text-[10px] text-gray-700" x-text="formatCurrency(count * (tierMeta[key]?.price || basePrice))"></span>
                            <button type="button" @click="tierQuantities[key] = 0" class="hover:text-black text-sm font-black ml-1">&times;</button>
                        </span>
                    </template>
                </template>

                <!-- Individual seat pills (Concert) -->
                <template x-for="seat in selectedSeats" :key="seat.id">
                    <span 
                        class="px-3.5 py-1.5 rounded-full text-xs font-black flex items-center gap-2 shadow-sm transition-all animate-fade-in"
                        :class="seat.type.includes('svip') || seat.type.includes('vip') ? 'badge-gold' : (seat.type.includes('skybox') ? 'badge-rose' : (seat.type.includes('cat1') ? 'badge-sage' : 'badge-beige'))"
                    >
                        <span x-text="seat.row + seat.number"></span>
                        <span class="text-[10px] opacity-80" x-text="'(' + seat.typeName + ')'"></span>
                        <span class="text-[10px] font-mono" x-text="formatCurrency(seat.price)"></span>
                        <button 
                            type="button" 
                            @click="removeSeat(seat.id)" 
                            class="hover:scale-125 transition-transform ml-0.5 text-sm font-black leading-none"
                            title="Xóa ghế này"
                        >
                            &times;
                        </button>
                    </span>
                </template>
            </div>
        </div>

        <!-- Right: Subtotal & Action -->
        <div class="flex items-center justify-between lg:justify-end gap-6 w-full lg:w-auto shrink-0 pt-4 lg:pt-0 border-t lg:border-t-0 border-black/10">
            <div class="text-left lg:text-right">
                <div class="flex items-center gap-1.5 text-xs text-gray-500 font-bold">
                    <span>Tổng Tạm Tính</span>
                    <span class="badge-gold text-[9px] px-1.5 py-0.2 rounded-full font-black">VAT ĐÃ GỒM</span>
                </div>
                <span class="font-display font-black text-2xl sm:text-3xl text-black" x-text="formattedTotalPrice">0 ₫</span>
            </div>

            <button
                type="button"
                @click="confirmHold()"
                :disabled="totalTicketCount === 0 || isSubmitting"
                class="btn-rose px-8 py-3.5 rounded-full font-black text-sm flex items-center gap-2.5 shadow-lg hover:shadow-xl transition-all"
                :class="{ 'opacity-50 pointer-events-none grayscale': totalTicketCount === 0 || isSubmitting }"
            >
                <span x-text="isSubmitting ? 'Đang giữ chỗ...' : 'Xác Nhận Giữ Chỗ'">Xác Nhận Giữ Chỗ</span>
                <span x-show="totalTicketCount > 0 && !isSubmitting" x-text="'(' + totalTicketCount + ' vé)'"></span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
            </button>
        </div>
    </div>

    <!-- Notification Toast -->
    <div 
        x-show="showToast" 
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 scale-95"
        {{-- Đặt phía trên nút chatbot (fixed bottom-right) để không bị che. --}}
        class="fixed bottom-28 right-4 sm:right-6 z-50 bg-black text-white border border-white/20 px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 max-w-[calc(100vw-2rem)] sm:max-w-sm"
        role="status"
        aria-live="polite"
        style="display: none;"
    >
        <div 
            class="w-3 h-3 rounded-full shrink-0"
            :class="{
                'bg-emerald-400': notificationType === 'success',
                'bg-amber-400': notificationType === 'warning',
                'bg-red-400': notificationType === 'error',
                'bg-rose-taupe': notificationType === 'info'
            }"
        ></div>
        <span class="text-xs sm:text-sm font-bold leading-tight" x-text="notificationMessage"></span>
    </div>
</div>
