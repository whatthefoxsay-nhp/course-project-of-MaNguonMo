<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Showtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'room_id',
        'start_time',
        'end_time',
        'base_price',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'base_price' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function movie(): BelongsTo
    {
        return $this->event();
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function showtimeSeats(): HasMany
    {
        return $this->hasMany(ShowtimeSeat::class);
    }

    public function zoneTickets(): HasMany
    {
        return $this->hasMany(ZoneTicket::class);
    }

    /** Suất diễn đã phát sinh đơn vé (kể cả đơn đã hủy) — xóa/đổi sự kiện sẽ làm hỏng lịch sử đơn. */
    public function hasBookingItems(): bool
    {
        return BookingItem::forShowtimes([$this->id])->exists();
    }

    public function availableSeatsCount(): int
    {
        return $this->showtimeSeats()->where('status', 'available')->count();
    }

    /**
     * Gắn sẵn sự kiện (kèm toàn bộ suất diễn) cho các suất cần hiển thị session_label,
     * tránh mỗi suất tự query lại sự kiện + danh sách suất (N+1).
     *
     * @param  iterable<Showtime>  $showtimes
     */
    public static function primeSessionLabels(Event $event, iterable $showtimes): void
    {
        if (! $event->relationLoaded('showtimes')) {
            $event->setRelation('showtimes', $event->showtimes()->orderBy('start_time')->get());
        }

        foreach ($showtimes as $showtime) {
            $showtime->setRelation('event', $event);
        }
    }

    /** @return array{0: int, 1: int} [thứ tự suất (từ 1), tổng số suất của sự kiện] */
    private function sessionPosition(): array
    {
        $event = $this->event;
        if (! $event) {
            return [1, 0];
        }
        if (! $event->relationLoaded('showtimes')) {
            $event->setRelation('showtimes', $event->showtimes()->orderBy('start_time')->get());
        }

        $showtimes = $event->showtimes->sortBy('start_time')->values();
        $index = $showtimes->search(fn ($s) => $s->id === $this->id);

        return [($index !== false ? $index : 0) + 1, $showtimes->count()];
    }

    public function getSessionLabelAttribute(): string
    {
        [$num, $total] = $this->sessionPosition();
        if ($total <= 1) {
            return 'Suất Diễn Duy Nhất';
        }

        $categorySlug = $this->event?->category?->slug ?? '';
        if ($categorySlug === 'concert') {
            return $num === 1 ? 'Đêm 1 • Opening Night' : 'Đêm 2 • Grand Finale';
        }
        if ($categorySlug === 'trien-lam') {
            return $num === 1 ? 'Ngày 1 • Khai Mạc Trải Nghiệm' : 'Ngày 2 • Trải Nghiệm Chính Thức';
        }
        if ($categorySlug === 'san-khau-kich') {
            return $num === 1 ? 'Suất 1 • Đêm Khai Màn' : 'Suất 2 • Đêm Bế Mạc';
        }

        return "Suất Diễn {$num}";
    }

    public function getSessionShortLabelAttribute(): string
    {
        [$num, $total] = $this->sessionPosition();
        if ($total <= 1) {
            return 'Suất Duy Nhất';
        }

        $categorySlug = $this->event?->category?->slug ?? '';
        if ($categorySlug === 'concert') {
            return "Đêm {$num}";
        }
        if ($categorySlug === 'trien-lam') {
            return "Ngày {$num}";
        }

        return "Suất {$num}";
    }
}
