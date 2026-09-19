<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Tài Khoản' }} - TicketBox Official</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-black bg-[#FAF9F6] antialiased min-h-screen flex flex-col justify-between items-center p-4 sm:p-6 selection:bg-rose-taupe selection:text-white">
    
    <!-- Subtle Ambient Lighting -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute -top-[10%] -left-[10%] w-[55vw] h-[55vw] rounded-full bg-[#C08497]/10 blur-[130px]"></div>
        <div class="absolute -bottom-[10%] -right-[10%] w-[50vw] h-[50vw] rounded-full bg-[#D4AF37]/10 blur-[140px]"></div>
    </div>

    <!-- Top Header Navigation -->
    <header class="w-full max-w-xl relative z-10 flex items-center justify-between py-4">
        <a href="/" class="flex items-center gap-2.5 group">
            <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-gold-antique to-rose-taupe flex items-center justify-center text-black font-display font-black text-lg shadow-sm group-hover:scale-105 transition-transform">
                T
            </div>
            <span class="font-display font-black text-2xl text-black tracking-tight">
                Ticket<span class="text-rose-taupe">Box</span>
            </span>
        </a>

        <a href="/" class="px-4 py-2 rounded-full text-xs font-bold text-slate-700 bg-white hover:bg-[#C08497] hover:text-white border border-slate-200/80 shadow-sm transition-all flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Quay lại trang chủ</span>
        </a>
    </header>

    <!-- Main Auth Card -->
    <main class="w-full max-w-lg relative z-10 my-auto py-4 sm:py-6">
        <div class="bg-white rounded-3xl p-7 sm:p-9 border border-slate-200/90 shadow-2xl shadow-slate-200/60 relative overflow-hidden">
            <!-- Top Ambient Accent Gradient Line -->
            <div class="h-1.5 bg-gradient-to-r from-rose-500 via-amber-400 to-rose-600 absolute top-0 inset-x-0"></div>
            {{ $slot }}
        </div>
    </main>

    <!-- Bottom Footer -->
    <footer class="w-full max-w-xl relative z-10 text-center py-4 text-xs text-gray-400 font-medium">
        © 2026 TicketBox Vietnam JSC. Nền tảng đặt vé Sự kiện &amp; Concert chuyên nghiệp.
    </footer>

</body>
</html>
