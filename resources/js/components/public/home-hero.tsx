import { Link } from '@inertiajs/react';
import { CalendarCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { HeroSlideData } from '@/types';

const ROTATION_MS = 7000;

/**
 * The full-bleed homepage hero.
 *
 * Slides are stacked and cross-faded rather than swapped, so the browser keeps
 * every image decoded and the transition never flashes. Rotation pauses for
 * visitors who have asked for reduced motion.
 */
export function HomeHero({
    slides,
    bookingHref,
    eyebrow,
}: {
    slides: HeroSlideData[];
    bookingHref: string;
    eyebrow: string;
}) {
    const [index, setIndex] = useState(0);

    useEffect(() => {
        if (slides.length < 2) {
            return;
        }

        const reducedMotion =
            typeof window !== 'undefined' &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (reducedMotion) {
            return;
        }

        const timer = window.setInterval(
            () => setIndex((current) => (current + 1) % slides.length),
            ROTATION_MS,
        );

        return () => window.clearInterval(timer);
    }, [slides.length]);

    const slide = slides[index];

    if (!slide) {
        return null;
    }

    return (
        <section className="relative isolate flex min-h-[68vh] items-end overflow-hidden bg-navy lg:min-h-[80vh]">
            {slides.map((item, position) => (
                <div
                    key={item.id}
                    aria-hidden={position !== index}
                    className={cn(
                        'absolute inset-0 transition-opacity duration-1000 ease-out motion-reduce:transition-none',
                        position === index ? 'opacity-100' : 'opacity-0',
                    )}
                >
                    {item.image ? (
                        <img
                            src={item.image.hero}
                            alt=""
                            loading={position === 0 ? 'eager' : 'lazy'}
                            decoding="async"
                            className="size-full object-cover"
                        />
                    ) : (
                        <div className="size-full bg-linear-to-br from-navy to-lake-dark" />
                    )}
                </div>
            ))}

            <div
                aria-hidden
                className="absolute inset-0 bg-linear-to-t from-navy/92 via-navy/55 to-navy/25"
            />

            <div className="relative mx-auto w-full max-w-7xl px-5 pt-28 pb-24 sm:px-8 lg:pb-28">
                <p className="text-xs font-semibold tracking-[0.25em] text-gold uppercase">
                    {eyebrow}
                </p>

                <h1 className="mt-4 max-w-3xl font-display text-4xl leading-[1.04] font-semibold text-balance text-white sm:text-5xl lg:text-6xl">
                    {slide.headline}
                </h1>

                {slide.subheadline && (
                    <p className="mt-5 max-w-2xl text-base leading-relaxed text-sand/85 sm:text-lg">
                        {slide.subheadline}
                    </p>
                )}

                <div className="mt-9 flex flex-wrap gap-3">
                    {bookingHref ? (
                        <Button asChild size="lg">
                            <a
                                href={bookingHref}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <CalendarCheck />
                                {slide.cta_label ?? 'Book Your Stay'}
                            </a>
                        </Button>
                    ) : null}

                    <Button
                        asChild
                        size="lg"
                        variant="outline"
                        className="border-white/45 bg-white/10 text-white hover:bg-white/20 hover:text-white"
                    >
                        <Link href={slide.secondary_cta_url ?? '/rooms'}>
                            {slide.secondary_cta_label ?? 'Explore Rooms'}
                        </Link>
                    </Button>
                </div>

                {slides.length > 1 && (
                    <div className="mt-10 flex items-center gap-2">
                        {slides.map((item, position) => (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() => setIndex(position)}
                                aria-label={`Show slide ${position + 1}: ${item.headline}`}
                                aria-current={position === index}
                                className={cn(
                                    'h-1 rounded-full transition-all duration-300',
                                    position === index
                                        ? 'w-10 bg-gold'
                                        : 'w-5 bg-white/40 hover:bg-white/70',
                                )}
                            />
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}
