import type * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * The Lakeside wordmark.
 *
 * This is the script from the hotel's logo, cut away from the
 * "HOTEL AND CONFERENCE CENTRE" strapline and the "Senga Bay, Salima" pill that
 * sit under it in the full lockup. Those only stay legible above roughly 80px
 * tall, which is far too big for a sidebar row or the top of a login panel, so
 * the script travels on its own. The cut is a measured crop of the original
 * file, not a redraw.
 *
 * It is served as an image rather than inlined as SVG because the source artwork
 * is raster - a painted script over gradient waves, with no paths to recolour or
 * resize from. The artwork carries a white background rather than transparency,
 * which is why the sidebar keeps it on a plate: in dark mode the panel is navy
 * and the raw mark is deep blue, so it would otherwise sink into it.
 */
export function LakesideLogo({
    className,
    alt = 'Lakeside Hotel and Conference Centre',
    ...props
}: React.ComponentProps<'img'>) {
    return (
        <img
            src="/bucket/lakeside-wordmark.png"
            alt={alt}
            className={cn('w-auto', className)}
            {...props}
        />
    );
}
