<?php

namespace App\Support;

use App\Enums\PaymentOption;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingExtra;
use App\Models\Setting;
use App\Services\Booking\AvailabilityOffer;
use Illuminate\Support\Collection;

/**
 * Shapes the booking engine's output for the website.
 *
 * Prices leave here exactly as the engine calculated them. Tax in particular is
 * not recomputed for display: {@see Tax} is the same arithmetic the folio uses,
 * so the figure a guest is quoted is the figure they are billed. What this adds
 * is presentation - the amount as a guest should read it, and the parts of it
 * when the hotel has asked to show them separately.
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
            // The whole media set rather than one URL, so the results list can let
            // the browser choose a size instead of always taking the large one.
            'image' => SitePresenter::media($roomType->getFirstMedia('cover')),
            'available' => $offer->available,
            'nights' => count($quote->nightly),
            'nightly' => $quote->nightly,
            'average_nightly' => $quote->averageNightly,
            'extra_guests' => $quote->extraGuests,
            'extra_person_price' => $quote->extraPersonPrice,
            'subtotal' => $quote->subtotal,
            'tax_total' => $tax,
            'total' => number_format((float) $quote->subtotal + (float) $tax, 2, '.', ''),
            'pricing' => Tax::breakdown(
                $quote->subtotal,
                '0.00',
                number_format((float) $quote->subtotal + (float) $tax, 2, '.', ''),
            ),
            'amenities' => $roomType->amenities->pluck('name')->all(),
        ];
    }

    /**
     * One extra as the booking screens read it. Priced and named as it was when
     * the guest chose it, not as the catalogue reads now.
     *
     * @return array<string, mixed>
     */
    private static function extraLine(BookingExtra $extra): array
    {
        return [
            'id' => $extra->getKey(),
            'name' => $extra->name,
            'label' => $extra->label(),
            'price_basis' => $extra->price_basis,
            'basis_label' => $extra->priceBasisLabel(),
            'unit_price' => $extra->unit_price,
            'quantity' => $extra->quantity,
            'subtotal' => $extra->subtotal,
        ];
    }

    /**
     * One extra as it is offered while booking.
     *
     * Carries everything the form needs to describe it, price it and bound its
     * quantity, so the running total it shows is worked out from the same numbers
     * the reservation will be built from.
     *
     * @return array<string, mixed>
     */
    public static function extraOption(Activity $activity): array
    {
        $bounds = $activity->quantityBounds();

        return [
            'id' => $activity->getKey(),
            'name' => $activity->name,
            'description' => $activity->description,
            'duration' => $activity->durationForHumans(),
            'price' => number_format((float) $activity->price, 2, '.', ''),
            'price_basis' => $activity->price_basis,
            'basis_label' => $activity->priceBasisLabel(),
            'min_quantity' => $bounds['min'],
            'max_quantity' => $bounds['max'],
            'quantity_label' => $bounds['label'],
        ];
    }

    /**
     * The extras a guest may add to a stay.
     *
     * `active()` has already filtered and ordered them. Complimentary activities
     * are left out: this list is of things that cost money, and something that
     * costs nothing is not a figure to add to a folio.
     *
     * @param  Collection<int, Activity>  $activities
     * @return list<array<string, mixed>>
     */
    public static function extraOptions(Collection $activities): array
    {
        return array_values(
            $activities
                ->reject(fn (Activity $activity): bool => $activity->isComplimentary())
                ->map(fn (Activity $activity): array => self::extraOption($activity))
                ->all(),
        );
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
            /* The whole stay, extras included - what the guest owes. The extras
               themselves are itemised below. */
            'total' => $booking->total,
            /*
             * The accommodation on its own, and deliberately without a total
             * passed in: `breakdown` derives its total from the net and the two
             * taxes, which guarantees the rows it renders add up to the figure
             * beside them. Handing it the booking's total instead would fold the
             * extras into the accommodation's tax rows and the parts would no
             * longer reconcile.
             */
            'pricing' => Tax::breakdown(
                $booking->subtotal,
                $booking->discount_total,
            ),
            'extras' => $booking->extras
                ->map(fn (BookingExtra $extra): array => self::extraLine($extra))
                ->values()
                ->all(),
            'extras_total' => number_format(
                (float) $booking->extras->sum('subtotal'),
                2,
                '.',
                '',
            ),
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
