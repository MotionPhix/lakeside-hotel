<?php

namespace Database\Factories;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+265 99 '.fake()->numerify('### ####'),
            'country' => fake()->randomElement([
                'Malawi', 'South Africa', 'Zambia', 'Zimbabwe',
                'United Kingdom', 'Germany', 'Kenya', 'United States',
            ]),
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'id_number' => null,
            'notes' => null,
            'marketing_opt_in' => fake()->boolean(30),
        ];
    }

    /**
     * Indicate that the guest is travelling from within Malawi.
     */
    public function local(): static
    {
        return $this->state(fn (array $attributes): array => [
            'country' => 'Malawi',
            'city' => fake()->randomElement(['Lilongwe', 'Blantyre', 'Mzuzu', 'Salima', 'Zomba']),
            'phone' => '+265 88 '.fake()->numerify('### ####'),
        ]);
    }

    /**
     * Indicate that the guest has opted in to marketing.
     */
    public function subscribed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'marketing_opt_in' => true,
        ]);
    }
}
