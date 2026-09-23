<?php

namespace App\Services\Booking;

use App\Models\RoomType;

/**
 * What a stay in one room category costs, priced night by night.
 *
 * The nightly map is the important part: it is what gets written onto the booking
 * line, so a later rate change can never rewrite a folio that has already been
 * agreed. Every figure here is exclusive of tax, which the booking adds once at
 * the end.
 */
final readonly class StayQuote
{
    /**
     * @param  array<string, string>  $nightly  `Y-m-d` => nightly rate, room and extras together.
     */
    public function __construct(
        public RoomType $roomType,
        public array $nightly,
        public string $subtotal,
        public string $averageNightly,
        public int $extraGuests,
        public string $extraPersonPrice,
        public ?int $ratePlanId,
    ) {}

    /**
     * What a guest is told the room costs per night, rounded to the whole kwacha.
     * The precise figure lives in the nightly map.
     */
    public function nightlyFrom(): string
    {
        return number_format((float) $this->averageNightly, 0, '.', '');
    }
}
