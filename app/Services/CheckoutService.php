<?php

namespace App\Services;

use App\Exceptions\SeatHoldException;
use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use App\Models\Discount;
use App\Models\ShowtimeSeat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CheckoutService
{
    public const PAYMENT_METHODS = ['vietqr', 'momo', 'zalopay', 'card'];

    /**
     * Thanh toán: ghế đang giữ -> đơn "confirmed".
     *
     * @throws SeatHoldException
     */
    public function checkout(
        User $user,
        string $paymentMethod,
        string $deliveryEmail,
        ?string $deliveryName = null,
        ?string $deliveryPhone = null,
        ?string $discountCode = null,
        ?array $expectedSeatIds = null
    ): Booking {
        $booking = DB::transaction(function () use (
            $user,
            $paymentMethod,
            $deliveryEmail,
            $deliveryName,
            $deliveryPhone,
            $discountCode,
            $expectedSeatIds
        ) {
            // Kiểm tra nếu có ghế do user giữ đã bị hết hạn
            $expiredSeats = ShowtimeSeat::where('held_by_user_id', $user->id)
                ->where('held_until', '<=', now())
                ->lockForUpdate()
                ->get();

            if ($expiredSeats->isNotEmpty()) {
                throw new SeatHoldException('Một hoặc nhiều ghế trong giỏ vé đã hết thời gian giữ chỗ. Vui lòng chọn lại.');
            }

            $query = ShowtimeSeat::heldBy($user)
                ->with(['seat.zone', 'showtime'])
                ->lockForUpdate();

            if (! empty($expectedSeatIds)) {
                $query->whereIn('id', $expectedSeatIds);
            }

            $seats = $query->get();

            if ($seats->isEmpty()) {
                throw new SeatHoldException('Giỏ vé trống hoặc đã hết thời gian giữ chỗ. Vui lòng chọn lại ghế.');
            }

            if (! empty($expectedSeatIds) && $seats->count() !== count($expectedSeatIds)) {
                throw new SeatHoldException('Một hoặc nhiều ghế đã không còn khả dụng hoặc bị hủy giữ chỗ. Vui lòng chọn lại.');
            }

            $priceOf = fn (ShowtimeSeat $seat) => $seat->price_override ?? $seat->effective_price;
            $subtotal = (int) $seats->sum($priceOf);

            // Xử lý mã giảm giá
            $discountCodeToApply = $discountCode ?? session('applied_discount.code');
            $discountId = null;
            $discountAmount = 0;

            if ($discountCodeToApply) {
                $discount = Discount::where('code', strtoupper(trim($discountCodeToApply)))->first();
                if ($discount && $discount->isValidForOrder($subtotal)) {
                    $discountAmount = $discount->calculateDiscount($subtotal);
                    $discountId = $discount->id;
                    $discount->increment('used_count');
                }
            }

            $totalPrice = max(0, $subtotal - $discountAmount);

            $booking = Booking::create([
                'user_id' => $user->id,
                'total_price' => $totalPrice,
                'discount_id' => $discountId,
                'discount_amount' => $discountAmount,
                'status' => 'confirmed',
                'payment_method' => $paymentMethod,
                'buyer_name' => $deliveryName ?? $user->name,
                'buyer_phone' => $deliveryPhone ?? $user->phone,
                'buyer_email' => $deliveryEmail,
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

            if (session()->has('applied_discount')) {
                session()->forget('applied_discount');
            }

            return $booking;
        });

        try {
            Mail::to($deliveryEmail)->queue(new BookingConfirmationMail($booking));
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Không thể gửi email xác nhận đặt vé: ' . $exception->getMessage(), [
                'booking_id' => $booking->id,
                'delivery_email' => $deliveryEmail,
            ]);
        }

        return $booking;
    }
}
