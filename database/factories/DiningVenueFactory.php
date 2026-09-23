<?php

namespace Database\Factories;

use App\Models\DiningVenue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DiningVenue>
 */
class DiningVenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'type' => fake()->randomElement(['restaurant', 'bar', 'lounge', 'pool_bar']),
            'tagline' => fake()->sentence(6),
            'description' => fake()->paragraphs(2, true),
            'opening_hours' => [
                'breakfast' => '06:30 - 10:00',
                'lunch' => '12:00 - 15:00',
                'dinner' => '18:00 - 22:00',
            ],
            'dress_code' => fake()->randomElement(['Smart casual', 'Resort casual', null]),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the venue is hidden from the website.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
