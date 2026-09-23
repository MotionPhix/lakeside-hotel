<?php

namespace App\Support;

use App\Enums\PaymentOption;
use App\Models\Booking;
use App\Models\Setting;
use App\Services\Booking\AvailabilityOffer;

/**
 * Shapes the booking engine's output for the website.
 *
 * Prices leave here exactly as the engine calculated them. Tax in particular is
 * not recomputed for display: {@see Booking::taxFor()} is the same call the folio
 * makes, so the figure a guest is quoted is the figure they are billed.
 */
final class BookingPresenter
{
    /**
     * One room category the hotel can sell for the dates being searched. Priced
     * as the booking would be, so the number on the results card is the number on
     * the confirmation.
     *
     * @return array<string, mixed>
     */
    public static function offer(AvailabilityOffer $offer): array
    {
        $quote = $offer->quote;
        $roomType = $quote->roomType;

        $tax = Booking::taxFor($quote->subtotal);

        return [
            'slug' => $roomType->slug,
            'name' => $roomType->name,
            'tagline' => $roomType->tagline,
            'description' => $roomType->description,
            'bed_configuration' => $roomType->bed_configuration,
            'size_sqm' => $roomType->size_sqm,
            'capacity_adults' => $roomType->capacity_adults,
            'capacity_children' => $roomType->capacity_children,
            'image' => $roomType->heroUrl(),
            'available' => $offer->available,
            'nights' => count($quote->nightly),
            'nightly' => $quote->nightly,
            'average_nightly' => $quote->averageNightly,
            'extra_guests' => $quote->extraGuests,
            'extra_person_price' => $quote->extraPersonPrice,
            'subtotal' => $quote->subtotal,
            'tax_total' => $tax,
            'total' => number_format((float) $quote->subtotal + (float) $tax, 2, '.', ''),
            'amenities' => $roomType->amenities->pluck('name')->all(),
        ];
    }

    /**
     * A reservation, for the guest's own summary page.
     *
     * @return array<string, mixed>
     */
    public static function booking(Booking $booking): array
    {
        return [
            'reference' => $booking->reference,
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'payment_status' => $booking->payment_status->value,
            'payment_status_label' => $booking->payment_status->label(),
            'check_in' => $booking->check_in->toDateString(),
            'check_out' => $booking->check_out->toDateString(),
            'nights' => $booking->nights,
            'adults' => $booking->adults,
            'children' => $booking->children,
            'currency' => $booking->currency,
            'subtotal' => $booking->subtotal,
            'discount_total' => $booking->discount_total,
            'tax_total' => $booking->tax_total,
            'total' => $booking->total,
            'amount_paid' => $booking->amount_paid,
            'balance' => $booking->balance(),
            'payment_method' => $booking->payment_method?->value,
            'payment_method_label' => $booking->payment_method?->label(),
            'airport_transfer' => $booking->airport_transfer,
            'special_requests' => $booking->special_requests,
            'guest' => [
                'name' => $booking->guest->fullName(),
                'email' => $booking->guest->email,
                'phone' => $booking->guest->phone,
            ],
            'rooms' => $booking->items
                ->map(fn ($item): array => [
                    'name' => $item->roomType?->name,
                    'slug' => $item->roomType?->slug,
                    'subtotal' => $item->subtotal,
                    'nightly_rates' => $item->nightly_rates,
                ])
                ->all(),
        ];
    }

    /**
     * The terms, times and payment choices every booking page shows.
     *
     * Which payment options are offered depends on what the hotel has switched on
     * and on whether a gateway key is present: a hotel with no key still takes
     * bookings, it simply settles them at the desk.
     *
     * @return array<string, mixed>
     */
    public static function context(): array
    {
        $gatewayReady = filled(config('paychangu.secret_key'));

        $enabled = [
            PaymentOption::PayNow->value => $gatewayReady && self::flag('booking.online_payment_enabled', true),
            PaymentOption::PayAtHotel->value => self::flag('booking.pay_at_hotel_enabled', true),
        ];

        $options = [];

        foreach ([PaymentOption::PayNow, PaymentOption::PayAtHotel] as $option) {
            if ($enabled[$option->value]) {
                $options[] = [
                    'value' => $option->value,
                    'label' => $option->label(),
                    'deposit_percentage' => $option->depositPercentage(),
                ];
            }
        }

        return [
            'check_in_time' => (string) Setting::value('hotel.check_in_time', '14:00'),
            'check_out_time' => (string) Setting::value('hotel.check_out_time', '10:00'),
            'cancellation_policy' => HotelProfile::cancellationPolicy(),
            'child_policy' => HotelProfile::childPolicy(),
            'transfer_note' => (string) Setting::value(
                'booking.transfer_note',
                'We can meet you at Kamuzu International Airport or Salima airstrip. Tell us your flight and we will confirm the pickup.',
            ),
            'payment_options' => $options,
            'online_payment_available' => $enabled[PaymentOption::PayNow->value],
        ];
    }

    private static function flag(string $key, bool $default): bool
    {
        return filter_var(Setting::value($key, $default ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);
    }
}
