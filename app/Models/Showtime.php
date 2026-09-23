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

    public function availableSeatsCount(): int
    {
        return $this->showtimeSeats()->where('status', 'available')->count();
    }

    public function getSessionLabelAttribute(): string
    {
        $showtimes = $this->relationLoaded('event') && $this->event && $this->event->relationLoaded('showtimes')
            ? $this->event->showtimes->sortBy('start_time')->values()
            : ($this->event ? $this->event->showtimes()->orderBy('start_time')->get() : collect());

        $total = $showtimes->count();
        if ($total <= 1) {
            return 'Suất Diễn Duy Nhất';
        }

        $index = $showtimes->search(fn ($s) => $s->id === $this->id);
        $num = ($index !== false ? $index : 0) + 1;

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
        $showtimes = $this->relationLoaded('event') && $this->event && $this->event->relationLoaded('showtimes')
            ? $this->event->showtimes->sortBy('start_time')->values()
            : ($this->event ? $this->event->showtimes()->orderBy('start_time')->get() : collect());

        $total = $showtimes->count();
        if ($total <= 1) {
            return 'Suất Duy Nhất';
        }

        $index = $showtimes->search(fn ($s) => $s->id === $this->id);
        $num = ($index !== false ? $index : 0) + 1;

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
