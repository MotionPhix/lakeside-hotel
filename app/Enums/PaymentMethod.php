<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * The instrument a payment was actually made with. PayChangu reports back the
 * mobile money network, a card payment, or a manual settlement at the desk.
 */
enum PaymentMethod: string
{
    use ProvidesOptions;

    case Card = 'card';
    case AirtelMoney = 'airtel_money';
    case TnmMpamba = 'tnm_mpamba';
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Card',
            self::AirtelMoney => 'Airtel Money',
            self::TnmMpamba => 'TNM Mpamba',
            self::Cash => 'Cash',
            self::BankTransfer => 'Bank transfer',
            self::Other => 'Other',
        };
    }

    /**
     * Whether the method is a Malawian mobile money wallet.
     */
    public function isMobileMoney(): bool
    {
        return in_array($this, [self::AirtelMoney, self::TnmMpamba], true);
    }
}
