<?php

namespace App\Services;

use App\Exceptions\DiscountException;
use App\Exceptions\SeatHoldException;
use App\Models\Booking;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\ZoneTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public const PAYMENT_METHODS = ['vietqr', 'momo', 'zalopay', 'card'];

    public function __construct(private readonly DiscountService $discounts)
    {
    }

    /** Tạm tính các vé user đang giữ (chưa hết hạn) — dùng cho bước áp mã giảm giá ở giỏ vé. */
    public function heldSubtotal(User $user): int
    {
        $seats = ShowtimeSeat::heldBy($user)->with('seat', 'showtime')->get();
        $zoneTotal = ZoneTicket::where('status', 'held')->where('held_by_user_id', $user->id)
            ->where('held_until', '>', now())->sum('price');

        return (int) ($seats->sum(fn (ShowtimeSeat $seat) => self::seatPrice($seat)) + $zoneTotal);
    }

    private static function seatPrice(ShowtimeSeat $seat): int
    {
        return (int) ($seat->price_override ?? $seat->effective_price);
    }

    /**
     * Thanh toán giả lập (spec mục 5): ghế đang giữ -> đơn "confirmed".
     *
     * @param  array{name?: ?string, phone?: ?string, email?: ?string}  $buyer  người nhận vé (mặc định lấy từ tài khoản)
     *
     * @param  ?string  $discountCode  mã giảm giá (kiểm tra lại & tính tiền giảm phía server)
     *
     * @throws SeatHoldException|DiscountException
     */
    public function checkout(User $user, string $paymentMethod, array $buyer = [], ?string $discountCode = null): Booking
    {
        return DB::transaction(function () use ($user, $paymentMethod, $buyer, $discountCode) {
            $seats = ShowtimeSeat::heldBy($user)
                ->with(['seat', 'showtime'])
                ->lockForUpdate()
                ->get();
            $zoneTickets = ZoneTicket::where('status', 'held')->where('held_by_user_id', $user->id)
                ->where('held_until', '>', now())->with('showtime')->lockForUpdate()->get();

            if ($seats->isEmpty() && $zoneTickets->isEmpty()) {
                throw new SeatHoldException('Giỏ vé trống hoặc đã hết thời gian giữ chỗ. Vui lòng chọn lại ghế.');
            }

            $this->ensureShowtimesBookable($seats->pluck('showtime')->concat($zoneTickets->pluck('showtime')));

            $priceOf = fn (ShowtimeSeat $seat) => self::seatPrice($seat);
            $subtotal = $seats->sum($priceOf) + $zoneTickets->sum('price');

            $discount = null;
            $discountAmount = 0;
            if ($discountCode !== null && trim($discountCode) !== '') {
                // Khóa dòng mã giảm giá để 2 đơn đồng thời không vượt quá max_uses.
                [$discount, $discountAmount] = $this->discounts->resolve($discountCode, $subtotal, lockForUpdate: true);
                $discount->increment('used_count');
            }

            $booking = Booking::create([
                'user_id' => $user->id,
                'total_price' => $subtotal - $discountAmount,
                'discount_id' => $discount?->id,
                'discount_amount' => $discountAmount,
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

    /**
     * Kiểm tra lại lúc thanh toán: suất chưa bắt đầu và sự kiện vẫn đang mở bán
     * (admin có thể ẩn sự kiện hoặc suất đã qua giờ trong lúc khách đang giữ vé).
     *
     * @param  iterable<int, Showtime|null>  $showtimes
     *
     * @throws SeatHoldException
     */
    private function ensureShowtimesBookable(iterable $showtimes): void
    {
        foreach (collect($showtimes)->filter()->unique('id') as $showtime) {
            $showtime->loadMissing('event');

            if ($showtime->event?->status !== 'published') {
                throw new SeatHoldException('Sự kiện hiện không mở bán, không thể thanh toán.');
            }
            if ($showtime->start_time->isPast()) {
                throw new SeatHoldException('Suất diễn đã bắt đầu, không thể thanh toán.');
            }
        }
    }
}
