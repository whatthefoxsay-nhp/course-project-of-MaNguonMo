<?php

namespace App\Http\Controllers;

use App\Exceptions\SeatHoldException;
use App\Http\Requests\HoldSeatsRequest;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\ZoneTicket;
use App\Services\SeatHoldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeatHoldController extends Controller
{
    public function store(HoldSeatsRequest $request, Showtime $showtime, SeatHoldService $holds): JsonResponse
    {
        try {
            $seats = $holds->hold($request->user(), $showtime, $request->seatIds(), $request->tierQuantities(), $request->zoneQuantities());
        } catch (SeatHoldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $zoneTickets = ZoneTicket::where('showtime_id', $showtime->id)->where('status', 'held')->where('held_by_user_id', $request->user()->id)->where('held_until', '>', now())->get();
        $heldCount = $seats->count() + $zoneTickets->count();
        $expiresAt = collect([$seats->min('held_until'), $zoneTickets->min('held_until')])->filter()->min();

        return response()->json([
            'message' => 'Đã giữ '.$heldCount.' vé trong '.SeatHoldService::HOLD_MINUTES.' phút.',
            'held_until' => $expiresAt->toIso8601String(),
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

    public function destroyZoneTicket(Request $request, ZoneTicket $zoneTicket, SeatHoldService $holds): JsonResponse
    {
        try {
            $holds->releaseZoneTicket($request->user(), $zoneTicket);
        } catch (SeatHoldException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Đã bỏ vé khỏi giỏ.']);
    }
}
