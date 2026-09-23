<?php

namespace Database\Factories;

use App\Models\DiningVenue;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dining_venue_id' => DiningVenue::factory(),
            'name' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(10),
            'price' => fake()->numberBetween(8_000, 65_000),
            'category' => fake()->randomElement(MenuItem::CATEGORIES),
            'is_signature' => false,
            'is_vegetarian' => fake()->boolean(25),
            'is_available' => true,
            'sort_order' => fake()->numberBetween(0, 30),
        ];
    }

    /**
     * Indicate that the dish is one the kitchen is known for.
     */
    public function signature(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_signature' => true,
        ]);
    }

    /**
     * Indicate that the dish is off the menu today.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_available' => false,
        ]);
    }
}
