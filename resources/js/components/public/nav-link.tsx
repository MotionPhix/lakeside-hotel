import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';

/**
 * Where a link is being drawn, which decides how the current one is marked.
 *
 * `header` and `mobile` are the two states of the same menu - the horizontal bar
 * and the panel that replaces it below 1280px - and `footer` is the same links
 * listed again at the bottom of every page.
 */
export type NavLinkVariant = 'header' | 'mobile' | 'footer';

/**
 * The active treatment is one marker in one colour, the hotel's gold, so a guest
 * sees the same signal wherever they meet the navigation:
 *
 *   header - a gold rule sitting inside the bottom of the pill
 *   mobile - a gold underline under the label
 *   footer - the same underline
 *
 * The gold is not a new idea here. The hero slider already marks the slide you
 * are on with a gold bar, and the footer's column headings are gold, so this
 * reads as the site's own "you are here" rather than something bolted on.
 *
 * Nothing above changes weight. Bolding the current item would re-measure it and
 * nudge every link after it sideways the moment a guest navigates - the sort of
 * jump that reads as a bug. Colour and the marker carry it instead.
 */
const VARIANTS: Record<NavLinkVariant, { base: string; active: string }> = {
    header: {
        base: 'relative rounded-md px-3 py-2 text-sm font-medium text-navy/80 transition-colors hover:bg-sand hover:text-lake',
        active: "text-lake after:absolute after:inset-x-3 after:bottom-0.5 after:h-0.5 after:rounded-full after:bg-gold after:content-['']",
    },
    mobile: {
        base: 'rounded-md px-3 py-3 font-display text-lg font-semibold text-navy transition-colors',
        active: 'text-lake underline decoration-gold decoration-2 underline-offset-[6px]',
    },
    footer: {
        base: 'text-sand/75 transition-colors hover:text-white',
        active: 'text-white underline decoration-gold decoration-2 underline-offset-4',
    },
};

/** One trailing slash removed, so `/rooms/` and `/rooms` answer the same. */
function normalise(path: string): string {
    return path.length > 1 ? path.replace(/\/+$/, '') : path;
}

/** The path a link resolves to, ignoring anything after `?` or `#`. */
function pathOf(href: NonNullable<InertiaLinkProps['href']>): string {
    const url = toUrl(href);

    if (url.startsWith('http')) {
        try {
            return normalise(new URL(url).pathname);
        } catch {
            return '';
        }
    }

    return normalise(url.split(/[?#]/)[0]);
}

/**
 * A navigation link that knows whether it leads to where the guest already is.
 *
 * Used by every place the site lists its pages - the header bar, the menu panel
 * and both footer columns - because they are the same navigation drawn three
 * ways, and marking the current page in only some of them is worse than marking
 * it in none. Written once, they cannot disagree about where the guest is.
 */
export function NavLink({
    href,
    variant,
    onNavigate,
    className,
    children,
}: {
    href: NonNullable<InertiaLinkProps['href']>;
    variant: NavLinkVariant;
    /** Called before navigating, which is how the menu panel closes itself. */
    onNavigate?: () => void;
    className?: string;
    children: ReactNode;
}) {
    const { currentUrl } = useCurrentUrl();

    const target = pathOf(href);
    const here = normalise(currentUrl);

    const isExact = target !== '' && here === target;

    /*
     * `/` is a prefix of every path there is, so the home link can only ever be
     * answered by the exact match above - a prefix test would light Home up on
     * every page of the site. Everything else owns the paths beneath it, which is
     * what makes a room's own page still read as Rooms. The trailing slash
     * matters: without it `/offers-archive` would answer to Offers.
     */
    const isSection =
        !isExact && target !== '/' && here.startsWith(`${target}/`);

    const active = isExact || isSection;

    return (
        <Link
            href={href}
            onClick={onNavigate}
            /*
             * "page" only where this is the page. A section a guest happens to be
             * standing inside is "true", which is the value that exists for
             * exactly that - a screen reader announcing the rooms list as the
             * current page while the guest reads one particular room would be
             * telling them something untrue.
             */
            aria-current={isExact ? 'page' : isSection ? 'true' : undefined}
            className={cn(
                VARIANTS[variant].base,
                active && VARIANTS[variant].active,
                className,
            )}
        >
            {children}
        </Link>
    );
}
