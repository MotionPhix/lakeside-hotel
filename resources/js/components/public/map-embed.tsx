import { cn } from '@/lib/utils';

/**
 * A Google Maps embed.
 *
 * Uses the plain `output=embed` endpoint rather than the JavaScript API, so the
 * site needs no Google API key and no third-party script for a map that is
 * effectively a static picture.
 */
export function MapEmbed({
    latitude,
    longitude,
    zoom = 14,
    title,
    className,
}: {
    latitude: number;
    longitude: number;
    zoom?: number;
    title: string;
    className?: string;
}) {
    const src = `https://www.google.com/maps?q=${latitude},${longitude}&z=${zoom}&output=embed`;

    return (
        <iframe
            title={title}
            src={src}
            loading="lazy"
            referrerPolicy="no-referrer-when-downgrade"
            className={cn('h-full w-full border-0', className)}
        />
    );
}

/**
 * A link that opens the location in Google Maps for directions.
 */
export function directionsLink(latitude: number, longitude: number): string {
    return `https://www.google.com/maps/dir/?api=1&destination=${latitude},${longitude}`;
}
