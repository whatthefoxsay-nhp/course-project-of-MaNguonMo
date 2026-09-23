<?php

namespace App\Services;

use App\Exceptions\SeatHoldException;
use App\Models\Booking;
use App\Models\ShowtimeSeat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public const PAYMENT_METHODS = ['vietqr', 'momo', 'zalopay', 'card'];

    /**
     * Thanh toán giả lập (spec mục 5): ghế đang giữ -> đơn "confirmed".
     *
     * @throws SeatHoldException
     */
    public function checkout(User $user, string $paymentMethod): Booking
    {
        return DB::transaction(function () use ($user, $paymentMethod) {
            $seats = ShowtimeSeat::heldBy($user)
                ->with(['seat', 'showtime'])
                ->lockForUpdate()
                ->get();

            if ($seats->isEmpty()) {
                throw new SeatHoldException('Giỏ vé trống hoặc đã hết thời gian giữ chỗ. Vui lòng chọn lại ghế.');
            }

            $priceOf = fn (ShowtimeSeat $seat) => $seat->price_override ?? $seat->effective_price;

            $booking = Booking::create([
                'user_id' => $user->id,
                'total_price' => $seats->sum($priceOf),
                'status' => 'confirmed',
                'payment_method' => $paymentMethod,
            ]);

            foreach ($seats as $seat) {
                $booking->items()->create([
                    'showtime_seat_id' => $seat->id,
                    'price' => $priceOf($seat),
                ]);

                $seat->update([
                    'status' => 'booked',
                    'held_by_user_id' => null,
                    'held_until' => null,
                ]);
            }

            return $booking;
        });
    }
}
