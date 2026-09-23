<?php

namespace Database\Factories;

use App\Models\SiteSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSection>
 */
class SiteSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page' => 'home',
            'key' => fake()->unique()->randomElement(SiteSection::HOME_SECTIONS),
            'eyebrow' => fake()->word(),
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(12),
            'config' => null,
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the section is switched off.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
