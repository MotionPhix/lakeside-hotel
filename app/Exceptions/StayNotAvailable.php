<?php

namespace App\Exceptions;

use App\Models\RoomType;
use App\Services\Booking\StayRequest;
use RuntimeException;

/**
 * Thrown when a stay cannot be sold: the party does not fit the category, or the
 * last room of it went while the guest was filling in the form.
 */
final class StayNotAvailable extends RuntimeException
{
    public static function for(RoomType $roomType, StayRequest $stay): self
    {
        return new self(sprintf(
            'No %s is free for %d night(s) from %s for %d guest(s).',
            $roomType->name,
            $stay->nights(),
            $stay->checkIn->format('j F Y'),
            $stay->guests(),
        ));
    }
}
