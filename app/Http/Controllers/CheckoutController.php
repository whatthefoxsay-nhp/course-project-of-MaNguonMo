<?php

namespace App\Http\Controllers;

use App\Exceptions\DiscountException;
use App\Exceptions\SeatHoldException;
use App\Http\Requests\CheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, CheckoutService $checkout): JsonResponse
    {
        try {
            $booking = $checkout->checkout(
                $request->user(),
                $request->validated('payment_method'),
                $request->buyer(),
                $request->validated('discount_code'),
            );
        } catch (DiscountException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['discount_code' => [$exception->getMessage()]],
            ], 422);
        } catch (SeatHoldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Thanh toán thành công! Vé điện tử đã được phát hành.',
            'booking_code' => $booking->booking_code,
            'total_price' => $booking->total_price,
            'discount_amount' => $booking->discount_amount,
            'redirect' => route('bookings.history'),
        ]);
    }
}
