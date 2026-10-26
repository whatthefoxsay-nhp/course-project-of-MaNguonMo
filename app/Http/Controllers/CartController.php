<?php

namespace App\Http\Controllers;

use App\Models\ShowtimeSeat;
use App\Support\TicketTiers;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $seats = ShowtimeSeat::heldBy($request->user())
            ->with(['seat.zone', 'showtime.room', 'showtime.event.category'])
            ->orderBy('id')
            ->get();

        $items = $seats->map(function (ShowtimeSeat $held) {
            $zone = $held->seat->zone;

            return (object) [
                'id' => $held->id,
                'showtime' => $held->showtime,
                'movie' => $held->showtime->event,
                'seat' => (object) [
                    // Khu đứng: ghế ẩn, chỉ hiện số thứ tự vé.
                    'row_label' => $zone?->isStanding() ? 'Vé số ' : $held->seat->row_label,
                    'seat_number' => $held->seat->seat_number,
                    'type' => $zone?->name ?? TicketTiers::seatName($held->seat->type),
                ],
                'price' => $held->price_override ?? $held->effective_price,
            ];
        })->all();

        $expiresInSeconds = $seats->isEmpty()
            ? 0
            : (int) max(0, now()->diffInSeconds($seats->min('held_until')));

        return view('cart.index', [
            'items' => $items,
            'total' => array_sum(array_map(fn ($item) => $item->price, $items)),
            'expiresInSeconds' => $expiresInSeconds,
        ]);
    }
}
