import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * Every whole number from one bound to the other.
 *
 * Used wherever a quantity is chosen - how many places on a boat trip, how many
 * hours of kayak hire - so the options offered are the bounds the server gave
 * rather than a second opinion about them.
 */
export function quantitiesBetween(min: number, max: number): number[] {
    const counts: number[] = [];

    for (let count = min; count <= max; count += 1) {
        counts.push(count);
    }

    return counts;
}
