<?php

namespace App\Enums;

/**
 * Every capability that can be granted to a member of staff.
 *
 * Permissions are named `<module>.<action>` so the dashboard navigation, the
 * route middleware and the admin UI can all be driven from this single list.
 */
enum Permission: string
{
    case ViewDashboard = 'dashboard.view';

    case ViewBookings = 'bookings.view';
    case ManageBookings = 'bookings.manage';

    case ViewGuests = 'guests.view';
    case ManageGuests = 'guests.manage';

    case ViewInquiries = 'inquiries.view';
    case ManageInquiries = 'inquiries.manage';

    case ViewRooms = 'rooms.view';
    case ManageRooms = 'rooms.manage';
    case ManageAvailability = 'availability.manage';

    case ViewPricing = 'pricing.view';
    case ManagePricing = 'pricing.manage';

    case ManageContent = 'content.manage';
    case ManagePromotions = 'promotions.manage';
    case ModerateReviews = 'reviews.moderate';
    case ManageMedia = 'media.manage';

    case ViewUsers = 'users.view';
    case ManageUsers = 'users.manage';

    case ViewReports = 'reports.view';

    case ManageSystem = 'system.manage';

    /**
     * Human readable label for the admin interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::ViewDashboard => 'View dashboard',
            self::ViewBookings => 'View reservations',
            self::ManageBookings => 'Manage reservations',
            self::ViewGuests => 'View guests',
            self::ManageGuests => 'Manage guests',
            self::ViewInquiries => 'View inquiries',
            self::ManageInquiries => 'Manage inquiries',
            self::ViewRooms => 'View rooms',
            self::ManageRooms => 'Manage rooms and room types',
            self::ManageAvailability => 'Manage room availability',
            self::ViewPricing => 'View rates',
            self::ManagePricing => 'Manage rates and pricing rules',
            self::ManageContent => 'Manage website content',
            self::ManagePromotions => 'Manage promotions and coupons',
            self::ModerateReviews => 'Moderate guest reviews',
            self::ManageMedia => 'Manage the media library',
            self::ViewUsers => 'View staff accounts',
            self::ManageUsers => 'Manage staff accounts',
            self::ViewReports => 'View reports',
            self::ManageSystem => 'Manage system settings',
        };
    }

    /**
     * Dashboard module this permission unlocks (the part before the dot).
     */
    public function group(): string
    {
        return explode('.', $this->value)[0];
    }
}
