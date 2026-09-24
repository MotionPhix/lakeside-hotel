<?php

namespace App\Exceptions;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Raised when the desk asks for a door it cannot have: a room of the wrong
 * category, one that is out of service, one blocked for maintenance, or one
 * another guest is already sleeping in.
 *
 * These are ordinary refusals rather than faults - the desk is choosing from a
 * list while somebody else is checking another guest in - so the controller says
 * why instead of letting them become a 500.
 */
final class RoomNotAvailable extends RuntimeException
{
    /**
     * The booking has been cancelled or has already ended, so there is no stay
     * left to put a room against.
     */
    public static function released(Booking $booking): self
    {
        return new self(sprintf(
            'Booking %s is %s, so it no longer holds a room.',
            $booking->reference,
            $booking->status->label(),
        ));
    }

    public static function wrongType(Room $room, BookingItem $item): self
    {
        return new self(sprintf(
            'Room %s is a %s, and this line is booked as a %s.',
            $room->name,
            $room->roomType?->name ?? 'different category',
            $item->roomType?->name ?? 'category',
        ));
    }

    public static function notSellable(Room $room): self
    {
        return new self(sprintf(
            'Room %s is %s, so it cannot be given to a guest.',
            $room->name,
            strtolower($room->status->label()),
        ));
    }

    public static function blocked(Room $room, CarbonInterface $from, CarbonInterface $to): self
    {
        return new self(sprintf(
            'Room %s is blocked for maintenance over %s, so it cannot be given to a guest.',
            $room->name,
            self::window($from, $to),
        ));
    }

    public static function taken(Room $room, CarbonInterface $from, CarbonInterface $to): self
    {
        return new self(sprintf(
            'Room %s is already taken for %s.',
            $room->name,
            self::window($from, $to),
        ));
    }

    /**
     * The room is not going to a guest - it is being taken away from one. Raised
     * when a room is closed for maintenance over nights somebody is already
     * sleeping in, which has to be sorted out before the room comes off sale.
     */
    public static function givenToAGuest(Room $room, CarbonInterface $from, CarbonInterface $to): self
    {
        return new self(sprintf(
            'Room %s has a guest in it for %s. Move them to another room first, or pick different dates.',
            $room->name,
            self::window($from, $to),
        ));
    }

    /**
     * The nights in question, said the way the desk would say them.
     */
    private static function window(CarbonInterface $from, CarbonInterface $to): string
    {
        return $from->format('j M') === $to->format('j M')
            ? $from->format('j M Y')
            : sprintf('%s to %s', $from->format('j M'), $to->format('j M Y'));
    }
}
