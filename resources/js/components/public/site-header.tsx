import { Link, usePage } from '@inertiajs/react';
import { CalendarCheck, Menu, Phone } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { siteNav, whatsappLink } from '@/lib/site-nav';
import { home } from '@/routes';
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

    const bookingLink = whatsappLink(
        site,
        'Hello Lakeside Hotel, I would like to check availability.',
    );

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
                        className="h-11 w-auto sm:h-12"
                    />
                </Link>

                <nav
                    aria-label="Main"
                    className="hidden items-center gap-0.5 lg:flex"
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

                <div className="hidden items-center gap-2 lg:flex">
                    <a
                        href={`tel:${site.contact.phone.replace(/\s/g, '')}`}
                        className="flex items-center gap-2 rounded-md px-2 py-2 text-sm font-medium text-navy/80 transition-colors hover:text-lake"
                    >
                        <Phone className="size-4" aria-hidden />
                        {site.contact.phone}
                    </a>
                    <Button asChild size="lg">
                        <a href={bookingLink} target="_blank" rel="noreferrer">
                            <CalendarCheck />
                            Book Your Stay
                        </a>
                    </Button>
                </div>

                <Sheet open={open} onOpenChange={setOpen}>
                    <SheetTrigger asChild>
                        <Button
                            variant="outline"
                            size="icon"
                            className="lg:hidden"
                            aria-label="Open menu"
                        >
                            <Menu />
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
                                <a
                                    href={bookingLink}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <CalendarCheck />
                                    Book Your Stay
                                </a>
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
