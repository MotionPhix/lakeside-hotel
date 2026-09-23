import { Head, router } from '@inertiajs/react';
import { BedDouble, CalendarDays, Search, Users } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { DateRangePicker } from '@/components/date-picker';
import { ContactCta } from '@/components/public/contact-cta';
import { PageHero } from '@/components/public/page-hero';
import { Reveal } from '@/components/public/reveal';
import { Section, SectionHeading } from '@/components/public/section';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

/** The "any room" choice shadcn's Select needs as a real value. */
const ANY_ROOM = 'any';

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
    const [range, setRange] = useState({
        from: search.check_in,
        to: search.check_out,
    });
    const [adults, setAdults] = useState(String(search.adults));
    const [children, setChildren] = useState(String(search.children));
    const [roomType, setRoomType] = useState(search.room_type || ANY_ROOM);

    const today = new Date().toISOString().slice(0, 10);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        router.get(
            bookingRoutes.index.url(),
            {
                check_in: range.from,
                check_out: range.to,
                adults,
                children,
                room_type: roomType === ANY_ROOM ? '' : roomType,
            },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Book your stay">
                <meta
                    name="description"
                    content="Check availability and book a room at Lakeside Hotel and Conference Centre, Senga Bay, Salima."
                />
            </Head>

            <PageHero
                eyebrow="Book a stay"
                title="Check availability"
                description="Choose your nights and we will show you what is free, with the rate for every night of your stay."
                breadcrumb="Booking"
            />

            <Section tone="white">
                <form
                    onSubmit={submit}
                    className="rounded-xl border border-navy/10 bg-white p-4 shadow-sm sm:p-6"
                >
                    <div className="grid gap-4 lg:grid-cols-12">
                        <div className="grid gap-1.5 lg:col-span-4">
                            <Label className="text-xs text-navy/60">
                                Check in – check out
                            </Label>
                            <DateRangePicker
                                from={range.from}
                                to={range.to}
                                onChange={setRange}
                                min={today}
                                placeholder="Choose your dates"
                                className="w-full rounded-md border-navy/15 bg-white text-sm text-navy"
                            />
                        </div>

                        <div className="grid gap-1.5 lg:col-span-3">
                            <Label className="text-xs text-navy/60">
                                Guests
                            </Label>
                            <div className="grid grid-cols-2 gap-2">
                                <Select
                                    value={adults}
                                    onValueChange={setAdults}
                                >
                                    <SelectTrigger
                                        className="w-full rounded-md border-navy/15 bg-white text-sm text-navy"
                                        aria-label="Adults"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {[1, 2, 3, 4, 5, 6].map((count) => (
                                            <SelectItem
                                                key={count}
                                                value={String(count)}
                                            >
                                                {count}{' '}
                                                {count === 1
                                                    ? 'adult'
                                                    : 'adults'}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select
                                    value={children}
                                    onValueChange={setChildren}
                                >
                                    <SelectTrigger
                                        className="w-full rounded-md border-navy/15 bg-white text-sm text-navy"
                                        aria-label="Children"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {[0, 1, 2, 3].map((count) => (
                                            <SelectItem
                                                key={count}
                                                value={String(count)}
                                            >
                                                {count}{' '}
                                                {count === 1
                                                    ? 'child'
                                                    : 'children'}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid gap-1.5 lg:col-span-3">
                            <Label className="text-xs text-navy/60">
                                Room type
                            </Label>
                            <Select
                                value={roomType}
                                onValueChange={setRoomType}
                            >
                                <SelectTrigger className="w-full rounded-md border-navy/15 bg-white text-sm text-navy">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ANY_ROOM}>
                                        Any room
                                    </SelectItem>
                                    {roomTypes.map((option) => (
                                        <SelectItem
                                            key={option.slug}
                                            value={option.slug}
                                        >
                                            {option.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="flex items-end lg:col-span-2">
                            <Button type="submit" size="lg" className="w-full">
                                <Search />
                                Check availability
                            </Button>
                        </div>
                    </div>

                    <p className="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-navy/55">
                        <span className="flex items-center gap-1.5">
                            <CalendarDays className="size-3.5" aria-hidden />
                            Check in from {booking.check_in_time}, out by{' '}
                            {booking.check_out_time}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <Users className="size-3.5" aria-hidden />
                            Best rate guaranteed when you book direct
                        </span>
                    </p>
                </form>

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
                    <div className="mt-10 grid gap-6 lg:grid-cols-2">
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

                <div className="mt-12 grid gap-6 md:grid-cols-3">
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
                <img
                    src={offer.image}
                    alt={offer.name}
                    loading="lazy"
                    decoding="async"
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

                <dl className="mt-4 space-y-2 text-sm">
                    <div className="flex justify-between gap-4">
                        <dt className="text-navy/60">
                            Room, {offer.nights} nights
                        </dt>
                        <dd className="text-navy">
                            {formatMoney(offer.subtotal, currency)}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-4">
                        <dt className="text-navy/60">VAT and tourism levy</dt>
                        <dd className="text-navy">
                            {formatMoney(offer.tax_total, currency)}
                        </dd>
                    </div>
                    <div className="flex justify-between gap-4 border-t border-navy/10 pt-2 text-base font-semibold">
                        <dt className="text-navy">Total</dt>
                        <dd className="text-navy">
                            {formatMoney(offer.total, currency)}
                        </dd>
                    </div>
                </dl>

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
