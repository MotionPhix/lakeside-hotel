<?php

namespace Database\Factories;

use App\Enums\RateAdjustmentType;
use App\Enums\RatePlanType;
use App\Models\RatePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RatePlan>
 */
class RatePlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(RatePlanType::cases());

        return [
            'room_type_id' => null,
            'name' => $type->label().' rate',
            'code' => strtoupper($type->value).'-'.fake()->unique()->numerify('####'),
            'type' => $type,
            'adjustment_type' => RateAdjustmentType::Percentage,
            'amount' => fake()->randomElement([-15, -10, 10, 15, 20]),
            'min_nights' => null,
            'days_of_week' => null,
            'starts_on' => null,
            'ends_on' => null,
            'priority' => $type->defaultPriority(),
            'is_active' => true,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the plan only applies on Friday and Saturday nights.
     */
    public function weekend(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Weekend rate',
            'type' => RatePlanType::Weekend,
            'days_of_week' => [5, 6],
            'priority' => RatePlanType::Weekend->defaultPriority(),
        ]);
    }

    /**
     * Indicate that the plan applies between two dates.
     */
    public function season(string $startsOn, string $endsOn): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => RatePlanType::Seasonal,
            'adjustment_type' => RateAdjustmentType::Override,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'priority' => RatePlanType::Seasonal->defaultPriority(),
        ]);
    }

    /**
     * Indicate that the plan is not currently applied.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
