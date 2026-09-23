<?php

namespace Database\Factories;

use App\Models\HeroSlide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeroSlide>
 */
class HeroSlideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'headline' => fake()->sentence(4),
            'subheadline' => fake()->sentence(12),
            'cta_label' => 'Book Your Stay',
            'cta_url' => '/booking',
            'secondary_cta_label' => 'Explore Rooms',
            'secondary_cta_url' => '/rooms',
            'sort_order' => fake()->numberBetween(0, 5),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the slide is hidden from the homepage.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
