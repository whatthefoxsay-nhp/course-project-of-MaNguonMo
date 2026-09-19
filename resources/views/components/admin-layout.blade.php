@props(['title' => null, 'header' => null])

@include('layouts.admin', ['title' => $title, 'header' => $header, 'slot' => $slot])
