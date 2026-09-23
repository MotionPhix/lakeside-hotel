<?php

namespace Database\Factories;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Amenity>
 */
class AmenityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => fake()->randomElement([
                'wifi', 'utensils', 'wine', 'presentation', 'waves',
                'anchor', 'car', 'concierge-bell', 'party-popper', 'ship',
            ]),
            'category' => fake()->randomElement(['general', 'room', 'dining', 'leisure', 'business']),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the amenity is hidden from the website.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
