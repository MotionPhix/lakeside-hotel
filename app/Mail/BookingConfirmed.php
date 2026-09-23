<?php

namespace App\Mail;

use App\Models\Booking;
use App\Support\HotelProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your stay at '.HotelProfile::details()['name'].' — '.$this->booking->reference,
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
