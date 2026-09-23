<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Discount;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;
use App\Support\TicketTiers;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with Event categories, venues, events, showtimes and sample bookings.
     */
    public function run(RoomSeatGenerator $roomSeats, ShowtimeSeatGenerator $showtimeSeats): void
    {
        // 1. Setup Spatie Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // 2. Create Users & Staff
        $admin = User::firstOrCreate(
            ['email' => 'admin@ticketbox.vn'],
            [
                'name' => 'Quản Trị Viên (Admin)',
                'phone' => '0901234567',
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles([$adminRole]);

        $subAdmin = User::firstOrCreate(
            ['email' => 'moderator@ticketbox.vn'],
            [
                'name' => 'Trần Quản Lý (Moderator)',
                'phone' => '0908889999',
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $subAdmin->syncRoles([$adminRole]);

        $sampleUsers = [
            ['name' => 'Nguyễn Văn Khách', 'email' => 'user@ticketbox.vn', 'phone' => '0912345678', 'is_active' => true],
            ['name' => 'Lê Thu Trang', 'email' => 'lethutrang@gmail.com', 'phone' => '0987654321', 'is_active' => true],
            ['name' => 'Trần Đình Khôi', 'email' => 'trandinhkhoi@gmail.com', 'phone' => '0933112233', 'is_active' => false],
            ['name' => 'Phạm Minh Hoàng', 'email' => 'phamminhhoang@gmail.com', 'phone' => '0944556677', 'is_active' => true],
            ['name' => 'Hoàng Thu Thảo', 'email' => 'hoangthao@gmail.com', 'phone' => '0966778899', 'is_active' => true],
            ['name' => 'Nguyễn Hải Đăng', 'email' => 'nguyenhaidang@gmail.com', 'phone' => '0911223344', 'is_active' => false],
            ['name' => 'Vũ Minh Trí', 'email' => 'vuminhtri@gmail.com', 'phone' => '0977889900', 'is_active' => true],
            ['name' => 'Đặng Thị Mai', 'email' => 'dangthimai@gmail.com', 'phone' => '0922334455', 'is_active' => true],
            ['name' => 'Bùi Duy Khánh', 'email' => 'buiduykhanh@gmail.com', 'phone' => '0955667788', 'is_active' => true],
            ['name' => 'Nguyễn Kim Ngân', 'email' => 'nguyenkimngan@gmail.com', 'phone' => '0933445566', 'is_active' => true],
            ['name' => 'Đỗ Gia Huy', 'email' => 'dogiahuy@gmail.com', 'phone' => '0988990011', 'is_active' => true],
        ];

        $demoUser = null;
        foreach ($sampleUsers as $uData) {
            $u = User::firstOrCreate(
                ['email' => $uData['email']],
                [
                    'name' => $uData['name'],
                    'phone' => $uData['phone'],
                    'is_active' => $uData['is_active'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ]
            );
            $u->syncRoles([$userRole]);
            if ($uData['email'] === 'user@ticketbox.vn') {
                $demoUser = $u;
            }
        }

        // 3. Create Event Categories
        $categoriesData = [
            [
                'name' => 'Concert',
                'slug' => 'concert',
                'description' => 'Đại nhạc hội bùng nổ, liveshow ca sĩ và đêm nhạc sống quy mô lớn.',
            ],
            [
                'name' => 'Hòa Nhạc',
                'slug' => 'hoa-nhac',
                'description' => 'Đêm diễn giao hưởng thính phòng, thính phòng cổ điển, opera và hòa tấu nghệ thuật.',
            ],
            [
                'name' => 'Hội Thảo',
                'slug' => 'hoi-thao',
                'description' => 'Hội nghị công nghệ, diễn đàn kinh tế, tọa đàm chuyên gia và sự kiện kết nối doanh nghiệp.',
            ],
            [
                'name' => 'Triển Lãm',
                'slug' => 'trien-lam',
                'description' => 'Không gian nghệ thuật thị giác, triển lãm tương tác đa giác quan và hội chợ sáng tạo.',
            ],
            [
                'name' => 'Fan Meeting',
                'slug' => 'fan-meeting',
                'description' => 'Buổi gặp gỡ, giao lưu fansign, chụp ảnh và tương tác độc quyền cùng thần tượng & nghệ sĩ.',
            ],
            [
                'name' => 'Nhạc Kịch & Sân Khấu',
                'slug' => 'san-khau-kich',
                'description' => 'Vở kịch kinh điển, hài kịch sân khấu và nhạc kịch Broadway dàn dựng công phu.',
            ],
            [
                'name' => 'Festival & Lễ Hội',
                'slug' => 'festival',
                'description' => 'Lễ hội âm nhạc ngoài trời, lễ hội văn hóa - ẩm thực và sự kiện đếm ngược (countdown).',
            ],
            [
                'name' => 'Workshop & Masterclass',
                'slug' => 'workshop',
                'description' => 'Khóa đào tạo kỹ năng ngắn hạn, lớp học nghệ thuật và chia sẻ kinh nghiệm cùng chuyên gia.',
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['slug']] = Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        // 4. Khán phòng / địa điểm
        $rooms = [];
        foreach (self::VENUES as $key => $venue) {
            $rooms[$key] = Room::updateOrCreate(['name' => $venue['name']], $venue);
            if ($rooms[$key]->seats()->doesntExist()) {
                $roomSeats->generate($rooms[$key], $venue['layout_preset'], []);
            }
        }

        // 5. Sự kiện (nội dung chuyển từ DemoCatalog)
        $events = [];
        $basePrices = [];
        foreach (require __DIR__.'/data/events.php' as $row) {
            [, $type, $title, $slug, $demoCategoryId, $description, $duration, $basePrice, $isSeated, $details] = $row;

            $events[$slug] = Event::updateOrCreate(['slug' => $slug], [
                'type' => $type,
                'title' => $title,
                'category_id' => $categories[self::CATEGORY_BY_DEMO_ID[$demoCategoryId]]->id,
                'description' => $description,
                'duration_minutes' => $duration,
                'poster_path' => "https://picsum.photos/seed/{$slug}/480/720",
                'status' => 'published',
                'is_seated' => $isSeated,
                'details' => $details,
                'release_date' => now()->toDateString(),
            ]);
            $basePrices[$slug] = $basePrice;
        }

        // 6. Suất diễn (không chồng giờ trong cùng khán phòng)
        $today = Carbon::today();
        $showtimes = [];
        foreach (self::SCHEDULE as [$slug, $roomKey, $dayOffset, $start, $end]) {
            $startAt = $today->copy()->addDays($dayOffset)->setTimeFromTimeString($start);
            $showtime = Showtime::firstOrCreate(
                ['event_id' => $events[$slug]->id, 'room_id' => $rooms[$roomKey]->id, 'start_time' => $startAt],
                [
                    'end_time' => $today->copy()->addDays($dayOffset)->setTimeFromTimeString($end),
                    'base_price' => $basePrices[$slug],
                ]
            );
            $showtimeSeats->generate($showtime);
            $showtimes[$slug] ??= $showtime;
        }

        // 7. Đơn đặt vé mẫu cho tài khoản demo
        if ($demoUser) {
            $this->seedBooking($demoUser, $showtimes['hoa-nhac-giao-huong-saigon-philharmonic'], 2, 'TBX-89214', 'confirmed');
            $this->seedBooking($demoUser, $showtimes['nhac-kich-les-miserables-vn'], 1, 'TBX-77102', 'cancelled');
        }

        // 8. Bình luận mẫu
        Comment::firstOrCreate(
            ['user_id' => $demoUser?->id ?? $admin->id, 'event_id' => $events['hoa-nhac-giao-huong-saigon-philharmonic']->id],
            ['content' => 'Khán phòng cách âm tuyệt vời, dàn nhạc giao hưởng chơi xuất thần và rất truyền cảm hứng!']
        );
        Comment::firstOrCreate(
            ['user_id' => $admin->id, 'event_id' => $events['live-concert-anh-trai-vuot-ngan-chong-gai-2026']->id],
            ['content' => 'Sân khấu quy mô khủng, hệ thống ghế ngồi khán đài được phân luồng rất khoa học.']
        );

        // 9. Create Standard Discounts / Vouchers
        $discountsData = [
            [
                'code' => 'TICKETBOX2026',
                'title' => 'Ưu đãi chào mừng thành viên mới 2026',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'min_order_value' => 200000,
                'max_discount_amount' => 100000,
                'used_count' => 14,
                'max_uses' => 500,
                'is_active' => true,
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'applicable_to' => 'Tất cả sự kiện & concert',
            ],
            [
                'code' => 'VIPCONCERT30',
                'title' => 'Giảm 30% cho khách hàng VIP đặt vé sớm',
                'discount_type' => 'percentage',
                'discount_value' => 30,
                'min_order_value' => 500000,
                'max_discount_amount' => 300000,
                'used_count' => 8,
                'max_uses' => 100,
                'is_active' => true,
                'start_date' => now()->subMonths(1),
                'end_date' => now()->addMonths(6),
                'applicable_to' => 'Vé VIP & Super VIP',
            ],
            [
                'code' => 'EARLYBIRD50K',
                'title' => 'Voucher giảm 50.000đ khi đặt trước 7 ngày',
                'discount_type' => 'fixed',
                'discount_value' => 50000,
                'min_order_value' => 300000,
                'max_discount_amount' => 50000,
                'used_count' => 32,
                'max_uses' => 1000,
                'is_active' => true,
                'start_date' => now()->subMonths(2),
                'end_date' => now()->addMonths(8),
                'applicable_to' => 'Live Concert & Liveshow',
            ],
        ];

        foreach ($discountsData as $d) {
            Discount::firstOrCreate(['code' => $d['code']], $d);
        }
    }

    /** id danh mục trong DemoCatalog -> slug danh mục đã seed ở bước 3 */
    private const CATEGORY_BY_DEMO_ID = [
        1 => 'concert', 2 => 'hoa-nhac', 3 => 'hoi-thao', 4 => 'trien-lam',
        5 => 'fan-meeting', 6 => 'san-khau-kich', 7 => 'festival', 8 => 'workshop',
    ];

    private const VENUES = [
        'qk7' => [
            'name' => 'Sân Vận Động Quân Khu 7 (SVĐ QK7 Arena)',
            'address' => 'Số 202 Hoàng Văn Thụ, Phường 9, Quận Phú Nhuận, TP.HCM',
            'latitude' => 10.8005, 'longitude' => 106.6658,
            'capacity' => 20000, 'layout_preset' => 'mega_concert',
        ],
        'opera' => [
            'name' => 'Nhà Hát Thành Phố (Saigon Opera House)',
            'address' => '07 Công Trường Lam Sơn, Bến Nghé, Quận 1, TP.HCM',
            'latitude' => 10.7766, 'longitude' => 106.7032,
            'capacity' => 800, 'layout_preset' => 'theater_hall',
        ],
        'secc' => [
            'name' => 'Trung Tâm Hội Nghị & Triển Lãm SECC Hall A',
            'address' => '799 Nguyễn Văn Linh, Tân Phú, Quận 7, TP.HCM',
            'latitude' => 10.7302, 'longitude' => 106.7218,
            'capacity' => 3000, 'layout_preset' => 'convention_center',
        ],
        'hoabinh' => [
            'name' => 'Nhà Hát Hòa Bình Main Hall',
            'address' => '240 đường 3 Tháng 2, Phường 12, Quận 10, TP.HCM',
            'latitude' => 10.7712, 'longitude' => 106.6745,
            'capacity' => 2500, 'layout_preset' => 'theater_hall',
        ],
    ];

    /** [slug sự kiện, khán phòng, cách hôm nay (ngày), giờ bắt đầu, giờ kết thúc] */
    private const SCHEDULE = [
        ['live-concert-anh-trai-vuot-ngan-chong-gai-2026', 'qk7', 1, '19:00', '23:00'],
        ['live-concert-anh-trai-vuot-ngan-chong-gai-2026', 'qk7', 8, '19:00', '23:00'],
        ['saigon-autumn-music-light-festival-2026', 'qk7', 4, '17:00', '23:00'],
        ['hoa-nhac-giao-huong-saigon-philharmonic', 'opera', 2, '19:30', '21:30'],
        ['hoa-nhac-giao-huong-saigon-philharmonic', 'opera', 9, '19:30', '21:30'],
        ['nhac-kich-les-miserables-vn', 'opera', 3, '19:30', '22:15'],
        ['nhac-kich-les-miserables-vn', 'opera', 10, '19:30', '22:15'],
        ['vietnam-tech-summit-ai-expo-2026', 'secc', 5, '08:30', '17:00'],
        ['trien-lam-nghe-thuat-van-gogh-impressionism', 'secc', 6, '09:00', '18:00'],
        ['trien-lam-nghe-thuat-van-gogh-impressionism', 'secc', 7, '09:00', '18:00'],
        ['masterclass-sang-tao-phim-ai-2026', 'secc', 11, '09:00', '17:00'],
        ['fan-meeting-running-man-vn-2026', 'hoabinh', 3, '18:30', '21:00'],
    ];

    private function seedBooking(User $user, Showtime $showtime, int $seatCount, string $code, string $status): void
    {
        if (Booking::where('booking_code', $code)->exists()) {
            return;
        }

        $seats = $showtime->showtimeSeats()->with('seat')
            ->where('status', 'available')
            ->orderBy('id')
            ->take($seatCount)
            ->get();

        $prices = $seats->mapWithKeys(fn ($showtimeSeat) => [
            $showtimeSeat->id => TicketTiers::seatPrice($showtimeSeat->seat->type, $showtime->base_price),
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'booking_code' => $code,
            'total_price' => $prices->sum(),
            'status' => $status,
        ]);

        foreach ($seats as $showtimeSeat) {
            BookingItem::create([
                'booking_id' => $booking->id,
                'showtime_seat_id' => $showtimeSeat->id,
                'price' => $prices[$showtimeSeat->id],
            ]);

            if ($status === 'confirmed') {
                $showtimeSeat->update(['status' => 'booked']);
            }
        }
    }
}
