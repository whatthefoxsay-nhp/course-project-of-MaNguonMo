<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Event extends Model
{
    use GeneratesUniqueSlug, HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'type',
        'title',
        'slug',
        'description',
        'poster_path',
        'banner_path',
        'category_id',
        'duration_minutes',
        'status',
        'release_date',
        'is_seated',
        'details',
    ];

    protected $casts = [
        'release_date' => 'date',
        'duration_minutes' => 'integer',
        'is_seated' => 'boolean',
        'details' => 'array',
    ];

    /** Các key trong cột JSON `details`, đọc được như thuộc tính thường: $event->venue_name */
    public const DETAIL_KEYS = [
        'venue_name', 'venue_address', 'venue_gates', 'parking_info',
        'participants_title', 'participants_summary', 'host_mc', 'special_guests',
        'timeline', 'lineup', 'organizers', 'entry_policy',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = static::uniqueSlug($event->title);
            }
        });
    }

    public function getAttribute($key)
    {
        if (in_array($key, self::DETAIL_KEYS, true)) {
            return $this->details[$key] ?? null;
        }

        return parent::getAttribute($key);
    }

    /** Tên cũ các view đang dùng. */
    public function getIsSeatedConcertAttribute(): bool
    {
        return (bool) $this->is_seated;
    }

    /**
     * Giá cơ bản thấp nhất trong các suất diễn (hiển thị "Từ ...₫").
     * Controller danh sách nên gọi withMin('showtimes', 'base_price') để tránh N+1.
     */
    public function getBasePriceAttribute(): ?int
    {
        $min = array_key_exists('showtimes_min_base_price', $this->attributes)
            ? $this->attributes['showtimes_min_base_price']
            : $this->showtimes()->min('base_price');

        return $min === null ? null : (int) $min;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class, 'event_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'event_id');
    }

    public function getPosterUrlAttribute(): string
    {
        if ($this->poster_path) {
            if (str_starts_with($this->poster_path, 'http://') || str_starts_with($this->poster_path, 'https://')) {
                return $this->poster_path;
            }
            if (Storage::disk('public')->exists($this->poster_path)) {
                return Storage::url($this->poster_path);
            }
        }
        return "https://picsum.photos/seed/{$this->slug}/480/720";
    }
}
