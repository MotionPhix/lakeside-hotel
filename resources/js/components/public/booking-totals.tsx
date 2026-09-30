import type { ReactNode } from 'react';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Pricing } from '@/types';

/**
 * What a stay costs, shown one of two ways.
 *
 * The total is the same either way - that is the point of quoting inclusive and
 * offering the breakdown. What the hotel's "show taxes separately" setting changes
 * is whether VAT and the tourism levy appear as lines of their own or are folded
 * into the price:
 *
 *   off - the room, any discount, and the total. VAT and the levy are inside
 *         those figures, said once in a note underneath.
 *   on  - the room before tax, the discount before tax, VAT at its rate, the levy
 *         at its rate, and the total.
 *
 * Every figure comes from the server already worked out, including both readings
 * of the room and the discount, so nothing here does arithmetic on money. A
 * summary whose own rows do not add up is worse than one that shows less.
 */
export function BookingTotals({
    pricing,
    label,
    currency,
    className,
    children,
}: {
    pricing: Pricing | null | undefined;
    /** What the room charge is called, e.g. "Room, 3 nights". */
    label: string;
    currency?: string;
    className?: string;
    /** Rows to place after the total, such as what has been paid. */
    children?: ReactNode;
}) {
    if (!pricing) {
        return null;
    }

    const itemised = pricing.show_separately;
    const discount = Number(itemised ? pricing.discount_net : pricing.discount_gross);

    return (
        <div className={className}>
            <dl className="space-y-2 text-sm">
                <Row
                    /*
                     * Only worth saying when the tax is not laid out below: with
                     * the breakdown on, the reader can see it for themselves.
                     */
                    label={
                        itemised ? label : `${label} (incl. VAT & levy)`
                    }
                    value={formatMoney(
                        itemised ? pricing.subtotal_net : pricing.subtotal_gross,
                        currency,
                    )}
                />

                {discount > 0 && (
                    <Row
                        label="Discount"
                        value={`−${formatMoney(
                            itemised
                                ? pricing.discount_net
                                : pricing.discount_gross,
                            currency,
                        )}`}
                    />
                )}

                {itemised && (
                    <>
                        <Row
                            label={`VAT ${rate(pricing.vat.rate)}%`}
                            value={formatMoney(pricing.vat.amount, currency)}
                        />
                        <Row
                            label={`Tourism levy ${rate(pricing.levy.rate)}%`}
                            value={formatMoney(pricing.levy.amount, currency)}
                        />
                    </>
                )}

                <Row
                    label="Total"
                    value={formatMoney(pricing.amount, currency)}
                    emphasis
                />

                {children}
            </dl>

            {!itemised && (
                <p className="mt-3 text-xs text-navy/55">
                    Includes VAT and the tourism levy.
                </p>
            )}
        </div>
    );
}

function Row({
    label,
    value,
    emphasis,
}: {
    label: string;
    value: string;
    emphasis?: boolean;
}) {
    return (
        <div
            className={cn(
                'flex justify-between gap-4',
                emphasis &&
                    'border-t border-navy/10 pt-2 text-base font-semibold',
            )}
        >
            <dt className={emphasis ? 'text-navy' : 'text-navy/60'}>{label}</dt>
            <dd className="text-navy tabular-nums">{value}</dd>
        </div>
    );
}

/**
 * 16.5 rather than 16.50, and 1 rather than 1.0 - the rates are decimal settings
 * and a trailing zero reads as precision nobody asked for.
 */
export function rate(percent: number): string {
    return String(Number(percent.toFixed(2)));
}
