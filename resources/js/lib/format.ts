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

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as const;
const WEEKDAYS_LONG = [
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
] as const;
const MONTHS = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
] as const;
const MONTHS_LONG = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
] as const;

/**
 * Read a `YYYY-MM-DD` string as a local date.
 *
 * The parts are fed to the constructor rather than the string, because parsing
 * "2026-11-10" as a string treats it as UTC midnight and can land on the day
 * before for anybody east of Greenwich.
 */
function parseIsoDate(iso: string): Date | null {
    const parts = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso);

    if (parts === null) {
        return null;
    }

    return new Date(Number(parts[1]), Number(parts[2]) - 1, Number(parts[3]));
}

/**
 * One night of a stay, e.g. "Tue 10 Nov".
 *
 * The names are spelled out here rather than asked of `toLocaleDateString`,
 * because the server and the browser do not agree on what the default locale
 * means: Node renders "Tue, 10 Nov" where a browser renders "Tue 10 Nov". React
 * sees the two disagree and throws the server's markup away, which is a real
 * error in the console and a wasted render on every booking.
 */
export function formatNight(iso: string): string {
    const date = parseIsoDate(iso);

    if (date === null) {
        return iso;
    }

    return `${WEEKDAYS[date.getDay()]} ${date.getDate()} ${MONTHS[date.getMonth()]}`;
}

/**
 * A whole date, e.g. "Tuesday 10 November 2026". Spelled out for the same
 * reason, and because a booking confirmation should read the same on every
 * machine that opens it.
 */
export function formatLongDate(iso: string): string {
    const date = parseIsoDate(iso);

    if (date === null) {
        return iso;
    }

    return `${WEEKDAYS_LONG[date.getDay()]} ${date.getDate()} ${MONTHS_LONG[date.getMonth()]} ${date.getFullYear()}`;
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
