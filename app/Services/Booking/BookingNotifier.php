<?php

namespace App\Services\Booking;

use App\Mail\BookingConfirmed;
use App\Mail\BookingReceived;
use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

/**
 * The two messages a reservation produces: the guest's copy and the desk's.
 *
 * A booking becomes real by two routes. Paying online settles it, and the
 * confirmation is sent from the payment reconciliation. Reserving to pay on
 * arrival never touches the gateway at all - so both routes have to send from
 * here, or the guest is promised an email on the confirmation page that nothing
 * ever sends.
 */
final class BookingNotifier
{
    public function confirmToGuest(Booking $booking): void
    {
        Mail::to($booking->guest->email)->send(new BookingConfirmed($booking));
    }

    /**
     * Tell the reservations desk. Sent to the address the hotel edits in the
     * dashboard, so a booking never lands somewhere nobody reads.
     */
    public function notifyDesk(Booking $booking): void
    {
        $inbox = Setting::value('hotel.email', config('mail.from.address'));

        if ($inbox === null || $inbox === '') {
            return;
        }

        Mail::to($inbox)->send(new BookingReceived($booking));
    }

    /**
     * Both copies, for a booking that is held without any payment attempt.
     */
    public function bookingHeld(Booking $booking): void
    {
        $this->confirmToGuest($booking);
        $this->notifyDesk($booking);
    }
}
