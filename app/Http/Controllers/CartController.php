<?php

namespace App\Http\Controllers;

use App\Exceptions\DiscountException;
use App\Models\ShowtimeSeat;
use App\Models\ZoneTicket;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Support\TicketTiers;
use Illuminate\Http\JsonResponse;
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

    /** Xem trước mã giảm giá cho giỏ vé hiện tại (tiền giảm tính lại ở server khi thanh toán). */
    public function applyDiscount(Request $request, CheckoutService $checkout, DiscountService $discounts): JsonResponse
    {
        $validated = $request->validate(
            ['code' => ['required', 'string', 'max:32']],
            [],
            ['code' => 'mã giảm giá'],
        );

        $subtotal = $checkout->heldSubtotal($request->user());
        if ($subtotal <= 0) {
            return response()->json(['message' => 'Giỏ vé trống hoặc đã hết thời gian giữ chỗ.'], 422);
        }

        try {
            [$discount, $amount] = $discounts->resolve($validated['code'], $subtotal);
        } catch (DiscountException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['code' => [$exception->getMessage()]],
            ], 422);
        }

        return response()->json([
            'message' => 'Đã áp dụng mã '.$discount->code.': giảm '.number_format($amount, 0, ',', '.').'₫.',
            'code' => $discount->code,
            'title' => $discount->title,
            'subtotal' => $subtotal,
            'discount_amount' => $amount,
            'total' => $subtotal - $amount,
        ]);
    }
}
