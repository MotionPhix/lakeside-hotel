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
            /*
             * Four digits, because a room is unique within its category and the
             * hotel's own rooms are named "Room 101" upwards. Faker only keeps
             * its own values apart, so a room invented in the same three digits
             * as a room that is already standing would eventually be named after
             * it and fail on the unique index.
             */
            'name' => 'Room '.fake()->unique()->numberBetween(1000, 9999),
            'number' => (string) fake()->numberBetween(1000, 9999),
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
