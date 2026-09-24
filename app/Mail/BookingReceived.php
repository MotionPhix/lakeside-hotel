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
 * The reservations desk's copy. Deliberately plain and dense: whoever is on the
 * desk wants the reference, the dates, the party and anything the guest asked
 * for, without the marketing wrapper the guest gets.
 */
final class BookingReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Booking $booking) {}

    /**
     * The desk's copy wears the hotel's own name too: it lands in the same inbox
     * as the guest's, and the two should be recognisable as one sender.
     */
    public function envelope(): Envelope
    {
        $hotel = HotelProfile::details();

        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                $hotel['name'] !== '' ? $hotel['name'] : (string) config('mail.from.name'),
            ),
            subject: 'New booking '.$this->booking->reference.' — '
                .$this->booking->guest->fullName().' — '
                .$this->booking->check_in->format('j M').' to '.$this->booking->check_out->format('j M Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-received',
            with: ['hotel' => HotelProfile::details()],
        );
    }
}
