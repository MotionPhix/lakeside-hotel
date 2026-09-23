<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingItem>
 */
class BookingItemFactory extends Factory
{
    /**
     * The number of nights priced into a generated line.
     */
    private const NIGHTS = 3;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pricePerNight = fake()->numberBetween(120_000, 480_000);

        return [
            'booking_id' => Booking::factory(),
            'room_type_id' => RoomType::factory(),
            'room_id' => null,
            'rate_plan_id' => null,
            'adults' => 2,
            'children' => 0,
            'price_per_night' => $pricePerNight,
            'subtotal' => $pricePerNight * self::NIGHTS,
            'nightly_rates' => null,
        ];
    }

    /**
     * Indicate that the line prices a stay of a given length.
     */
    public function forNights(int $nights): static
    {
        return $this->state(fn (array $attributes): array => [
            'subtotal' => (float) $attributes['price_per_night'] * $nights,
            'nightly_rates' => array_fill(0, $nights, $attributes['price_per_night']),
        ]);
    }
}
