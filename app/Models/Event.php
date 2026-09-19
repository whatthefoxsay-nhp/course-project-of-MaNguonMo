<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'release_date' => 'date',
        'duration_minutes' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title);
            }
        });
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

    public function isEvent(): bool
    {
        return true;
    }
}
