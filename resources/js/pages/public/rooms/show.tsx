import { Head, Link, usePage } from '@inertiajs/react';
import { BedDouble, MessageCircle, Ruler, Users } from 'lucide-react';
import { RoomCard } from '@/components/public/cards';
import { ContactCta } from '@/components/public/contact-cta';
import { PageHero } from '@/components/public/page-hero';
import { Reveal } from '@/components/public/reveal';
import { Section, SectionHeading } from '@/components/public/section';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/format';
import { whatsappLink } from '@/lib/site-nav';
import rooms from '@/routes/site/rooms';
import type { RoomTypeDetail, SharedData } from '@/types';

type Props = {
    roomType: RoomTypeDetail;
    otherRoomTypes: RoomTypeDetail[];
};

export default function RoomShow({ roomType, otherRoomTypes }: Props) {
    const { site } = usePage<SharedData>().props;

    const bookingLink = whatsappLink(
        site,
        `Hello ${site.name}, I would like to book the ${roomType.name}.`,
    );

    const facts = [
        {
            icon: Users,
            label: `${roomType.max_occupancy} guests`,
            detail: `${roomType.capacity_adults} adults${
                roomType.capacity_children > 0
                    ? ` and ${roomType.capacity_children} children`
                    : ''
            }`,
        },
        roomType.size_sqm !== null && {
            icon: Ruler,
            label: `${roomType.size_sqm} m²`,
            detail: 'Room size',
        },
        roomType.bed_configuration && {
            icon: BedDouble,
            label: roomType.bed_configuration,
            detail: 'Bedding',
        },
    ].filter(Boolean) as {
        icon: typeof Users;
        label: string;
        detail: string;
    }[];

    return (
        <>
            <Head title={roomType.name}>
                <meta
                    name="description"
                    content={roomType.tagline ?? roomType.description}
                />
            </Head>

            <PageHero
                eyebrow="Accommodation"
                title={roomType.name}
                description={roomType.tagline}
                imageUrl={roomType.cover?.hero ?? null}
                breadcrumb={roomType.name}
            />

            <Section tone="white">
                <div className="grid gap-12 lg:grid-cols-3 lg:gap-16">
                    <div className="lg:col-span-2">
                        <div className="space-y-4 text-base leading-relaxed text-navy/75">
                            {roomType.description}
                        </div>

                        <dl className="mt-10 grid gap-6 border-y border-navy/10 py-8 sm:grid-cols-3">
                            {facts.map((fact) => (
                                <div
                                    key={fact.detail}
                                    className="flex items-start gap-3"
                                >
                                    <fact.icon
                                        className="mt-0.5 size-5 shrink-0 text-lake"
                                        aria-hidden
                                    />
                                    <div>
                                        <dt className="font-medium text-navy">
                                            {fact.label}
                                        </dt>
                                        <dd className="text-xs text-navy/55">
                                            {fact.detail}
                                        </dd>
                                    </div>
                                </div>
                            ))}
                        </dl>

                        {roomType.amenities.length > 0 && (
                            <>
                                <h2 className="mt-10 font-display text-2xl font-semibold text-navy">
                                    In this room
                                </h2>
                                <ul className="mt-5 grid gap-x-6 gap-y-2.5 sm:grid-cols-2">
                                    {roomType.amenities.map((amenity) => (
                                        <li
                                            key={amenity.id}
                                            className="flex items-center gap-2.5 text-sm text-navy/75"
                                        >
                                            <span
                                                aria-hidden
                                                className="size-1.5 rounded-full bg-gold"
                                            />
                                            {amenity.name}
                                        </li>
                                    ))}
                                </ul>
                            </>
                        )}

                        {roomType.images.length > 0 && (
                            <>
                                <h2 className="mt-12 font-display text-2xl font-semibold text-navy">
                                    Gallery
                                </h2>
                                <div className="mt-5 grid gap-3 sm:grid-cols-2">
                                    {roomType.images.map((image) => (
                                        <img
                                            key={image.id}
                                            src={image.card}
                                            alt={image.alt}
                                            loading="lazy"
                                            className="aspect-4/3 w-full rounded-lg object-cover"
                                        />
                                    ))}
                                </div>
                            </>
                        )}
                    </div>

                    <aside className="lg:sticky lg:top-24 lg:self-start">
                        <div className="rounded-xl border border-navy/10 bg-sand p-6">
                            <p className="text-xs text-navy/55">Rates from</p>
                            <p className="mt-1 font-display text-3xl font-semibold text-lake">
                                {formatMoney(roomType.from_price)}
                            </p>
                            <p className="text-xs text-navy/55">
                                per night, breakfast included
                            </p>

                            <dl className="mt-6 space-y-2 border-t border-navy/10 pt-5 text-sm text-navy/70">
                                <div className="flex justify-between gap-4">
                                    <dt>Weekend rate</dt>
                                    <dd className="font-medium text-navy">
                                        {formatMoney(
                                            roomType.weekend_price ??
                                                roomType.base_price,
                                        )}
                                    </dd>
                                </div>
                                {roomType.min_nights > 1 && (
                                    <div className="flex justify-between gap-4">
                                        <dt>Minimum stay</dt>
                                        <dd className="font-medium text-navy">
                                            {roomType.min_nights} nights
                                        </dd>
                                    </div>
                                )}
                                <div className="flex justify-between gap-4">
                                    <dt>Rooms of this type</dt>
                                    <dd className="font-medium text-navy">
                                        {roomType.total_rooms}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt>Check in</dt>
                                    <dd className="font-medium text-navy">
                                        from {site.contact.check_in_time}
                                    </dd>
                                </div>
                            </dl>

                            <div className="mt-6 flex flex-col gap-3">
                                {bookingLink && (
                                    <Button asChild size="lg">
                                        <a
                                            href={bookingLink}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            <MessageCircle />
                                            Check availability
                                        </a>
                                    </Button>
                                )}
                                <Button asChild variant="outline" size="lg">
                                    <a
                                        href={`tel:${site.contact.phone.replace(/\s/g, '')}`}
                                    >
                                        Call reception
                                    </a>
                                </Button>
                            </div>

                            <p className="mt-4 text-xs leading-relaxed text-navy/55">
                                {site.booking.cancellation_policy}
                            </p>
                        </div>

                        <Button asChild variant="ghost" className="mt-4 w-full">
                            <Link href={rooms.index()}>Compare all rooms</Link>
                        </Button>
                    </aside>
                </div>
            </Section>

            {otherRoomTypes.length > 0 && (
                <Section tone="sand">
                    <Reveal>
                        <SectionHeading
                            eyebrow="Also available"
                            title="Other rooms you may like"
                        />
                    </Reveal>
                    <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {otherRoomTypes.map((other, index) => (
                            <Reveal key={other.id} delay={index * 60}>
                                <RoomCard roomType={other} />
                            </Reveal>
                        ))}
                    </div>
                </Section>
            )}

            <ContactCta
                message={`Hello ${site.name}, I would like to ask about the ${roomType.name}.`}
            />
        </>
    );
}
