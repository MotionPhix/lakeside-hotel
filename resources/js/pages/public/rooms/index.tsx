import { SeoHead } from '@/components/public/seo-head';
import { AmenityTile } from '@/components/public/cards';
import { RoomExplorer } from '@/components/public/room-explorer';
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
            <SeoHead />

            <PageHero
                eyebrow="Accommodation"
                title="Rooms, suites and lakeside chalets"
                description={`${roomTypes.length} room categories, from doubles to suites. Compare the space, beds and amenities, then find the right stay for your dates.`}
                breadcrumb="Rooms"
            />

            <Section tone="white">
                <RoomExplorer roomTypes={roomTypes} />
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
                    <div className="mt-12 grid gap-4 sm:auto-rows-fr sm:grid-cols-2 lg:grid-cols-3">
                        {amenities.map((amenity, index) => (
                            /* h-full on the wrapper as well: the wrapper is the
                               grid child, so it is the element stretched to the
                               row height, and the card inside can only match it
                               if the wrapper passes that height down. */
                            <Reveal
                                key={amenity.id}
                                delay={index * 35}
                                className="h-full"
                            >
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
