import {
    BedDouble,
    CalendarCheck,
    CalendarRange,
    Globe2,
    Image,
    LayoutGrid,
    Megaphone,
    MessageSquare,
    Settings,
    Star,
    Tags,
    TrendingUp,
    Users,
    UserSquare2,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { Permission } from '@/types';

export type HotelModule = {
    key: string;
    title: string;
    description: string;
    icon: LucideIcon;
    permission: Permission;
    /** Milestone in which the module becomes available in the dashboard. */
    phase: number;
};

/**
 * The dashboard modules, mirroring `App\Enums\Permission`. Both the sidebar and
 * the dashboard overview read from this list so the navigation can never drift
 * from the permissions the server enforces.
 */
export const hotelModules: HotelModule[] = [
    {
        key: 'dashboard',
        title: 'Dashboard',
        description: 'Arrivals, departures, occupancy and revenue at a glance.',
        icon: LayoutGrid,
        permission: 'dashboard.view',
        phase: 1,
    },
    {
        key: 'users',
        title: 'Staff accounts',
        description: 'Invite team members and control what each role can do.',
        icon: Users,
        permission: 'users.view',
        phase: 1,
    },
    {
        key: 'bookings',
        title: 'Reservations',
        description: 'Confirm, cancel, check in and check out guests.',
        icon: CalendarCheck,
        permission: 'bookings.view',
        phase: 5,
    },
    {
        key: 'guests',
        title: 'Guests',
        description: 'Guest history and contact records.',
        icon: UserSquare2,
        permission: 'guests.view',
        phase: 5,
    },
    {
        key: 'inquiries',
        title: 'Inquiries',
        description: 'Website enquiries, event requests and follow ups.',
        icon: MessageSquare,
        permission: 'inquiries.view',
        phase: 5,
    },
    {
        key: 'rooms',
        title: 'Rooms',
        description: 'Room types, physical rooms, amenities and images.',
        icon: BedDouble,
        permission: 'rooms.view',
        phase: 5,
    },
    {
        key: 'availability',
        title: 'Availability',
        description: 'Open and close rooms for maintenance or house use.',
        icon: CalendarRange,
        permission: 'availability.manage',
        phase: 5,
    },
    {
        key: 'pricing',
        title: 'Rates & pricing',
        description: 'Seasonal, weekend, holiday and corporate rates.',
        icon: Tags,
        permission: 'pricing.view',
        phase: 5,
    },
    {
        key: 'content',
        title: 'Website content',
        description: 'Hero slides, about section, dining, gallery and offers.',
        icon: Globe2,
        permission: 'content.manage',
        phase: 5,
    },
    {
        key: 'media',
        title: 'Media library',
        description: 'Every photo and video used across the website.',
        icon: Image,
        permission: 'media.manage',
        phase: 5,
    },
    {
        key: 'reviews',
        title: 'Guest reviews',
        description: 'Moderate feedback, reply and feature testimonials.',
        icon: Star,
        permission: 'reviews.moderate',
        phase: 5,
    },
    {
        key: 'promotions',
        title: 'Promotions',
        description: 'Coupon codes, campaigns and newsletter subscribers.',
        icon: Megaphone,
        permission: 'promotions.manage',
        phase: 5,
    },
    {
        key: 'reports',
        title: 'Reports',
        description: 'Occupancy, revenue, booking trends and guest analytics.',
        icon: TrendingUp,
        permission: 'reports.view',
        phase: 5,
    },
    {
        key: 'system',
        title: 'System settings',
        description: 'Hotel details, integrations and platform configuration.',
        icon: Settings,
        permission: 'system.manage',
        phase: 5,
    },
];
