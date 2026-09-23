import { Head, usePage } from '@inertiajs/react';
import { Clock, Mail, MapPin, MessageCircle, Phone } from 'lucide-react';
import { AttractionCard } from '@/components/public/cards';
import { ContactForm } from '@/components/public/contact-form';
import { MapEmbed, directionsLink } from '@/components/public/map-embed';
import { PageHero } from '@/components/public/page-hero';
import { Reveal } from '@/components/public/reveal';
import { Section, SectionHeading } from '@/components/public/section';
import { Button } from '@/components/ui/button';
import { whatsappLink } from '@/lib/site-nav';
import type { NearbyAttractionData, SharedData } from '@/types';

type Props = {
    attractions: NearbyAttractionData[];
};

export default function Contact({ attractions }: Props) {
    const { site } = usePage<SharedData>().props;
    const { contact } = site;

    const channels = [
        {
            icon: Phone,
            label: 'Reception',
            value: contact.phone,
            href: `tel:${contact.phone.replace(/\s/g, '')}`,
        },
        {
            icon: MessageCircle,
            label: 'WhatsApp',
            value: contact.whatsapp,
            href: whatsappLink(site),
        },
        {
            icon: Mail,
            label: 'Reservations',
            value: contact.email,
            href: `mailto:${contact.email}`,
        },
        {
            icon: Mail,
            label: 'Events and conferences',
            value: contact.events_email,
            href: `mailto:${contact.events_email}`,
        },
        {
            icon: Clock,
            label: 'Check in / out',
            value: `From ${contact.check_in_time} · By ${contact.check_out_time}`,
            href: null,
        },
    ];

    return (
        <>
            <Head title="Contact us">
                <meta
                    name="description"
                    content={`Contact ${site.name} in Senga Bay, Salima: phone, WhatsApp, email, directions and a contact form.`}
                />
            </Head>

            <PageHero
                eyebrow="Contact"
                title="Get in touch"
                description="Reception is staffed around the clock. For reservations and events, use the numbers below or send us a message."
                breadcrumb="Contact"
            />

            <Section tone="white">
                <div className="grid gap-12 lg:grid-cols-3 lg:gap-16">
                    <div className="lg:col-span-2">
                        <SectionHeading
                            eyebrow="Send a message"
                            title="How can we help?"
                            description="Tell us your dates and what you are planning. We answer within a few hours."
                        />
                        <div className="mt-8">
                            <ContactForm />
                        </div>
                    </div>

                    <aside className="space-y-3">
                        {channels.map((channel) => (
                            <div
                                key={channel.label}
                                className="flex items-start gap-3 rounded-xl border border-navy/10 bg-mist p-5"
                            >
                                <channel.icon
                                    className="mt-0.5 size-5 shrink-0 text-lake"
                                    aria-hidden
                                />
                                <div className="min-w-0">
                                    <p className="text-xs text-navy/55">
                                        {channel.label}
                                    </p>
                                    {channel.href ? (
                                        <a
                                            href={channel.href}
                                            target={
                                                channel.href.startsWith('https')
                                                    ? '_blank'
                                                    : undefined
                                            }
                                            rel="noreferrer"
                                            className="font-medium break-words text-navy transition-colors hover:text-lake"
                                        >
                                            {channel.value}
                                        </a>
                                    ) : (
                                        <p className="font-medium text-navy">
                                            {channel.value}
                                        </p>
                                    )}
                                </div>
                            </div>
                        ))}

                        <div className="rounded-xl border border-navy/10 bg-mist p-5">
                            <div className="flex items-start gap-3">
                                <MapPin
                                    className="mt-0.5 size-5 shrink-0 text-lake"
                                    aria-hidden
                                />
                                <div>
                                    <p className="text-xs text-navy/55">
                                        Address
                                    </p>
                                    <p className="font-medium text-navy">
                                        {contact.address}
                                    </p>
                                </div>
                            </div>
                            <Button
                                asChild
                                variant="outline"
                                className="mt-4 w-full"
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
                        </div>
                    </aside>
                </div>
            </Section>

            <Section tone="sand" spacing="tight">
                <div className="h-80 overflow-hidden rounded-xl border border-navy/10 lg:h-[26rem]">
                    <MapEmbed
                        latitude={contact.latitude}
                        longitude={contact.longitude}
                        zoom={contact.map_zoom}
                        title={`Map showing ${site.name} in Senga Bay`}
                    />
                </div>
            </Section>

            {attractions.length > 0 && (
                <Section tone="white">
                    <Reveal>
                        <SectionHeading
                            eyebrow="While you are here"
                            title="Nearby"
                        />
                    </Reveal>
                    <div className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {attractions.slice(0, 6).map((attraction, index) => (
                            <Reveal key={attraction.id} delay={index * 45}>
                                <AttractionCard attraction={attraction} />
                            </Reveal>
                        ))}
                    </div>
                </Section>
            )}
        </>
    );
}
