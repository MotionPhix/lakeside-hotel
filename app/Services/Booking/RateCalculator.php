<?php

namespace App\Services\Booking;

use App\Models\RatePlan;
use App\Models\RoomType;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Prices a stay night by night.
 *
 * The order of business is: the category's walk-up rate for that night, itself
 * chosen between the midweek and weekend price; then the highest priority rate
 * plan that covers the night; then the supplement for anybody above the
 * category's included occupancy.
 *
 * Occupancy rule, which the rest of the system leans on: `capacity_adults` is how
 * many adults the base rate already covers, and `capacity_children` how many
 * children stay free. One more adult can be added on an extra bed at
 * `extra_person_price` per night - the arrangement that lets a single room be
 * sold to a couple, which is what the supplement is for.
 */
final class RateCalculator
{
    /**
     * The most adults a category will take: those included, plus one extra bed.
     */
    public function maxAdults(RoomType $roomType): int
    {
        return $roomType->capacity_adults + 1;
    }

    /**
     * Whether a party fits the category's occupancy at all.
     */
    public function fits(RoomType $roomType, StayRequest $stay): bool
    {
        return $stay->adults >= 1
            && $stay->adults <= $this->maxAdults($roomType)
            && $stay->children <= $roomType->capacity_children;
    }

    /**
     * How many guests at the stay are above what the base rate covers.
     */
    public function extraGuests(RoomType $roomType, StayRequest $stay): int
    {
        return max($stay->adults - $roomType->capacity_adults, 0);
    }

    /**
     * Price every night of the stay.
     */
    public function quote(RoomType $roomType, StayRequest $stay): StayQuote
    {
        $plans = RatePlan::query()
            ->active()
            ->forRoomType($roomType->getKey())
            ->get();

        $extraGuests = $this->extraGuests($roomType, $stay);
        $extraPerNight = (float) $roomType->extra_person_price * $extraGuests;

        $nightly = [];
        $subtotal = 0.0;
        $ratePlanId = null;

        foreach ($stay->nightDates() as $date) {
            $rate = (float) $this->rateFor($roomType, $date, $plans, $stay->nights());

            $plan = $this->planFor($date, $plans, $stay->nights());

            // Remember the first plan that actually priced something, so the
            // booking line can name the rate it was sold on.
            $ratePlanId ??= $plan?->getKey();

            $charge = $rate + $extraPerNight;
            $subtotal += $charge;

            $nightly[$date->format('Y-m-d')] = number_format($charge, 2, '.', '');
        }

        $nights = max(count($nightly), 1);

        return new StayQuote(
            roomType: $roomType,
            nightly: $nightly,
            subtotal: number_format($subtotal, 2, '.', ''),
            averageNightly: number_format($subtotal / $nights, 2, '.', ''),
            extraGuests: $extraGuests,
            extraPersonPrice: $roomType->extra_person_price,
            ratePlanId: $ratePlanId,
        );
    }

    /**
     * The walk-up rate for a night, after any plan that covers it.
     *
     * @param  Collection<int, RatePlan>  $plans
     */
    private function rateFor(RoomType $roomType, CarbonInterface $date, Collection $plans, int $nights): string
    {
        $base = $roomType->nightlyRateFor($date);

        return $this->planFor($date, $plans, $nights)?->applyTo($base) ?? $base;
    }

    /**
     * The plan that prices a given night: the highest priority plan that covers
     * the date and whose minimum stay the booking meets.
     *
     * @param  Collection<int, RatePlan>  $plans
     */
    private function planFor(CarbonInterface $date, Collection $plans, int $nights): ?RatePlan
    {
        return $plans
            ->filter(fn (RatePlan $plan): bool => $plan->coversDate($date)
                && ($plan->min_nights === null || $nights >= $plan->min_nights))
            ->sortByDesc('priority')
            ->first();
    }
}
