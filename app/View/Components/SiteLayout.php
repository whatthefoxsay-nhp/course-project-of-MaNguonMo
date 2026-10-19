<?php

namespace App\View\Components;

use App\Support\UpcomingShowtimes;
use Illuminate\View\Component;
use Illuminate\View\View;

class SiteLayout extends Component
{
    public function __construct(public ?string $title = null)
    {
    }

    public function render(): View
    {
        return view('layouts.site', [
            'upcomingShowtimes' => UpcomingShowtimes::for(auth()->user()),
        ]);
    }
}
