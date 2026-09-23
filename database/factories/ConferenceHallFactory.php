<?php

namespace Database\Factories;

use App\Models\ConferenceHall;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ConferenceHall>
 */
class ConferenceHallFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => Str::headline($name).' Hall',
            'slug' => Str::slug($name.'-hall'),
            'capacity' => fake()->randomElement([20, 50, 100, 250]),
            'layout' => 'Theatre, classroom or boardroom',
            'description' => fake()->paragraph(),
            'features' => [
                'HD overhead projector',
                'Inbuilt HD sound system',
                'Cordless and pin microphones',
                'Flipcharts',
            ],
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the hall is not currently offered.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
