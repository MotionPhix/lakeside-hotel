import type { MediaImage } from '@/types/hotel';

/**
 * The single place a photograph is written into the page.
 *
 * Every image the site serves exists in three fixed sizes - 600x400, 1000x750 and
 * 1920x1080 - so `srcSet` hands the browser all three and lets it choose. A phone
 * on a slow connection then takes the 600, a laptop takes the 1000, and neither
 * pays for the 1920 it will never display. The alternative, one large file scaled
 * down by CSS, is the most common way a page like this becomes slow.
 *
 * The dimensions are declared as attributes as well as in the sizing. The
 * stylesheet usually decides the box, but the browser reads `width`/`height`
 * before the stylesheet arrives, and without them the text below an image jumps
 * down the page as each photograph loads.
 */

/** The three sizes, named once so a call site reads as prose. */
export const imageBoxes = {
    /** Full-bleed banners. */
    full: '100vw',
    /** Two to a row on a wide screen. */
    half: '(min-width: 1024px) 50vw, 100vw',
    /** Three to a row on a wide screen, two on a tablet. */
    third: '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw',
    /** Four to a row on a wide screen. */
    quarter: '(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw',
    /** A thumbnail in a list row: never wider than the fixed thumb. */
    thumb: '(min-width: 640px) 200px, 33vw',
} as const;

const boxFor = {
    thumb: { width: 600, height: 400 },
    card: { width: 1000, height: 750 },
    hero: { width: 1920, height: 1080 },
} as const;

export function ResponsiveImage({
    image,
    /** The size used when the browser cannot use `srcSet`. */
    from = 'card',
    sizes = imageBoxes.third,
    alt,
    className,
    /**
     * Set on the one image above the fold that the eye lands on first. It is the
     * only thing on the page worth telling the browser to fetch ahead of the rest,
     * and marking everything urgent marks nothing urgent.
     */
    priority = false,
    ...rest
}: {
    image: MediaImage;
    from?: keyof typeof boxFor;
    sizes?: string;
    alt?: string;
    className?: string;
    priority?: boolean;
} & Omit<React.ComponentPropsWithoutRef<'img'>, 'src' | 'srcSet' | 'sizes' | 'alt' | 'width' | 'height' | 'loading'>) {
    const box = boxFor[from];

    return (
        <img
            {...rest}
            src={image[from]}
            srcSet={`${image.thumb} 600w, ${image.card} 1000w, ${image.hero} 1920w`}
            sizes={sizes}
            width={box.width}
            height={box.height}
            alt={alt ?? image.alt ?? ''}
            loading={priority ? 'eager' : 'lazy'}
            fetchPriority={priority ? 'high' : 'auto'}
            decoding="async"
            className={className}
        />
    );
}
