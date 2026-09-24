<?php

namespace App\Mail;

use App\Models\Booking;
use App\Support\HotelProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The guest's copy of a settled reservation: what they booked, what it cost, and
 * what happens when they arrive.
 *
 * Sent from inside the payment reconciliation, which is the only place that can
 * tell a first confirmation from a repeated webhook, so a guest is never emailed
 * twice for the same booking.
 */
final class BookingConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Booking $booking) {}

    /**
     * Who the guest sees it came from, and where a reply should land.
     *
     * The display name is the hotel's own, read from settings, so changing what
     * the hotel calls itself changes what arrives in somebody's inbox. The reply
     * address is the reservations one rather than whatever the sending box is:
     * a guest hitting reply should reach the desk that can answer them.
     */
    public function envelope(): Envelope
    {
        $hotel = HotelProfile::details();

        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                $hotel['name'] !== '' ? $hotel['name'] : (string) config('mail.from.name'),
            ),
            // A list, because there can be more than one reply address. Handing
            // this a single Address makes Laravel treat its properties as a list
            // of them, and the hotel's name then fails RFC validation as an email.
            replyTo: $hotel['email'] !== '' ? [new Address($hotel['email'], $hotel['name'])] : [],
            subject: 'Your stay at '.$hotel['name'].' — '.$this->booking->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-confirmed',
            with: [
                'hotel' => HotelProfile::details(),
                'cancellationPolicy' => HotelProfile::cancellationPolicy(),
                'childPolicy' => HotelProfile::childPolicy(),
            ],
        );
    }
}
