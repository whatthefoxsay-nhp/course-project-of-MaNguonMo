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
        'google_id',
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
     * URL ảnh đại diện, hoặc null nếu chưa có (giao diện dùng $user->initials làm dự phòng).
     * Không gọi dịch vụ ảnh bên ngoài để tạo avatar (không gửi tên người dùng ra ngoài).
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return Storage::disk('public')->url($this->avatar);
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
}
