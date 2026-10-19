<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ShowtimeSeat;
use App\Models\ZoneTicket;
use App\Services\SalesReportService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(SalesReportService $sales): View
    {
        return view('admin.reports.index', $this->reportData($sales));
    }

    public function pdf(SalesReportService $sales): View
    {
        return view('admin.reports.pdf', $this->reportData($sales));
    }

    /** Số liệu dùng chung cho trang báo cáo và bản in PDF. */
    private function reportData(SalesReportService $sales): array
    {
        return [
            'totalRevenue' => Booking::where('status', 'confirmed')->sum('total_price'),
            'totalTicketsSold' => ShowtimeSeat::where('status', 'booked')->count() + ZoneTicket::where('status', 'booked')->count(),
            'totalBookings' => Booking::count(),
            'eventsRevenue' => $sales->eventsRevenue(),
            'categoriesRevenue' => $sales->categoriesRevenue(),
        ];
    }
}
