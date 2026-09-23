<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Khán phòng '.fake()->unique()->word(),
            'address' => fake()->address(),
            'capacity' => 200,
            'layout_preset' => 'custom_grid',
            'seat_config' => ['rows' => 3, 'cols' => 4],
        ];
    }
}
