<?php

use App\Mail\BookingConfirmed;
use App\Mail\BookingReceived;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/*
 * The confirmation page tells every guest their copy is on its way, so the mail
 * has to leave on both routes to a booking: paid online (covered by the payment
 * tests) and held for the desk, which never touches the gateway.
 */

beforeEach(function (): void {
    $this->seed();

    Setting::store('hotel.email', 'reservations@lakesidehotelmw.net');
});

function bookingPayload(array $overrides = []): array
{
    $checkIn = Carbon::parse('2027-02-09');

    return array_merge([
        'room_type' => 'standard-double',
        'check_in' => $checkIn->toDateString(),
        'check_out' => $checkIn->copy()->addDays(2)->toDateString(),
        'adults' => 2,
        'children' => 0,
        'first_name' => 'Held',
        'last_name' => 'Guest',
        'email' => 'held@example.com',
        'phone' => '+265 999 000 555',
        'payment_option' => 'pay_at_hotel',
        'terms' => true,
    ], $overrides);
}

test('a booking held for payment on arrival emails the guest and the desk', function () {
    Mail::fake();

    $this->post('/booking', bookingPayload())->assertRedirect();

    Mail::assertSent(BookingConfirmed::class, 1);
    Mail::assertSent(BookingConfirmed::class, fn ($mail) => $mail->hasTo('held@example.com'));
    Mail::assertSent(BookingReceived::class, 1);
    Mail::assertSent(BookingReceived::class, fn ($mail) => $mail->hasTo('reservations@lakesidehotelmw.net'));
});

test('a bank transfer booking is emailed the same way', function () {
    Mail::fake();

    $this->post('/booking', bookingPayload([
        'payment_option' => 'bank_transfer',
        'email' => 'transfer@example.com',
    ]))->assertRedirect();

    Mail::assertSent(BookingConfirmed::class, fn ($mail) => $mail->hasTo('transfer@example.com'));
});

test('a booking paid online is not emailed until the payment settles', function () {
    Mail::fake();

    $this->post('/booking', bookingPayload([
        'payment_option' => 'pay_now',
        'email' => 'paying@example.com',
    ]))->assertRedirect();

    // The guest is on their way to the gateway; the confirmation belongs to the
    // moment the money is confirmed, not to the moment the form was submitted.
    Mail::assertNotSent(BookingConfirmed::class);
});
