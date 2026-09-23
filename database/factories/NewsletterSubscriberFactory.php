<?php

namespace Database\Factories;

use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'status' => NewsletterSubscriber::STATUS_SUBSCRIBED,
            'source' => fake()->randomElement(['footer', 'booking', 'offers_page']),
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ];
    }

    /**
     * Indicate that the address has left the list.
     */
    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ]);
    }
}
