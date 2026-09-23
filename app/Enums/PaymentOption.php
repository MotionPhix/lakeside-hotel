<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * The choice a guest makes at checkout: pay online now, or settle at the hotel.
 */
enum PaymentOption: string
{
    use ProvidesOptions;

    case PayNow = 'pay_now';
    case PayAtHotel = 'pay_at_hotel';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::PayNow => 'Pay online now',
            self::PayAtHotel => 'Reserve and pay at the hotel',
            self::BankTransfer => 'Bank transfer',
        };
    }

    /**
     * Whether the option routes through the PayChangu gateway.
     */
    public function usesGateway(): bool
    {
        return $this === self::PayNow;
    }

    /**
     * The deposit percentage taken up front, if any.
     */
    public function depositPercentage(): int
    {
        return match ($this) {
            self::PayNow => 100,
            self::PayAtHotel => 0,
            self::BankTransfer => 0,
        };
    }
}
