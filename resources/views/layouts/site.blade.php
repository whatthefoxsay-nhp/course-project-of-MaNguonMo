<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Đặt Vé' }} - TicketBox Đồ Án</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-gray-900 flex flex-col">

    <header class="glass-nav sticky top-4 z-10 mx-4 sm:mx-6 lg:mx-8 rounded-full">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                <a href="{{ route('home') }}" class="text-xl font-bold text-rose-600 shrink-0">
                    Ticket<span class="text-gray-900">Box</span>
                </a>

                <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-600">
                    <a href="{{ route('movies.index') }}" class="hover:text-rose-600 {{ request()->routeIs('movies.*') ? 'text-rose-600' : '' }}">Phim &amp; Sự kiện</a>
                    @auth
                        <a href="{{ route('bookings.history') }}" class="hover:text-rose-600 {{ request()->routeIs('bookings.*') ? 'text-rose-600' : '' }}">Vé của tôi</a>
                    @endauth
                </nav>

                <form action="{{ route('movies.index') }}" method="GET" class="hidden sm:block flex-1 max-w-sm">
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Tìm phim, sự kiện..."
                        class="glass-input w-full text-sm border-0 focus:ring-2 focus:ring-rose-400"
                    >
                </form>

                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('cart.index') }}" class="relative text-gray-600 hover:text-rose-600" title="Giỏ vé">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.94-4.693 2.436-7.152.083-.41-.235-.798-.653-.798H5.106M7.5 14.25L5.106 5.25M9.75 18.75a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm9 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                        </svg>
                    </a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-700 hover:text-rose-600">{{ Auth::user()->name }}</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 hover:text-rose-600">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="glass-btn-primary text-sm font-medium px-4 py-2">Đăng ký</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="glass-nav text-gray-500 text-sm mt-16 mx-4 sm:mx-6 lg:mx-8 mb-4 !rounded-3xl">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            Đồ án môn Mã nguồn mở — Website Quản lý đặt vé (phiên bản đơn giản, dữ liệu demo).
        </div>
    </footer>

</body>
</html>
