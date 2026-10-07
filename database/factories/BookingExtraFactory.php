<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingExtra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingExtra>
 */
class BookingExtraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(15_000, 90_000);
        $quantity = fake()->numberBetween(1, 4);

        return [
            'booking_id' => Booking::factory(),
            'activity_id' => Activity::factory(),
            'name' => 'Sunset cruise',
            'price_basis' => 'per_person',
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $unitPrice * $quantity,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the extra is priced per group, so the quantity is always one.
     */
    public function perGroup(): static
    {
        return $this->state(fn (array $attributes): array => [
            'price_basis' => 'per_group',
            'quantity' => 1,
            'subtotal' => $attributes['unit_price'],
        ]);
    }
}
