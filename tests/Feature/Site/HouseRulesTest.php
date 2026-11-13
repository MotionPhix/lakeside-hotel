<?php

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\DiningVenue;
use App\Models\Offer;
use App\Support\HotelProfile;
use Inertia\Testing\AssertableInertia as Assert;

test('guests receive the alcohol and outside food rules with public pages', function () {
    $this->seed();
    $this->get(route('site.rooms.index'))->assertInertia(fn (Assert $page) => $page
        ->where('site.booking.house_rules', HotelProfile::HOUSE_RULES)
        ->has('roomTypes', 7)
        ->has('roomTypes.0.amenities'));
    $lounge = DiningVenue::query()->where('slug', 'bar-and-lounge')->sole();
    expect($lounge->description)->toContain('mocktails', 'shisha', 'Alcohol is not served or permitted');
    expect($lounge->description)->not->toContain('Local gins', 'South African wine');
    expect(Offer::query()->where('coupon_code', 'WEEKEND15')->sole()->description)->not->toContain('wine');
});

test('the guest confirmation email repeats the hotel house rules', function () {
    $this->seed();
    $booking = Booking::query()->firstOrFail();
    $html = (new BookingConfirmed($booking))->render();

    expect($html)->toContain('Alcohol is not permitted anywhere on the property.', 'Guests may not bring their own food.');
});
