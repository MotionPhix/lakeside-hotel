import { BookingTotals } from '@/components/public/booking-totals';
import type { Pricing } from '@/types';

/**
 * The tax inside a rate, beneath the rate itself.
 *
 * The caller has already shown the figure - this is the explanation of it, and
 * which explanation depends on the hotel's "show taxes separately" setting. With
 * it off the rate is simply inclusive and says so; with it on the same money is
 * laid out as the room, VAT and the levy.
 *
 * See {@link BookingTotals} for the itemised view, which this defers to so the
 * markup exists in one place.
 */
export function PriceLines({
    pricing,
    currency,
    className,
}: {
    pricing: Pricing | null | undefined;
    currency?: string;
    className?: string;
}) {
    if (!pricing) {
        return null;
    }

    if (!pricing.show_separately) {
        return (
            <p className={className ?? 'text-xs text-navy/55'}>
                Includes VAT and the tourism levy.
            </p>
        );
    }

    return (
        <BookingTotals
            pricing={pricing}
            label="Room"
            currency={currency}
            className={className}
        />
    );
}
