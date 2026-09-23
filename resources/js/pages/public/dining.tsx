import { Head } from '@inertiajs/react';
import { Clock, Shirt, Star } from 'lucide-react';
import { ContactCta } from '@/components/public/contact-cta';
import { PageHero } from '@/components/public/page-hero';
import { Reveal } from '@/components/public/reveal';
import { Section, SectionHeading } from '@/components/public/section';
import { formatMoney } from '@/lib/format';
import type { ContentBlockData, DiningVenueData } from '@/types';

type Props = {
    intro: ContentBlockData | null;
    venues: DiningVenueData[];
};

export default function Dining({ intro, venues }: Props) {
    return (
        <>
            <Head title="Restaurant & Dining">
                <meta
                    name="description"
                    content="Dining at Lakeside Hotel and Conference Centre, Senga Bay: chambo and tilapia from Lake Malawi, a terrace restaurant, the Anchor Bar and a poolside bar."
                />
            </Head>

            <PageHero
                eyebrow="Dining"
                title={intro?.title ?? 'Food from the lake and the farm'}
                description={intro?.subtitle}
                imageUrl={venues[0]?.cover?.hero ?? null}
                breadcrumb="Dining"
            />

            {intro?.body && (
                <Section tone="white" spacing="tight">
                    <div className="max-w-3xl space-y-4 text-base leading-relaxed whitespace-pre-line text-navy/70">
                        {intro.body}
                    </div>
                </Section>
            )}

            {venues.map((venue, index) => (
                <Section
                    key={venue.id}
                    tone={index % 2 === 0 ? 'sand' : 'white'}
                >
                    <div className="grid gap-10 lg:grid-cols-2 lg:gap-16">
                        <Reveal className={index % 2 === 1 ? 'lg:order-2' : ''}>
                            <div className="overflow-hidden rounded-xl bg-sand">
                                {venue.cover ? (
                                    <img
                                        src={venue.cover.card}
                                        alt={venue.cover.alt || venue.name}
                                        loading="lazy"
                                        className="aspect-4/3 w-full object-cover"
                                    />
                                ) : (
                                    <div className="aspect-4/3 w-full bg-linear-to-br from-navy to-lake-dark" />
                                )}
                            </div>
                        </Reveal>

                        <Reveal delay={100}>
                            <SectionHeading
                                eyebrow="Venue"
                                title={venue.name}
                                description={venue.tagline}
                            />

                            {venue.description && (
                                <p className="mt-5 text-base leading-relaxed text-navy/70">
                                    {venue.description}
                                </p>
                            )}

                            <ul className="mt-6 space-y-2 text-sm text-navy/70">
                                {venue.opening_hours &&
                                    Object.entries(venue.opening_hours).map(
                                        ([period, hours]) => (
                                            <li
                                                key={period}
                                                className="flex items-center gap-2.5"
                                            >
                                                <Clock
                                                    className="size-4 text-lake"
                                                    aria-hidden
                                                />
                                                <span className="capitalize">
                                                    {period}
                                                </span>
                                                <span className="text-navy/50">
                                                    {hours}
                                                </span>
                                            </li>
                                        ),
                                    )}
                                {venue.dress_code && (
                                    <li className="flex items-center gap-2.5">
                                        <Shirt
                                            className="size-4 text-lake"
                                            aria-hidden
                                        />
                                        {venue.dress_code}
                                    </li>
                                )}
                            </ul>

                            {venue.signature_dishes.length > 0 && (
                                <div className="mt-8">
                                    <h3 className="flex items-center gap-2 font-display text-lg font-semibold text-navy">
                                        <Star
                                            className="size-4 fill-current text-gold"
                                            aria-hidden
                                        />
                                        Signature dishes
                                    </h3>
                                    <ul className="mt-4 space-y-4">
                                        {venue.signature_dishes.map((dish) => (
                                            <li
                                                key={dish.id}
                                                className="border-b border-navy/10 pb-3"
                                            >
                                                <div className="flex items-baseline justify-between gap-4">
                                                    <p className="font-medium text-navy">
                                                        {dish.name}
                                                    </p>
                                                    <p className="shrink-0 font-display font-semibold text-lake">
                                                        {formatMoney(
                                                            dish.price,
                                                        )}
                                                    </p>
                                                </div>
                                                {dish.description && (
                                                    <p className="mt-1 text-sm text-navy/60">
                                                        {dish.description}
                                                    </p>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </Reveal>
                    </div>

                    {venue.menu.length > 0 && (
                        <Reveal delay={120}>
                            <div className="mt-16 border-t border-navy/10 pt-12">
                                <h3 className="font-display text-2xl font-semibold text-navy">
                                    Full menu
                                </h3>
                                <div className="mt-8 grid gap-10 md:grid-cols-2 lg:grid-cols-3">
                                    {venue.menu.map((section) => (
                                        <div key={section.key}>
                                            <p className="text-xs font-semibold tracking-[0.18em] text-gold-dark uppercase">
                                                {section.label}
                                            </p>
                                            <ul className="mt-4 space-y-3">
                                                {section.items.map((dish) => (
                                                    <li key={dish.id}>
                                                        <div className="flex items-baseline justify-between gap-4">
                                                            <p className="text-sm font-medium text-navy">
                                                                {dish.name}
                                                                {dish.is_vegetarian && (
                                                                    <span className="ml-2 rounded-full bg-lake-light px-2 py-0.5 text-[10px] font-semibold text-lake">
                                                                        Veg
                                                                    </span>
                                                                )}
                                                            </p>
                                                            <p className="shrink-0 text-sm text-navy/70">
                                                                {formatMoney(
                                                                    dish.price,
                                                                )}
                                                            </p>
                                                        </div>
                                                        {dish.description && (
                                                            <p className="mt-0.5 text-xs text-navy/55">
                                                                {
                                                                    dish.description
                                                                }
                                                            </p>
                                                        )}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </Reveal>
                    )}
                </Section>
            ))}

            <ContactCta
                title="Book a table"
                description="Reservations are recommended for dinner and essential on public holidays."
                message="Hello Lakeside Hotel, I would like to book a table."
            />
        </>
    );
}
