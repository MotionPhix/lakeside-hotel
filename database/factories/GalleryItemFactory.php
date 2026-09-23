<?php

namespace Database\Factories;

use App\Models\GalleryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryItem>
 */
class GalleryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'caption' => fake()->sentence(6),
            'category' => fake()->randomElement(GalleryItem::CATEGORIES),
            'type' => 'image',
            'video_url' => null,
            'sort_order' => fake()->numberBetween(0, 30),
            'is_featured' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the tile is a hosted video rather than a photograph.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v='.fake()->regexify('[A-Za-z0-9_-]{11}'),
        ]);
    }

    /**
     * Indicate that the tile appears on the homepage gallery strip.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_featured' => true,
        ]);
    }
}
