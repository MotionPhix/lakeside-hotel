<?php

namespace Database\Factories;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => ucfirst($title),
            'slug' => Str::slug($title),
            'subtitle' => fake()->sentence(8),
            'description' => fake()->paragraphs(2, true),
            'highlight' => fake()->randomElement(['Save 20%', 'Stay 3 pay 2', 'Free sunset cruise']),
            'discount_label' => fake()->randomElement(['20% off', 'From MWK 95,000', 'Complimentary upgrade']),
            'type' => fake()->randomElement(Offer::TYPES),
            'starts_on' => now()->subWeek(),
            'ends_on' => now()->addMonths(2),
            'coupon_code' => null,
            'terms' => 'Subject to availability. Blackout dates apply over public holidays.',
            'sort_order' => fake()->numberBetween(0, 10),
            'is_featured' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the offer is highlighted on the homepage.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the offer has finished.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_on' => now()->subMonths(4),
            'ends_on' => now()->subMonth(),
        ]);
    }

    /**
     * Indicate that the offer is hidden from the website.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
