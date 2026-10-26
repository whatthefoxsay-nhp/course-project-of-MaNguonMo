<?php

namespace App\Services;

use App\Exceptions\SeatHoldException;
use App\Models\SeatZone;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Support\TicketTiers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SeatHoldService
{
    public const HOLD_MINUTES = 10;

    public const MAX_SEATS = 8;

    private const RELEASED = [
        'status' => 'available',
        'held_by_user_id' => null,
        'held_until' => null,
        'price_override' => null,
    ];

    /**
     * Giữ chỗ cho user — tất cả hoặc không gì cả.
     *
     * @param  list<int>  $showtimeSeatIds  ghế khách tự chọn trên sơ đồ (id bảng showtime_seats)
     * @param  array<int, int>  $zoneQuantities  [id khu => số vé] cho khu bán theo số lượng (server tự gán ghế)
     * @return Collection<int, ShowtimeSeat>
     *
     * @throws SeatHoldException
     */
    public function hold(User $user, Showtime $showtime, array $showtimeSeatIds, array $zoneQuantities = []): Collection
    {
        $showtime->loadMissing('event');
        $event = $showtime->event;
        $zoneQuantities = array_filter($zoneQuantities, fn (int $quantity) => $quantity > 0);

        if ($event->status !== 'published') {
            throw new SeatHoldException('Sự kiện hiện không mở bán.');
        }
        if ($showtime->start_time->isPast()) {
            throw new SeatHoldException('Suất diễn đã bắt đầu, không thể đặt vé.');
        }
        if ($showtimeSeatIds !== [] && ! $event->is_seated) {
            throw new SeatHoldException('Sự kiện này không chọn ghế trên sơ đồ, vui lòng chọn khu vực và số lượng.');
        }

        $zones = SeatZone::where('room_id', $showtime->room_id)
            ->whereIn('id', array_keys($zoneQuantities))
            ->get()
            ->keyBy('id');
        if ($zones->count() !== count($zoneQuantities)) {
            throw new SeatHoldException('Khu vực không hợp lệ.');
        }
        if ($event->is_seated && $zones->contains(fn (SeatZone $zone) => ! $zone->isStanding())) {
            throw new SeatHoldException('Khu này chọn ghế trên sơ đồ.');
        }

        $requestedSeats = count($showtimeSeatIds) + array_sum($zoneQuantities);
        if ($requestedSeats === 0) {
            throw new SeatHoldException('Vui lòng chọn ít nhất 1 vé.');
        }

        return DB::transaction(function () use ($user, $showtime, $showtimeSeatIds, $zoneQuantities, $zones, $requestedSeats) {
            // Mỗi user chỉ giữ ghế của 1 suất tại 1 thời điểm.
            ShowtimeSeat::where('held_by_user_id', $user->id)
                ->where('status', 'held')
                ->where('showtime_id', '!=', $showtime->id)
                ->update(self::RELEASED);

            $alreadyHeld = ShowtimeSeat::where('showtime_id', $showtime->id)->heldBy($user)->count();
            if ($alreadyHeld + $requestedSeats > self::MAX_SEATS) {
                throw new SeatHoldException('Mỗi lượt đặt tối đa '.self::MAX_SEATS." chỗ (bạn đang giữ {$alreadyHeld} chỗ).");
            }

            $heldUntil = now()->addMinutes(self::HOLD_MINUTES);
            $held = new Collection;

            // 1) Ghế khách tự chọn: khóa dòng rồi mới kiểm tra còn trống (chống 2 người cùng giữ).
            if ($showtimeSeatIds !== []) {
                $picked = ShowtimeSeat::with('seat.zone')
                    ->where('showtime_id', $showtime->id)
                    ->whereIn('id', $showtimeSeatIds)
                    ->lockForUpdate()
                    ->get();

                if ($picked->count() !== count(array_unique($showtimeSeatIds))) {
                    throw new SeatHoldException('Có ghế không thuộc suất diễn này.');
                }
                if ($picked->contains(fn (ShowtimeSeat $seat) => $seat->seat->zone?->isStanding() || $seat->seat->type === 'standing_pit')) {
                    throw new SeatHoldException('Khu đứng bán theo số lượng, không chọn theo ghế.');
                }

                $unavailable = $picked->reject(fn (ShowtimeSeat $seat) => $seat->isHoldable());
                if ($unavailable->isNotEmpty()) {
                    $codes = $unavailable->map(fn (ShowtimeSeat $seat) => $seat->seat->full_code)->implode(', ');
                    throw new SeatHoldException("Ghế {$codes} vừa có người giữ hoặc đã bán. Vui lòng chọn ghế khác.");
                }

                foreach ($picked as $seat) {
                    $price = $seat->seat->zone?->priceFor($showtime->base_price)
                        ?? TicketTiers::seatPrice($seat->seat->type, $showtime->base_price);
                    $this->markHeld($seat, $user, $heldUntil, $price);
                }
                $held = $held->merge($picked);
            }

            // 2) Khu bán theo số lượng: lấy ghế trống đầu tiên của khu (hàng trước được gán trước),
            //    ghi ngay từng khu để khu sau không lấy trùng.
            foreach ($zoneQuantities as $zoneId => $quantity) {
                $zone = $zones[$zoneId];

                $assigned = ShowtimeSeat::select('showtime_seats.*')
                    ->join('seats', 'seats.id', '=', 'showtime_seats.seat_id')
                    ->where('showtime_seats.showtime_id', $showtime->id)
                    ->where('seats.zone_id', $zone->id)
                    ->holdable()
                    ->orderBy('showtime_seats.id')
                    ->limit($quantity)
                    ->lockForUpdate()
                    ->get();

                if ($assigned->count() < $quantity) {
                    throw new SeatHoldException("Khu \"{$zone->name}\" chỉ còn {$assigned->count()} chỗ.");
                }

                foreach ($assigned as $seat) {
                    $this->markHeld($seat, $user, $heldUntil, $zone->priceFor($showtime->base_price));
                }
                $held = $held->merge($assigned->load('seat'));
            }

            return $held;
        });
    }

    /** @throws SeatHoldException */
    public function release(User $user, ShowtimeSeat $seat): void
    {
        $released = ShowtimeSeat::whereKey($seat->id)
            ->where('status', 'held')
            ->where('held_by_user_id', $user->id)
            ->update(self::RELEASED);

        if ($released === 0) {
            throw new SeatHoldException('Ghế này không nằm trong giỏ vé của bạn.');
        }
    }

    public function releaseExpired(): int
    {
        return ShowtimeSeat::where('status', 'held')
            ->where('held_until', '<', now())
            ->update(self::RELEASED);
    }

    private function markHeld(ShowtimeSeat $seat, User $user, $heldUntil, int $price): void
    {
        $seat->update([
            'status' => 'held',
            'held_by_user_id' => $user->id,
            'held_until' => $heldUntil,
            'price_override' => $price,
        ]);
    }
}
