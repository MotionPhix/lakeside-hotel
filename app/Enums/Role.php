<?php

namespace App\Enums;

/**
 * Roles a user account can hold.
 *
 * `Guest` is the safe default for an account that has not been given a role by
 * an administrator yet: it holds no permissions at all and can never reach the
 * dashboard.
 *
 * The five staff roles match the roles requested for Lakeside Hotel.
 */
enum Role: string
{
    case Guest = 'guest';
    case SystemAdmin = 'system_admin';
    case Admin = 'admin';
    case HotelManager = 'hotel_manager';
    case Reception = 'reception';
    case Marketing = 'marketing';

    /**
     * Human readable label for the admin interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Guest => 'Guest',
            self::SystemAdmin => 'System Admin',
            self::Admin => 'Admin',
            self::HotelManager => 'Hotel Manager',
            self::Reception => 'Reception Staff',
            self::Marketing => 'Marketing Staff',
        };
    }

    /**
     * Explanation shown next to the role picker when managing staff accounts.
     */
    public function description(): string
    {
        return match ($this) {
            self::Guest => 'No dashboard access. Awaiting a role assignment.',
            self::SystemAdmin => 'Developer account. Unrestricted access to every module and system settings.',
            self::Admin => 'Hotel owner. Full access to all business modules.',
            self::HotelManager => 'Runs day-to-day operations: rooms, rates, reservations, content and reports.',
            self::Reception => 'Front desk: reservations, check-in and check-out, guests and inquiries.',
            self::Marketing => 'Website content, promotions, reviews and marketing reports.',
        };
    }

    /**
     * Seniority of the role. Used to stop staff managing accounts above their own level.
     */
    public function level(): int
    {
        return match ($this) {
            self::Guest => 0,
            self::Reception, self::Marketing => 20,
            self::HotelManager => 60,
            self::Admin => 80,
            self::SystemAdmin => 100,
        };
    }

    /**
     * Whether this role is allowed to sign in to the dashboard at all.
     */
    public function isStaff(): bool
    {
        return $this !== self::Guest;
    }

    /**
     * The permissions granted by this role.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Guest => [],

            self::SystemAdmin => Permission::cases(),

            self::Admin => array_values(array_filter(
                Permission::cases(),
                fn (Permission $permission): bool => $permission !== Permission::ManageSystem,
            )),

            self::HotelManager => [
                Permission::ViewDashboard,
                Permission::ViewBookings,
                Permission::ManageBookings,
                Permission::ViewGuests,
                Permission::ManageGuests,
                Permission::ViewInquiries,
                Permission::ManageInquiries,
                Permission::ViewRooms,
                Permission::ManageRooms,
                Permission::ManageAvailability,
                Permission::ViewPricing,
                Permission::ManagePricing,
                Permission::ManageContent,
                Permission::ManagePromotions,
                Permission::ModerateReviews,
                Permission::ManageMedia,
                Permission::ViewUsers,
                Permission::ViewReports,
            ],

            self::Reception => [
                Permission::ViewDashboard,
                Permission::ViewBookings,
                Permission::ManageBookings,
                Permission::ViewGuests,
                Permission::ManageGuests,
                Permission::ViewInquiries,
                Permission::ManageInquiries,
                Permission::ViewRooms,
                Permission::ManageAvailability,
            ],

            self::Marketing => [
                Permission::ViewDashboard,
                Permission::ViewBookings,
                Permission::ViewRooms,
                Permission::ManageContent,
                Permission::ManagePromotions,
                Permission::ModerateReviews,
                Permission::ManageMedia,
                Permission::ViewReports,
            ],
        };
    }

    /**
     * Whether this role holds the given permission, or all of them when an array is given.
     *
     * @param  Permission|array<int, Permission>  $permissions
     */
    public function hasPermission(Permission|array $permissions): bool
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        return array_all($permissions, fn (Permission $permission): bool => in_array($permission, $this->permissions(), true));
    }

    /**
     * The roles that may be assigned to a staff account, in display order.
     *
     * @return list<Role>
     */
    public static function assignable(): array
    {
        return [
            self::Admin,
            self::HotelManager,
            self::Reception,
            self::Marketing,
            self::SystemAdmin,
        ];
    }

    /**
     * The roles that hold at least one permission.
     *
     * @return list<Role>
     */
    public static function staff(): array
    {
        return array_values(array_filter(self::cases(), fn (Role $role): bool => $role->isStaff()));
    }
}
