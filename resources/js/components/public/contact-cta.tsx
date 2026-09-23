import { usePage } from '@inertiajs/react';
import { Mail, MessageCircle, Phone } from 'lucide-react';
import { Section, SectionHeading } from '@/components/public/section';
import { Button } from '@/components/ui/button';
import { whatsappLink } from '@/lib/site-nav';
import type { SharedData } from '@/types';

/**
 * The closing call to action on the inner pages.
 */
export function ContactCta({
    title = 'Ready to plan your stay?',
    description = 'Our reservations team answers within a few hours, every day of the week.',
    message,
}: {
    title?: string;
    description?: string;
    message?: string;
}) {
    const { site } = usePage<SharedData>().props;
    const { contact } = site;

    const link = whatsappLink(
        site,
        message ?? `Hello ${site.name}, I would like to check availability.`,
    );

    return (
        <Section tone="sand" spacing="tight">
            <div className="grid gap-8 lg:grid-cols-3 lg:items-center">
                <div className="lg:col-span-2">
                    <SectionHeading
                        eyebrow="Get in touch"
                        title={title}
                        description={description}
                    />
                </div>
                <div className="flex flex-col gap-3">
                    {link && (
                        <Button asChild size="lg">
                            <a href={link} target="_blank" rel="noreferrer">
                                <MessageCircle />
                                WhatsApp us
                            </a>
                        </Button>
                    )}
                    <Button asChild variant="outline" size="lg">
                        <a href={`tel:${contact.phone.replace(/\s/g, '')}`}>
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
            </div>
        </Section>
    );
}
