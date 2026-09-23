/**
 * Money and date formatting for the public website.
 *
 * Prices arrive from the server as decimal strings (for example "320000.00"), so
 * they are never subject to floating point rounding on the way in.
 */
export function formatMoney(
    value: string | number | null,
    currency = 'MWK',
): string {
    if (value === null || value === '') {
        return 'On request';
    }

    const amount = typeof value === 'string' ? Number.parseFloat(value) : value;

    if (!Number.isFinite(amount)) {
        return 'On request';
    }

    return `${currency} ${amount.toLocaleString('en-US', {
        maximumFractionDigits: 0,
    })}`;
}

/**
 * How a price is charged, e.g. "per person".
 */
export function formatPriceBasis(basis: string): string {
    const labels: Record<string, string> = {
        per_person: 'per person',
        per_group: 'per group',
        per_hour: 'per hour',
        per_event: 'per event',
        per_day: 'per day',
        complimentary: 'complimentary',
    };

    return labels[basis] ?? basis.replace(/_/g, ' ');
}

/**
 * Turn a snake_case category into a readable label.
 */
export function formatLabel(value: string): string {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}
