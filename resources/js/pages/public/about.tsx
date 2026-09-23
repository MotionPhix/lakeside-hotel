import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { AttractionCard } from '@/components/public/cards';
import { ContactCta } from '@/components/public/contact-cta';
import { MapEmbed, directionsLink } from '@/components/public/map-embed';
import { PageHero } from '@/components/public/page-hero';
import { Reveal } from '@/components/public/reveal';
import { Section, SectionHeading } from '@/components/public/section';
import { Button } from '@/components/ui/button';
import type {
    ContentBlockData,
    NearbyAttractionData,
    SharedData,
} from '@/types';

type Props = {
    about: ContentBlockData | null;
    lake: ContentBlockData | null;
    attractions: NearbyAttractionData[];
};

export default function About({ about, lake, attractions }: Props) {
    const { site } = usePage<SharedData>().props;
    const { contact } = site;

    return (
        <>
            <Head title="About us">
                <meta
                    name="description"
                    content={`About ${site.name}: a lakeside hotel and conference centre on the shore of Lake Malawi at Senga Bay, Salima.`}
                />
            </Head>

            <PageHero
                eyebrow="About"
                title={about?.title ?? `About ${site.name}`}
                description={about?.subtitle}
                breadcrumb="About"
            />

            {about?.body && (
                <Section tone="white">
                    <div className="grid gap-12 lg:grid-cols-3 lg:gap-16">
                        <div className="space-y-5 text-base leading-relaxed whitespace-pre-line text-navy/70 lg:col-span-2">
                            {about.body}
                        </div>

                        <aside className="rounded-xl border border-navy/10 bg-sand p-6">
                            <p className="text-xs font-semibold tracking-[0.18em] text-gold-dark uppercase">
                                Find us
                            </p>
                            <p className="mt-4 font-display text-lg font-semibold text-navy">
                                {site.name}
                            </p>
                            <p className="mt-1 text-sm text-navy/65">
                                {contact.address}
                            </p>
                            <dl className="mt-5 space-y-2 border-t border-navy/10 pt-5 text-sm">
                                <div className="flex justify-between gap-4">
                                    <dt className="text-navy/60">Latitude</dt>
                                    <dd className="text-navy">
                                        {contact.latitude}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-navy/60">Longitude</dt>
                                    <dd className="text-navy">
                                        {contact.longitude}
                                    </dd>
                                </div>
                            </dl>
                            <Button
                                asChild
                                variant="outline"
                                className="mt-6 w-full"
                            >
                                <a
                                    href={directionsLink(
                                        contact.latitude,
                                        contact.longitude,
                                    )}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    Get directions
                                </a>
                            </Button>
                        </aside>
                    </div>
                </Section>
            )}

            {lake?.body && (
                <Section tone="sand">
                    <div className="grid gap-10 lg:grid-cols-2 lg:items-center lg:gap-16">
                        <Reveal>
                            <SectionHeading
                                eyebrow="The lake"
                                title={lake.title}
                                description={lake.subtitle}
                            />
                            <div className="mt-6 space-y-4 text-base leading-relaxed whitespace-pre-line text-navy/70">
                                {lake.body}
                            </div>
                        </Reveal>
                        <Reveal delay={100}>
                            <div className="h-72 overflow-hidden rounded-xl border border-navy/10 lg:h-96">
                                <MapEmbed
                                    latitude={contact.latitude}
                                    longitude={contact.longitude}
                                    zoom={contact.map_zoom + 1}
                                    title={`Map of ${site.name} and the surrounding bay`}
                                />
                            </div>
                        </Reveal>
                    </div>
                </Section>
            )}

            {attractions.length > 0 && (
                <Section tone="white">
                    <Reveal>
                        <SectionHeading
                            eyebrow="Around us"
                            title="What is nearby"
                            description="Everything within an easy drive of Senga Bay."
                        />
                    </Reveal>
                    <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {attractions.map((attraction, index) => (
                            <Reveal key={attraction.id} delay={index * 45}>
                                <AttractionCard attraction={attraction} />
                            </Reveal>
                        ))}
                    </div>
                    <Reveal delay={100}>
                        <div className="mt-10">
                            <Button asChild variant="outline">
                                <Link href="/activities">
                                    Things to do
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </div>
                    </Reveal>
                </Section>
            )}

            <ContactCta />
        </>
    );
}
