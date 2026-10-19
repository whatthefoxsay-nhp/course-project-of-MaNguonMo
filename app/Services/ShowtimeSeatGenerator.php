<?php

namespace App\Services;

use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;

class ShowtimeSeatGenerator
{
    public function generate(Showtime $showtime): int
    {
        $now = now();

        $rows = Seat::where('room_id', $showtime->room_id)
            ->pluck('id')
            ->map(fn (int $seatId) => [
                'showtime_id' => $showtime->id,
                'seat_id' => $seatId,
                'status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            ShowtimeSeat::insertOrIgnore($chunk); // unique(showtime_id, seat_id) -> chạy lại không nhân đôi
        }

        return count($rows);
    }

    public function regenerate(Showtime $showtime): int
    {
        $showtime->showtimeSeats()->delete();

        return $this->generate($showtime);
    }
}
