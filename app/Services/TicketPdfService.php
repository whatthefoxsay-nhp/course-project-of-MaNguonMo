<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketPdfService
{
    public function generate(Booking $booking): string
    {
        $booking->loadMissing([
            'items.showtimeSeat.seat.zone',
            'items.showtimeSeat.showtime.event',
            'items.showtimeSeat.showtime.room',
        ]);

        return Pdf::loadView('tickets.pdf', compact('booking'))
            ->setPaper('a5', 'landscape')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
            ])
            ->output();
    }

    public function generateReceipt(Booking $booking): string
    {
        $booking->loadMissing([
            'items.showtimeSeat.seat.zone',
            'items.showtimeSeat.showtime.event',
            'items.showtimeSeat.showtime.room',
        ]);

        return Pdf::loadView('tickets.receipt-pdf', compact('booking'))
            ->setPaper('a5', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
            ])
            ->output();
    }
}
