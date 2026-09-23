import {
    Anchor,
    BugOff,
    Car,
    ConciergeBell,
    PartyPopper,
    Presentation,
    Shirt,
    ShowerHead,
    Snowflake,
    Ship,
    Sparkles,
    Tv,
    Umbrella,
    Utensils,
    Waves,
    Wifi,
    Wine,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

/**
 * Maps the icon name stored on an amenity to a Lucide icon, so staff can pick an
 * icon in the dashboard without any code change.
 */
const icons: Record<string, LucideIcon> = {
    wifi: Wifi,
    utensils: Utensils,
    wine: Wine,
    presentation: Presentation,
    waves: Waves,
    ship: Ship,
    anchor: Anchor,
    car: Car,
    'concierge-bell': ConciergeBell,
    'party-popper': PartyPopper,
    snowflake: Snowflake,
    'shower-head': ShowerHead,
    tv: Tv,
    shirt: Shirt,
    umbrella: Umbrella,
    'bug-off': BugOff,
};

export function AmenityIcon({
    name,
    className,
}: {
    name: string | null;
    className?: string;
}) {
    const Icon = (name ? icons[name] : undefined) ?? Sparkles;

    return <Icon className={className} aria-hidden />;
}

export { icons as amenityIcons };
