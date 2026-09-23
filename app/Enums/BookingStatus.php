<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Lifecycle of a reservation, from enquiry to check-out.
 */
enum BookingStatus: string
{
    use ProvidesOptions;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::CheckedIn => 'Checked in',
            self::CheckedOut => 'Checked out',
            self::Cancelled => 'Cancelled',
            self::NoShow => 'No show',
        };
    }

    /**
     * Whether the booking still holds rooms for its dates.
     */
    public function holdsInventory(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::CheckedIn], true);
    }

    /**
     * Whether the booking has finished or been voided.
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::CheckedOut, self::Cancelled, self::NoShow], true);
    }

    public function variant(): string
    {
        return match ($this) {
            self::Pending => 'outline',
            self::Confirmed => 'secondary',
            self::CheckedIn => 'default',
            self::CheckedOut => 'outline',
            self::Cancelled, self::NoShow => 'destructive',
        };
    }
}
