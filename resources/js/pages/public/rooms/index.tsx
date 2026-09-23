import { Head } from '@inertiajs/react';
import { AmenityTile, RoomCard } from '@/components/public/cards';
import { ContactCta } from '@/components/public/contact-cta';
import { PageHero } from '@/components/public/page-hero';
import { Reveal } from '@/components/public/reveal';
import { Section, SectionHeading } from '@/components/public/section';
import type { AmenitySummary, RoomTypeDetail } from '@/types';

type Props = {
    roomTypes: RoomTypeDetail[];
    amenities: AmenitySummary[];
};

export default function RoomsIndex({ roomTypes, amenities }: Props) {
    return (
        <>
            <Head title="Rooms & Suites">
                <meta
                    name="description"
                    content="Rooms, executive suites, family rooms and lakeside chalets at Lakeside Hotel and Conference Centre, Senga Bay, Salima."
                />
            </Head>

            <PageHero
                eyebrow="Accommodation"
                title="Rooms, suites and lakeside chalets"
                description="Five room categories, all with air conditioning, hot water and views over the gardens or the lake. Rates include breakfast for two."
                breadcrumb="Rooms"
            />

            <Section tone="white">
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {roomTypes.map((roomType, index) => (
                        <Reveal key={roomType.id} delay={index * 60}>
                            <RoomCard roomType={roomType} />
                        </Reveal>
                    ))}
                </div>
            </Section>

            {amenities.length > 0 && (
                <Section tone="sand">
                    <Reveal>
                        <SectionHeading
                            eyebrow="Facilities"
                            title="Everything on site"
                            description="Included with every stay."
                            align="center"
                        />
                    </Reveal>
                    <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {amenities.map((amenity, index) => (
                            <Reveal key={amenity.id} delay={index * 35}>
                                <AmenityTile amenity={amenity} />
                            </Reveal>
                        ))}
                    </div>
                </Section>
            )}

            <ContactCta
                title="Not sure which room suits you?"
                description="Tell us who is travelling and what you have in mind, and we will suggest the right room."
                message="Hello Lakeside Hotel, please help me choose a room."
            />
        </>
    );
}
