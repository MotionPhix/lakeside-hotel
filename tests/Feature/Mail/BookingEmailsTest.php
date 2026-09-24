<?php

use App\Mail\BookingConfirmed;
use App\Mail\BookingReceived;
use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * The two letters a booking produces, tested as letters.
 *
 * Rendered rather than sent: what is being checked is what a guest and a
 * receptionist will actually see, and a template that throws is a booking nobody
 * hears about. The chrome is checked on both, because the surest way for a hotel
 * to look like three businesses is for its own emails to look like three senders.
 */

beforeEach(function (): void {
    $this->seed();

    $this->booking = Booking::query()->with('guest', 'items.roomType')->firstOrFail();

    Setting::store('hotel.name', 'Lakeside Hotel and Conference Centre', 'general');
    Setting::store('hotel.email', 'reservations@lakesidehotelmw.net', 'general');
    Setting::store('hotel.phone', '+265 1 234 567', 'general');
    Setting::store('hotel.address', 'Senga Bay, Salima District, Malawi', 'general');
});

test('the guest\'s confirmation reads as a letter, not a data dump', function () {
    $html = (new BookingConfirmed($this->booking))->render();

    expect($html)
        ->toContain('Dear '.$this->booking->guest->fullName())
        ->toContain($this->booking->reference)
        ->toContain($this->booking->check_in->format('D j F Y'))
        ->toContain($this->booking->check_out->format('D j F Y'))
        // The currency and the thousands separator, because 320000 and 320,000 are
        // read very differently by somebody checking a price.
        ->toContain(number_format((float) $this->booking->total, 0, '.', ','))
        // A way to get back to the booking.
        ->toContain(route('site.booking.show', $this->booking->reference));
});

test('the desk\'s copy carries what the desk acts on', function () {
    $html = (new BookingReceived($this->booking))->render();

    expect($html)
        ->toContain($this->booking->guest->fullName())
        ->toContain($this->booking->guest->email)
        ->toContain($this->booking->reference)
        ->toContain(route('admin.bookings.show', $this->booking->reference))
        // The desk's own words about what to do with it.
        ->toContain('Confirm it, take payment or allocate a room');
});

test('both letters are recognisably from the same hotel', function () {
    $guest = (new BookingConfirmed($this->booking))->render();
    $desk = (new BookingReceived($this->booking))->render();

    $chrome = [
        // The white band the mark sits on, the navy band under it, the line of
        // gold under that, and the footer's own heading and contact line.
        'background:#ffffff;padding:26px 32px 20px',
        'background:#17324d',
        'background:#d9a441',
        'Need help?',
        'reservations@lakesidehotelmw.net',
        'Senga Bay, Salima District, Malawi',
        'Lakeside Hotel and Conference Centre',
    ];

    foreach ($chrome as $marker) {
        expect($guest)->toContain($marker)
            ->and($desk)->toContain($marker);
    }
});

test('the letterhead carries the mark, or the hotel\'s name when it cannot', function () {
    /*
     * Rendered without being sent there is no message to embed the mark into, so
     * what this holds is the fallback - which is also what a reader whose client
     * blocks images sees: the hotel's name rather than a gap where its mark used
     * to be. The embedded mark itself can only be checked by sending one.
     */
    $html = (new BookingConfirmed($this->booking))->render();

    expect($html)
        ->toContain('Lakeside Hotel and Conference Centre')
        // The card is square now: a rounded top would clip the white band the mark
        // sits on into a floating plate.
        ->not->toContain('border-radius:14px');
});

test('the name a guest sees is the hotel\'s, not the application\'s', function () {
    Setting::store('hotel.name', 'Somewhere Else Lodge', 'general');

    expect((new BookingConfirmed($this->booking))->envelope()->from->name)
        ->toBe('Somewhere Else Lodge')
        ->and((new BookingReceived($this->booking))->envelope()->from->name)
        ->toBe('Somewhere Else Lodge');
});

test('a reply goes to the desk rather than to the sending address', function () {
    $envelope = (new BookingConfirmed($this->booking))->envelope();

    // There can be more than one reply-to, so the envelope holds a list.
    expect($envelope->replyTo)->toHaveCount(1)
        ->and($envelope->replyTo[0]->address)->toBe('reservations@lakesidehotelmw.net');
});

test('a booking with something left to pay says so, and one settled does not', function () {
    $this->booking->forceFill([
        'total' => '100000.00',
        'amount_paid' => '40000.00',
    ])->save();

    $owing = (new BookingConfirmed($this->booking))->render();

    expect($owing)->toContain('Still to pay')
        ->toContain('payable on arrival');

    $this->booking->forceFill(['amount_paid' => '100000.00'])->save();

    $settled = (new BookingConfirmed($this->booking->refresh()))->render();

    expect($settled)
        ->toContain('paid in full')
        ->not->toContain('Still to pay')
        ->not->toContain('payable on arrival');
});

test('a booking with nothing paid does not claim a payment was made', function () {
    $this->booking->forceFill(['amount_paid' => '0.00'])->save();

    $html = (new BookingConfirmed($this->booking->refresh()))->render();

    // A "Paid" row reading zero reads like a mistake; the absence of one does not.
    expect($html)->not->toContain('>Paid<');
});
