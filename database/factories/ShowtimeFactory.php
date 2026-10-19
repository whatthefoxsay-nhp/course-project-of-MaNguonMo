<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Showtime>
 */
class ShowtimeFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(3)->setTime(19, 0);

        return [
            'event_id' => Event::factory(),
            'room_id' => Room::factory(),
            'start_time' => $start,
            'end_time' => $start->copy()->addHours(3),
            'base_price' => 200000,
        ];
    }
}
