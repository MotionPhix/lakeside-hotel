import { CalendarDays, Search, Users } from 'lucide-react';
import type { FormEvent } from 'react';
import { useId, useState } from 'react';
import { DateRangePicker } from '@/components/date-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

/**
 * The dates and party a guest is searching with.
 *
 * `roomType` is a room's slug, or {@link ANY_ROOM} for no preference - shadcn's
 * Select reserves the empty string, so "no preference" needs a value of its own.
 */
export type BookingSearchValues = {
    from: string;
    to: string;
    adults: string;
    children: string;
    roomType: string;
};

/** The "no preference" choice for room type. */
export const ANY_ROOM = 'any';

const EMPTY: BookingSearchValues = {
    from: '',
    to: '',
    adults: '2',
    children: '0',
    roomType: ANY_ROOM,
};

/** A night in its own right, and the most a single room here sleeps. */
const ADULT_COUNTS = [1, 2, 3, 4, 5, 6];
const CHILD_COUNTS = [0, 1, 2, 3, 4];

/**
 * Today, as the guest's own calendar reads it.
 *
 * Built from the local parts rather than `toISOString()`, which is UTC. Malawi is
 * two hours ahead, so between ten at night and midnight `toISOString()` is already
 * tomorrow - and the picker would refuse to sell the very night a guest was
 * standing at the desk to book. The calendar itself parses these strings as local
 * dates, so this has to be a local date for the two to agree.
 */
export function todayIso(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

export function emptyBookingSearch(): BookingSearchValues {
    return { ...EMPTY };
}

/** Labels sit a touch darker than the site's body grey: at 12px, /60 fails AA. */
const LABEL = 'text-xs font-medium text-navy/70';

/**
 * Every control in the bar, in one place.
 *
 * The height is not set here - a rule in app.css forces the select triggers, the
 * popover trigger and the button to `h-11` together, which is what keeps the row
 * level at 44px wherever it is used.
 */
const CONTROL =
    'w-full rounded-md border-navy/15 bg-white text-sm text-navy focus-visible:border-lake focus-visible:ring-[3px] focus-visible:ring-lake/35';

/**
 * The quick booking bar's fields, and the button that searches with them.
 *
 * One component for the two places a guest searches from - the homepage widget
 * and the booking page - because they are the same search: the same four choices,
 * the same widths, the same breakpoints. Written twice they had already drifted
 * into two different grids, and only one of them had been taught that two selects
 * side by side clip "0 children" once the column narrows.
 *
 * It owns what is typed and the caller owns what happens next, so the homepage
 * needs no state of its own. A caller whose starting values can change underneath
 * it - the booking page, where the results on screen came from a particular search
 * - passes a `key` so the fields are re-seeded alongside those results, rather
 * than describing a different search from the one being shown.
 */
export function BookingSearchFields({
    initialValues,
    onSearch,
    roomTypes,
    checkInTime,
    checkOutTime,
    min,
    className,
}: {
    /** What the fields start at. Left out, two adults and no room preference. */
    initialValues?: Partial<BookingSearchValues>;
    /** Called with the values once they will actually produce a result. */
    onSearch: (value: BookingSearchValues) => void;
    roomTypes: { slug: string; name: string }[];
    /** Shown under the fields, from the hotel's own settings. */
    checkInTime: string;
    /** Left out where the shorter note is the one that has been signed off. */
    checkOutTime?: string;
    /** ISO date; nights before it cannot be chosen. Defaults to the local today. */
    min?: string;
    className?: string;
}) {
    const base = useId();

    const ids = {
        dates: `${base}-dates`,
        adults: `${base}-adults`,
        children: `${base}-children`,
        room: `${base}-room`,
        error: `${base}-dates-error`,
    };

    const [values, setValues] = useState<BookingSearchValues>(() => ({
        ...EMPTY,
        ...initialValues,
    }));

    const [error, setError] = useState<string | null>(null);

    const patch = (changed: Partial<BookingSearchValues>) => {
        setValues((current) => ({ ...current, ...changed }));
    };

    /*
     * react-day-picker reports both ends on the first click, so a range whose ends
     * are the same day is a half-finished selection rather than a one-night stay.
     * Comparing the strings as well as checking they exist catches that, and any
     * hand-edited URL arriving with the dates the wrong way round.
     */
    const hasNights = Boolean(
        values.from && values.to && values.to > values.from,
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!hasNights) {
            setError(
                'Choose your check-in and check-out dates to see what is free.',
            );
            document.getElementById(ids.dates)?.focus();

            return;
        }

        setError(null);
        onSearch(values);
    };

    /** Correcting the dates clears the complaint about them straight away. */
    const changeDates = (range: { from: string; to: string }) => {
        patch(range);
        setError(null);
    };

    return (
        <form onSubmit={submit} noValidate className={className}>
            {/*
                Twelve columns from lg. The button takes a row of its own at lg and
                joins the row at xl: in between, the four fields and the button on a
                single line leaves the button about 140px, which is narrower than
                its own label and was wrapping onto two lines in the middle of a
                tablet. The fields fill all twelve at lg so the row has no gap in it.
            */}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-12">
                <div className="flex flex-col justify-end gap-1.5 sm:col-span-2 lg:col-span-4 xl:col-span-3">
                    <Label htmlFor={ids.dates} className={LABEL}>
                        Check in – check out
                    </Label>
                    <DateRangePicker
                        id={ids.dates}
                        from={values.from}
                        to={values.to}
                        onChange={changeDates}
                        min={min ?? todayIso()}
                        placeholder="Choose your dates"
                        aria-invalid={Boolean(error)}
                        aria-describedby={error ? ids.error : undefined}
                        className={CONTROL}
                    />
                </div>

                <div className="flex flex-col justify-end gap-1.5 lg:col-span-2">
                    <Label htmlFor={ids.adults} className={LABEL}>
                        Adults
                    </Label>
                    <Select
                        value={values.adults}
                        onValueChange={(adults) => patch({ adults })}
                    >
                        <SelectTrigger
                            id={ids.adults}
                            className={CONTROL}
                            aria-label="Adults"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {ADULT_COUNTS.map((count) => (
                                <SelectItem key={count} value={String(count)}>
                                    {count} {count === 1 ? 'adult' : 'adults'}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex flex-col justify-end gap-1.5 lg:col-span-2">
                    <Label htmlFor={ids.children} className={LABEL}>
                        Children
                    </Label>
                    <Select
                        value={values.children}
                        onValueChange={(children) => patch({ children })}
                    >
                        <SelectTrigger
                            id={ids.children}
                            className={CONTROL}
                            aria-label="Children"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {CHILD_COUNTS.map((count) => (
                                <SelectItem key={count} value={String(count)}>
                                    {count} {count === 1 ? 'child' : 'children'}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex flex-col justify-end gap-1.5 sm:col-span-2 lg:col-span-4 xl:col-span-3">
                    <Label htmlFor={ids.room} className={LABEL}>
                        Room type
                    </Label>
                    <Select
                        value={values.roomType}
                        onValueChange={(roomType) => patch({ roomType })}
                    >
                        <SelectTrigger
                            id={ids.room}
                            className={CONTROL}
                            aria-label="Room type"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY_ROOM}>Any room</SelectItem>
                            {roomTypes.map((room) => (
                                <SelectItem key={room.slug} value={room.slug}>
                                    {room.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex flex-col justify-end sm:col-span-2 lg:col-span-12 xl:col-span-2">
                    <Button type="submit" size="lg" className="w-full">
                        <Search aria-hidden />
                        Check availability
                    </Button>
                </div>
            </div>

            {/*
                `role="alert"` so it is announced the moment it appears: the field it
                refers to is above, and a guest using a screen reader should not have
                to go looking for why nothing happened.
            */}
            <InputError
                id={ids.error}
                role="alert"
                message={error ?? undefined}
                className="mt-2 text-xs"
            />

            <p className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-navy/70">
                <span className="flex items-center gap-1.5">
                    <CalendarDays className="size-3.5" aria-hidden />
                    Check in from {checkInTime}
                    {checkOutTime ? `, out by ${checkOutTime}` : ''}
                </span>
                <span className="flex items-center gap-1.5">
                    <Users className="size-3.5" aria-hidden />
                    Best rate guaranteed when you book direct
                </span>
            </p>
        </form>
    );
}

/** Keeps the room-type sentinel out of the query string. */
export function roomTypeParam(roomType: string): string {
    return roomType === ANY_ROOM ? '' : roomType;
}
