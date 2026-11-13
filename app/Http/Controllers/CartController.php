<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\ShowtimeSeat;
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

    public function applyDiscount(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = strtoupper(trim($request->input('code')));
        $discount = Discount::where('code', $code)->first();

        if (! $discount) {
            return response()->json(['message' => 'Mã giảm giá không tồn tại trên hệ thống.'], 422);
        }

        if (! $discount->is_active) {
            return response()->json(['message' => 'Mã giảm giá đã tạm ngưng áp dụng.'], 422);
        }

        if ($discount->start_date && $discount->start_date->isFuture()) {
            return response()->json(['message' => 'Mã giảm giá chưa đến ngày có hiệu lực.'], 422);
        }

        if ($discount->end_date && $discount->end_date->isPast()) {
            return response()->json(['message' => 'Mã giảm giá đã hết hạn sử dụng.'], 422);
        }

        if ($discount->max_uses && $discount->used_count >= $discount->max_uses) {
            return response()->json(['message' => 'Mã giảm giá đã hết lượt sử dụng.'], 422);
        }

        $seats = ShowtimeSeat::heldBy($request->user())->get();
        if ($seats->isEmpty()) {
            return response()->json(['message' => 'Giỏ vé của bạn đang trống.'], 422);
        }

        $subtotal = (int) $seats->sum(fn ($seat) => $seat->price_override ?? $seat->effective_price);

        if ($subtotal < $discount->min_order_value) {
            return response()->json([
                'message' => 'Đơn hàng chưa đạt mức tối thiểu ' . number_format($discount->min_order_value, 0, ',', '.') . '₫ để áp dụng mã giảm giá này.',
            ], 422);
        }

        $discountAmount = $discount->calculateDiscount($subtotal);
        $finalTotal = max(0, $subtotal - $discountAmount);

        session([
            'applied_discount' => [
                'id' => $discount->id,
                'code' => $discount->code,
                'discount_amount' => $discountAmount,
                'final_total' => $finalTotal,
            ],
        ]);

        return response()->json([
            'message' => "Áp dụng mã giảm giá [{$discount->code}] thành công!",
            'discount_code' => $discount->code,
            'discount_amount' => $discountAmount,
            'subtotal' => $subtotal,
            'final_total' => $finalTotal,
        ]);
    }
}
