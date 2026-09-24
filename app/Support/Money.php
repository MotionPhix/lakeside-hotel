<?php

namespace App\Support;

/**
 * Money as the hotel writes it.
 *
 * One place, because a confirmation email, a folio and a dashboard tile that
 * disagree about thousand separators read like three different businesses.
 *
 * Amounts are whole kwacha: the currency has no minor unit in practice, and a
 * rate of 320,000.00 quoted to a guest as 320,000 is the same number with fewer
 * things to misread.
 */
final class Money
{
    public static function format(?string $currency, string|float|int|null $amount): string
    {
        return trim(($currency ?? 'MWK').' '.number_format((float) $amount, 0, '.', ','));
    }
}
