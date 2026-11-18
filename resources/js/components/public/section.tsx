import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * The one column every piece of page content sits in.
 *
 * Wider than nothing, narrower than the header band (`max-w-7xl`): the
 * navigation is chrome and reads as a full-width bar, while the text, grids,
 * the booking checker and the footer all share this column and so share a left
 * and right edge at every viewport.
 *
 * The horizontal padding belongs *here*, on the same element as the max-width,
 * and not on an outer wrapper. `max-w-6xl` inside a padded parent centres in
 * the padding box, which moves the content 32px outboard of anything that
 * applies its padding inside the max-width - that was what put the Welcome
 * section out of line with the availability checker. One class string, used
 * everywhere, keeps them together.
 */
export const contentColumn = 'mx-auto w-full max-w-6xl px-5 sm:px-8';

type Tone = 'white' | 'sand' | 'mist' | 'navy';

const toneClasses: Record<Tone, string> = {
    white: 'bg-white text-navy',
    sand: 'bg-sand text-navy',
    mist: 'bg-mist text-navy',
    navy: 'bg-navy text-sand',
};

/**
 * A page section: consistent vertical rhythm and a maximum width.
 *
 * The public site is deliberately light-only. Sections use the fixed brand
 * colours rather than theme tokens so a visitor's dark mode preference can never
 * invert the photography-led layout.
 */
export function Section({
    children,
    id,
    tone = 'white',
    className,
    spacing = 'normal',
}: {
    children: ReactNode;
    id?: string;
    tone?: Tone;
    className?: string;
    spacing?: 'normal' | 'tight' | 'loose';
}) {
    const spacingClasses = {
        tight: 'py-12 sm:py-14',
        normal: 'py-16 sm:py-20 lg:py-24',
        loose: 'py-20 sm:py-28 lg:py-32',
    };

    return (
        <section
            id={id}
            className={cn(
                spacingClasses[spacing],
                toneClasses[tone],
                className,
            )}
        >
            <div className={contentColumn}>{children}</div>
        </section>
    );
}

/**
 * The heading block that opens most sections: a small gold eyebrow, a Fraunces
 * display title, and optional supporting copy.
 */
export function SectionHeading({
    eyebrow,
    title,
    description,
    align = 'left',
    invert = false,
    className,
}: {
    eyebrow?: string;
    title: string;
    description?: string | null;
    align?: 'left' | 'center';
    invert?: boolean;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'max-w-3xl',
                align === 'center' && 'mx-auto text-center',
                className,
            )}
        >
            {eyebrow && (
                <p
                    className={cn(
                        'mb-3 text-xs font-semibold tracking-[0.2em] uppercase',
                        invert ? 'text-gold' : 'text-gold-dark',
                    )}
                >
                    {eyebrow}
                </p>
            )}
            <h2
                className={cn(
                    'font-display text-3xl leading-tight font-semibold text-balance sm:text-4xl lg:text-5xl',
                    invert ? 'text-white' : 'text-navy',
                )}
            >
                {title}
            </h2>
            {description && (
                <p
                    className={cn(
                        'mt-4 text-base leading-relaxed sm:text-lg',
                        invert ? 'text-sand/80' : 'text-navy/70',
                    )}
                >
                    {description}
                </p>
            )}
        </div>
    );
}

/**
 * The gold rule used to separate key blocks.
 */
export function GoldRule({ className }: { className?: string }) {
    return (
        <span
            aria-hidden
            className={cn('block h-px w-16 bg-gold', className)}
        />
    );
}
