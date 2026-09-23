<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Why a room was taken off sale for a date range.
 */
enum AvailabilityBlockReason: string
{
    use ProvidesOptions;

    case Maintenance = 'maintenance';
    case DeepClean = 'deep_clean';
    case HouseUse = 'house_use';
    case Renovation = 'renovation';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Maintenance => 'Maintenance',
            self::DeepClean => 'Deep clean',
            self::HouseUse => 'House use',
            self::Renovation => 'Renovation',
            self::Unavailable => 'Unavailable',
        };
    }
}
