<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\RoomZone;
use App\Models\Showtime;
use App\Services\ShowtimeSeatGenerator;
use App\Services\ZoneLayoutGenerator;
use Illuminate\Database\Seeder;

class ConcertZoneVenueSeeder extends Seeder
{
    /**
     * Seed a full-scale concert venue with all zone kinds (seated, standing, table, admission)
     * to test the new Zone-based venue management and booking features.
     */
    public function run(ZoneLayoutGenerator $zoneLayouts, ShowtimeSeatGenerator $showtimeSeats): void
    {
        // 1. Tạo Khán Phòng Quy Mô Concert
        $room = Room::updateOrCreate(
            ['name' => 'Sân Vận Động Quốc Gia Mỹ Đình - Sơ Đồ Super Concert 2026'],
            [
                'address' => 'Đường Lê Đức Thọ, Phường Mỹ Đình 1, Quận Nam Từ Liêm, Hà Nội',
                'latitude' => 21.0205,
                'longitude' => 105.7638,
                'layout_preset' => 'outdoor_concert',
                'seat_config' => ['base_price' => 350000],
            ]
        );

        // 2. Định nghĩa các phân khu đa dạng (đầy đủ các kind: seated, standing, table, admission)
        $zoneDefinitions = [
            // Khu 1: SVIP Diamond sát mép sân khấu B-Stage (Seated)
            [
                'code' => 'SVIP',
                'name' => 'SVIP Diamond Kim Cương (B-Stage & Catwalk)',
                'kind' => RoomZone::KIND_SEATED,
                'seat_type' => 'svip_diamond',
                'ticket_tier' => 'svip_diamond',
                'rows' => 2,
                'cols' => 12, // 24 ghế
            ],
            // Khu 2: VIP Gold trực diện mặt sàn chính (Seated)
            [
                'code' => 'VIP',
                'name' => 'VIP Gold Mặt Sàn Trực Diện',
                'kind' => RoomZone::KIND_SEATED,
                'seat_type' => 'vip_gold',
                'ticket_tier' => 'vip_gold',
                'rows' => 3,
                'cols' => 14, // 42 ghế
            ],
            // Khu 3: Fanzone PIT Trái (Standing - Tồn kho số lượng, không sinh ghế ảo)
            [
                'code' => 'PIT-L',
                'name' => 'Fanzone Standing Pit Trái (GA Rock Zone)',
                'kind' => RoomZone::KIND_STANDING,
                'seat_type' => RoomZone::STANDING_TYPE,
                'ticket_tier' => 'standing_pit',
                'capacity' => 300,
            ],
            // Khu 4: Fanzone PIT Phải (Standing - Tồn kho số lượng, không sinh ghế ảo)
            [
                'code' => 'PIT-R',
                'name' => 'Fanzone Standing Pit Phải (GA Wave Zone)',
                'kind' => RoomZone::KIND_STANDING,
                'seat_type' => RoomZone::STANDING_TYPE,
                'ticket_tier' => 'standing_pit',
                'capacity' => 300,
            ],
            // Khu 5: Skybox VIP Table Lounge (Table - Bàn tiệc VIP, hỗ trợ chọn từng chỗ hoặc Đặt cả bàn)
            [
                'code' => 'TABLE',
                'name' => 'Royal Sky Lounge (Bàn Tiệc & Rượu Vang VIP)',
                'kind' => RoomZone::KIND_TABLE,
                'seat_type' => 'skybox_suite',
                'ticket_tier' => 'skybox_suite',
                'tables' => 6,
                'seats_per_table' => 6, // 36 ghế (6 bàn T01-T06)
            ],
            // Khu 6: Khán Đài CAT 1 Trung Tâm Tầng 1 (Seated)
            [
                'code' => 'CAT1',
                'name' => 'Khán Đài CAT 1 Trung Tâm Tầng 1',
                'kind' => RoomZone::KIND_SEATED,
                'seat_type' => 'cat1_stand',
                'ticket_tier' => 'cat1_stand',
                'rows' => 4,
                'cols' => 16, // 64 ghế
            ],
            // Khu 7: Khán Đài CAT 2 Cánh Tầng 2 (Seated)
            [
                'code' => 'CAT2',
                'name' => 'Khán Đài CAT 2 Cánh Gió Tầng 2',
                'kind' => RoomZone::KIND_SEATED,
                'seat_type' => 'cat2_wings',
                'ticket_tier' => 'cat2_wings',
                'rows' => 4,
                'cols' => 14, // 56 ghế
            ],
            // Khu 8: Vé Phổ Thông Vào Cửa Festival (Admission - Bán theo số lượng vé)
            [
                'code' => 'GA',
                'name' => 'Vé Vào Cửa Phổ Thông (Festival Pass)',
                'kind' => RoomZone::KIND_ADMISSION,
                'seat_type' => 'cat1_stand',
                'ticket_tier' => 'standard_pass',
                'capacity' => 1000,
            ],
        ];

        // 3. Khởi tạo / Cập nhật cấu trúc phân khu & ghế thật
        // Lưu ý: Nếu phòng đã có showtimes từ trước, xóa showtimes cũ của phòng test này để generate lại nếu cần
        if ($room->showtimes()->exists()) {
            foreach ($room->showtimes as $oldShowtime) {
                $oldShowtime->showtimeSeats()->delete();
                $oldShowtime->zoneTickets()->delete();
                $oldShowtime->delete();
            }
        }

        $zoneLayouts->generate($room, $zoneDefinitions);

        // 4. Tìm hoặc tạo Sự Kiện Concert quy mô lớn
        $category = Category::firstOrCreate(
            ['slug' => 'concert'],
            ['name' => 'Concert', 'description' => 'Đại nhạc hội bùng nổ, liveshow ca sĩ và đêm nhạc sống quy mô lớn.']
        );

        $event = Event::updateOrCreate(
            ['slug' => 'super-concert-dinh-cao-am-nhac-2026'],
            [
                'type' => 'concert',
                'title' => 'Super Concert 2026: Đỉnh Cao Âm Nhạc & Trình Diễn Đa Giác Quan',
                'category_id' => $category->id,
                'description' => 'Đại nhạc hội đỉnh cao hội tụ dàn nghệ sĩ hàng đầu cùng hệ thống sân khấu B-Stage, hiệu ứng laser 3D và không gian âm thanh vòm đỉnh cao tại SVĐ Quốc Gia Mỹ Đình.',
                'duration_minutes' => 240,
                'poster_path' => 'https://picsum.photos/seed/super-concert-2026/480/720',
                'status' => 'published',
                'is_seated' => true,
                'details' => [
                    'lineup' => ['SOOBIN', 'Sơn Tùng M-TP', 'Vũ Cát Tường', 'Đen Vâu', 'MONO', 'tlinh'],
                    'venue_name' => 'Sân Vận Động Quốc Gia Mỹ Đình',
                    'doors_open' => '17:00',
                    'age_restriction' => '12+',
                    'highlights' => ['Sân khấu Catwalk & B-Stage kim cương', 'Khu đứng Fanzone cuồng nhiệt', 'Sky Lounge thưởng tiệc rượu & cocktail'],
                ],
                'release_date' => now()->toDateString(),
            ]
        );

        // 5. Tạo Suất Diễn (Showtime) mở bán
        $showtime = Showtime::updateOrCreate(
            [
                'event_id' => $event->id,
                'room_id' => $room->id,
            ],
            [
                'start_time' => now()->addDays(2)->setTime(19, 30),
                'end_time' => now()->addDays(2)->setTime(23, 30),
                'base_price' => 350000,
            ]
        );

        // 6. Sinh Showtime Seats cho các ghế thật
        $showtimeSeats->regenerate($showtime);

        $this->command?->info("Đã tạo khán phòng concert [{$room->name}] (ID: {$room->id}) với sức chứa: {$room->capacity} chỗ.");
        $this->command?->info("Đã tạo suất diễn [ID: {$showtime->id}] cho sự kiện [{$event->title}].");
    }
}
