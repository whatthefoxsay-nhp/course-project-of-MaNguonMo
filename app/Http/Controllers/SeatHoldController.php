<?php

namespace App\Http\Controllers;

use App\Exceptions\SeatHoldException;
use App\Http\Requests\HoldSeatsRequest;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\SeatHoldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeatHoldController extends Controller
{
    public function store(HoldSeatsRequest $request, Showtime $showtime, SeatHoldService $holds): JsonResponse
    {
        try {
            $seats = $holds->hold($request->user(), $showtime, $request->seatIds(), $request->tierQuantities());
        } catch (SeatHoldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Đã giữ '.$seats->count().' chỗ trong '.SeatHoldService::HOLD_MINUTES.' phút.',
            'held_until' => $seats->first()->fresh()->held_until->toIso8601String(),
            'redirect' => route('cart.index'),
        ]);
    }

    public function destroy(Request $request, ShowtimeSeat $showtimeSeat, SeatHoldService $holds): JsonResponse
    {
        try {
            $holds->release($request->user(), $showtimeSeat);
        } catch (SeatHoldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Đã bỏ ghế khỏi giỏ vé.']);
    }
}
