<?php

namespace Database\Factories;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Guest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = now()->addDays(fake()->numberBetween(-90, 120))->startOfDay();
        $nights = fake()->numberBetween(1, 7);

        return [
            /*
             * No reference here on purpose: the model issues one as the booking
             * is written, which is the only moment it can be sure of stepping
             * over what is already on file. A number invented by the factory can
             * collide with a seeded booking, and a number read from the table
             * here would be the same for every booking in a batch.
             */
            'guest_id' => Guest::factory(),
            'status' => fake()->randomElement(BookingStatus::cases()),
            'source' => fake()->randomElement(BookingSource::cases()),
            'check_in' => $checkIn,
            'check_out' => $checkIn->addDays($nights),
            'nights' => $nights,
            'adults' => fake()->numberBetween(1, 2),
            'children' => fake()->numberBetween(0, 2),
            'currency' => 'MWK',
            'special_requests' => null,
            'airport_transfer' => fake()->boolean(20),
            'transfer_details' => null,
            'internal_notes' => null,
        ];
    }

    /**
     * Every booking needs at least one room line, and its money totals follow
     * from that line, so build one as soon as the booking exists.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Booking $booking): void {
            if ($booking->items()->exists()) {
                return;
            }

            BookingItem::factory()->create([
                'booking_id' => $booking->getKey(),
                'adults' => $booking->adults,
                'children' => $booking->children,
            ]);

            $booking->recalculateTotals()->syncPaymentStatus()->save();
        });
    }

    /**
     * Indicate that the booking is waiting to be confirmed.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Pending,
        ]);
    }

    /**
     * Indicate that the booking has been confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the guest is in house.
     */
    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::CheckedIn,
            'confirmed_at' => now()->subDays(2),
            'checked_in_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the stay has finished.
     */
    public function checkedOut(): static
    {
        return $this->state(function (array $attributes): array {
            $checkIn = now()->subDays(6)->startOfDay();

            return [
                'status' => BookingStatus::CheckedOut,
                'check_in' => $checkIn,
                'check_out' => $checkIn->addDays(3),
                'nights' => 3,
                'confirmed_at' => now()->subWeek(),
                'checked_in_at' => now()->subDays(6),
                'checked_out_at' => now()->subDays(3),
            ];
        });
    }

    /**
     * Indicate that the booking was cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now()->subDay(),
            'cancellation_reason' => 'Guest changed their travel plans.',
        ]);
    }

    /**
     * Indicate that the guest is arriving and leaving on specific dates.
     */
    public function forStay(string $checkIn, string $checkOut): static
    {
        return $this->state(function (array $attributes) use ($checkIn, $checkOut): array {
            $from = CarbonImmutable::parse($checkIn)->startOfDay();
            $to = CarbonImmutable::parse($checkOut)->startOfDay();

            return [
                'check_in' => $from,
                'check_out' => $to,
                'nights' => (int) $from->diffInDays($to),
            ];
        });
    }
}
