@props(['title' => null])

@include('layouts.site', ['title' => $title, 'slot' => $slot])
