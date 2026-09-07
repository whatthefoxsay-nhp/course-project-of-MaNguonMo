<?php

namespace App\Support;

/**
 * Temporary dummy data source for UI-first development (spec section 8, "UI trước BE").
 *
 * Every returned object uses the exact field names the real Eloquent models will have
 * (see docs/superpowers/specs/2026-09-07-ticket-booking-platform-design.md section 3),
 * so Phase 1 BE work swaps this class for real queries without touching any Blade view.
 * Delete this class once real models/controllers exist.
 */
class DemoCatalog
{
    public static function categories(): array
    {
        return [
            (object) ['id' => 1, 'name' => 'Hành động'],
            (object) ['id' => 2, 'name' => 'Tình cảm'],
            (object) ['id' => 3, 'name' => 'Hoạt hình'],
            (object) ['id' => 4, 'name' => 'Kinh dị'],
            (object) ['id' => 5, 'name' => 'Sự kiện âm nhạc'],
        ];
    }

    public static function movies(): array
    {
        $categories = collect(self::categories())->keyBy('id');

        $rows = [
            [1, 'movie', 'Lật Mặt 7: Một Điều Ước', 'lat-mat-7-mot-dieu-uoc', 1,
                'Bốn chị em ruột cùng nhau thực hiện điều ước cuối cùng cho mẹ.', 118],
            [2, 'movie', 'Mai', 'mai', 2,
                'Câu chuyện tình yêu giữa một nhân viên massage và một tay chơi hào hoa.', 131],
            [3, 'movie', 'Wolfoo Và Cuộc Đua Tam Giới', 'wolfoo-va-cuoc-dua-tam-gioi', 3,
                'Wolfoo cùng bạn bè phiêu lưu qua ba vùng đất kỳ diệu.', 95],
            [4, 'movie', 'Quỷ Ăn Tạng', 'quy-an-tang', 4,
                'Một gia đình chuyển đến căn nhà cũ và phát hiện thế lực siêu nhiên.', 102],
            [5, 'event', 'Đêm Nhạc Acoustic Ngoài Trời', 'dem-nhac-acoustic-ngoai-troi', 5,
                'Đêm nhạc acoustic ngoài trời với các nghệ sĩ indie nổi bật.', null],
        ];

        return collect($rows)->map(function ($r) use ($categories) {
            [$id, $type, $title, $slug, $categoryId, $description, $duration] = $r;

            return (object) [
                'id' => $id,
                'type' => $type,
                'title' => $title,
                'slug' => $slug,
                'description' => $description,
                'poster_path' => "https://picsum.photos/seed/{$slug}/480/720",
                'duration_minutes' => $duration,
                'status' => 'published',
                'category' => $categories[$categoryId],
            ];
        })->all();
    }

    public static function movieBySlug(string $slug): ?object
    {
        return collect(self::movies())->firstWhere('slug', $slug);
    }

    public static function rooms(): array
    {
        return [
            (object) ['id' => 1, 'name' => 'Phòng 1 - CGV Landmark', 'address' => '720A Điện Biên Phủ, Quận Bình Thạnh, TP.HCM', 'latitude' => 10.7955, 'longitude' => 106.7218, 'capacity' => 40],
            (object) ['id' => 2, 'name' => 'Phòng 2 - Galaxy Nguyễn Du', 'address' => '116 Nguyễn Du, Quận 1, TP.HCM', 'latitude' => 10.7724, 'longitude' => 106.6931, 'capacity' => 40],
            (object) ['id' => 3, 'name' => 'Sân khấu ngoài trời - Công viên 23/9', 'address' => 'Công viên 23/9, Quận 1, TP.HCM', 'latitude' => 10.7686, 'longitude' => 106.6917, 'capacity' => 60],
        ];
    }

    public static function showtimesForMovie(int $movieId): array
    {
        $rooms = collect(self::rooms())->keyBy('id');
        $roomId = $movieId % 3 === 0 ? 3 : ($movieId % 2 === 0 ? 2 : 1);

        return [
            (object) [
                'id' => $movieId * 10 + 1,
                'movie_id' => $movieId,
                'room' => $rooms[$roomId],
                'start_time' => now()->addDay()->setTime(14, 0),
                'base_price' => 75000,
            ],
            (object) [
                'id' => $movieId * 10 + 2,
                'movie_id' => $movieId,
                'room' => $rooms[$roomId],
                'start_time' => now()->addDay()->setTime(19, 30),
                'base_price' => 90000,
            ],
            (object) [
                'id' => $movieId * 10 + 3,
                'movie_id' => $movieId,
                'room' => $rooms[$roomId === 3 ? 1 : $roomId + 1],
                'start_time' => now()->addDays(2)->setTime(20, 0),
                'base_price' => 90000,
            ],
        ];
    }

    public static function showtimeById(int $showtimeId): ?object
    {
        $movieId = intdiv($showtimeId, 10);

        return collect(self::showtimesForMovie($movieId))->firstWhere('id', $showtimeId);
    }

    /**
     * Deterministic seat grid: 5 rows (A-E) x 8 seats, row E is VIP.
     * A fixed subset is marked booked/held so all three states are visible in the UI.
     */
    public static function seatsForShowtime(int $showtimeId): array
    {
        $rows = ['A', 'B', 'C', 'D', 'E'];
        $bookedIndexes = [3, 4, 12, 20, 27];
        $heldIndexes = [7, 21];

        $seats = [];
        $index = 0;
        foreach ($rows as $row) {
            for ($number = 1; $number <= 8; $number++) {
                $status = 'available';
                if (in_array($index, $bookedIndexes, true)) {
                    $status = 'booked';
                } elseif (in_array($index, $heldIndexes, true)) {
                    $status = 'held';
                }

                $seats[] = (object) [
                    'id' => $showtimeId * 100 + $index,
                    'showtime_id' => $showtimeId,
                    'row_label' => $row,
                    'seat_number' => $number,
                    'type' => $row === 'E' ? 'vip' : 'normal',
                    'status' => $status,
                ];
                $index++;
            }
        }

        return $seats;
    }

    public static function cartItems(): array
    {
        $movie = self::movies()[0];
        $showtime = self::showtimesForMovie($movie->id)[0];

        return [
            (object) [
                'showtime' => $showtime,
                'movie' => $movie,
                'seat' => (object) ['row_label' => 'C', 'seat_number' => 4, 'type' => 'normal'],
                'price' => 75000,
            ],
            (object) [
                'showtime' => $showtime,
                'movie' => $movie,
                'seat' => (object) ['row_label' => 'C', 'seat_number' => 5, 'type' => 'normal'],
                'price' => 75000,
            ],
        ];
    }

    public static function bookingHistory(): array
    {
        $movie = self::movies()[1];
        $showtime = self::showtimesForMovie($movie->id)[1];

        return [
            (object) [
                'id' => 1,
                'booking_code' => 'TB-20260901-A1B2',
                'movie' => $movie,
                'showtime' => $showtime,
                'seats' => ['D2', 'D3'],
                'total_price' => 180000,
                'status' => 'confirmed',
                'created_at' => now()->subDays(6),
            ],
            (object) [
                'id' => 2,
                'booking_code' => 'TB-20260828-C3D4',
                'movie' => self::movies()[4],
                'showtime' => self::showtimesForMovie(5)[2],
                'seats' => ['E1'],
                'total_price' => 90000,
                'status' => 'cancelled',
                'created_at' => now()->subDays(10),
            ],
        ];
    }
}
