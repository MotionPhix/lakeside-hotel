import { Link, usePage } from '@inertiajs/react';
import { CalendarCheck, Phone } from 'lucide-react';
import { ListSortDescending } from '@/components/icons/list-sort-descending';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { siteNav } from '@/lib/site-nav';
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
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const bookingLink = bookingRoutes.index.url();

    return (
        <header className="sticky top-0 z-50 border-b border-navy/10 bg-white">
            <div className="mx-auto flex h-18 max-w-7xl items-center justify-between gap-4 px-5 sm:px-8">
                <Link
                    href={home()}
                    className="flex shrink-0 items-center"
                    aria-label={`${site.name} home`}
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
                    Eight links, a phone number and a button need about 1280px
                    between them. They were switching on at `lg` (1024px), which
                    is exactly where an iPad Pro 13 sits in portrait - 1032px -
                    so the widest tablet in common use got a desktop layout with
                    13px of overflow, the phone number broken over three lines
                    inside a 72px bar, and the button cut off at the edge. Below
                    this the menu button takes over, which is the layout that
                    width can actually carry.
                */}
                <nav
                    aria-label="Main"
                    className="hidden items-center gap-0.5 xl:flex"
                >
                    {siteNav.map((item) => (
                        <Link
                            key={item.title}
                            href={item.href}
                            className={`rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-sand hover:text-lake ${
                                isCurrentOrParentUrl(
                                    typeof item.href === 'string'
                                        ? item.href
                                        : item.href.url,
                                )
                                    ? 'text-lake'
                                    : 'text-navy/80'
                            }`}
                        >
                            {item.title}
                        </Link>
                    ))}
                </nav>

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
                            Book Your Stay
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
                            <Link
                                href={home()}
                                onClick={() => setOpen(false)}
                                className="rounded-md px-3 py-3 font-display text-lg font-semibold text-navy"
                            >
                                Home
                            </Link>
                            {siteNav.map((item) => (
                                <Link
                                    key={item.title}
                                    href={item.href}
                                    onClick={() => setOpen(false)}
                                    className="rounded-md px-3 py-3 font-display text-lg font-semibold text-navy"
                                >
                                    {item.title}
                                </Link>
                            ))}
                        </div>

                        <div className="mt-auto flex flex-col gap-3 p-4">
                            <Button asChild size="lg" className="w-full">
                                <Link
                                    href={bookingLink}
                                    onClick={() => setOpen(false)}
                                >
                                    <CalendarCheck />
                                    Book Your Stay
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
