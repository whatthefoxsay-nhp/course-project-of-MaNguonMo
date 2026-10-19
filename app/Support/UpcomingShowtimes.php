<?php

namespace App\Support;

use App\Models\BookingItem;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Suất diễn sắp tới mà người dùng đã có vé (booking "confirmed") — dùng cho
 * chuông thông báo và chấm nhắc ở mục "Vé của tôi" trên layout site.
 */
class UpcomingShowtimes
{
    /** Suất diễn bắt đầu trong khoảng này (giờ) được coi là "sắp diễn ra". */
    public const SOON_HOURS = 24;

    /** @return Collection<int, Showtime> */
    public static function for(?User $user, int $limit = 3): Collection
    {
        if (! $user) {
            return collect();
        }

        // Ghế có số (showtime_seats) hoặc vé khu vực (zone_tickets) — gom về showtime_id.
        $bookedShowtimeIds = BookingItem::query()
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->leftJoin('showtime_seats', 'showtime_seats.id', '=', 'booking_items.showtime_seat_id')
            ->leftJoin('zone_tickets', 'zone_tickets.id', '=', 'booking_items.zone_ticket_id')
            ->where('bookings.user_id', $user->id)
            ->where('bookings.status', 'confirmed')
            ->selectRaw('COALESCE(showtime_seats.showtime_id, zone_tickets.showtime_id)');

        return Showtime::query()
            ->with(['event:id,title,slug', 'room:id,name'])
            ->whereIn('id', $bookedShowtimeIds)
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }

    public static function isSoon(Showtime $showtime): bool
    {
        return $showtime->start_time->lte(now()->addHours(self::SOON_HOURS));
    }
}
