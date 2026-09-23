<?php

namespace Database\Factories;

use App\Models\NearbyAttraction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NearbyAttraction>
 */
class NearbyAttractionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'category' => fake()->randomElement(['nature', 'culture', 'adventure', 'town', 'market']),
            'description' => fake()->paragraph(),
            'distance_km' => fake()->randomFloat(2, 1, 120),
            'travel_time_minutes' => fake()->numberBetween(5, 180),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the attraction is hidden from the website.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
