<?php

namespace App\Http\Controllers;

use App\Exceptions\SeatHoldException;
use App\Http\Requests\CheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, CheckoutService $checkout): JsonResponse
    {
        try {
            $booking = $checkout->checkout($request->user(), $request->validated('payment_method'));
        } catch (SeatHoldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Thanh toán thành công! Vé điện tử đã được phát hành.',
            'booking_code' => $booking->booking_code,
            'total_price' => $booking->total_price,
            'redirect' => route('bookings.history'),
        ]);
    }
}
