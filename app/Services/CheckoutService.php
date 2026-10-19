<?php

namespace App\Services;

use App\Exceptions\SeatHoldException;
use App\Models\Booking;
use App\Models\ShowtimeSeat;
use App\Models\ZoneTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public const PAYMENT_METHODS = ['vietqr', 'momo', 'zalopay', 'card'];

    /**
     * Thanh toán giả lập (spec mục 5): ghế đang giữ -> đơn "confirmed".
     *
     * @param  array{name?: ?string, phone?: ?string, email?: ?string}  $buyer  người nhận vé (mặc định lấy từ tài khoản)
     *
     * @throws SeatHoldException
     */
    public function checkout(User $user, string $paymentMethod, array $buyer = []): Booking
    {
        return DB::transaction(function () use ($user, $paymentMethod, $buyer) {
            $seats = ShowtimeSeat::heldBy($user)
                ->with(['seat', 'showtime'])
                ->lockForUpdate()
                ->get();
            $zoneTickets = ZoneTicket::where('status', 'held')->where('held_by_user_id', $user->id)
                ->where('held_until', '>', now())->with('showtime')->lockForUpdate()->get();

            if ($seats->isEmpty() && $zoneTickets->isEmpty()) {
                throw new SeatHoldException('Giỏ vé trống hoặc đã hết thời gian giữ chỗ. Vui lòng chọn lại ghế.');
            }

            $priceOf = fn (ShowtimeSeat $seat) => $seat->price_override ?? $seat->effective_price;

            $booking = Booking::create([
                'user_id' => $user->id,
                'total_price' => $seats->sum($priceOf) + $zoneTickets->sum('price'),
                'status' => 'confirmed',
                'payment_method' => $paymentMethod,
                'buyer_name' => array_key_exists('name', $buyer) ? $buyer['name'] : $user->name,
                'buyer_phone' => array_key_exists('phone', $buyer) ? $buyer['phone'] : $user->phone,
                'buyer_email' => array_key_exists('email', $buyer) ? $buyer['email'] : $user->email,
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

            foreach ($zoneTickets as $ticket) {
                $booking->items()->create([
                    'zone_ticket_id' => $ticket->id,
                    'price' => $ticket->price,
                ]);
                $ticket->update([
                    'status' => 'booked',
                    'held_by_user_id' => null,
                    'held_until' => null,
                ]);
            }

            return $booking;
        });
    }
}
