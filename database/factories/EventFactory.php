<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'type' => 'event',
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'category_id' => Category::factory(),
            'duration_minutes' => 120,
            'status' => 'published',
            'release_date' => now()->addWeek(),
            'is_seated' => true,
            'details' => null,
        ];
    }
}
