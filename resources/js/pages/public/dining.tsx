import { SeoHead } from '@/components/public/seo-head';
import { Clock, Shirt, Star } from 'lucide-react';
import { ContactCta } from '@/components/public/contact-cta';
import { DiningMenu } from '@/components/public/dining-menu';
import { PageHero } from '@/components/public/page-hero';
import {
    imageBoxes,
    ResponsiveImage,
} from '@/components/public/responsive-image';
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
            <SeoHead />

            <PageHero
                eyebrow="Dining"
                title={intro?.title ?? 'Food from the lake and the farm'}
                description={intro?.subtitle}
                image={venues[0]?.cover}
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
                                    <ResponsiveImage
                                        image={venue.cover}
                                        from="card"
                                        sizes={imageBoxes.half}
                                        alt={venue.cover.alt || venue.name}
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

                    {venue.menu.length > 0 && <DiningMenu venue={venue} />}
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
