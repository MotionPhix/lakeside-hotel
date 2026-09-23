import { Head } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { ActivityCard } from '@/components/public/cards';
import { ContactCta } from '@/components/public/contact-cta';
import { PageHero } from '@/components/public/page-hero';
import { Reveal } from '@/components/public/reveal';
import { Section } from '@/components/public/section';
import type { ActivityData } from '@/types';

type Props = {
    activities: ActivityData[];
};

export default function Activities({ activities }: Props) {
    return (
        <>
            <Head title="Activities & Experiences">
                <meta
                    name="description"
                    content="Lake Malawi activities at Senga Bay: sunset cruises, fishing trips, snorkelling, kayaking, village walks and team building with Lakeside Hotel."
                />
            </Head>

            <PageHero
                eyebrow="Experiences"
                title="Days on Lake Malawi"
                description="Everything is arranged from the jetty by our activities team. Book the day before at reception, or ask us when you arrive."
                imageUrl={activities[0]?.cover?.hero ?? null}
                breadcrumb="Activities"
            />

            <Section tone="white">
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {activities.map((activity, index) => (
                        <Reveal key={activity.id} delay={index * 55}>
                            <ActivityCard activity={activity} />
                        </Reveal>
                    ))}
                </div>

                <Reveal delay={100}>
                    <div className="mt-12 flex flex-wrap items-start gap-3 rounded-xl border border-lake/20 bg-lake-light p-5 text-sm text-navy/75">
                        <Info
                            className="mt-0.5 size-4 shrink-0 text-lake"
                            aria-hidden
                        />
                        <p className="max-w-3xl">
                            Activities are subject to weather and lake
                            conditions. Boat trips need at least two guests and
                            should be booked a day ahead at reception. Tell us
                            when you arrive and we will fit everything around
                            your stay.
                        </p>
                    </div>
                </Reveal>
            </Section>

            <ContactCta
                title="Plan your days on the lake"
                description="Tell us what you would like to do and we will build it around your stay."
                message="Hello Lakeside Hotel, I would like to arrange an activity."
            />
        </>
    );
}
