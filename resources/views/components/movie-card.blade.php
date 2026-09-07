@props(['movie'])

<a href="{{ route('movies.show', $movie->slug) }}" class="group block glass-panel p-2 hover:bg-white/70 transition">
    <div class="aspect-[2/3] rounded-2xl overflow-hidden bg-gray-200">
        <img src="{{ $movie->poster_path }}" alt="{{ $movie->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
    </div>
    <div class="mt-2 px-1 pb-1">
        <span class="inline-block text-[11px] font-semibold uppercase tracking-wide text-rose-600">
            {{ $movie->type === 'event' ? 'Sự kiện' : 'Phim' }}
        </span>
        <h3 class="font-semibold text-gray-900 leading-snug group-hover:text-rose-600">{{ $movie->title }}</h3>
        <p class="text-xs text-gray-500">{{ $movie->category->name }}</p>
    </div>
</a>
