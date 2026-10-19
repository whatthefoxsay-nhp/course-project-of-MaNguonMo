<?php

namespace App\Services;

use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Event;
use App\Models\ShowtimeSeat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Số liệu doanh thu/lấp đầy cho Dashboard và Báo cáo, tính bằng truy vấn tổng hợp
 * (không eager-load toàn bộ ghế + vé của mọi suất diễn).
 *
 * Doanh thu = tổng booking_items.price của các đơn "confirmed", gồm cả vé chọn ghế
 * (showtime_seat_id) lẫn vé theo khu (zone_ticket_id). Đơn có mã giảm giá thì phần giảm được
 * phân bổ theo tỉ lệ giá từng vé, để tổng theo sự kiện/danh mục khớp "Tổng doanh thu" (bookings.total_price).
 */
class SalesReportService
{
    /** Giá vé sau khi trừ phần giảm giá của đơn: price × total_price / (total_price + discount_amount). */
    private const NET_ITEM_REVENUE = 'COALESCE(ROUND(SUM(booking_items.price * bookings.total_price * 1.0 '
        .'/ NULLIF(bookings.total_price + COALESCE(bookings.discount_amount, 0), 0))), 0)';

    /** @var Collection<int, array{revenue: int, tickets: int}>|null */
    private ?Collection $eventTotals = null;

    /** @return Collection<int, array{revenue: int, tickets: int}> theo event_id */
    public function revenueByEventId(): Collection
    {
        return $this->eventTotals ??= BookingItem::query()
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->leftJoin('showtime_seats', 'showtime_seats.id', '=', 'booking_items.showtime_seat_id')
            ->leftJoin('zone_tickets', 'zone_tickets.id', '=', 'booking_items.zone_ticket_id')
            ->join('showtimes', 'showtimes.id', '=', DB::raw('COALESCE(showtime_seats.showtime_id, zone_tickets.showtime_id)'))
            ->where('bookings.status', 'confirmed')
            ->groupBy('showtimes.event_id')
            ->selectRaw('showtimes.event_id as event_id, '.self::NET_ITEM_REVENUE.' as revenue, COUNT(*) as tickets')
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->event_id => ['revenue' => (int) $row->revenue, 'tickets' => (int) $row->tickets]]);
    }

    /** @return Collection<int, array{id: int, title: string, category: string, revenue: int, tickets: int}> */
    public function eventsRevenue(): Collection
    {
        $totals = $this->revenueByEventId();

        return Event::with('category')->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'category' => $event->category?->name ?? 'Sự kiện',
                'revenue' => $totals[$event->id]['revenue'] ?? 0,
                'tickets' => $totals[$event->id]['tickets'] ?? 0,
            ])
            ->sortByDesc('revenue')
            ->values();
    }

    /** @return Collection<int, array{name: string, revenue: int, tickets: int}> */
    public function categoriesRevenue(): Collection
    {
        $totals = $this->revenueByEventId();
        $eventsByCategory = Event::query()->get(['id', 'category_id'])->groupBy('category_id');

        return Category::query()->get()->map(function (Category $category) use ($totals, $eventsByCategory) {
            $eventIds = $eventsByCategory->get($category->id, collect())->pluck('id');

            return [
                'name' => $category->name,
                'revenue' => $eventIds->sum(fn ($id) => $totals[$id]['revenue'] ?? 0),
                'tickets' => $eventIds->sum(fn ($id) => $totals[$id]['tickets'] ?? 0),
            ];
        });
    }

    /**
     * Số ghế theo suất diễn và số ghế đã bán, gom theo một cột của bảng showtimes.
     *
     * @param  'room_id'|'event_id'  $column
     * @return Collection<int, array{total: int, booked: int}>
     */
    public function seatOccupancyBy(string $column): Collection
    {
        abort_unless(in_array($column, ['room_id', 'event_id'], true), 500);

        return ShowtimeSeat::query()
            ->join('showtimes', 'showtimes.id', '=', 'showtime_seats.showtime_id')
            ->groupBy("showtimes.{$column}")
            ->selectRaw("showtimes.{$column} as group_id, COUNT(*) as total, SUM(CASE WHEN showtime_seats.status = 'booked' THEN 1 ELSE 0 END) as booked")
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->group_id => ['total' => (int) $row->total, 'booked' => (int) $row->booked]]);
    }
}
