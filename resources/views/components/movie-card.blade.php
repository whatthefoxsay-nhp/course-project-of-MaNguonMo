@props(['movie' => null, 'event' => null, 'featured' => false])

<x-event-card :event="$event ?? $movie" :featured="$featured" />
