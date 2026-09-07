<?php

namespace App\Http\Controllers;

use App\Support\DemoCatalog;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function history(): View
    {
        return view('bookings.history', [
            'bookings' => DemoCatalog::bookingHistory(),
        ]);
    }
}
