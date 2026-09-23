import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Mail, MapPin, MessageCircle, Phone } from 'lucide-react';
import { BookingWidget } from '@/components/public/booking-widget';
import {
    ActivityCard,
    AmenityTile,
    AttractionCard,
    GalleryTile,
    OfferCard,
    PackageCard,
    RoomCard,
    TestimonialCard,
} from '@/components/public/cards';
import { HomeHero } from '@/components/public/home-hero';
import { MapEmbed, directionsLink } from '@/components/public/map-embed';
import { Reveal } from '@/components/public/reveal';
import { Section, SectionHeading } from '@/components/public/section';
import { Button } from '@/components/ui/button';
import { whatsappLink } from '@/lib/site-nav';
import rooms from '@/routes/site/rooms';
import type {
    ActivityData,
    AmenitySummary,
    ConferencePackageData,
    ContentBlockData,
    DiningVenueData,
    GalleryItemData,
    HeroSlideData,
    NearbyAttractionData,
    OfferData,
    RoomTypeSummary,
    SharedData,
    TestimonialData,
} from '@/types';

type Props = {
    heroSlides: HeroSlideData[];
    about: ContentBlockData | null;
    roomTypes: RoomTypeSummary[];
    amenities: AmenitySummary[];
    diningVenues: DiningVenueData[];
    activities: ActivityData[];
    conferencePackages: ConferencePackageData[];
    gallery: GalleryItemData[];
    testimonials: TestimonialData[];
    offers: OfferData[];
    attractions: NearbyAttractionData[];
};

export default function Home({
    heroSlides,
    about,
    roomTypes,
    amenities,
    diningVenues,
    activities,
    conferencePackages,
    gallery,
    testimonials,
    offers,
    attractions,
}: Props) {
    const { site } = usePage<SharedData>().props;
    const { contact } = site;

    const bookingHref = whatsappLink(
        site,
        `Hello ${site.name}, I would like to check availability.`,
    );

    return (
        <>
            <Head title={site.tagline}>
                <meta
                    name="description"
                    content={`${site.name} in Senga Bay, Salima. Rooms, suites, lakeside chalets, dining, conferences and lake activities on Lake Malawi.`}
                />
            </Head>

            <HomeHero
                slides={heroSlides}
                bookingHref={bookingHref}
                eyebrow={`Senga Bay · Lake Malawi`}
            />

            {roomTypes.length > 0 && (
                <div className="relative z-10 mx-auto -mt-12 max-w-7xl px-5 sm:px-8">
                    <BookingWidget
                        roomTypes={roomTypes.map((room) => ({
                            slug: room.slug,
                            name: room.name,
                        }))}
                    />
                </div>
            )}

            {/* About */}
            {about && (
                <Section id="about" tone="white">
                    <div className="grid gap-12 lg:grid-cols-2 lg:items-center lg:gap-16">
                        <Reveal>
                            <SectionHeading
                                eyebrow="Welcome"
                                title={about.title}
                                description={about.subtitle}
                            />
                            <div className="mt-6 space-y-4 text-base leading-relaxed whitespace-pre-line text-navy/70">
                                {about.body}
                            </div>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <Button asChild variant="outline">
                                    <Link href="/about">
                                        Our story
                                        <ArrowRight />
                                    </Link>
                                </Button>
                                <Button asChild variant="ghost">
                                    <a
                                        href={directionsLink(
                                            contact.latitude,
                                            contact.longitude,
                                        )}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        <MapPin />
                                        Get directions
                                    </a>
                                </Button>
                            </div>
                        </Reveal>

                        <Reveal delay={120} className="lg:pl-4">
                            <div className="h-80 overflow-hidden rounded-xl border border-navy/10 lg:h-[26rem]">
                                <MapEmbed
                                    latitude={contact.latitude}
                                    longitude={contact.longitude}
                                    zoom={contact.map_zoom}
                                    title={`Map showing ${site.name} in Senga Bay`}
                                />
                            </div>
                        </Reveal>
                    </div>
                </Section>
            )}

            {/* Accommodation */}
            {roomTypes.length > 0 && (
                <Section tone="sand">
                    <div className="flex flex-wrap items-end justify-between gap-6">
                        <Reveal>
                            <SectionHeading
                                eyebrow="Accommodation"
                                title="Rooms, suites and chalets by the water"
                                description="Every room looks out over the gardens or the lake, with breakfast included for two."
                            />
                        </Reveal>
                        <Reveal delay={80}>
                            <Button asChild variant="outline">
                                <Link href={rooms.index()}>
                                    All rooms
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </Reveal>
                    </div>

                    <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {roomTypes.map((roomType, index) => (
                            <Reveal key={roomType.id} delay={index * 70}>
                                <RoomCard roomType={roomType} />
                            </Reveal>
                        ))}
                    </div>
                </Section>
            )}

            {/* Amenities */}
            {amenities.length > 0 && (
                <Section tone="white">
                    <Reveal>
                        <SectionHeading
                            eyebrow="Facilities"
                            title="Everything you need on site"
                            align="center"
                        />
                    </Reveal>
                    <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {amenities.map((amenity, index) => (
                            <Reveal key={amenity.id} delay={index * 40}>
                                <AmenityTile amenity={amenity} />
                            </Reveal>
                        ))}
                    </div>
                </Section>
            )}

            {/* Dining */}
            {diningVenues.length > 0 && (
                <Section tone="navy">
                    <Reveal>
                        <SectionHeading
                            eyebrow="Dining"
                            title="Food from the lake and the farm"
                            description="Chambo and tilapia landed the same morning, served on a terrace above the water."
                            invert
                        />
                    </Reveal>

                    <div className="mt-12 grid gap-8 lg:grid-cols-3">
                        {diningVenues.map((venue, index) => (
                            <Reveal key={venue.id} delay={index * 80}>
                                <div className="flex h-full flex-col">
                                    <div className="aspect-16/10 overflow-hidden rounded-xl bg-navy-deep">
                                        {venue.cover ? (
                                            <img
                                                src={venue.cover.card}
                                                alt={
                                                    venue.cover.alt ||
                                                    venue.name
                                                }
                                                loading="lazy"
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <div className="size-full bg-linear-to-br from-navy-deep to-lake-dark" />
                                        )}
                                    </div>
                                    <h3 className="mt-5 font-display text-xl font-semibold text-white">
                                        {venue.name}
                                    </h3>
                                    {venue.tagline && (
                                        <p className="mt-1.5 text-sm text-sand/70">
                                            {venue.tagline}
                                        </p>
                                    )}
                                    {venue.signature_dishes.length > 0 && (
                                        <ul className="mt-4 space-y-2 text-sm text-sand/80">
                                            {venue.signature_dishes
                                                .slice(0, 3)
                                                .map((dish) => (
                                                    <li
                                                        key={dish.id}
                                                        className="flex justify-between gap-4 border-b border-white/10 pb-2"
                                                    >
                                                        <span>{dish.name}</span>
                                                    </li>
                                                ))}
                                        </ul>
                                    )}
                                </div>
                            </Reveal>
                        ))}
                    </div>

                    <Reveal delay={120}>
                        <div className="mt-10">
                            <Button asChild variant="secondary">
                                <Link href="/dining">
                                    See the menus
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </div>
                    </Reveal>
                </Section>
            )}

            {/* Activities */}
            {activities.length > 0 && (
                <Section tone="white">
                    <Reveal>
                        <SectionHeading
                            eyebrow="Experiences"
                            title="Days on Lake Malawi"
                            description="Sunset cruises, fishing at dawn, snorkelling over the rock shelves, or nothing at all."
                        />
                    </Reveal>
                    <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {activities.map((activity, index) => (
                            <Reveal key={activity.id} delay={index * 60}>
                                <ActivityCard activity={activity} />
                            </Reveal>
                        ))}
                    </div>
                    <Reveal delay={100}>
                        <div className="mt-10">
                            <Button asChild variant="outline">
                                <Link href="/activities">
                                    All activities
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </div>
                    </Reveal>
                </Section>
            )}

            {/* Conferences */}
            {conferencePackages.length > 0 && (
                <Section tone="mist">
                    <Reveal>
                        <SectionHeading
                            eyebrow="Conferences & events"
                            title="Meetings with a view"
                            description="A conference centre seating up to 120 delegates, weddings on the beach, and private events on the terrace."
                        />
                    </Reveal>
                    <div className="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {conferencePackages.map((pkg, index) => (
                            <Reveal key={pkg.id} delay={index * 70}>
                                <PackageCard pkg={pkg} />
                            </Reveal>
                        ))}
                    </div>
                    <Reveal delay={100}>
                        <div className="mt-10">
                            <Button asChild variant="outline">
                                <Link href="/conferences-events">
                                    Conference and event packages
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </div>
                    </Reveal>
                </Section>
            )}

            {/* Gallery */}
            {gallery.length > 0 && (
                <Section tone="white">
                    <div className="flex flex-wrap items-end justify-between gap-6">
                        <Reveal>
                            <SectionHeading
                                eyebrow="Gallery"
                                title="Senga Bay in pictures"
                            />
                        </Reveal>
                        <Reveal delay={80}>
                            <Button asChild variant="outline">
                                <Link href="/gallery">
                                    Full gallery
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </Reveal>
                    </div>
                    <div className="mt-12 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {gallery.map((item, index) => (
                            <Reveal key={item.id} delay={index * 40}>
                                <GalleryTile item={item} />
                            </Reveal>
                        ))}
                    </div>
                </Section>
            )}

            {/* Testimonials */}
            {testimonials.length > 0 && (
                <Section tone="sand">
                    <Reveal>
                        <SectionHeading
                            eyebrow="Guest reviews"
                            title="What guests say"
                            align="center"
                        />
                    </Reveal>
                    <div className="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {testimonials.map((testimonial, index) => (
                            <Reveal key={testimonial.id} delay={index * 60}>
                                <TestimonialCard testimonial={testimonial} />
                            </Reveal>
                        ))}
                    </div>
                </Section>
            )}

            {/* Offers */}
            {offers.length > 0 && (
                <Section tone="navy">
                    <Reveal>
                        <SectionHeading
                            eyebrow="Special offers"
                            title="Reasons to book direct"
                            description="Better rates than any booking site, and the flexibility to change your dates."
                            invert
                        />
                    </Reveal>
                    <div className="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {offers.map((offer, index) => (
                            <Reveal key={offer.id} delay={index * 70}>
                                <OfferCard offer={offer} tone="navy" />
                            </Reveal>
                        ))}
                    </div>
                    <Reveal delay={100}>
                        <div className="mt-10">
                            <Button asChild variant="secondary">
                                <Link href="/offers">
                                    All offers
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </div>
                    </Reveal>
                </Section>
            )}

            {/* Location */}
            {attractions.length > 0 && (
                <Section tone="white">
                    <Reveal>
                        <SectionHeading
                            eyebrow="Location"
                            title="Senga Bay, Salima"
                            description="About an hour and three quarters from Lilongwe, on the shore of Lake Malawi."
                        />
                    </Reveal>
                    <div className="mt-12 grid gap-8 lg:grid-cols-2 lg:items-start">
                        <div className="grid gap-4 sm:grid-cols-2">
                            {attractions.map((attraction, index) => (
                                <Reveal key={attraction.id} delay={index * 50}>
                                    <AttractionCard attraction={attraction} />
                                </Reveal>
                            ))}
                        </div>
                        <Reveal delay={120}>
                            <div className="h-80 overflow-hidden rounded-xl border border-navy/10 lg:h-[30rem]">
                                <MapEmbed
                                    latitude={contact.latitude}
                                    longitude={contact.longitude}
                                    zoom={contact.map_zoom}
                                    title={`Map showing ${site.name} in Senga Bay`}
                                />
                            </div>
                        </Reveal>
                    </div>
                </Section>
            )}

            {/* Contact */}
            <Section tone="sand" spacing="tight">
                <div className="grid gap-8 lg:grid-cols-3 lg:items-center">
                    <Reveal className="lg:col-span-2">
                        <SectionHeading
                            eyebrow="Get in touch"
                            title="Ready to plan your stay?"
                            description="Our reservations team answers within a few hours, every day of the week."
                        />
                    </Reveal>
                    <Reveal delay={100}>
                        <div className="flex flex-col gap-3">
                            {bookingHref && (
                                <Button asChild size="lg">
                                    <a
                                        href={bookingHref}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        <MessageCircle />
                                        WhatsApp us
                                    </a>
                                </Button>
                            )}
                            <Button asChild variant="outline" size="lg">
                                <a
                                    href={`tel:${contact.phone.replace(/\s/g, '')}`}
                                >
                                    <Phone />
                                    {contact.phone}
                                </a>
                            </Button>
                            <Button asChild variant="ghost" size="lg">
                                <a href={`mailto:${contact.email}`}>
                                    <Mail />
                                    {contact.email}
                                </a>
                            </Button>
                        </div>
                    </Reveal>
                </div>
            </Section>
        </>
    );
}
