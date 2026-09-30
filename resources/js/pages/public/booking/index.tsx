import { router } from '@inertiajs/react';
import { SeoHead } from '@/components/public/seo-head';
import { BedDouble } from 'lucide-react';
import {
    ANY_ROOM,
    BookingSearchFields,
    roomTypeParam,
} from '@/components/public/booking-search-fields';
import { BookingTotals } from '@/components/public/booking-totals';
import { ContactCta } from '@/components/public/contact-cta';
import { PageHero } from '@/components/public/page-hero';
import {
    imageBoxes,
    ResponsiveImage,
} from '@/components/public/responsive-image';
import { Reveal } from '@/components/public/reveal';
import { Section, SectionHeading } from '@/components/public/section';
import { Button } from '@/components/ui/button';
import { formatMoney, formatNight } from '@/lib/format';
import bookingRoutes from '@/routes/site/booking';
import type {
    BookingContext,
    BookingOffer,
    BookingSearch,
    RoomTypeOption,
} from '@/types';

type Props = {
    search: BookingSearch;
    offers: BookingOffer[];
    searched: boolean;
    roomTypes: RoomTypeOption[];
    booking: BookingContext;
};

/**
 * Step one of booking: pick the dates and party, then see what the hotel can
 * actually sell for them, priced.
 *
 * The search runs through the URL rather than in the browser, so a guest can
 * bookmark or share a set of dates, and the results they return to are the
 * results the server calculated against live inventory.
 */
export default function BookingIndex({
    search,
    offers,
    searched,
    roomTypes,
    booking,
}: Props) {
    /* Identifies the search the results belong to, so the fields can be re-seeded. */
    const searchKey = [
        search.check_in,
        search.check_out,
        search.adults,
        search.children,
        search.room_type,
    ].join('|');

    return (
        <>
            <SeoHead />

            <PageHero
                eyebrow="Book a stay"
                title="Check availability"
                description="Choose your nights and we will show you what is free, with the rate for every night of your stay."
                breadcrumb="Booking"
            />

            <Section tone="white">
                <div className="rounded-xl border border-navy/10 bg-white p-4 shadow-sm sm:p-6">
                    {/*
                        The same fields the homepage searches with. Keyed on the
                        search that produced the results below, so arriving back
                        here from the browser's history re-seeds them: without it
                        the fields would go on describing the search the guest had
                        just navigated away from.
                    */}
                    <BookingSearchFields
                        key={searchKey}
                        initialValues={{
                            from: search.check_in,
                            to: search.check_out,
                            adults: String(search.adults),
                            children: String(search.children),
                            roomType: search.room_type || ANY_ROOM,
                        }}
                        onSearch={(values) =>
                            router.get(
                                bookingRoutes.index.url(),
                                {
                                    check_in: values.from,
                                    check_out: values.to,
                                    adults: values.adults,
                                    children: values.children,
                                    room_type: roomTypeParam(values.roomType),
                                },
                                { preserveScroll: true },
                            )
                        }
                        roomTypes={roomTypes}
                        checkInTime={booking.check_in_time}
                        checkOutTime={booking.check_out_time}
                    />
                </div>

                {searched && offers.length === 0 && (
                    <div className="mt-10 rounded-xl border border-navy/10 bg-sand/50 p-8 text-center">
                        <p className="font-display text-xl font-semibold text-navy">
                            Nothing free for those dates
                        </p>
                        <p className="mx-auto mt-2 max-w-md text-sm text-navy/70">
                            Every room of that kind is taken, or the party is
                            larger than the category sleeps. Try different
                            dates, or tell us what you need and we will find
                            something.
                        </p>
                    </div>
                )}

                {searched && offers.length > 0 && (
                    <div className="mt-10 grid gap-6 lg:auto-rows-fr lg:grid-cols-2">
                        {offers.map((offer, index) => (
                            <Reveal key={offer.slug} delay={index * 60}>
                                <OfferResult
                                    offer={offer}
                                    search={search}
                                    currency="MWK"
                                />
                            </Reveal>
                        ))}
                    </div>
                )}

                {!searched && (
                    <p className="mt-8 text-sm text-navy/60">
                        Choose your dates above to see what is available. Every
                        room rate includes breakfast and the taxes shown.
                    </p>
                )}
            </Section>

            <Section tone="sand">
                <Reveal>
                    <SectionHeading
                        eyebrow="Before you book"
                        title="How booking works here"
                        description="Book direct and settle online or at the desk, whichever suits."
                        align="center"
                    />
                </Reveal>

                <div className="mt-12 grid gap-6 md:auto-rows-fr md:grid-cols-3">
                    <Reveal>
                        <PolicyCard
                            title="Paying"
                            body={
                                booking.online_payment_available
                                    ? 'Pay by card, Airtel Money or TNM Mpamba on our secure checkout, or reserve now and settle when you arrive.'
                                    : 'Reserve now and settle when you arrive. We accept card, Airtel Money, TNM Mpamba and cash at the desk.'
                            }
                        />
                    </Reveal>
                    <Reveal delay={60}>
                        <PolicyCard
                            title="Cancelling"
                            body={booking.cancellation_policy}
                        />
                    </Reveal>
                    <Reveal delay={120}>
                        <PolicyCard
                            title="Children"
                            body={booking.child_policy}
                        />
                    </Reveal>
                </div>
            </Section>

            <ContactCta
                title="Booking for a group or an event?"
                description="Conferences, weddings and larger parties are arranged with our team rather than online."
                message="Hello Lakeside Hotel, I would like to arrange a group booking."
            />
        </>
    );
}

/**
 * One sellable category for the searched dates: the rate for every night, what
 * the extras add, and the total with tax.
 */
function OfferResult({
    offer,
    search,
    currency,
}: {
    offer: BookingOffer;
    search: BookingSearch;
    currency: string;
}) {
    const reserve = () => {
        router.get(bookingRoutes.create.url(), {
            check_in: search.check_in,
            check_out: search.check_out,
            adults: search.adults,
            children: search.children,
            room_type: offer.slug,
        });
    };

    const nights = Object.entries(offer.nightly);

    return (
        <article className="flex h-full flex-col overflow-hidden rounded-xl border border-navy/10 bg-white">
            {offer.image && (
                <ResponsiveImage
                    image={offer.image}
                    from="hero"
                    sizes={imageBoxes.half}
                    alt={offer.name}
                    className="aspect-[16/9] w-full object-cover"
                />
            )}

            <div className="flex flex-1 flex-col p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 className="font-display text-xl font-semibold text-navy">
                            {offer.name}
                        </h3>
                        {offer.tagline && (
                            <p className="mt-1 text-sm text-navy/65">
                                {offer.tagline}
                            </p>
                        )}
                    </div>
                    <span className="rounded-full bg-sand px-3 py-1 text-xs font-medium text-navy">
                        {offer.available}{' '}
                        {offer.available === 1 ? 'room' : 'rooms'} left
                    </span>
                </div>

                <dl className="mt-5 space-y-1.5 border-y border-navy/10 py-4 text-sm">
                    {nights.map(([date, rate]) => (
                        <div
                            key={date}
                            className="flex items-center justify-between gap-4"
                        >
                            <dt className="text-navy/60">
                                {formatNight(date)}
                            </dt>
                            <dd className="text-navy">
                                {formatMoney(rate, currency)}
                            </dd>
                        </div>
                    ))}
                </dl>

                {offer.extra_guests > 0 && (
                    <p className="mt-3 text-xs text-navy/60">
                        Includes {offer.extra_guests} extra{' '}
                        {offer.extra_guests === 1 ? 'guest' : 'guests'} at{' '}
                        {formatMoney(offer.extra_person_price, currency)} a
                        night.
                    </p>
                )}

                <BookingTotals
                    pricing={offer.pricing}
                    label={`Room, ${offer.nights} nights`}
                    currency={currency}
                    className="mt-4"
                />

                <div className="mt-6 flex flex-1 items-end">
                    <Button onClick={reserve} size="lg" className="w-full">
                        <BedDouble />
                        Reserve this room
                    </Button>
                </div>
            </div>
        </article>
    );
}

function PolicyCard({ title, body }: { title: string; body: string }) {
    return (
        <div className="h-full rounded-xl border border-navy/10 bg-white p-6">
            <p className="text-xs font-semibold tracking-[0.18em] text-gold-dark uppercase">
                {title}
            </p>
            <p className="mt-3 text-sm leading-relaxed text-navy/75">{body}</p>
        </div>
    );
}
