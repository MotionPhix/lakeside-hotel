<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * The state of a single payment attempt against a booking. A booking can have
 * several of these: a failed attempt, then a successful one.
 */
enum PaymentRecordStatus: string
{
    use ProvidesOptions;

    case Pending = 'pending';
    case Successful = 'successful';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Successful => 'Successful',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Whether the money actually arrived.
     */
    public function isSettled(): bool
    {
        return $this === self::Successful;
    }

    public function variant(): string
    {
        return match ($this) {
            self::Pending => 'outline',
            self::Successful => 'secondary',
            self::Failed => 'destructive',
            self::Refunded => 'outline',
        };
    }
}
