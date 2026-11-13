<!-- Floating AI Chatbot Assistant (Frontend Interface) -->
@php
    $featuredEvent = \App\Models\Event::where('status', 'published')
        ->with(['showtimes.room'])
        ->withMin('showtimes', 'base_price')
        ->latest('id')
        ->first();
    $featuredMinPrice = $featuredEvent ? ($featuredEvent->base_price ?? 0) : 0;
@endphp
<div 
    x-data="ticketBotAssistant({{ json_encode($featuredEvent ? [
        'title' => $featuredEvent->title,
        'date' => $featuredEvent->showtimes->first() ? $featuredEvent->showtimes->first()->start_time->format('H:i - d/m/Y') : 'Đang cập nhật lịch diễn',
        'venue' => $featuredEvent->showtimes->first()?->room?->name ?? 'Trung tâm tổ chức sự kiện',
        'price' => number_format($featuredMinPrice, 0, ',', '.') . '₫',
        'poster' => $featuredEvent->poster_url,
        'link' => route('events.show', $featuredEvent->slug),
    ] : null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }})" 
    x-init="initBot()"
    id="ai-chatbot-widget"
    data-component="ai-chatbot"
    class="fixed bottom-5 right-5 sm:bottom-6 sm:right-6 z-50 font-sans"
>
    <!-- Welcome Greeting Speech Bubble (Auto-shows on first visit) -->
    <div 
        x-show="showGreeting && !isOpen"
        @click.outside="showGreeting = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-95"
        class="absolute bottom-20 sm:bottom-22 right-0 w-[340px] sm:w-[380px] max-w-[calc(100vw-32px)] bg-white/98 backdrop-blur-2xl rounded-2xl p-3.5 sm:p-4 border border-stone-200/90 shadow-[0_15px_45px_-10px_rgba(0,0,0,0.18)] mb-2.5 pointer-events-auto ring-1 ring-black/5"
        style="display: none;"
    >
        <!-- Triangle Pointer nicely aligned with the orb -->
        <div class="absolute -bottom-2 right-6 sm:right-7 w-4 h-4 bg-white border-b border-r border-stone-200 rotate-45"></div>

        <div class="flex items-start gap-3 relative z-10">
            <!-- Bot Avatar with Elegant Luxury Framing -->
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-500 via-amber-400 to-rose-600 p-0.5 shadow-sm shrink-0 flex items-center justify-center">
                <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <!-- Futuristic Cute Robot Face -->
                        <rect x="3" y="6" width="18" height="13" rx="4" stroke-width="2" stroke="currentColor" fill="#14161D" />
                        <circle cx="8.5" cy="11.5" r="1.75" fill="#FBBF24" />
                        <circle cx="15.5" cy="11.5" r="1.75" fill="#FBBF24" />
                        <path d="M9 15.5c1 .8 2 1 3 1s2-.2 3-1" stroke-width="1.75" stroke-linecap="round" stroke="#FBBF24" />
                        <path d="M12 2v4" stroke-width="2" stroke-linecap="round" stroke="currentColor" />
                        <circle cx="12" cy="2" r="1" fill="#C08497" />
                    </svg>
                </div>
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-black text-slate-900 flex items-center gap-1.5">
                        TicketBot AI ✨
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    </span>
                    <button 
                        type="button" 
                        @click="showGreeting = false" 
                        class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                        title="Đóng lời chào"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed font-normal">
                    👋 Xin chào! Tôi có thể giúp bạn tìm vé concert hot, hướng dẫn giữ chỗ hoặc giải đáp thanh toán nhé!
                </p>
                <button 
                    type="button" 
                    @click="openChat()" 
                    class="mt-2 inline-flex items-center gap-1.5 text-xs font-black text-[#C08497] hover:text-[#9A5369] transition-colors cursor-pointer group"
                >
                    <span>Mở trợ lý ảo trò chuyện</span>
                    <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Premium Floating Logo Trigger Button -->
    <button 
        type="button" 
        @click="toggleChat()"
        class="relative group focus:outline-none cursor-pointer select-none"
        aria-label="Mở trợ lý ảo AI"
    >
        <!-- Soft Ambient Drop Shadow Ring -->
        <span class="absolute inset-0 rounded-full bg-gradient-to-tr from-rose-500/30 via-amber-400/30 to-rose-600/30 blur-lg group-hover:blur-xl transition-all duration-300"></span>

        <!-- Floating Orb Body with Distinct Double-Border Aesthetics -->
        <div class="relative w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-b from-slate-900 via-slate-950 to-slate-900 p-0.5 shadow-[0_12px_30px_-5px_rgba(0,0,0,0.35)] border border-amber-400/40 group-hover:border-amber-300/80 ring-4 ring-black/5 group-hover:ring-amber-400/20 group-hover:scale-105 active:scale-95 transition-all duration-300 flex items-center justify-center">
            
            <!-- Icon When Closed: High-Tech Cute Robot Mascot -->
            <div x-show="!isOpen" class="flex flex-col items-center justify-center relative">
                <!-- Mascot Face -->
                <div class="relative">
                    <svg class="w-7 h-7 sm:w-7.5 sm:h-7.5 text-amber-300 drop-shadow-md group-hover:rotate-6 transition-transform duration-300" viewBox="0 0 24 24" fill="none">
                        <!-- Headphone / Ears -->
                        <rect x="2" y="8.5" width="2" height="6" rx="1" fill="#C08497" />
                        <rect x="20" y="8.5" width="2" height="6" rx="1" fill="#C08497" />
                        <path d="M3 11C3 6.02944 7.02944 2 12 2C16.9706 2 21 6.02944 21 11" stroke="#C08497" stroke-width="1.75" stroke-linecap="round" />
                        
                        <!-- Face Screen -->
                        <rect x="4" y="6" width="16" height="13" rx="4" fill="#0B0C10" stroke="#E5C768" stroke-width="1.75" />
                        
                        <!-- Glowing LED Eyes -->
                        <circle cx="9" cy="11.5" r="2" fill="#FBBF24" />
                        <circle cx="15" cy="11.5" r="2" fill="#FBBF24" />
                        <circle cx="9.6" cy="11" r="0.6" fill="#FFF" />
                        <circle cx="15.6" cy="11" r="0.6" fill="#FFF" />

                        <!-- Smile Mouth -->
                        <path d="M9.5 15.5C10.5 16.3 13.5 16.3 14.5 15.5" stroke="#FBBF24" stroke-width="1.5" stroke-linecap="round" />

                        <!-- Antenna Sparkle -->
                        <circle cx="12" cy="2" r="1.5" fill="#E5C768" />
                    </svg>

                    <!-- Star Sparkle Accent -->
                    <span class="absolute -top-1.5 -right-1.5 text-[10px] animate-spin text-amber-300" style="animation-duration: 6s;">✦</span>
                </div>
                
                <!-- Tiny Label Badge -->
                <span class="text-[8px] font-black uppercase tracking-wider text-amber-200/90 font-mono -mt-0.5">AI BOT</span>
            </div>

            <!-- Icon When Opened: Sleek Close Cross -->
            <div x-show="isOpen" style="display: none;">
                <svg class="w-6 h-6 text-white group-hover:rotate-90 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>

            <!-- Crisp Green Online Status Dot with White Isolation Ring -->
            <span class="absolute top-0 right-0 flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-white shadow-xs"></span>
            </span>
        </div>
    </button>

    <!-- Main Chat Window Drawer / Modal -->
    <div 
        x-show="isOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 scale-95"
        class="fixed bottom-20 sm:bottom-24 right-4 sm:right-6 w-[370px] sm:w-[420px] max-w-[calc(100vw-32px)] h-[580px] max-h-[calc(100vh-120px)] bg-white/95 backdrop-blur-2xl rounded-3xl border border-slate-200/90 shadow-[0_25px_70px_-15px_rgba(0,0,0,0.25)] flex flex-col overflow-hidden z-50"
        style="display: none;"
    >
        <!-- Top Ambient Gradient Accent Line -->
        <div class="h-1.5 bg-gradient-to-r from-rose-500 via-amber-400 to-rose-600 shrink-0"></div>

        <!-- Chat Header -->
        <div class="p-4 bg-white/90 border-b border-slate-100 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <!-- Avatar in Header -->
                <div class="relative">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#C08497] via-[#AA8820] to-[#3A5A40] p-0.5 shadow-md flex items-center justify-center">
                        <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-300" viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="6" width="18" height="13" rx="4" fill="#0B0C10" stroke="#E5C768" stroke-width="1.75" />
                                <circle cx="8.5" cy="11.5" r="1.75" fill="#FBBF24" />
                                <circle cx="15.5" cy="11.5" r="1.75" fill="#FBBF24" />
                                <path d="M9 15.5C10 16.3 13 16.3 14 15.5" stroke="#FBBF24" stroke-width="1.5" stroke-linecap="round" />
                                <path d="M12 2v4" stroke-width="1.75" stroke="#C08497" stroke-linecap="round" />
                                <circle cx="12" cy="2" r="1" fill="#FBBF24" />
                            </svg>
                        </div>
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white"></span>
                </div>

                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-display font-black text-slate-900 text-sm tracking-tight">TicketBot AI</h3>
                        <span class="badge-rose text-[9px] px-2 py-0.5 rounded-full font-black uppercase">Trợ Lý 24/7</span>
                    </div>
                    <p class="text-[11px] text-emerald-700 font-medium flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Đang trực tuyến · Sẵn sàng hỗ trợ
                    </p>
                </div>
            </div>

            <!-- Window Action Controls -->
            <div class="flex items-center gap-1">
                <!-- Clear / Reset Chat -->
                <button 
                    type="button" 
                    @click="resetChat()"
                    title="Làm mới hội thoại"
                    class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>

                <!-- Close / Minimize -->
                <button 
                    type="button" 
                    @click="isOpen = false"
                    title="Thu nhỏ"
                    class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Chat Messages Container -->
        <div 
            id="chat-messages-container"
            class="flex-1 p-4 overflow-y-auto space-y-4 bg-slate-50/70 scroll-smooth text-xs"
        >
            <!-- Date/Security Header Pill -->
            <div class="text-center my-1">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-200/80 text-[10px] font-bold text-slate-600">
                    <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Hôm nay · Hội thoại mã hóa bảo mật
                </span>
            </div>

            <!-- Message List -->
            <template x-for="(msg, index) in messages" :key="index">
                <div>
                    <!-- Bot Message Bubble -->
                    <template x-if="msg.sender === 'bot'">
                        <div class="flex items-start gap-2.5 max-w-[90%]">
                            <!-- Bot Avatar in Message -->
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-[#C08497] via-[#AA8820] to-[#3A5A40] p-0.5 shadow-xs shrink-0 mt-0.5">
                                <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                                    <svg class="w-4 h-4 text-amber-300" viewBox="0 0 24 24" fill="none">
                                        <rect x="3" y="6" width="18" height="13" rx="4" fill="#0B0C10" stroke="#E5C768" stroke-width="1.5" />
                                        <circle cx="8.5" cy="11.5" r="1.5" fill="#FBBF24" />
                                        <circle cx="15.5" cy="11.5" r="1.5" fill="#FBBF24" />
                                        <path d="M9 15.5C10 16.3 13 16.3 14 15.5" stroke="#FBBF24" stroke-width="1.25" stroke-linecap="round" />
                                    </svg>
                                </div>
                            </div>

                            <div class="space-y-1.5 flex-1 min-w-0">
                                <div class="bg-white rounded-2xl rounded-tl-sm p-4 border border-slate-200/90 shadow-xs text-slate-800 leading-relaxed text-[13px] space-y-3">
                                    <div class="space-y-2 prose-sm font-normal text-slate-800" x-html="msg.text"></div>

                                    <!-- Mini Event Recommendation Card inside Bot Bubble -->
                                    <template x-if="msg.event">
                                        <div class="mt-3 p-3 rounded-2xl bg-gradient-to-br from-amber-50/70 to-orange-50/40 border border-amber-200/80 shadow-2xs flex items-center gap-3">
                                            <img :src="msg.event.poster" class="w-14 h-18 object-cover rounded-xl shrink-0 border border-amber-200/60 shadow-xs">
                                            <div class="flex-1 min-w-0">
                                                <div class="badge-rose text-[9px] px-2 py-0.2 rounded-full font-black uppercase inline-block mb-1">CONCERT HOT</div>
                                                <h4 class="font-display font-black text-slate-900 text-xs truncate" x-text="msg.event.title"></h4>
                                                <div class="text-[11px] text-slate-600 font-medium mt-0.5 flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    <span x-text="msg.event.date"></span>
                                                </div>
                                                <div class="text-[11px] text-slate-600 font-medium flex items-center gap-1 mt-0.5 truncate">
                                                    <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                                    <span x-text="msg.event.venue"></span>
                                                </div>
                                                <div class="mt-2 flex items-center justify-between gap-2">
                                                    <span class="text-xs font-black text-[#976D00] font-mono" x-text="'Từ ' + msg.event.price"></span>
                                                    <a :href="msg.event.link" class="px-3 py-1 rounded-xl bg-[#C08497] hover:bg-[#A96B7E] text-white text-[11px] font-black shadow-xs transition-colors shrink-0">
                                                        Đặt vé &rarr;
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <span class="text-[10px] text-slate-400 block px-1" x-text="msg.time"></span>
                            </div>
                        </div>
                    </template>

                    <!-- User Message Bubble -->
                    <template x-if="msg.sender === 'user'">
                        <div class="flex items-end justify-end gap-2 max-w-[88%] ml-auto">
                            <div class="space-y-1 text-right">
                                <div class="bg-[#C08497] text-white rounded-2xl rounded-tr-sm px-4 py-3 shadow-sm text-left leading-relaxed text-[13px] font-semibold break-words">
                                    <span x-text="msg.text"></span>
                                </div>
                                <span class="text-[10px] text-slate-400 block px-1" x-text="msg.time"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Typing Indicator Animation (When bot is thinking) -->
            <div x-show="isTyping" class="flex items-start gap-2.5 max-w-[80%]" style="display: none;">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-[#C08497] via-[#AA8820] to-[#3A5A40] p-0.5 shadow-xs shrink-0">
                    <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-300" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="6" width="18" height="13" rx="4" fill="#0B0C10" stroke="#E5C768" stroke-width="1.5" />
                            <circle cx="8.5" cy="11.5" r="1.5" fill="#FBBF24" />
                            <circle cx="15.5" cy="11.5" r="1.5" fill="#FBBF24" />
                        </svg>
                    </div>
                </div>
                <div class="bg-white rounded-2xl rounded-tl-sm px-4 py-3 border border-slate-200/90 shadow-xs flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#C08497] animate-bounce" style="animation-delay: 0ms"></span>
                    <span class="w-2 h-2 rounded-full bg-[#AA8820] animate-bounce" style="animation-delay: 150ms"></span>
                    <span class="w-2 h-2 rounded-full bg-[#3A5A40] animate-bounce" style="animation-delay: 300ms"></span>
                    <span class="text-xs text-slate-500 font-medium ml-1">TicketBot đang soạn trả lời...</span>
                </div>
            </div>
        </div>

        <!-- Quick Suggestions Chips Bar -->
        <div class="px-3.5 py-2.5 bg-white border-t border-slate-100 flex items-center gap-2 overflow-x-auto shrink-0 scrollbar-none">
            <template x-for="(chip, idx) in quickChips" :key="idx">
                <button 
                    type="button" 
                    @click="askQuickQuestion(chip.prompt)"
                    class="px-3 py-1.5 rounded-full bg-slate-100 hover:bg-[#FFF8E1] hover:border-amber-300 hover:text-[#976D00] border border-slate-200/70 text-xs font-bold text-slate-700 shrink-0 transition-all cursor-pointer whitespace-nowrap shadow-2xs flex items-center gap-1.5"
                >
                    <span x-text="chip.icon"></span>
                    <span x-text="chip.label"></span>
                </button>
            </template>
        </div>

        <!-- Input Area Footer -->
        <div class="p-3.5 bg-white border-t border-slate-100 shrink-0">
            <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                <div class="relative flex-1">
                    <input 
                        type="text" 
                        x-model="userInput"
                        placeholder="Hỏi về sự kiện, giá vé, thanh toán..."
                        class="w-full bg-[#FAF9F6] focus:bg-white border border-slate-200/90 focus:border-[#C08497] focus:ring-4 focus:ring-[#C08497]/15 rounded-2xl py-2.5 pl-4 pr-10 text-xs sm:text-sm text-slate-900 placeholder-slate-400 font-medium transition-all shadow-2xs"
                        :disabled="isTyping"
                    >
                    <!-- Voice Mic Visual Button -->
                    <button 
                        type="button" 
                        title="Gợi ý câu hỏi hot"
                        @click="userInput = 'Gợi ý cho tôi concert hot nhất tuần này';"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#C08497] transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </button>
                </div>

                <!-- Submit Send Button (#C08497) -->
                <button 
                    type="submit" 
                    :disabled="!userInput.trim() || isTyping"
                    class="w-10 h-10 rounded-2xl bg-[#C08497] hover:bg-[#A96B7E] disabled:opacity-40 disabled:pointer-events-none text-white shadow-md shadow-[#C08497]/30 transition-all cursor-pointer flex items-center justify-center shrink-0 hover:scale-105 active:scale-95"
                >
                    <svg class="w-4 h-4 text-white rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
            <div class="text-[10px] text-center text-slate-400 mt-2 font-medium">
                TicketBot AI hỗ trợ tra cứu thông tin vé &amp; chính sách tự động 24/7.
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('ticketBotAssistant', (featuredEventData = null) => ({
        isOpen: false,
        showGreeting: false,
        isTyping: false,
        userInput: '',
        featuredEvent: featuredEventData,
        messages: [
            {
                sender: 'bot',
                text: '<p class="font-bold text-slate-900">Xin chào bạn! 👋</p><p>Tôi là <strong>TicketBot AI</strong> — Trợ lý ảo chính thức của nền tảng đặt vé <strong>TicketBox</strong>.</p><p class="text-slate-600 text-xs">Tôi có thể hỗ trợ bạn nhanh các nội dung:</p><ul class="space-y-1 text-xs text-slate-700 list-disc pl-4"><li>Tìm kiếm và gợi ý các đại nhạc hội Concert & Sự kiện hot.</li><li>Hướng dẫn quy trình giữ chỗ 10 phút & sơ đồ khán phòng.</li><li>Giải đáp thanh toán bằng VietQR, MoMo, ZaloPay và thẻ ngân hàng.</li><li>Tra cứu và xuất trình vé điện tử E-Ticket QR Code.</li></ul>',
                time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
                event: null
            }
        ],
        quickChips: [
            { icon: '🔥', label: 'Concert hot nhất', prompt: 'Gợi ý cho tôi concert hot nhất' },
            { icon: '🎟️', label: 'Cách giữ chỗ & đặt vé', prompt: 'Quy trình đặt vé và giữ chỗ như thế nào?' },
            { icon: '💳', label: 'Thanh toán VietQR & MoMo', prompt: 'Tôi thanh toán bằng VietQR và MoMo ra sao?' },
            { icon: '📱', label: 'Cách tải vé E-Ticket', prompt: 'Làm sao để tải và xem vé điện tử E-Ticket?' },
            { icon: '🏟️', label: 'Sơ đồ SVĐ Quân Khu 7', prompt: 'Cho tôi biết sơ đồ sân khấu SVĐ Quân Khu 7' },
            { icon: '🔄', label: 'Chính sách đổi trả', prompt: 'Chính sách hoàn hủy đổi vé như thế nào?' }
        ],

        initBot() {
            setTimeout(() => {
                if (!this.isOpen) {
                    this.showGreeting = true;
                    setTimeout(() => {
                        this.showGreeting = false;
                    }, 8000);
                }
            }, 1800);
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.showGreeting = false;
                this.scrollToBottom();
            }
        },

        openChat() {
            this.isOpen = true;
            this.showGreeting = false;
            this.scrollToBottom();
        },

        resetChat() {
            this.messages = [
                {
                    sender: 'bot',
                    text: '<p class="font-bold text-slate-900">Hội thoại đã được làm mới! ✨</p><p>Bạn cần TicketBot hỗ trợ tìm kiếm sự kiện hoặc giải đáp thông tin nào tiếp theo ạ?</p>',
                    time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
                    event: null
                }
            ];
        },

        askQuickQuestion(prompt) {
            this.userInput = prompt;
            this.sendMessage();
        },

        sendMessage() {
            const query = (this.userInput || '').trim();
            if (!query) return;

            // Add user message (x-text prevents XSS)
            this.messages.push({
                sender: 'user',
                text: query,
                time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
            });

            this.userInput = '';
            this.isTyping = true;
            this.scrollToBottom();

            // Simulate intelligent AI processing
            setTimeout(() => {
                const response = this.generateBotResponse(query);
                this.messages.push({
                    sender: 'bot',
                    text: response.text,
                    time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
                    event: response.event || null
                });
                this.isTyping = false;
                this.scrollToBottom();
            }, 500);
        },

        generateBotResponse(query) {
            const q = (query || '').toLowerCase().trim();

            // 1. Hot Concert / Event Recommendation
            if (q.includes('concert') || q.includes('anh trai') || q.includes('hot') || q.includes('gợi ý') || q.includes('sự kiện')) {
                if (this.featuredEvent) {
                    return {
                        text: '<p class="font-bold text-slate-900">🔥 Concert hot nhất đang mở bán:</p><p>Sự kiện <strong>' + this.featuredEvent.title + '</strong> đang là tâm điểm chú ý trên TicketBox. Nhanh tay chọn chỗ đẹp nhé!</p>',
                        event: this.featuredEvent
                    };
                } else {
                    return {
                        text: '<p class="font-bold text-slate-900">🔥 Sự kiện âm nhạc nổi bật:</p><p class="text-xs text-slate-600">Hiện tại hệ thống chưa có sự kiện mở bán nào mới. Bạn vui lòng quay lại sau nhé!</p>',
                        event: null
                    };
                }
            }

            // 2. Booking & 10-Minute Holding Process
            if (q.includes('giữ chỗ') || q.includes('đặt vé') || q.includes('quy trình') || q.includes('mua vé') || q.includes('khóa ghế')) {
                return {
                    text: '<p class="font-bold text-slate-900">🎟️ Quy trình đặt vé và giữ chỗ 3 bước siêu tốc:</p><ul class="space-y-2 text-xs list-disc pl-4"><li><strong>Bước 1: Chọn sự kiện & suất diễn</strong> mong muốn trên hệ thống TicketBox.</li><li><strong>Bước 2: Chọn vị trí ghế trên Sơ đồ 2D</strong> — Hệ thống sẽ <strong>tự động khóa ghế và giữ chỗ an toàn trong 10 phút</strong>. Nếu quá 10 phút mà chưa thanh toán, hệ thống sẽ <strong>tự động nhả ghế</strong> cho người khác chọn.</li><li><strong>Bước 3: Điền thông tin & thanh toán</strong> để nhận ngay <strong>Vé Điện Tử E-Ticket QR Code</strong>.</li></ul>'
                };
            }

            // 3. Payment Methods (VietQR, MoMo, ZaloPay, Cards)
            if (q.includes('thanh toán') || q.includes('vietqr') || q.includes('momo') || q.includes('zalopay') || q.includes('chuyển khoản') || q.includes('ngân hàng') || q.includes('tiền')) {
                return {
                    text: '<p class="font-bold text-slate-900">💳 Phương thức thanh toán được hỗ trợ:</p><div class="space-y-2 text-xs"><div class="p-2.5 rounded-xl bg-amber-50/80 border border-amber-200">🏦 <strong>Chuyển khoản VietQR</strong>: Quét mã QR chuẩn NAPAS 24/7 từ mọi ứng dụng ngân hàng, xác nhận nhanh chóng.</div><div class="p-2.5 rounded-xl bg-rose-50/80 border border-rose-200">🟣 <strong>Ví điện tử MoMo</strong>: Quét mã hoặc liên kết tài khoản MoMo thanh toán siêu tốc.</div><div class="p-2.5 rounded-xl bg-blue-50/80 border border-blue-200">🔷 <strong>Ví điện tử ZaloPay</strong>: Thanh toán tiện lợi qua cổng ZaloPay.</div><div class="p-2.5 rounded-xl bg-emerald-50/80 border border-emerald-200">💳 <strong>Thẻ ngân hàng</strong>: Chấp nhận thẻ ATM nội địa và thẻ thanh toán quốc tế (Visa/Mastercard).</div></div>'
                };
            }

            // 4. E-Ticket & QR code check-in & PDF
            if (q.includes('e-ticket') || q.includes('qr') || q.includes('tải vé') || q.includes('check-in') || q.includes('lưu vé')) {
                return {
                    text: '<p class="font-bold text-slate-900">📱 Hướng dẫn xem mã QR và tải vé điện tử:</p><p class="text-xs">Sau khi thanh toán thành công, bạn có thể dễ dàng quản lý vé:</p><div class="space-y-1.5 text-xs text-slate-700"><p>• Truy cập trang <a href="/bookings/history" class="text-[#C08497] font-bold underline">Lịch sử mua vé (/bookings/history)</a> để xem danh sách toàn bộ vé đã đặt.</p><p>• Nhấn vào nút <strong>"Xem vé điện tử"</strong> để hiển thị <strong>mã QR Code bảo mật</strong> phục vụ check-in tại cổng sự kiện.</p><p>• Chọn tính năng <strong>"Tải file PDF vé điện tử"</strong> để lưu vé trực tiếp về điện thoại hoặc in ra giấy.</p></div>'
                };
            }

            // 5. Ticket Price / Giá vé
            if (q.includes('giá vé') || q.includes('giá')) {
                return {
                    text: '<p class="font-bold text-slate-900">💰 Thông tin giá vé:</p><p class="text-xs">Giá vé từng sự kiện được niêm yết công khai trên trang chi tiết và sơ đồ chỗ ngồi. Tùy từng phân khu (SVIP, VIP, GA Standing hay Khán đài) sẽ có các mức phụ thu khác nhau.</p>'
                };
            }

            // 6. Discount voucher / Mã giảm giá
            if (q.includes('mã giảm giá') || q.includes('giảm giá') || q.includes('discount') || q.includes('voucher')) {
                return {
                    text: '<p class="font-bold text-slate-900">🎁 Mã giảm giá & Ưu đãi:</p><p class="text-xs">Bạn có thể áp dụng mã giảm giá tại bước xem giỏ vé (/cart) hoặc thanh toán (/checkout) để được giảm trực tiếp trên tổng đơn hàng.</p>'
                };
            }

            // 7. Stadium Arena / Sơ đồ SVĐ Quân Khu 7
            if (q.includes('sơ đồ') || q.includes('quân khu 7') || q.includes('khán đài') || q.includes('ghế')) {
                return {
                    text: '<p class="font-bold text-slate-900">🏟️ Phân khu Sân Vận Động Quân Khu 7:</p><div class="space-y-1.5 text-xs text-slate-700"><p>• <strong>Sân khấu chính & Catwalk Runway:</strong> Tâm điểm trình diễn hiệu ứng ánh sáng.</p><p>• <strong>SVIP B-Stage Floor:</strong> Ghế ngồi cự ly gần nhất, ngắm trọn thần tượng.</p><p>• <strong>GA Standing (Fanzone):</strong> Đứng quẩy nhiệt huyết ngay sát sàn diễn.</p><p>• <strong>Khán Đài A & B:</strong> Tầm nhìn toàn cảnh sân khấu bao quát từ trên cao.</p></div>'
                };
            }

            // 8. Refund / Exchange policy
            if (q.includes('đổi') || q.includes('hủy') || q.includes('hoàn') || q.includes('trả') || q.includes('chính sách')) {
                return {
                    text: '<p class="font-bold text-slate-900">🔄 Chính sách hoàn hủy & đổi vé:</p><p class="text-xs">Theo quy chế của Ban Tổ Chức, vé đã thanh toán thành công thường <strong>không áp dụng hoàn tiền</strong>.</p><p class="text-xs text-slate-600">Tuy nhiên, bạn có thể <strong>chuyển nhượng vé cho bạn bè/người thân</strong> qua file ảnh Vé Điện Tử E-Ticket hoặc liên hệ bộ phận CSKH để cập nhật thông tin đối soát trước giờ diễn 48h.</p>'
                };
            }

            // Fallback general response (HTML escaped query to prevent XSS)
            const safeQuery = query.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            return {
                text: '<p>Cảm ơn bạn đã gửi câu hỏi: <em>"' + safeQuery + '"</em>.</p><p class="text-xs text-slate-600">Tôi có thể hỗ trợ bạn tìm kiếm sự kiện âm nhạc, giải đáp sơ đồ sân khấu, cách giữ chỗ hoặc các hình thức thanh toán. Hãy chọn một trong các gợi ý bên dưới hoặc đặt câu hỏi chi tiết hơn nhé!</p>'
            };
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = document.getElementById('chat-messages-container');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        }
    }));
});
</script>
