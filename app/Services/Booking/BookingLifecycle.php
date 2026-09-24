<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Exceptions\BookingNotActionable;
use App\Models\Booking;
use Carbon\CarbonInterface;

/**
 * The moves a reservation can make once it exists, and the state each move is
 * legal from.
 *
 * Kept apart from the controller so the rules live in one place. The desk's
 * buttons and the tests both come through here, and the same map decides whether
 * an action is offered and whether it is permitted - so a button can never appear
 * for something the service would refuse.
 */
final class BookingLifecycle
{
    /**
     * Which states each action may be taken from.
     *
     * @var array<string, list<BookingStatus>>
     */
    private const TRANSITIONS = [
        'confirm' => [BookingStatus::Pending],
        'cancel' => [BookingStatus::Pending, BookingStatus::Confirmed],
        'check_in' => [BookingStatus::Confirmed],
        'check_out' => [BookingStatus::CheckedIn],
        'no_show' => [BookingStatus::Pending, BookingStatus::Confirmed],
    ];

    /**
     * Whether an action may be taken on this booking right now.
     */
    public function can(Booking $booking, string $action): bool
    {
        return in_array($booking->status, self::TRANSITIONS[$action] ?? [], true);
    }

    /**
     * Every action with whether it is currently available, for the dashboard to
     * render only the buttons that would do something.
     *
     * @return array<string, bool>
     */
    public function availableActions(Booking $booking): array
    {
        $actions = [];

        foreach (array_keys(self::TRANSITIONS) as $action) {
            $actions[$action] = $this->can($booking, $action);
        }

        return $actions;
    }

    /**
     * Confirm a pending reservation, holding it for the guest.
     */
    public function confirm(Booking $booking): Booking
    {
        $this->allow($booking, 'confirm');

        return $this->move($booking, BookingStatus::Confirmed, ['confirmed_at' => now()]);
    }

    /**
     * Cancel a reservation.
     *
     * Only before the guest is in house: once somebody has checked in the stay is
     * ended by checking them out. Cancelling releases the room, because a
     * cancelled booking is not one that holds inventory.
     */
    public function cancel(Booking $booking, ?string $reason = null): Booking
    {
        $this->allow($booking, 'cancel');

        return $this->move($booking, BookingStatus::Cancelled, [
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Check a guest in.
     */
    public function checkIn(Booking $booking, ?CarbonInterface $at = null): Booking
    {
        $this->allow($booking, 'check_in');

        return $this->move($booking, BookingStatus::CheckedIn, [
            'checked_in_at' => $at ?? now(),
        ]);
    }

    /**
     * Check a guest out. The room is released from that moment, so the booking
     * stops counting towards occupancy.
     */
    public function checkOut(Booking $booking, ?CarbonInterface $at = null): Booking
    {
        $this->allow($booking, 'check_out');

        return $this->move($booking, BookingStatus::CheckedOut, [
            'checked_out_at' => $at ?? now(),
        ]);
    }

    /**
     * Mark a guest who never arrived.
     *
     * Refused before the arrival date has come: a booking is not a no-show until
     * the day is here, and marking one early would release a room the guest may
     * still be travelling to.
     */
    public function markNoShow(Booking $booking, ?CarbonInterface $at = null): Booking
    {
        $this->allow($booking, 'no_show');

        $at ??= now();

        if ($at->copy()->startOfDay()->lt($booking->check_in->copy()->startOfDay())) {
            throw BookingNotActionable::tooEarly($booking, $booking->check_in);
        }

        return $this->move($booking, BookingStatus::NoShow, ['cancelled_at' => $at]);
    }

    private function allow(Booking $booking, string $action): void
    {
        if (! $this->can($booking, $action)) {
            throw BookingNotActionable::from($booking, $action, self::TRANSITIONS[$action] ?? []);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function move(Booking $booking, BookingStatus $status, array $attributes = []): Booking
    {
        $booking->forceFill($attributes + ['status' => $status])->save();

        return $booking->refresh();
    }
}
