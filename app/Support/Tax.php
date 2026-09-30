<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Setting;

/**
 * What tax is added to a price, and how it is shown to a guest.
 *
 * Two things used to be spread around the application and are gathered here.
 *
 * The first is the arithmetic. A quote shown to a guest and the folio they are
 * finally billed on have to agree, and they can only do that if one piece of code
 * decides what tax is - so this is that piece, and {@see Booking}
 * now delegates to it rather than keeping its own copy.
 *
 * The second is presentation. Guests are shown prices that already include VAT
 * and the tourism levy, because a price that grows by 17.5% at checkout is not
 * the price that was advertised. The split into separate lines is a preference,
 * not a different sum: the guest pays the same either way, which is the whole
 * point of quoting inclusive and offering the breakdown.
 *
 * The rates are editable in the dashboard. The constants below are the statutory
 * figures and are used only when nothing has been configured.
 */
class Tax
{
    /**
     * Value added tax on accommodation in Malawi.
     */
    public const VAT_RATE = 16.5;

    /**
     * Tourism levy charged on accommodation in Malawi.
     */
    public const LEVY_RATE = 1.0;

    public const VAT_SETTING = 'booking.vat_rate';

    public const LEVY_SETTING = 'booking.tourism_levy_rate';

    /**
     * Whether the two are broken out as separate lines on a guest-facing price.
     *
     * Off by default, so a guest sees one figure and can trust it.
     */
    public const SHOW_SEPARATELY_SETTING = 'booking.show_taxes_separately';

    public static function vatRate(): float
    {
        return (float) Setting::value(self::VAT_SETTING, (string) self::VAT_RATE);
    }

    public static function levyRate(): float
    {
        return (float) Setting::value(self::LEVY_SETTING, (string) self::LEVY_RATE);
    }

    /**
     * Both rates together, as a percentage - what a net price grows by.
     */
    public static function rate(): float
    {
        return self::vatRate() + self::levyRate();
    }

    public static function showSeparately(): bool
    {
        return filter_var(
            Setting::value(self::SHOW_SEPARATELY_SETTING, '0'),
            FILTER_VALIDATE_BOOL,
        );
    }

    /**
     * The two taxes on a net amount, and what they come to together.
     *
     * Every figure is a decimal string at two places, the same as every other
     * amount in the booking engine, so nothing is lost or rounded differently on
     * the way to a folio.
     *
     * @return array{vat: string, levy: string, total: string}
     */
    public static function on(string $net): array
    {
        $amount = (float) $net;

        $vat = round($amount * (self::vatRate() / 100), 2);
        $levy = round($amount * (self::levyRate() / 100), 2);

        return [
            'vat' => number_format($vat, 2, '.', ''),
            'levy' => number_format($levy, 2, '.', ''),
            'total' => number_format($vat + $levy, 2, '.', ''),
        ];
    }

    /**
     * What a guest pays for something priced net.
     */
    public static function inclusive(string $net): string
    {
        return number_format(
            (float) $net + (float) self::on($net)['total'],
            2,
            '.',
            '',
        );
    }

    /**
     * Everything a screen needs to show one price, in one shape.
     *
     * `amount` is the figure to display - it already includes the tax. The parts
     * are sent whether or not they are shown, so a page can break them out
     * without asking for anything else, and so a test can prove they add up.
     *
     * @return array<string, mixed>
     */
    public static function price(string $net): array
    {
        return self::breakdown($net, '0.00');
    }

    /**
     * The same shape for a total built from a room charge and a discount, with
     * tax charged on what is left.
     *
     * A guest is shown the room, then any discount, then the total, and every
     * figure has to be in the same terms or the rows will not add up. So the
     * block carries both readings of each figure: the net one, for the itemised
     * view, and the tax-inclusive one for the view where the tax is folded in.
     *
     * The inclusive discount is derived by subtraction rather than worked out
     * from the rate, so the room and the discount always reconcile to the total
     * printed beside them. Rounding each part separately can leave a cent adrift,
     * and a summary whose own rows do not add up undermines every other figure on
     * the page.
     *
     * @param  string|null  $total  The total as recorded. Left out, it is worked
     *                              out from the parts.
     * @return array<string, mixed>
     */
    public static function breakdown(
        string $subtotalNet,
        string $discountNet,
        ?string $total = null,
    ): array {
        $base = number_format((float) $subtotalNet - (float) $discountNet, 2, '.', '');
        $tax = self::on($base);

        $total ??= number_format((float) $base + (float) $tax['total'], 2, '.', '');

        $subtotalGross = self::inclusive($subtotalNet);

        return [
            'net' => $base,
            'amount' => $total,
            'subtotal_net' => number_format((float) $subtotalNet, 2, '.', ''),
            'subtotal_gross' => $subtotalGross,
            'discount_net' => number_format((float) $discountNet, 2, '.', ''),
            'discount_gross' => number_format(
                (float) $subtotalGross - (float) $total,
                2,
                '.',
                '',
            ),
            'vat' => ['rate' => self::vatRate(), 'amount' => $tax['vat']],
            'levy' => ['rate' => self::levyRate(), 'amount' => $tax['levy']],
            'tax_total' => $tax['total'],
            'includes_tax' => true,
            'show_separately' => self::showSeparately(),
        ];
    }
}
