import { LakesideLogo } from '@/components/lakeside-logo';

/**
 * The brand block at the top of the sidebar: the hotel's wordmark on a white
 * plate.
 *
 * The plate is doing real work in dark mode. There the sidebar is navy and the
 * mark is deep blue, so the artwork needs its own light ground or it sinks into
 * the panel. In light mode the sidebar is already white and the plate simply
 * disappears - which is why it is unconditional rather than branched on the
 * theme: it costs nothing when it is invisible and saves the mark when it is not.
 *
 * The wordmark carries the accessible name, so the link around it reads as the
 * hotel rather than as an unlabelled image.
 */
export default function AppLogo() {
    return (
        <span className="flex items-center rounded-lg bg-white px-2 py-1.5">
            <LakesideLogo className="h-6" />
        </span>
    );
}
