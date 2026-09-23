import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

/**
 * The banner that opens every inner page: a breadcrumb, a Fraunces title and a
 * short introduction, on either a sand or a navy ground.
 */
export function PageHero({
    eyebrow,
    title,
    description,
    tone = 'sand',
    imageUrl,
    actions,
    breadcrumb,
}: {
    eyebrow?: string;
    title: string;
    description?: string | null;
    tone?: 'sand' | 'navy';
    /** When supplied the banner becomes photographic with a navy scrim. */
    imageUrl?: string | null;
    actions?: ReactNode;
    breadcrumb?: string;
}) {
    const photographic = Boolean(imageUrl);

    return (
        <section
            className={cn(
                'relative isolate overflow-hidden px-5 pt-14 pb-16 sm:px-8 sm:pt-16 sm:pb-20',
                !photographic && tone === 'sand' && 'bg-sand',
                !photographic && tone === 'navy' && 'bg-navy',
                photographic && 'bg-navy',
            )}
        >
            {photographic && (
                <>
                    <img
                        src={imageUrl ?? ''}
                        alt=""
                        aria-hidden
                        className="absolute inset-0 size-full object-cover"
                    />
                    <div
                        aria-hidden
                        className="absolute inset-0 bg-linear-to-t from-navy/92 via-navy/70 to-navy/60"
                    />
                </>
            )}

            <div className="relative mx-auto w-full max-w-7xl">
                <nav
                    aria-label="Breadcrumb"
                    className={cn(
                        'flex items-center gap-1.5 text-xs',
                        photographic || tone === 'navy'
                            ? 'text-sand/70'
                            : 'text-navy/50',
                    )}
                >
                    <Link
                        href={home()}
                        className="transition-colors hover:text-gold"
                    >
                        Home
                    </Link>
                    <ChevronRight className="size-3.5" aria-hidden />
                    <span
                        className={
                            photographic || tone === 'navy'
                                ? 'text-sand'
                                : 'text-navy'
                        }
                    >
                        {breadcrumb ?? title}
                    </span>
                </nav>

                {eyebrow && (
                    <p className="mt-6 text-xs font-semibold tracking-[0.22em] text-gold uppercase">
                        {eyebrow}
                    </p>
                )}

                <h1
                    className={cn(
                        'mt-3 max-w-4xl font-display text-3xl leading-tight font-semibold text-balance sm:text-4xl lg:text-5xl',
                        photographic || tone === 'navy'
                            ? 'text-white'
                            : 'text-navy',
                    )}
                >
                    {title}
                </h1>

                {description && (
                    <p
                        className={cn(
                            'mt-5 max-w-2xl text-base leading-relaxed sm:text-lg',
                            photographic || tone === 'navy'
                                ? 'text-sand/85'
                                : 'text-navy/70',
                        )}
                    >
                        {description}
                    </p>
                )}

                {actions && (
                    <div className="mt-8 flex flex-wrap gap-3">{actions}</div>
                )}
            </div>
        </section>
    );
}
