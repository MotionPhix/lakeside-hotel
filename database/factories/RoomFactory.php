<?php

namespace Database\Factories;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'name' => 'Room '.fake()->unique()->numberBetween(1, 400),
            'number' => (string) fake()->unique()->numberBetween(100, 499),
            'floor' => fake()->randomElement(['Ground', 'First', 'Second']),
            'status' => RoomStatus::Available,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the room is closed for servicing.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RoomStatus::Maintenance,
        ]);
    }

    /**
     * Indicate that the room has been taken out of service entirely.
     */
    public function outOfService(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RoomStatus::OutOfService,
        ]);
    }
}
