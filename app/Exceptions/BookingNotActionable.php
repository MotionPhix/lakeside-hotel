<?php

namespace App\Exceptions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Raised when the desk asks a reservation to do something it cannot do from where
 * it currently is - checking out a guest who was never checked in, or cancelling
 * one that has already ended.
 *
 * These are ordinary refusals rather than faults, so the controller catches them
 * and says why instead of letting them become a 500.
 */
final class BookingNotActionable extends RuntimeException
{
    /**
     * @param  list<BookingStatus>  $allowed
     */
    public static function from(Booking $booking, string $action, array $allowed): self
    {
        $states = implode(' or ', array_map(
            fn (BookingStatus $status): string => $status->label(),
            $allowed,
        ));

        return new self(sprintf(
            'Booking %s is %s, so it cannot be %s. It has to be %s first.',
            $booking->reference,
            $booking->status->label(),
            $action,
            $states,
        ));
    }

    public static function tooEarly(Booking $booking, CarbonInterface $arrival): self
    {
        return new self(sprintf(
            'Booking %s is not due until %s, so it cannot be marked as a no-show yet.',
            $booking->reference,
            $arrival->format('j F Y'),
        ));
    }
}
