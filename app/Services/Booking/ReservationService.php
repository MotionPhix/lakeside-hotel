<?php

namespace App\Services\Booking;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentOption;
use App\Exceptions\StayNotAvailable;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\RoomType;
use Illuminate\Support\Facades\DB;

/**
 * Turns a priced stay into a reservation.
 *
 * The reservation is written as `pending` and holds its room immediately: the
 * availability count treats a pending line as a taken room, so two guests cannot
 * both be sold the last one while one of them is still on the payment page. A
 * payment succeeding confirms the booking; the dashboard can also confirm it by
 * hand for guests settling at the desk.
 */
final class ReservationService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly RateCalculator $rates,
    ) {}

    /**
     * Take a reservation, re-checking availability inside the transaction because
     * the search the guest is looking at could be minutes old.
     *
     * @param  array<string, mixed>  $guestAttributes
     * @param  array<string, mixed>|null  $transfer
     */
    public function reserve(
        RoomType $roomType,
        StayRequest $stay,
        array $guestAttributes,
        PaymentOption $paymentOption = PaymentOption::PayAtHotel,
        ?Coupon $coupon = null,
        bool $airportTransfer = false,
        ?string $specialRequests = null,
        ?array $transfer = null,
        BookingSource $source = BookingSource::Website,
    ): Booking {
        return DB::transaction(function () use (
            $roomType, $stay, $guestAttributes, $paymentOption,
            $coupon, $airportTransfer, $specialRequests, $transfer, $source,
        ): Booking {
            if (! $this->availability->isAvailable($roomType, $stay)) {
                throw StayNotAvailable::for($roomType, $stay);
            }

            $guest = $this->guestFor($guestAttributes);
            $quote = $this->rates->quote($roomType, $stay);

            $booking = Booking::query()->create([
                'reference' => Booking::generateReference(),
                'guest_id' => $guest->getKey(),
                'status' => BookingStatus::Pending,
                'source' => $source,
                'check_in' => $stay->checkIn,
                'check_out' => $stay->checkOut,
                'nights' => $stay->nights(),
                'adults' => $stay->adults,
                'children' => $stay->children,
                'currency' => Booking::currency(),
                'payment_method' => $paymentOption,
                'coupon_id' => $coupon?->getKey(),
                'special_requests' => $specialRequests,
                'airport_transfer' => $airportTransfer,
                'transfer_details' => $transfer,
            ]);

            $booking->items()->create([
                'room_type_id' => $roomType->getKey(),
                'rate_plan_id' => $quote->ratePlanId,
                'adults' => $stay->adults,
                'children' => $stay->children,
                'price_per_night' => $quote->averageNightly,
                'subtotal' => $quote->subtotal,
                'nightly_rates' => $quote->nightly,
            ]);

            $booking->recalculateTotals()->syncPaymentStatus()->save();

            return $booking->load('guest', 'items.roomType');
        });
    }

    /**
     * Find the guest by email, or start a record for them.
     *
     * An existing guest keeps any detail they did not retype: leaving the phone
     * field blank on a second booking should not erase the number the hotel
     * already has.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function guestFor(array $attributes): Guest
    {
        $existing = Guest::query()->where('email', $attributes['email'])->first();

        if ($existing instanceof Guest) {
            $existing
                ->fill(array_filter(
                    $attributes,
                    fn (mixed $value): bool => $value !== null && $value !== '',
                ))
                ->save();

            return $existing;
        }

        return Guest::query()->create($attributes);
    }
}
