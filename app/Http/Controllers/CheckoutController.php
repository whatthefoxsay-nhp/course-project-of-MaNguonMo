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
            $booking = $checkout->checkout(
                $request->user(),
                $request->validated('payment_method'),
                $request->validated('delivery_email'),
            );
        } catch (SeatHoldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Thanh toán thành công! Vé điện tử đã được phát hành.',
            'booking_code' => $booking->booking_code,
            'total_price' => $booking->total_price,
            'qr_data_uri' => 'data:image/svg+xml;base64,'.base64_encode((string) \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(250)
                ->margin(2)
                ->generate('TICKETBOX_AUTH_PASS_'.$booking->booking_code)),
            'redirect' => route('bookings.history'),
        ]);
    }
}
