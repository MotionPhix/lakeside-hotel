import { Link, usePage } from '@inertiajs/react';
import { Clock, Facebook, Instagram, Mail, MapPin, Phone } from 'lucide-react';
import { NavLink } from '@/components/public/nav-link';
import { NewsletterForm } from '@/components/public/newsletter-form';
import { footerNav, siteNav, whatsappLink } from '@/lib/site-nav';
import { login } from '@/routes';
import type { SharedData } from '@/types';

/**
 * The site footer.
 *
 * The brand block carries the hotel's full lockup - the script, the wave and the
 * "HOTEL AND CONFERENCE CENTRE" strapline - rather than the name set in type.
 *
 * That artwork is deep blue end to end, and this footer is navy: measured against
 * it the ink comes out at 0.49:1, which is to say invisible. So it is flattened to
 * white. `brightness-0` comes first because inverting the two blues directly turns
 * them orange and green; flattening to a single tone first makes the invert come
 * out white with the shapes intact. Transparency survives a filter, so the gaps
 * through the wave stay open.
 *
 * A light variant drawn for dark backgrounds would be better than a filter. The
 * artwork is raster with no paths to recolour, and there is not one.
 */
export function SiteFooter() {
    const { site } = usePage<SharedData>().props;

    const socials = [
        { href: site.social.facebook, label: 'Facebook', Icon: Facebook },
        { href: site.social.instagram, label: 'Instagram', Icon: Instagram },
    ].filter((social) => social.href !== '');

    return (
        <footer className="bg-navy text-sand">
            <div className="mx-auto grid max-w-7xl gap-10 px-5 py-14 sm:px-8 lg:grid-cols-12 lg:gap-8 lg:py-20">
                <div className="lg:col-span-4">
                    {/*
                        80px tall, which is the height the strapline needs before
                        it can be read - below that it turns to mush, and there is
                        no point printing it.

                        The file is its own resolution, so the browser is told the
                        ratio up front and the footer does not jump when it lands.
                    */}
                    <img
                        src="/bucket/lakeside-wordmark.png"
                        alt={site.name}
                        width={8528}
                        height={3057}
                        loading="lazy"
                        decoding="async"
                        className="h-20 w-auto brightness-0 invert"
                    />
                    <p className="mt-3 max-w-sm text-sm leading-relaxed text-sand/70">
                        {site.tagline}
                    </p>

                    {socials.length > 0 && (
                        <div className="mt-6 flex items-center gap-3">
                            {socials.map(({ href, label, Icon }) => (
                                <a
                                    key={label}
                                    href={href}
                                    target="_blank"
                                    rel="noreferrer"
                                    aria-label={label}
                                    className="rounded-full border border-sand/25 p-2 text-sand/80 transition-colors hover:border-gold hover:text-gold"
                                >
                                    <Icon className="size-4" aria-hidden />
                                </a>
                            ))}
                        </div>
                    )}
                </div>

                <nav aria-label="Footer" className="lg:col-span-2">
                    <p className="text-xs font-semibold tracking-[0.2em] text-gold uppercase">
                        Explore
                    </p>
                    <ul className="mt-4 space-y-2.5 text-sm">
                        {siteNav.map((item) => (
                            <li key={item.title}>
                                <NavLink href={item.href} variant="footer">
                                    {item.title}
                                </NavLink>
                            </li>
                        ))}
                    </ul>
                </nav>

                <div className="lg:col-span-3">
                    <p className="text-xs font-semibold tracking-[0.2em] text-gold uppercase">
                        Visit us
                    </p>
                    <ul className="mt-4 space-y-3 text-sm text-sand/75">
                        <li className="flex gap-3">
                            <MapPin
                                className="mt-0.5 size-4 shrink-0 text-gold"
                                aria-hidden
                            />
                            <span>{site.contact.address}</span>
                        </li>
                        {/* min-w-0 on each row so the text can be narrower than
                            its content. A flex row will not otherwise shrink past
                            its longest word, and the address below is one very
                            long word. */}
                        <li className="flex min-w-0 gap-3">
                            <Phone
                                className="mt-0.5 size-4 shrink-0 text-gold"
                                aria-hidden
                            />
                            <a
                                href={`tel:${site.contact.phone.replace(/\s/g, '')}`}
                                className="whitespace-nowrap transition-colors hover:text-white"
                            >
                                {site.contact.phone}
                            </a>
                        </li>
                        <li className="flex min-w-0 gap-3">
                            <Mail
                                className="mt-0.5 size-4 shrink-0 text-gold"
                                aria-hidden
                            />
                            {/* Allowed to break mid-word, which is the one place
                                on the site where that is the better trade: an
                                address has nowhere to break on its own, and the
                                alternative is a sideways-scrolling page. */}
                            <a
                                href={`mailto:${site.contact.email}`}
                                className="break-all transition-colors hover:text-white"
                            >
                                {site.contact.email}
                            </a>
                        </li>
                        <li className="flex gap-3">
                            <Clock
                                className="mt-0.5 size-4 shrink-0 text-gold"
                                aria-hidden
                            />
                            <span>
                                Check in {site.contact.check_in_time} · Check
                                out {site.contact.check_out_time}
                            </span>
                        </li>
                        {site.whatsapp_link && (
                            <li>
                                <a
                                    href={whatsappLink(site)}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 rounded-md border border-sand/25 px-3 py-2 font-medium transition-colors hover:border-gold hover:text-gold"
                                >
                                    WhatsApp{' '}
                                    {/* The number stays whole; the word before it
                                        is what gives way when there is no room. */}
                                    <span className="whitespace-nowrap">
                                        {site.contact.whatsapp}
                                    </span>
                                </a>
                            </li>
                        )}
                    </ul>
                </div>

                <div className="lg:col-span-3">
                    <p className="text-xs font-semibold tracking-[0.2em] text-gold uppercase">
                        Offers and news
                    </p>
                    <p className="mt-4 text-sm text-sand/70">
                        Occasional emails about lake packages, events and quiet
                        season rates. No more than once a month.
                    </p>
                    <NewsletterForm />
                    <ul className="mt-6 space-y-2 text-sm">
                        {footerNav.map((item) => (
                            <li key={item.title}>
                                <NavLink href={item.href} variant="footer">
                                    {item.title}
                                </NavLink>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            <div className="border-t border-sand/15">
                <div className="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-6 text-xs text-sand/60 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <p>
                        © {new Date().getFullYear()} {site.name}. All rights
                        reserved.
                    </p>
                    <Link
                        href={login()}
                        className="transition-colors hover:text-white"
                    >
                        Staff sign in
                    </Link>
                </div>
            </div>
        </footer>
    );
}
