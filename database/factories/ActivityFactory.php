<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraphs(2, true),
            'duration_minutes' => fake()->randomElement([30, 60, 90, 120, 180, 240]),
            'price' => fake()->numberBetween(15_000, 180_000),
            'price_basis' => fake()->randomElement(Activity::PRICE_BASES),
            'min_participants' => 1,
            'max_participants' => fake()->numberBetween(4, 20),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_featured' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the experience is highlighted on the homepage.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the experience is included free of charge.
     */
    public function complimentary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'price' => 0,
            'price_basis' => 'complimentary',
        ]);
    }

    /**
     * Indicate that the experience is no longer offered.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
