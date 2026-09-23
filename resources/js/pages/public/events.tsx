import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Users } from 'lucide-react';
import { PackageCard } from '@/components/public/cards';
import { ContactCta } from '@/components/public/contact-cta';
import { PageHero } from '@/components/public/page-hero';
import { Reveal } from '@/components/public/reveal';
import { Section, SectionHeading } from '@/components/public/section';
import { Button } from '@/components/ui/button';
import type { ConferencePackageData, ContentBlockData } from '@/types';

type Props = {
    intro: ContentBlockData | null;
    conferencePackages: ConferencePackageData[];
    weddingPackages: ConferencePackageData[];
    totalPackages: number;
    largestCapacity: number;
};

export default function Events({
    intro,
    conferencePackages,
    weddingPackages,
    totalPackages,
    largestCapacity,
}: Props) {
    const highlights = [
        {
            label: 'Delegates, theatre style',
            value: `Up to ${largestCapacity}`,
        },
        { label: 'Packages available', value: `${totalPackages}` },
        { label: 'Conference rooms', value: 'Two, or one' },
        { label: 'Catering', value: 'In-house' },
    ];

    return (
        <>
            <Head title="Conferences & Events">
                <meta
                    name="description"
                    content="Conference facilities and event packages at Lakeside Hotel and Conference Centre, Senga Bay. Meetings, corporate retreats, weddings and private events on Lake Malawi."
                />
            </Head>

            <PageHero
                eyebrow="Conferences & events"
                title={intro?.title ?? 'Meetings with a view'}
                description={intro?.subtitle}
                imageUrl={
                    conferencePackages[0]?.cover?.hero ??
                    weddingPackages[0]?.cover?.hero ??
                    null
                }
                breadcrumb="Conferences & events"
            />

            <Section tone="white" spacing="tight">
                <div className="grid gap-10 lg:grid-cols-3 lg:items-start">
                    {intro?.body && (
                        <div className="space-y-4 text-base leading-relaxed whitespace-pre-line text-navy/70 lg:col-span-2">
                            {intro.body}
                        </div>
                    )}
                    <dl className="grid grid-cols-2 gap-6 rounded-xl border border-navy/10 bg-sand p-6 lg:col-span-1">
                        {highlights.map((item) => (
                            <div key={item.label}>
                                <dt className="text-xs text-navy/55">
                                    {item.label}
                                </dt>
                                <dd className="mt-1 font-display text-xl font-semibold text-navy">
                                    {item.value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </Section>

            {conferencePackages.length > 0 && (
                <Section tone="sand">
                    <Reveal>
                        <SectionHeading
                            eyebrow="For business"
                            title="Conference and retreat packages"
                            description="Day delegate rates, residential retreats and half day meetings, all catered from our own kitchen."
                        />
                    </Reveal>
                    <div className="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {conferencePackages.map((pkg, index) => (
                            <Reveal key={pkg.id} delay={index * 70}>
                                <PackageCard pkg={pkg} />
                            </Reveal>
                        ))}
                    </div>
                </Section>
            )}

            {weddingPackages.length > 0 && (
                <Section tone="white">
                    <Reveal>
                        <SectionHeading
                            eyebrow="Celebrations"
                            title="Weddings and private events"
                            description="Say it on the sand, celebrate on the terrace, with the sun going down behind you."
                        />
                    </Reveal>
                    <div className="mt-12 grid gap-6 md:grid-cols-2">
                        {weddingPackages.map((pkg, index) => (
                            <Reveal key={pkg.id} delay={index * 70}>
                                <PackageCard pkg={pkg} />
                            </Reveal>
                        ))}
                    </div>
                </Section>
            )}

            <Section tone="mist" spacing="tight">
                <Reveal>
                    <div className="flex flex-wrap items-center justify-between gap-6 rounded-xl border border-navy/10 bg-white p-8">
                        <div className="flex items-start gap-4">
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-lake-light text-lake">
                                <Users className="size-5" aria-hidden />
                            </span>
                            <div>
                                <h2 className="font-display text-xl font-semibold text-navy">
                                    Planning something larger?
                                </h2>
                                <p className="mt-1 max-w-xl text-sm text-navy/65">
                                    Tell us the group size and the dates and we
                                    will put together a package around them.
                                </p>
                            </div>
                        </div>
                        <Button asChild size="lg">
                            <Link href="/contact">
                                Request a quote
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                </Reveal>
            </Section>

            <ContactCta
                title="Talk to our events team"
                description="We answer event enquiries within one working day."
                message="Hello Lakeside Hotel, I would like to discuss an event."
            />
        </>
    );
}
