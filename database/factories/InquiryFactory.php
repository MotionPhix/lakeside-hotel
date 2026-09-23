<?php

namespace Database\Factories;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '+265 99 '.fake()->numerify('### ####'),
            'subject' => fake()->sentence(6),
            'message' => fake()->paragraphs(2, true),
            'type' => fake()->randomElement(InquiryType::cases()),
            'status' => InquiryStatus::New,
            'preferred_date' => null,
            'guests_count' => null,
            'assigned_to' => null,
            'responded_at' => null,
            'response' => null,
            'source_page' => '/contact',
        ];
    }

    /**
     * Indicate that the hotel has replied.
     */
    public function responded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InquiryStatus::Responded,
            'response' => 'Thank you for getting in touch - our reservations team has sent you the details.',
            'responded_at' => now(),
        ]);
    }

    /**
     * Indicate that the enquiry is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InquiryStatus::Closed,
            'responded_at' => now()->subDays(3),
        ]);
    }

    /**
     * Indicate that the enquiry is a conference or event request.
     */
    public function conference(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => InquiryType::Conference,
            'subject' => 'Conference booking for 60 delegates',
            'preferred_date' => now()->addMonths(2),
            'guests_count' => 60,
            'source_page' => '/conferences',
        ]);
    }
}
