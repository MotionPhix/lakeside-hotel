<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => null,
            'guest_name' => fake()->name(),
            'guest_country' => fake()->randomElement([
                'Malawi', 'South Africa', 'Zambia', 'United Kingdom', 'Germany', 'Kenya',
            ]),
            'rating' => fake()->numberBetween(3, 5),
            'title' => fake()->sentence(5),
            'quote' => fake()->paragraph(),
            'stayed_on' => now()->subDays(fake()->numberBetween(10, 400)),
            'source' => fake()->randomElement(Testimonial::SOURCES),
            'is_approved' => false,
            'is_featured' => false,
            'response' => null,
            'responded_at' => null,
            'responded_by' => null,
        ];
    }

    /**
     * Indicate that the review has been published.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_approved' => true,
        ]);
    }

    /**
     * Indicate that the review appears in the homepage carousel.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_approved' => true,
            'is_featured' => true,
            'rating' => 5,
        ]);
    }

    /**
     * Indicate that the hotel has replied.
     */
    public function withResponse(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_approved' => true,
            'response' => 'Thank you for the kind words - we hope to welcome you back to Senga Bay soon.',
            'responded_at' => now(),
        ]);
    }
}
