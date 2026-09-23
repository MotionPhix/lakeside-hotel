import { Head } from '@inertiajs/react';
import { OfferCard } from '@/components/public/cards';
import { ContactCta } from '@/components/public/contact-cta';
import { PageHero } from '@/components/public/page-hero';
import { Reveal } from '@/components/public/reveal';
import { Section } from '@/components/public/section';
import type { OfferData } from '@/types';

type Props = {
    offers: OfferData[];
};

export default function Offers({ offers }: Props) {
    return (
        <>
            <Head title="Special Offers">
                <meta
                    name="description"
                    content="Special offers at Lakeside Hotel and Conference Centre, Senga Bay: weekend specials, stay 3 pay 2, honeymoon packages, corporate rates and green season discounts."
                />
            </Head>

            <PageHero
                eyebrow="Special offers"
                title="Reasons to book direct"
                description="Better rates than any booking site, and the flexibility to change your dates."
                imageUrl={offers[0]?.image?.hero ?? null}
                breadcrumb="Offers"
            />

            <Section tone="white">
                {offers.length === 0 ? (
                    <p className="text-navy/65">
                        There are no offers running at the moment. Please ask us
                        about the best available rate for your dates.
                    </p>
                ) : (
                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {offers.map((offer, index) => (
                            <Reveal key={offer.id} delay={index * 60}>
                                <OfferCard offer={offer} />
                            </Reveal>
                        ))}
                    </div>
                )}

                <Reveal delay={120}>
                    <div className="mt-12 rounded-xl border border-navy/10 bg-mist p-6 text-sm leading-relaxed text-navy/70">
                        <p className="font-medium text-navy">
                            Terms and conditions
                        </p>
                        <p className="mt-2">
                            {offers[0]?.terms ??
                                'Subject to availability. Cannot be combined with other offers. Blackout dates apply over public holidays and the festive season.'}
                        </p>
                    </div>
                </Reveal>
            </Section>

            <ContactCta
                title="Ask about the best rate"
                description="Send us your dates and we will confirm the lowest rate we can offer."
                message="Hello Lakeside Hotel, please quote me your best rate."
            />
        </>
    );
}
