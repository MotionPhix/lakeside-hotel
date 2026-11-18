import type { InertiaLinkProps } from '@inertiajs/react';
import site from '@/routes/site';
import rooms from '@/routes/site/rooms';

export type SiteNavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

/*
 * Each page is written once, then referred to by the lists below. Written twice
 * the label and the href drift apart, and a page ends up called one thing in the
 * bar and another in the footer.
 */
const accommodation: SiteNavItem = {
    title: 'Accommodation',
    href: rooms.index(),
};
const restaurant: SiteNavItem = { title: 'Restaurant', href: site.dining() };
const activities: SiteNavItem = {
    title: 'Activities',
    href: site.activities(),
};
const conference: SiteNavItem = { title: 'Conference', href: site.events() };
const gallery: SiteNavItem = { title: 'Gallery', href: site.gallery() };
const offers: SiteNavItem = { title: 'Offers', href: site.offers() };
const about: SiteNavItem = { title: 'About', href: site.about() };
const contact: SiteNavItem = { title: 'Contact', href: site.contact() };

/**
 * The pages that sit in the bar itself.
 *
 * Four, in the order a guest books a stay: where they sleep, where they meet,
 * where they eat, and how to reach the desk. The rest are one click away behind
 * More, which is what keeps the bar short enough to hold a phone number and the
 * booking button on the same line at 1280px.
 */
export const primaryNav: SiteNavItem[] = [
    accommodation,
    conference,
    restaurant,
    contact,
];

/**
 * Everything that used to sit in the bar and no longer has to.
 *
 * Kept in the order the old bar listed them, so the menu reads the same way the
 * navigation always did rather than in an order only this list knows.
 *
 * "Activities" is here because the page exists and guests use it; the four in
 * the bar are the ones a booking turns on - where you sleep, meet and eat, and
 * the desk - not the only four the hotel has. The names the rest of the site
 * uses are kept: the footer's second column still spells the conference page
 * out in full, and that page is titled "Conferences & events".
 */
export const moreNav: SiteNavItem[] = [activities, gallery, offers, about];

/**
 * The public website navigation, shared by the mobile menu and the footer.
 *
 * The desktop bar draws `primaryNav` plus `moreNav` instead: the same pages, in
 * two groups. This list stays complete so nothing is only reachable on a wide
 * screen.
 */
export const siteNav: SiteNavItem[] = [
    accommodation,
    restaurant,
    activities,
    conference,
    gallery,
    offers,
    about,
    contact,
];

/**
 * The shorter second list in the footer.
 */
export const footerNav: SiteNavItem[] = [
    { title: 'Booking policies', href: site.policies() },
    { title: 'Gallery', href: site.gallery() },
    { title: 'Conferences & events', href: site.events() },
    { title: 'Special offers', href: site.offers() },
];

/**
 * Build the WhatsApp link, optionally pre-filling the message.
 */
export function whatsappLink(
    site$: { whatsapp_link: string },
    message?: string,
): string {
    if (!site$.whatsapp_link) {
        return '';
    }

    return message
        ? `${site$.whatsapp_link}?text=${encodeURIComponent(message)}`
        : site$.whatsapp_link;
}
