<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The handful of hotel details a confirmation email has to quote.
 *
 * Read from settings rather than hardcoded, because the phone number on the
 * reservation desk changes more often than the code does.
 */
final class HotelProfile
{
    /**
     * @return array<string, string>
     */
    public static function details(): array
    {
        return [
            'name' => (string) Setting::value('hotel.name', (string) config('app.name')),
            'address' => (string) Setting::value('hotel.address', ''),
            'phone' => (string) Setting::value('hotel.phone', ''),
            'email' => (string) Setting::value('hotel.email', ''),
            'website' => (string) Setting::value('hotel.website', ''),
            'check_in' => (string) Setting::value('hotel.check_in_time', '14:00'),
            'check_out' => (string) Setting::value('hotel.check_out_time', '10:00'),
        ];
    }

    /**
     * The cancellation terms shown before a guest commits, and repeated in the
     * email so they have them in writing.
     */
    public static function cancellationPolicy(): string
    {
        return (string) Setting::value(
            'booking.cancellation_policy',
            'Free cancellation up to 48 hours before arrival. Later cancellations and no-shows are charged the first night.',
        );
    }

    public static function childPolicy(): string
    {
        return (string) Setting::value(
            'booking.child_policy',
            'Children under 5 stay free when sharing with two adults.',
        );
    }
}
