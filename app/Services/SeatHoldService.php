<?php

namespace App\Services;

use App\Exceptions\SeatHoldException;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\RoomZone;
use App\Models\ZoneTicket;
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
     * @param  array<string, int>  $tierQuantities  hạng bán theo số lượng => số vé (server tự gán ghế)
     * @return Collection<int, ShowtimeSeat>
     *
     * @throws SeatHoldException
     */
    public function hold(User $user, Showtime $showtime, array $showtimeSeatIds, array $tierQuantities = [], array $zoneQuantities = []): Collection
    {
        $showtime->loadMissing('event', 'room');
        $event = $showtime->event;
        $tierQuantities = array_filter($tierQuantities, fn (int $quantity) => $quantity > 0);
        $zoneQuantities = array_filter($zoneQuantities, fn (int $quantity) => $quantity > 0);

        // Backward compatibility for clients that still submit standing_pit as a pass tier.
        if (($tierQuantities['standing_pit'] ?? 0) > 0) {
            $standingZone = RoomZone::where('room_id', $showtime->room_id)->where('kind', RoomZone::KIND_STANDING)->first();
            if ($standingZone) {
                $zoneQuantities[$standingZone->id] = ($zoneQuantities[$standingZone->id] ?? 0) + $tierQuantities['standing_pit'];
                unset($tierQuantities['standing_pit']);
            }
        }

        if ($event->status !== 'published') {
            throw new SeatHoldException('Sự kiện hiện không mở bán.');
        }
        if ($showtime->start_time->isPast()) {
            throw new SeatHoldException('Suất diễn đã bắt đầu, không thể đặt vé.');
        }
        if ($showtimeSeatIds !== [] && ! $event->is_seated && ! $showtime->room->isZoned()) {
            throw new SeatHoldException('Sự kiện này không chọn ghế trên sơ đồ, vui lòng chọn hạng vé.');
        }
        foreach (array_keys($tierQuantities) as $tier) {
            if (! TicketTiers::isPassTier($tier, $event->is_seated)) {
                throw new SeatHoldException('Hạng vé không hợp lệ.');
            }
        }
        $zones = RoomZone::where('room_id', $showtime->room_id)->whereIn('id', array_keys($zoneQuantities))->get()->keyBy('id');
        if ($zones->count() !== count($zoneQuantities) || $zones->contains(fn (RoomZone $zone) => ! $zone->sellsByQuantity())) {
            throw new SeatHoldException('Khu vé theo số lượng không hợp lệ.');
        }

        $requestedSeats = count($showtimeSeatIds);
        foreach ($tierQuantities as $tier => $quantity) {
            $requestedSeats += $quantity * TicketTiers::seatsPerUnit($tier);
        }
        $requestedSeats += array_sum($zoneQuantities);
        if ($requestedSeats === 0) {
            throw new SeatHoldException('Vui lòng chọn ít nhất 1 vé.');
        }

        return DB::transaction(function () use ($user, $showtime, $event, $showtimeSeatIds, $tierQuantities, $zoneQuantities, $requestedSeats) {
            // Mỗi user chỉ giữ ghế của 1 suất tại 1 thời điểm.
            ShowtimeSeat::where('held_by_user_id', $user->id)
                ->where('status', 'held')
                ->where('showtime_id', '!=', $showtime->id)
                ->update(self::RELEASED);

            ZoneTicket::where('held_by_user_id', $user->id)
                ->where('status', 'held')
                ->where('showtime_id', '!=', $showtime->id)
                ->where('held_until', '>', now())
                ->delete();

            $alreadyHeld = ShowtimeSeat::where('showtime_id', $showtime->id)->heldBy($user)->count();
            $alreadyHeld += ZoneTicket::where('showtime_id', $showtime->id)->where('status', 'held')->where('held_by_user_id', $user->id)->where('held_until', '>', now())->count();
            if ($alreadyHeld + $requestedSeats > self::MAX_SEATS) {
                throw new SeatHoldException('Mỗi lượt đặt tối đa '.self::MAX_SEATS." chỗ (bạn đang giữ {$alreadyHeld} chỗ).");
            }

            $heldUntil = now()->addMinutes(self::HOLD_MINUTES);
            $held = new Collection;

            // 1) Ghế khách tự chọn: khóa dòng rồi mới kiểm tra còn trống (chống 2 người cùng giữ).
            if ($showtimeSeatIds !== []) {
                $picked = ShowtimeSeat::with('seat')
                    ->where('showtime_id', $showtime->id)
                    ->whereIn('id', $showtimeSeatIds)
                    ->lockForUpdate()
                    ->get();

                if ($picked->count() !== count(array_unique($showtimeSeatIds))) {
                    throw new SeatHoldException('Có ghế không thuộc suất diễn này.');
                }
                if ($picked->contains(fn (ShowtimeSeat $seat) => $seat->seat->type === 'standing_pit')) {
                    throw new SeatHoldException('Vé đứng được bán theo số lượng, không chọn theo ghế.');
                }

                $unavailable = $picked->reject(fn (ShowtimeSeat $seat) => $seat->isHoldable());
                if ($unavailable->isNotEmpty()) {
                    $codes = $unavailable->map(fn (ShowtimeSeat $seat) => $seat->seat->full_code)->implode(', ');
                    throw new SeatHoldException("Ghế {$codes} vừa có người giữ hoặc đã bán. Vui lòng chọn ghế khác.");
                }

                foreach ($picked as $seat) {
                    $this->markHeld($seat, $user, $heldUntil, TicketTiers::seatPrice($seat->seat->type, $showtime->base_price));
                }
                $held = $held->merge($picked);
            }

            // 2) Hạng bán theo số lượng: server tự lấy ghế trống đúng loại (ghi ngay từng hạng
            //    để hạng sau không lấy trùng ghế của hạng trước).
            foreach ($tierQuantities as $tier => $quantity) {
                $needed = $quantity * TicketTiers::seatsPerUnit($tier);

                $assigned = ShowtimeSeat::select('showtime_seats.*')
                    ->join('seats', 'seats.id', '=', 'showtime_seats.seat_id')
                    ->where('showtime_seats.showtime_id', $showtime->id)
                    ->whereIn('seats.type', TicketTiers::passSeatTypes($tier))
                    ->holdable()
                    ->orderBy('showtime_seats.id')
                    ->limit($needed)
                    ->lockForUpdate()
                    ->get();

                if ($assigned->count() < $needed) {
                    $tierName = TicketTiers::for($showtime->base_price, $event->is_seated)[$tier]['name'];
                    throw new SeatHoldException("Hạng vé \"{$tierName}\" chỉ còn {$assigned->count()} chỗ.");
                }

                $pricePerSeat = TicketTiers::pricePerSeat($tier, $showtime->base_price, $event->is_seated);
                foreach ($assigned as $seat) {
                    $this->markHeld($seat, $user, $heldUntil, $pricePerSeat);
                }
                $held = $held->merge($assigned->load('seat'));
            }

            foreach ($zoneQuantities as $zoneId => $quantity) {
                $zone = RoomZone::whereKey($zoneId)->where('room_id', $showtime->room_id)->lockForUpdate()->first();
                if (! $zone || ! $zone->sellsByQuantity()) {
                    throw new SeatHoldException('Khu vé không tồn tại hoặc không bán theo số lượng.');
                }

                $occupied = ZoneTicket::where('showtime_id', $showtime->id)->where('zone_id', $zone->id)
                    ->where(fn ($query) => $query->where('status', 'booked')->orWhere(fn ($held) => $held->where('status', 'held')->where('held_until', '>', now())))
                    ->count();
                if ($occupied + $quantity > $zone->capacity) {
                    throw new SeatHoldException("Khu {$zone->name} chỉ còn ".max(0, $zone->capacity - $occupied).' vé.');
                }

                $tier = $zone->ticket_tier ?: ($zone->kind === RoomZone::KIND_STANDING && $event->is_seated ? 'standing_pit' : 'standard_pass');
                $tiers = TicketTiers::for($showtime->base_price, $event->is_seated);
                if (! isset($tiers[$tier])) {
                    throw new SeatHoldException('Hạng vé của khu vực chưa được cấu hình cho sự kiện này.');
                }
                $price = (int) $tiers[$tier]['price'];
                for ($index = 0; $index < $quantity; $index++) {
                    ZoneTicket::create([
                        'showtime_id' => $showtime->id,
                        'zone_id' => $zone->id,
                        'status' => 'held',
                        'held_by_user_id' => $user->id,
                        'held_until' => $heldUntil,
                        'price' => $price,
                    ]);
                }
            }

            // Giỏ vé có 1 đồng hồ chung: giữ thêm vé thì gia hạn luôn các vé đang giữ của suất này,
            // tránh trường hợp vé cũ hết hạn trước và checkout chỉ đặt được một phần giỏ.
            ShowtimeSeat::where('showtime_id', $showtime->id)->heldBy($user)->update(['held_until' => $heldUntil]);
            ZoneTicket::where('showtime_id', $showtime->id)->where('status', 'held')
                ->where('held_by_user_id', $user->id)->where('held_until', '>', now())
                ->update(['held_until' => $heldUntil]);

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

    /** @throws SeatHoldException */
    public function releaseZoneTicket(User $user, ZoneTicket $ticket): void
    {
        $released = ZoneTicket::whereKey($ticket->id)->where('status', 'held')
            ->where('held_by_user_id', $user->id)->where('held_until', '>', now())->delete();
        if ($released === 0) {
            throw new SeatHoldException('Vé này không nằm trong giỏ vé của bạn.');
        }
    }

    public function releaseExpired(): int
    {
        $seats = ShowtimeSeat::where('status', 'held')
            ->where('held_until', '<', now())
            ->update(self::RELEASED);
        $zones = ZoneTicket::where('status', 'held')->where('held_until', '<', now())->delete();

        return $seats + $zones;
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
