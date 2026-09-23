import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { GalleryTile } from '@/components/public/cards';
import { ContactCta } from '@/components/public/contact-cta';
import { PageHero } from '@/components/public/page-hero';
import { Section } from '@/components/public/section';
import { cn } from '@/lib/utils';
import { formatLabel } from '@/lib/format';
import type { GalleryItemData } from '@/types';

type Props = {
    items: GalleryItemData[];
    categories: string[];
};

export default function Gallery({ items, categories }: Props) {
    const [active, setActive] = useState<string>('all');

    const visible = useMemo(
        () =>
            active === 'all'
                ? items
                : items.filter((item) => item.category === active),
        [active, items],
    );

    const filters = ['all', ...categories];

    return (
        <>
            <Head title="Gallery">
                <meta
                    name="description"
                    content="Photographs and video from Lakeside Hotel and Conference Centre in Senga Bay, Salima: the lake, rooms, dining, activities and events."
                />
            </Head>

            <PageHero
                eyebrow="Gallery"
                title="Senga Bay in pictures"
                description="The lake, the gardens, the rooms, the food and the long sunsets."
                breadcrumb="Gallery"
            />

            <Section tone="white">
                <div className="flex flex-wrap gap-2">
                    {filters.map((category) => (
                        <button
                            key={category}
                            type="button"
                            onClick={() => setActive(category)}
                            aria-pressed={active === category}
                            className={cn(
                                'rounded-full border px-4 py-2 text-sm font-medium transition-colors',
                                active === category
                                    ? 'border-lake bg-lake text-white'
                                    : 'border-navy/15 text-navy/70 hover:border-lake/40 hover:text-lake',
                            )}
                        >
                            {category === 'all'
                                ? 'Everything'
                                : formatLabel(category)}
                        </button>
                    ))}
                </div>

                {visible.length === 0 ? (
                    <p className="mt-12 text-navy/60">
                        No photographs in this category yet.
                    </p>
                ) : (
                    <div className="mt-10 columns-1 gap-3 sm:columns-2 lg:columns-3 [&>figure]:mb-3">
                        {visible.map((item) => (
                            <figure
                                key={item.id}
                                className="break-inside-avoid"
                            >
                                <GalleryTile item={item} />
                            </figure>
                        ))}
                    </div>
                )}
            </Section>

            <ContactCta
                title="Want to see it in person?"
                description="Tell us your dates and we will hold a room for you."
                message="Hello Lakeside Hotel, I would like to check availability."
            />
        </>
    );
}
