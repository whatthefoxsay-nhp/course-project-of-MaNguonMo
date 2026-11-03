<?php

namespace App\Http\Controllers;

use App\Exceptions\DiscountException;
use App\Models\ShowtimeSeat;
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
