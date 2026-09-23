import type { InertiaLinkProps } from '@inertiajs/react';
import site from '@/routes/site';
import rooms from '@/routes/site/rooms';

export type SiteNavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

/**
 * The public website navigation, shared by the header and the footer.
 */
export const siteNav: SiteNavItem[] = [
    { title: 'Rooms', href: rooms.index() },
    { title: 'Dining', href: site.dining() },
    { title: 'Activities', href: site.activities() },
    { title: 'Conferences', href: site.events() },
    { title: 'Gallery', href: site.gallery() },
    { title: 'Offers', href: site.offers() },
    { title: 'About', href: site.about() },
    { title: 'Contact', href: site.contact() },
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
