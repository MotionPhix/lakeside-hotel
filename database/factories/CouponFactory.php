<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $discountType = fake()->randomElement(['percentage', 'fixed']);

        return [
            'code' => strtoupper(fake()->unique()->bothify('????##')),
            'description' => fake()->sentence(6),
            'discount_type' => $discountType,
            'discount_value' => $discountType === 'percentage'
                ? fake()->randomElement([5, 10, 15, 20])
                : fake()->numberBetween(20_000, 100_000),
            'min_nights' => null,
            'min_spend' => null,
            'room_type_id' => null,
            'valid_from' => now()->subWeek(),
            'valid_until' => now()->addMonths(3),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the coupon takes a percentage off.
     */
    public function percentage(int $percent = 10): static
    {
        return $this->state(fn (array $attributes): array => [
            'discount_type' => 'percentage',
            'discount_value' => $percent,
        ]);
    }

    /**
     * Indicate that the coupon has been used up.
     */
    public function exhausted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_limit' => 5,
            'used_count' => 5,
        ]);
    }

    /**
     * Indicate that the coupon has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'valid_from' => now()->subMonths(3),
            'valid_until' => now()->subMonth(),
        ]);
    }

    /**
     * Indicate that the coupon has been switched off.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
