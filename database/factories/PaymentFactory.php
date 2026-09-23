<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'provider' => Payment::PROVIDER_PAYCHANGU,
            'provider_reference' => 'PCH-'.fake()->unique()->numerify('##########'),
            'provider_transaction_id' => null,
            'method' => fake()->randomElement([
                PaymentMethod::Card,
                PaymentMethod::AirtelMoney,
                PaymentMethod::TnmMpamba,
            ]),
            'amount' => fake()->numberBetween(200_000, 2_000_000),
            'currency' => 'MWK',
            'status' => PaymentRecordStatus::Pending,
            'paid_at' => null,
            'payload' => null,
            'refunded_amount' => 0,
            'refunded_at' => null,
        ];
    }

    /**
     * Indicate that the gateway confirmed the payment.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentRecordStatus::Successful,
            'paid_at' => now(),
            'payload' => ['status' => 'success', 'source' => 'factory'],
        ]);
    }

    /**
     * Indicate that the attempt failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentRecordStatus::Failed,
            'payload' => ['status' => 'failed', 'reason' => 'Insufficient funds'],
        ]);
    }

    /**
     * Indicate that the payment was refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentRecordStatus::Refunded,
            'paid_at' => now()->subDays(3),
            'refunded_amount' => $attributes['amount'],
            'refunded_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment was recorded at the front desk.
     */
    public function atDesk(): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => Payment::PROVIDER_MANUAL,
            'provider_reference' => null,
            'method' => PaymentMethod::Cash,
            'status' => PaymentRecordStatus::Successful,
            'paid_at' => now(),
        ]);
    }
}
