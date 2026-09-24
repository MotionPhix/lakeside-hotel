import { router } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

type FilterValue = string | number | boolean | null | undefined;

/**
 * Filter state that applies itself.
 *
 * Filtering is the back end's work - the list is queried, ordered and paginated
 * there - so a filter change is a request. What this hook removes is the *click*:
 * typing asks once the typist pauses, and choosing from a select, a checkbox or a
 * date asks at once, because a choice is already a decision and there is nothing
 * left to wait for. A "Filter" button asks the user to say the same thing twice.
 *
 * Three details are what make it usable rather than merely automatic:
 *
 * - The first run is skipped, so opening a page does not re-request the page that
 *   has just rendered it.
 * - Every change *replaces* the previous history entry instead of pushing one, or
 *   a five-letter search would leave five pages behind the back button.
 * - State and scroll position are preserved, so the list does not jump to the top
 *   and the box being typed in is not wiped out by the answer to its own question.
 *
 * Anything blank is left out of the query, so clearing a filter produces the URL
 * you would have got by never setting it.
 */
export function useFilters<T extends Record<string, FilterValue>>(
    url: string,
    current: T,
    delay = 300,
) {
    const [values, setValues] = useState<T>(current);

    // The values as they were on arrival, so clearing can go back to the page the
    // user opened rather than to a blank one.
    const untouched = useRef(values);
    const skipFirstRun = useRef(true);
    const waitFor = useRef(delay);

    const query = useMemo(() => serialise(values), [values]);

    // The payload is read through a ref so the effect can depend on the query
    // alone: as a dependency, an object rebuilt each render would schedule a
    // request per render.
    const payload = useRef(query);
    payload.current = query;

    useEffect(() => {
        if (skipFirstRun.current) {
            skipFirstRun.current = false;

            return;
        }

        const wait = waitFor.current;
        waitFor.current = delay;

        const timer = setTimeout(() => {
            router.get(url, payload.current, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, wait);

        return () => clearTimeout(timer);
    }, [query, url, delay]);

    /**
     * Set one filter.
     *
     * Pass `true` for a control that has finished deciding - a select, a
     * checkbox, a date - and leave it out while a person is still typing.
     */
    const set = useCallback(
        <K extends keyof T>(field: K, value: T[K], immediate = false) => {
            waitFor.current = immediate ? 0 : delay;

            setValues((previous) =>
                previous[field] === value
                    ? previous
                    : { ...previous, [field]: value },
            );
        },
        [delay],
    );

    const reset = useCallback(() => {
        waitFor.current = 0;

        setValues(blank(untouched.current));
    }, []);

    return {
        values,
        set,
        reset,
        /** Whether anything is actually being filtered, for showing Clear. */
        active: Object.keys(query).length > 0,
    };
}

/**
 * The values as a query string object, with anything blank left out.
 */
function serialise<T extends Record<string, FilterValue>>(
    values: T,
): Record<string, string> {
    const query: Record<string, string> = {};

    for (const [field, value] of Object.entries(values)) {
        if (
            value === null ||
            value === undefined ||
            value === '' ||
            value === false
        ) {
            continue;
        }

        query[field] = value === true ? '1' : String(value);
    }

    return query;
}

/**
 * The same shape as the given values, with every filter cleared.
 */
function blank<T extends Record<string, FilterValue>>(values: T): T {
    return Object.fromEntries(
        Object.entries(values).map(([field, value]) => [
            field,
            typeof value === 'boolean' ? false : '',
        ]),
    ) as T;
}
