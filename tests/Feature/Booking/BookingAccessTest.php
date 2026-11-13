<?php

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

test('a booking reference alone cannot reveal a guest reservation', function () {
    $booking = Booking::factory()->pending()->create();

    $this->get(route('site.booking.show', $booking->reference))->assertForbidden();
});

test('a signed guest link shows the reservation and a protected payment action', function () {
    $booking = Booking::factory()->pending()->create();

    $this->get(URL::signedRoute('site.booking.show', $booking->reference))
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/booking/show')
            ->where('reservation.reference', $booking->reference)
            ->where('reservation.guest.email', $booking->guest->email)
            ->where('reservation.payment_url', URL::signedRoute('site.booking.pay', $booking->reference)));
});

test('a guest link cannot be reused for another booking', function () {
    $booking = Booking::factory()->pending()->create();
    $other = Booking::factory()->pending()->create();
    $url = str_replace($booking->reference, $other->reference, URL::signedRoute('site.booking.show', $booking->reference));

    $this->get($url)->assertForbidden();
});

test('payment attempts require their own signed link', function () {
    $booking = Booking::factory()->pending()->create();
    Http::preventStrayRequests();

    $this->post(route('site.booking.pay', $booking->reference))->assertForbidden();
    $wrongAction = str_replace('/booking/'.$booking->reference.'?', '/booking/'.$booking->reference.'/pay?', URL::signedRoute('site.booking.show', $booking->reference));
    $this->post($wrongAction)->assertForbidden();

    $this->assertDatabaseCount('payments', 0);
    Http::assertNothingSent();
});

test('an unsigned callback cannot issue a private guest link', function () {
    $booking = Booking::factory()->pending()->create();
    Http::preventStrayRequests();

    $this->get(route('site.booking.callback', $booking->reference))->assertForbidden();

    Http::assertNothingSent();
});

test('a signed callback accepts gateway query fields and preserves protected access', function () {
    $booking = Booking::factory()->pending()->create();
    $url = URL::signedRoute('site.booking.callback', $booking->reference).'&tx_ref=unknown&status=success';
    Http::preventStrayRequests();

    $this->get($url)->assertRedirect(URL::signedRoute('site.booking.show', $booking->reference));

    Http::assertNothingSent();
    $this->assertDatabaseCount('payments', 0);
});

test('a callback signature cannot be moved to another reservation', function () {
    $booking = Booking::factory()->pending()->create();
    $other = Booking::factory()->pending()->create();
    $url = str_replace($booking->reference, $other->reference, URL::signedRoute('site.booking.callback', $booking->reference));

    $this->get($url.'&status=success')->assertForbidden();
});
