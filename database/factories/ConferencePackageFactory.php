<?php

namespace Database\Factories;

use App\Models\ConferencePackage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConferencePackage>
 */
class ConferencePackageFactory extends Factory
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
            'type' => fake()->randomElement(ConferencePackage::TYPES),
            'tagline' => fake()->sentence(6),
            'description' => fake()->paragraphs(2, true),
            'capacity_min' => 10,
            'capacity_max' => fake()->randomElement([40, 80, 120, 200]),
            'price' => fake()->numberBetween(35_000, 120_000),
            'price_basis' => fake()->randomElement(['per_person', 'per_day', 'per_event']),
            'includes' => [
                'Venue hire',
                'Projector and screen',
                'Mid-morning tea and coffee',
                'Buffet lunch',
                'Dedicated events coordinator',
            ],
            'sort_order' => fake()->numberBetween(0, 10),
            'is_featured' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the package is sold for conferences and retreats.
     */
    public function conference(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'conference',
        ]);
    }

    /**
     * Indicate that the package is sold for weddings.
     */
    public function wedding(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'wedding',
        ]);
    }

    /**
     * Indicate that the package is highlighted on the website.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_featured' => true,
        ]);
    }
}
