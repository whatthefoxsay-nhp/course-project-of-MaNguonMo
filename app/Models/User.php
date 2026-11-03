<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'phone',
        'avatar',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function heldSeats(): HasMany
    {
        return $this->hasMany(ShowtimeSeat::class, 'held_by_user_id');
    }

    public function getRoleNameAttribute(): string
    {
        return $this->hasRole('admin') ? 'Quản trị viên' : 'Khách hàng';
    }

    /**
     * Chữ cái đầu (tối đa 2) để hiện avatar dự phòng. Dùng mb_* vì tên tiếng Việt
     * có ký tự nhiều byte (Đ, Ă, ...), substr() sẽ cắt hỏng chữ.
     */
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/u', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY);

        if (! $words) {
            return '?';
        }

        $initials = mb_substr($words[0], 0, 1);
        if (count($words) > 1) {
            $initials .= mb_substr($words[count($words) - 1], 0, 1);
        }

        return mb_strtoupper($initials);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Đang hoạt động' : 'Đã bị khóa';
    }

    /**
     * URL ảnh đại diện, hoặc null nếu chưa có (giao diện dùng $user->initials làm dự phòng).
     * Ảnh trên Drive đi qua proxy cùng origin; đường dẫn cũ dùng URL storage; URL ngoài khác giữ nguyên.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            $fileId = $this->extractGoogleDriveFileId($this->avatar);
            if ($fileId !== null) {
                return route('users.avatar', $this).'?v='.rawurlencode($fileId);
            }

            return $this->avatar;
        }

        return Storage::disk('public')->url($this->avatar);
    }

    private function extractGoogleDriveFileId(string $url): ?string
    {
        $parts = parse_url($url);
        if (! in_array(strtolower((string) ($parts['host'] ?? '')), ['drive.google.com', 'docs.google.com'], true)) {
            return null;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $fileId = $query['id'] ?? null;

        if (! is_string($fileId) && preg_match('~/(?:file/)?d/([A-Za-z0-9_-]+)(?:/|$)~', (string) ($parts['path'] ?? ''), $matches)) {
            $fileId = $matches[1];
        }

        return is_string($fileId) && preg_match('/^[A-Za-z0-9_-]+$/', $fileId) ? $fileId : null;
    }
}
