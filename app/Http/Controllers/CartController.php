<?php

namespace App\Http\Controllers;

use App\Models\ShowtimeSeat;
use App\Models\ZoneTicket;
use App\Support\TicketTiers;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $seats = ShowtimeSeat::heldBy($request->user())
            ->with(['seat', 'showtime.room', 'showtime.event.category'])
            ->orderBy('id')
            ->get();
        $zoneTickets = ZoneTicket::where('status', 'held')->where('held_by_user_id', $request->user()->id)
            ->where('held_until', '>', now())->with(['zone', 'showtime.room', 'showtime.event.category'])
            ->orderBy('id')->get();

        $items = $seats->map(function (ShowtimeSeat $held) {
            $tiers = TicketTiers::for($held->showtime->base_price, true);
            $type = TicketTiers::normalizeSeatType($held->seat->type);

            return (object) [
                'id' => $held->id,
                'is_zone_ticket' => false,
                'showtime' => $held->showtime,
                'movie' => $held->showtime->event,
                'seat' => (object) [
                    'row_label' => $held->seat->row_label,
                    'seat_number' => $held->seat->seat_number,
                    'type' => $tiers[$type]['label'] ?? $type,
                ],
                'price' => $held->price_override ?? $held->effective_price,
            ];
        });

        $zoneItems = $zoneTickets->map(fn (ZoneTicket $ticket) => (object) [
            'id' => $ticket->id,
            'is_zone_ticket' => true,
            'showtime' => $ticket->showtime,
            'movie' => $ticket->showtime->event,
            'zone' => $ticket->zone,
            'seat' => (object) [
                'row_label' => $ticket->zone->code,
                'seat_number' => $ticket->id,
                'type' => $ticket->zone->name.' — vé số '.$ticket->id,
            ],
            'price' => $ticket->price,
        ]);
        $items = $items->concat($zoneItems)->values()->all();

        $heldUntil = $seats->pluck('held_until')->concat($zoneTickets->pluck('held_until'))->filter();
        $expiresInSeconds = $heldUntil->isEmpty()
            ? 0
            : (int) max(0, now()->diffInSeconds($heldUntil->min()));

        return view('cart.index', [
            'items' => $items,
            'total' => array_sum(array_map(fn ($item) => $item->price, $items)),
            'expiresInSeconds' => $expiresInSeconds,
        ]);
    }
}
