import {
    Anchor,
    BugOff,
    Car,
    Coffee,
    ConciergeBell,
    CupSoda,
    FileText,
    Laptop,
    Mic,
    PartyPopper,
    PenLine,
    Phone,
    Presentation,
    Printer,
    Projector,
    Refrigerator,
    Shirt,
    ShowerHead,
    Snowflake,
    Speaker,
    Sparkles,
    Target,
    Trees,
    Tv,
    Umbrella,
    Utensils,
    Waves,
    Wifi,
    Wind,
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
    ship: Anchor,
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
    refrigerator: Refrigerator,
    coffee: Coffee,
    wind: Wind,
    target: Target,
    trees: Trees,
    'cup-soda': CupSoda,
    projector: Projector,
    mic: Mic,
    'file-text': FileText,
    speaker: Speaker,
    phone: Phone,
    printer: Printer,
    pen: PenLine,
    laptop: Laptop,
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
