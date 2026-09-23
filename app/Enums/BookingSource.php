<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Where a reservation came from, used for the booking trend reports.
 */
enum BookingSource: string
{
    use ProvidesOptions;

    case Website = 'website';
    case Phone = 'phone';
    case Email = 'email';
    case WalkIn = 'walk_in';
    case Agent = 'agent';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::Phone => 'Phone',
            self::Email => 'Email',
            self::WalkIn => 'Walk in',
            self::Agent => 'Travel agent',
        };
    }
}
