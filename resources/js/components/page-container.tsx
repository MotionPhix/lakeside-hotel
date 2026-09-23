import type * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * The width the admin panel's main column is held to.
 *
 * A dashboard that runs the full width of a large monitor reads as a spreadsheet
 * rather than a product: tables stretch, a row's value drifts far from its label,
 * and the eye has to travel the whole screen to follow a single line. Holding the
 * column to a comfortable measure and centring it keeps the content together
 * whatever the window is doing.
 *
 * 4xl is about 896px. That is room for a five or six column table without the
 * line lengths getting away from the reader, which is the widest thing the
 * dashboard actually has to show.
 */
export const PAGE_MAX_WIDTH = 'max-w-4xl';

/**
 * The one container an admin screen's content sits in.
 *
 * Width and padding live here rather than on individual pages, so a new screen
 * cannot arrive with its own idea of how wide the panel should be - which is
 * exactly how one page ends up edge to edge while the next one is centred. The
 * page header renders through this too, so the breadcrumb lines up with the
 * content underneath it instead of floating off to the left.
 */
export function PageContainer({
    className,
    children,
    ...props
}: React.ComponentProps<'div'>) {
    return (
        <div
            className={cn(
                'mx-auto w-full px-4 sm:px-6',
                PAGE_MAX_WIDTH,
                className,
            )}
            {...props}
        >
            {children}
        </div>
    );
}
