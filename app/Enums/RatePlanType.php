<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * The reason a rate plan exists. When several plans cover the same night the
 * one with the highest priority wins.
 */
enum RatePlanType: string
{
    use ProvidesOptions;

    case Seasonal = 'seasonal';
    case Weekend = 'weekend';
    case Holiday = 'holiday';
    case Corporate = 'corporate';
    case Promotional = 'promotional';

    public function label(): string
    {
        return match ($this) {
            self::Seasonal => 'Seasonal',
            self::Weekend => 'Weekend',
            self::Holiday => 'Holiday',
            self::Corporate => 'Corporate',
            self::Promotional => 'Promotional',
        };
    }

    /**
     * Default priority so the more specific plans beat the broader ones.
     */
    public function defaultPriority(): int
    {
        return match ($this) {
            self::Seasonal => 10,
            self::Weekend => 20,
            self::Holiday => 30,
            self::Corporate => 40,
            self::Promotional => 50,
        };
    }
}
