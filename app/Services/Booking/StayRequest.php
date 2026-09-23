<?php

namespace App\Services\Booking;

use Carbon\CarbonInterface;

/**
 * What a guest is asking for: the nights, the party, and optionally the category
 * they have their eye on.
 *
 * Dates are check-in inclusive and check-out exclusive, which is the convention
 * the whole booking table uses. Everything downstream reads the nights from here
 * rather than re-deriving them, so a stay of three nights cannot be counted as
 * two in one place and four in another.
 */
final readonly class StayRequest
{
    public function __construct(
        public CarbonInterface $checkIn,
        public CarbonInterface $checkOut,
        public int $adults = 2,
        public int $children = 0,
        public ?string $roomTypeSlug = null,
        public ?string $couponCode = null,
    ) {}

    /**
     * How many nights the stay covers.
     */
    public function nights(): int
    {
        return (int) $this->checkIn->diffInDays($this->checkOut);
    }

    /**
     * How many people are travelling.
     */
    public function guests(): int
    {
        return $this->adults + $this->children;
    }

    /**
     * Every night of the stay, in order. The room is occupied from check-in until
     * the morning of check-out, so the last night is the one before check-out.
     *
     * @return list<CarbonInterface>
     */
    public function nightDates(): array
    {
        $nights = [];
        $cursor = $this->checkIn->copy()->startOfDay();
        $last = $this->checkOut->copy()->startOfDay();

        while ($cursor->lt($last)) {
            $nights[] = $cursor->copy();
            $cursor = $cursor->addDay();
        }

        return $nights;
    }
}
