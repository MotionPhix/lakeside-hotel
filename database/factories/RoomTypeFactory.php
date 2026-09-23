<?php

namespace Database\Factories;

use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));
        $basePrice = fake()->numberBetween(120_000, 480_000);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'tagline' => fake()->sentence(6),
            'description' => fake()->paragraphs(2, true),
            'capacity_adults' => 2,
            'capacity_children' => 0,
            'size_sqm' => fake()->numberBetween(24, 80),
            'bed_configuration' => 'One king bed',
            'base_price' => $basePrice,
            'weekend_price' => (int) round($basePrice * 1.2),
            'extra_person_price' => 35_000,
            'min_nights' => 1,
            'sort_order' => fake()->numberBetween(0, 10),
            'is_featured' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category appears on the homepage.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the category is no longer on sale.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category sleeps a family.
     */
    public function family(): static
    {
        return $this->state(fn (array $attributes): array => [
            'capacity_adults' => 2,
            'capacity_children' => 3,
            'bed_configuration' => 'One king bed and three single beds',
        ]);
    }
}
