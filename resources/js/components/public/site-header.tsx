import { Link, usePage } from '@inertiajs/react';
import { CalendarCheck, MoreHorizontal, Phone } from 'lucide-react';
import { ListSortDescending } from '@/components/icons/list-sort-descending';
import { NavLink } from '@/components/public/nav-link';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { useState } from 'react';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { moreNav, primaryNav, siteNav } from '@/lib/site-nav';
import { home } from '@/routes';
import bookingRoutes from '@/routes/site/booking';
import type { SharedData } from '@/types';

/**
 * The public site header.
 *
 * A solid white bar rather than one floating over the hero: the hotel's logo is
 * deep blue artwork with no light variant, so it needs a light surface to stay
 * legible.
 */
export function SiteHeader() {
    const { site } = usePage<SharedData>().props;
    const [open, setOpen] = useState(false);
    const { isCurrentUrl } = useCurrentUrl();

    const bookingLink = bookingRoutes.index.url();

    /* Is the page on screen one of the three behind More? */
    const moreIsCurrent = moreNav.some((item) => isCurrentUrl(item.href));

    return (
        <header className="sticky top-0 z-50 border-b border-navy/10 bg-white">
            {/*
                The bar is deliberately one step wider (`max-w-7xl`) than the
                content column in `section.tsx` (`max-w-6xl`). The navigation is
                chrome: it reads as a full-width band with the logo sitting a
                little outside the text below it, while the sections, page heroes
                and the footer all line up in the tighter column.
            */}
            <div className="mx-auto flex h-18 max-w-7xl items-center justify-between gap-4 px-5 sm:px-8">
                {/*
                    The logo is the only way home from the desktop bar - there is
                    no Home link beside the other eight - so it is what carries
                    "you are here" on the homepage, where no other item is current.
                */}
                <Link
                    href={home()}
                    className="flex shrink-0 items-center"
                    aria-label={`${site.name} home`}
                    aria-current={isCurrentUrl(home()) ? 'page' : undefined}
                >
                    <img
                        src={site.logo}
                        alt={site.name}
                        /* The size of the file itself. The stylesheet sets the
                           height and lets the width follow, and the browser needs
                           to know the ratio before the image arrives or the whole
                           header moves down the moment it does. */
                        width={8084}
                        height={2304}
                        loading="eager"
                        decoding="async"
                        className="h-11 w-auto sm:h-12"
                    />
                </Link>

                {/*
                    The bar still switches on at `xl` (1280px), for the same
                    reason it always did: below that, a phone number that must
                    not wrap and a booking button with a label do not fit beside
                    the links, and the menu button takes over.

                    What changed is how much has to fit. Four links and the More
                    trigger are narrower than the eight links they replace, so
                    the bar has room to breathe rather than being fitted to the
                    pixel - and the three pages that moved into More are still
                    one click away, which is the trade that buys the longer
                    labels ("Accommodation" is half again the width of "Rooms").

                    Both sit in one flex row: as separate children of the
                    space-between bar they would be pushed apart, leaving More
                    floating in the gap between the links and the phone number.
                */}
                <div className="hidden items-center gap-0.5 xl:flex">
                    <nav
                        aria-label="Main"
                        className="flex items-center gap-0.5"
                    >
                        {primaryNav.map((item) => (
                            <NavLink
                                key={item.title}
                                href={item.href}
                                variant="header"
                            >
                                {item.title}
                            </NavLink>
                        ))}
                    </nav>

                    {/*
                        The More icon carries the rest of the old bar. It is
                        icon-only so the row stays short, which makes the
                        accessible name - not the glyph - the thing that tells a
                        screen reader what it opens.

                        The gold rule under it is the header's own "you are
                        here" marker, reused: while the guest is on Gallery,
                        Offers or About, the page they are reading is hidden
                        behind this button, so the button is what has to say so.
                    */}
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button
                                type="button"
                                aria-label="More pages"
                                className={cn(
                                    'relative inline-flex size-9 cursor-pointer items-center justify-center rounded-md text-navy/80 transition-colors hover:bg-sand hover:text-lake focus-visible:ring-2 focus-visible:ring-lake focus-visible:outline-none',
                                    moreIsCurrent &&
                                        "text-lake after:absolute after:inset-x-1.5 after:bottom-0.5 after:h-0.5 after:rounded-full after:bg-gold after:content-['']",
                                )}
                            >
                                <MoreHorizontal
                                    className="size-5"
                                    aria-hidden
                                />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-44">
                            {moreNav.map((item) => (
                                <DropdownMenuItem key={item.title} asChild>
                                    <Link
                                        href={item.href}
                                        className={cn(
                                            'cursor-pointer',
                                            isCurrentUrl(item.href) &&
                                                'text-lake',
                                        )}
                                        aria-current={
                                            isCurrentUrl(item.href)
                                                ? 'page'
                                                : undefined
                                        }
                                    >
                                        {item.title}
                                    </Link>
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <div className="hidden items-center gap-2 xl:flex">
                    {/*
                        A phone number is the one string on the page that must
                        never be broken across lines: "+265 / 1 263 / 400" is not
                        a number anyone can read back.
                    */}
                    <a
                        href={`tel:${site.contact.phone.replace(/\s/g, '')}`}
                        className="flex items-center gap-2 rounded-md px-2 py-2 text-sm font-medium whitespace-nowrap text-navy/80 transition-colors hover:text-lake"
                    >
                        <Phone className="size-4 shrink-0" aria-hidden />
                        {site.contact.phone}
                    </a>
                    <Button asChild size="lg">
                        <Link href={bookingLink}>
                            <CalendarCheck />
                            Book now
                        </Link>
                    </Button>
                </div>

                <Sheet open={open} onOpenChange={setOpen}>
                    <SheetTrigger asChild>
                        {/* Explicitly square. `size="icon"` alone left this 36
                            wide and 44 tall, which read as a squashed rectangle
                            next to a square-ish logo. */}
                        <Button
                            variant="outline"
                            size="icon"
                            className="size-11 shrink-0 xl:hidden"
                            aria-label="Open menu"
                        >
                            <ListSortDescending className="size-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent
                        side="right"
                        className="w-full max-w-sm bg-white"
                    >
                        <SheetTitle className="px-4 pt-4 text-left">
                            <span className="sr-only">Menu</span>
                        </SheetTitle>

                        <div className="flex flex-col gap-1 px-4 pt-8">
                            <NavLink
                                href={home()}
                                variant="mobile"
                                onNavigate={() => setOpen(false)}
                            >
                                Home
                            </NavLink>
                            {siteNav.map((item) => (
                                <NavLink
                                    key={item.title}
                                    href={item.href}
                                    variant="mobile"
                                    onNavigate={() => setOpen(false)}
                                >
                                    {item.title}
                                </NavLink>
                            ))}
                        </div>

                        <div className="mt-auto flex flex-col gap-3 p-4">
                            <Button asChild size="lg" className="w-full">
                                <Link
                                    href={bookingLink}
                                    onClick={() => setOpen(false)}
                                >
                                    <CalendarCheck />
                                    Book now
                                </Link>
                            </Button>
                            <Button
                                asChild
                                variant="outline"
                                size="lg"
                                className="w-full"
                            >
                                <a
                                    href={`tel:${site.contact.phone.replace(/\s/g, '')}`}
                                >
                                    <Phone />
                                    {site.contact.phone}
                                </a>
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </header>
    );
}
