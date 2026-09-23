<?php

namespace Database\Factories;

use App\Enums\AvailabilityBlockReason;
use App\Models\AvailabilityBlock;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailabilityBlock>
 */
class AvailabilityBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsOn = now()->addDays(fake()->numberBetween(1, 60));

        return [
            'room_id' => Room::factory(),
            'starts_on' => $startsOn,
            'ends_on' => $startsOn->addDays(fake()->numberBetween(1, 7)),
            'reason' => fake()->randomElement(AvailabilityBlockReason::cases()),
            'notes' => null,
            'created_by' => null,
        ];
    }

    /**
     * Indicate that the room is closed for maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'reason' => AvailabilityBlockReason::Maintenance,
        ]);
    }
}
