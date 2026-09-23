<?php

namespace Database\Factories;

use App\Models\ContentBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentBlock>
 */
class ContentBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'title' => fake()->sentence(4),
            'subtitle' => fake()->sentence(8),
            'body' => fake()->paragraphs(3, true),
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the block is a draft, hidden from the website.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
