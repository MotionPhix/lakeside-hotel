<?php

namespace App\Services\Booking;

/**
 * One category the hotel can sell for a stay: what it costs, and how many rooms
 * of it are still free.
 */
final readonly class AvailabilityOffer
{
    public function __construct(
        public StayQuote $quote,
        public int $available,
    ) {}
}
