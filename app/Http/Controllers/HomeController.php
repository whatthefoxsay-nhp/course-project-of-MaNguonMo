<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $events = Event::published()
            ->with('category')
            ->withMin('showtimes', 'base_price')
            ->latest('release_date')
            ->take(8)
            ->get();

        return view('home', ['movies' => $events->all()]);
    }
}
