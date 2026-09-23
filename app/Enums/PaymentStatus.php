<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * How settled a booking is.
 */
enum PaymentStatus: string
{
    use ProvidesOptions;

    case Unpaid = 'unpaid';
    case DepositPaid = 'deposit_paid';
    case Paid = 'paid';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::DepositPaid => 'Deposit paid',
            self::Paid => 'Paid',
            self::PartiallyRefunded => 'Partially refunded',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Whether the guest still owes money.
     */
    public function hasBalance(): bool
    {
        return in_array($this, [self::Unpaid, self::DepositPaid], true);
    }

    public function variant(): string
    {
        return match ($this) {
            self::Unpaid => 'destructive',
            self::DepositPaid => 'outline',
            self::Paid => 'secondary',
            self::PartiallyRefunded, self::Refunded => 'outline',
        };
    }
}
