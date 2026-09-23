<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with Event categories and mock data:
     * Concert, Hòa Nhạc, Hội Thảo, Triển Lãm, Fan Meeting, Nhạc Kịch, Festival, Workshop.
     */
    public function run(): void
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

        // 3. Create Event Categories according to specific categories requirements
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

        // 4. Create Event Venues / Auditoriums (Rooms)
        $room1 = Room::updateOrCreate(
            ['name' => 'Khán Phòng Hòa Nhạc Saigon Grand Hall'],
            [
                'address' => 'Nhà Hát Thành Phố, 07 Công Trường Lam Sơn, Bến Nghé, Quận 1, TP.HCM',
                'latitude' => 10.776600,
                'longitude' => 106.703200,
                'capacity' => 60,
            ]
        );

        $room2 = Room::updateOrCreate(
            ['name' => 'Trung Tâm Hội Nghị & Triển Lãm SECC Hall A'],
            [
                'address' => '799 Nguyễn Văn Linh, Tân Phú, Quận 7, TP.HCM',
                'latitude' => 10.730200,
                'longitude' => 106.721800,
                'capacity' => 80,
            ]
        );

        $room3 = Room::updateOrCreate(
            ['name' => 'Sân Khấu Live Concert Sân Vận Động QK7'],
            [
                'address' => 'Sân Vận Động Quân Khu 7, Hoàng Văn Thụ, Tân Bình, TP.HCM',
                'latitude' => 10.800500,
                'longitude' => 106.665800,
                'capacity' => 100,
            ]
        );

        $room4 = Room::updateOrCreate(
            ['name' => 'Nhà Hát Lớn Hòa Bình (Main Auditorium)'],
            [
                'address' => '240 đường 3 Tháng 2, Phường 12, Quận 10, TP.HCM',
                'latitude' => 10.771200,
                'longitude' => 106.674500,
                'capacity' => 70,
            ]
        );

        // 5. Generate Physical Seats for each Venue
        $this->generateSeatsForRoom($room1, ['A', 'B', 'C', 'D', 'E', 'F'], 10, ['E', 'F']);
        $this->generateSeatsForRoom($room2, ['A', 'B', 'C', 'D'], 20, ['A', 'B']);
        $this->generateSeatsForRoom($room3, ['A', 'B', 'C', 'D', 'E'], 20, ['D', 'E']);
        $this->generateSeatsForRoom($room4, ['A', 'B', 'C', 'D', 'E', 'F', 'G'], 10, ['A', 'B']);

        // 6. Create Events matching the categories
        $eventsData = [
            [
                'type' => 'event',
                'title' => 'Live Concert: Anh Trai Vượt Ngàn Chông Gai 2026',
                'slug' => 'live-concert-anh-trai-vuot-ngan-chong-gai-2026',
                'category_id' => $categories['concert']->id,
                'duration_minutes' => 240,
                'description' => 'Đại nhạc hội bùng nổ quy tụ hơn 30 anh tài hàng đầu với sân khấu 360 độ, hiệu ứng pháo hoa, laser tiêu chuẩn quốc tế.',
                'poster_path' => 'https://picsum.photos/seed/concert-anh-trai-2026/480/720',
                'status' => 'published',
                'release_date' => now()->addDays(15),
            ],
            [
                'type' => 'event',
                'title' => 'Đêm Hòa Nhạc Giao Hưởng: The Sound of Saigon Philharmonic',
                'slug' => 'hoa-nhac-giao-huong-saigon-philharmonic',
                'category_id' => $categories['hoa-nhac']->id,
                'duration_minutes' => 120,
                'description' => 'Hành trình âm nhạc thính phòng đỉnh cao đưa khán giả đắm chìm trong các kiệt tác của Mozart, Beethoven và Tchaikovsky.',
                'poster_path' => 'https://picsum.photos/seed/symphony-philharmonic/480/720',
                'status' => 'published',
                'release_date' => now()->addDays(5),
            ],
            [
                'type' => 'event',
                'title' => 'Vietnam Tech Summit & Global AI Expo 2026',
                'slug' => 'vietnam-tech-summit-ai-expo-2026',
                'category_id' => $categories['hoi-thao']->id,
                'duration_minutes' => 480,
                'description' => 'Diễn đàn trí tuệ nhân tạo và công nghệ tương lai hàng đầu với hơn 50 diễn giả chuyên gia từ Google, OpenAI, Microsoft và Nvidia.',
                'poster_path' => 'https://picsum.photos/seed/tech-summit-expo/480/720',
                'status' => 'published',
                'release_date' => now()->addDays(10),
            ],
            [
                'type' => 'event',
                'title' => 'Triển Lãm Đa Giác Quan: Van Gogh & Impressionism Experience',
                'slug' => 'trien-lam-nghe-thuat-van-gogh-impressionism',
                'category_id' => $categories['trien-lam']->id,
                'duration_minutes' => 90,
                'description' => 'Không gian ánh sáng tương tác đa giác quan 360 độ đưa người xem đắm chìm vào thế giới hội họa kiệt tác của Vincent Van Gogh.',
                'poster_path' => 'https://picsum.photos/seed/van-gogh-exhibition/480/720',
                'status' => 'published',
                'release_date' => now()->addDays(2),
            ],
            [
                'type' => 'event',
                'title' => 'Fan Meeting 2026: Gặp Gỡ & Ký Tặng Dàn Cast Running Man VN',
                'slug' => 'fan-meeting-running-man-vn-2026',
                'category_id' => $categories['fan-meeting']->id,
                'duration_minutes' => 150,
                'description' => 'Đêm fan meeting độc quyền giao lưu, ký tặng fansign 1:1, biểu diễn âm nhạc acoustic và chụp ảnh polaroid cùng thần tượng.',
                'poster_path' => 'https://picsum.photos/seed/fan-meeting-special/480/720',
                'status' => 'published',
                'release_date' => now()->addDays(12),
            ],
            [
                'type' => 'event',
                'title' => 'Vở Nhạc Kịch Broadway: Những Người Khốn Khổ (Les Misérables VN)',
                'slug' => 'nhac-kich-les-miserables-vn',
                'category_id' => $categories['san-khau-kich']->id,
                'duration_minutes' => 165,
                'description' => 'Tác phẩm nhạc kịch kinh điển thế giới được chuyển soạn công phu với dàn hợp xướng 80 người và phục trang hoàng gia lộng lẫy.',
                'poster_path' => 'https://picsum.photos/seed/les-miserables-musical/480/720',
                'status' => 'published',
                'release_date' => now()->addDays(8),
            ],
            [
                'type' => 'event',
                'title' => 'Saigon Autumn Music & Light Festival 2026',
                'slug' => 'saigon-autumn-music-light-festival-2026',
                'category_id' => $categories['festival']->id,
                'duration_minutes' => 360,
                'description' => 'Lễ hội âm nhạc ngoài trời kết hợp trình diễn nghệ thuật ánh sáng 3D mapping quy mô 20.000 khán giả bên bờ sông Sài Gòn.',
                'poster_path' => 'https://picsum.photos/seed/music-festival-light/480/720',
                'status' => 'published',
                'release_date' => now()->addDays(20),
            ],
            [
                'type' => 'event',
                'title' => 'Masterclass: Nghệ Thuật Kể Chuyện & Sáng Tạo Phim Bằng AI',
                'slug' => 'masterclass-sang-tao-phim-ai-2026',
                'category_id' => $categories['workshop']->id,
                'duration_minutes' => 240,
                'description' => 'Buổi workshop thực hành 1 ngày cùng các đạo diễn và giám đốc sáng tạo hàng đầu về ứng dụng AI tạo sinh trong truyền thông.',
                'poster_path' => 'https://picsum.photos/seed/workshop-masterclass/480/720',
                'status' => 'published',
                'release_date' => now()->addDays(6),
            ],
        ];

        $createdEvents = [];
        foreach ($eventsData as $eData) {
            $createdEvents[] = Event::updateOrCreate(['slug' => $eData['slug']], $eData);
        }

        // 7. Create Event Schedules / Showtimes
        $now = Carbon::today();

        // Showtime 1: Concert in Stadium (Tomorrow 19:00)
        $st1 = Showtime::firstOrCreate(
            ['event_id' => $createdEvents[0]->id, 'room_id' => $room3->id, 'start_time' => $now->copy()->addDay()->setTime(19, 00)],
            ['end_time' => $now->copy()->addDay()->setTime(23, 00), 'base_price' => 250000]
        );
        $this->generateShowtimeSeats($st1);

        // Showtime 2: Symphony in Saigon Grand Hall (Today 19:30)
        $st2 = Showtime::firstOrCreate(
            ['event_id' => $createdEvents[1]->id, 'room_id' => $room1->id, 'start_time' => $now->copy()->setTime(19, 30)],
            ['end_time' => $now->copy()->setTime(21, 30), 'base_price' => 180000]
        );
        $this->generateShowtimeSeats($st2);

        // Showtime 3: Tech Summit in SECC (In 3 days 08:30)
        $st3 = Showtime::firstOrCreate(
            ['event_id' => $createdEvents[2]->id, 'room_id' => $room2->id, 'start_time' => $now->copy()->addDays(3)->setTime(8, 30)],
            ['end_time' => $now->copy()->addDays(3)->setTime(17, 00), 'base_price' => 300000]
        );
        $this->generateShowtimeSeats($st3);

        // Showtime 4: Fan Meeting in Hoa Binh Theatre (In 5 days 18:30)
        $st4 = Showtime::firstOrCreate(
            ['event_id' => $createdEvents[4]->id, 'room_id' => $room4->id, 'start_time' => $now->copy()->addDays(5)->setTime(18, 30)],
            ['end_time' => $now->copy()->addDays(5)->setTime(21, 00), 'base_price' => 220000]
        );
        $this->generateShowtimeSeats($st4);

        // Showtime 5: Van Gogh Exhibition in SECC (In 2 days 09:00)
        $st5 = Showtime::firstOrCreate(
            ['event_id' => $createdEvents[3]->id, 'room_id' => $room2->id, 'start_time' => $now->copy()->addDays(2)->setTime(9, 00)],
            ['end_time' => $now->copy()->addDays(2)->setTime(18, 00), 'base_price' => 150000]
        );
        $this->generateShowtimeSeats($st5);

        // 8. Create Sample Confirmed Bookings for demoUser
        if ($demoUser) {
            $sampleSeat1 = $st2->showtimeSeats()->first();
            if ($sampleSeat1) {
                $sampleSeat1->update(['status' => 'booked']);

                $booking = Booking::firstOrCreate(
                    ['booking_code' => 'TBX-89214'],
                    [
                        'user_id' => $demoUser->id,
                        'total_price' => 180000,
                        'status' => 'confirmed',
                    ]
                );

                BookingItem::firstOrCreate(
                    ['booking_id' => $booking->id, 'showtime_seat_id' => $sampleSeat1->id],
                    ['price' => 180000]
                );
            }
        }

        // 9. Create Sample Comments
        Comment::firstOrCreate(
            ['user_id' => $demoUser ? $demoUser->id : $admin->id, 'event_id' => $createdEvents[1]->id],
            ['content' => 'Khán phòng cách âm tuyệt vời, dàn nhạc giao hưởng chơi xuất thần và rất truyền cảm hứng!']
        );

        Comment::firstOrCreate(
            ['user_id' => $admin->id, 'event_id' => $createdEvents[0]->id],
            ['content' => 'Sân khấu quy mô khủng, hệ thống ghế ngồi khán đài được phân luồng rất khoa học.']
        );

        // 10. Create Standard Discounts / Vouchers
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
            \App\Models\Discount::firstOrCreate(['code' => $d['code']], $d);
        }
    }

    private function generateSeatsForRoom(Room $room, array $rows, int $seatsPerRow, array $vipRows = []): void
    {
        foreach ($rows as $row) {
            for ($num = 1; $num <= $seatsPerRow; $num++) {
                $isVip = in_array($row, $vipRows, true);
                Seat::firstOrCreate(
                    ['room_id' => $room->id, 'row_label' => $row, 'seat_number' => $num],
                    ['type' => $isVip ? 'vip' : 'normal']
                );
            }
        }
    }

    private function generateShowtimeSeats(Showtime $showtime): void
    {
        $seats = Seat::where('room_id', $showtime->room_id)->get();
        foreach ($seats as $seat) {
            ShowtimeSeat::firstOrCreate(
                ['showtime_id' => $showtime->id, 'seat_id' => $seat->id],
                ['status' => 'available']
            );
        }
    }
}

