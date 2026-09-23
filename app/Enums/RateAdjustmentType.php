<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * How a rate plan changes the nightly price.
 */
enum RateAdjustmentType: string
{
    use ProvidesOptions;

    case Fixed = 'fixed';
    case Percentage = 'percentage';
    case Override = 'override';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Add or subtract an amount',
            self::Percentage => 'Add or subtract a percentage',
            self::Override => 'Replace the nightly price',
        };
    }
}
