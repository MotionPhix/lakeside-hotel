import { Head, usePage } from '@inertiajs/react';
import { Clock, CreditCard, Percent, Users } from 'lucide-react';
import { ContactCta } from '@/components/public/contact-cta';
import { PageHero } from '@/components/public/page-hero';
import { Reveal } from '@/components/public/reveal';
import { Section, SectionHeading } from '@/components/public/section';
import type { ContentBlockData, SharedData } from '@/types';

type Props = {
    policies: ContentBlockData | null;
    transfers: ContentBlockData | null;
};

export default function Policies({ policies, transfers }: Props) {
    const { site } = usePage<SharedData>().props;
    const { booking } = site;

    const essentials = [
        {
            icon: Clock,
            label: 'Check in',
            value: `From ${site.contact.check_in_time}`,
        },
        {
            icon: Clock,
            label: 'Check out',
            value: `By ${site.contact.check_out_time}`,
        },
        {
            icon: CreditCard,
            label: 'Deposit',
            value: `${booking.deposit_percentage}% on booking`,
        },
        {
            icon: Percent,
            label: 'Taxes',
            value: `${booking.vat_rate}% VAT + ${booking.tourism_levy_rate}% levy`,
        },
        {
            icon: Users,
            label: 'Children',
            value: 'Under 12 stay free sharing',
        },
        {
            icon: CreditCard,
            label: 'Currency',
            value: `Rates in ${booking.currency}`,
        },
    ];

    return (
        <>
            <Head title="Booking policies">
                <meta
                    name="description"
                    content="Check in and check out times, deposits, cancellation policy, child policy and airport transfers at Lakeside Hotel and Conference Centre, Senga Bay."
                />
            </Head>

            <PageHero
                eyebrow="Before you book"
                title={policies?.title ?? 'Booking policies'}
                description={policies?.subtitle}
                breadcrumb="Booking policies"
            />

            <Section tone="white" spacing="tight">
                <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {essentials.map((item) => (
                        <div
                            key={item.label}
                            className="flex items-start gap-3 rounded-xl border border-navy/10 bg-mist p-5"
                        >
                            <item.icon
                                className="mt-0.5 size-5 shrink-0 text-lake"
                                aria-hidden
                            />
                            <div>
                                <dt className="text-xs text-navy/55">
                                    {item.label}
                                </dt>
                                <dd className="mt-0.5 font-medium text-navy">
                                    {item.value}
                                </dd>
                            </div>
                        </div>
                    ))}
                </dl>
            </Section>

            {policies?.body && (
                <Section tone="sand">
                    <Reveal>
                        <div className="max-w-3xl space-y-5 text-base leading-relaxed whitespace-pre-line text-navy/75">
                            {policies.body}
                        </div>
                    </Reveal>
                </Section>
            )}

            {transfers && (
                <Section tone="white">
                    <div className="grid gap-10 lg:grid-cols-2 lg:gap-16">
                        <Reveal>
                            <SectionHeading
                                eyebrow="Getting here"
                                title={transfers.title}
                                description={transfers.subtitle}
                            />
                            {transfers.body && (
                                <div className="mt-6 space-y-4 text-base leading-relaxed whitespace-pre-line text-navy/70">
                                    {transfers.body}
                                </div>
                            )}
                            {booking.transfer_note && (
                                <p className="mt-6 rounded-lg border border-lake/20 bg-lake-light p-4 text-sm text-navy/75">
                                    {booking.transfer_note}
                                </p>
                            )}
                        </Reveal>
                    </div>
                </Section>
            )}

            <ContactCta
                title="Questions about your booking?"
                description="Ask us anything about deposits, dates or transfers and we will answer the same day."
                message="Hello Lakeside Hotel, I have a question about booking."
            />
        </>
    );
}
