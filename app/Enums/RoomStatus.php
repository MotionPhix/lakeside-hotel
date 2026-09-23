<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * The state of an individual, physical room.
 */
enum RoomStatus: string
{
    use ProvidesOptions;

    case Available = 'available';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';
    case OutOfService = 'out_of_service';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Occupied => 'Occupied',
            self::Maintenance => 'Maintenance',
            self::OutOfService => 'Out of service',
        };
    }

    /**
     * Whether the room can be sold for new dates.
     */
    public function isBookable(): bool
    {
        return $this === self::Available;
    }

    /**
     * Badge variant used by the dashboard.
     */
    public function variant(): string
    {
        return match ($this) {
            self::Available => 'secondary',
            self::Occupied => 'default',
            self::Maintenance, self::OutOfService => 'destructive',
        };
    }
}
