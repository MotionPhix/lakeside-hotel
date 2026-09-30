<?php

use App\Models\Booking;
use App\Models\RoomType;
use App\Models\Setting;
use App\Support\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/*
 * Prices a guest reads, and the money the hotel charges, are the same number.
 *
 * These assert the values a page is handed rather than the markup it produces.
 * The front end is rendered in the browser, so the props are the contract and the
 * markup is a copy of it - asserting the markup here would be asserting a string
 * this application does not itself generate.
 */

beforeEach(function (): void {
    $this->seed();

    // How the site ships: one price, tax already inside it.
    Setting::store(Tax::SHOW_SEPARATELY_SETTING, '0', 'booking', 'boolean');
});

/** The Inertia props of a page. */
function pageProps($response): array
{
    return $response->viewData('page')['props'] ?? [];
}

/** A search far enough out to be free of whatever a previous test left behind. */
function futureStay(): array
{
    return [
        'check_in' => Carbon::now()->addDays(40)->toDateString(),
        'check_out' => Carbon::now()->addDays(42)->toDateString(),
        'adults' => 2,
        'children' => 0,
    ];
}

test('a room is advertised at the price a guest pays, with the tax already in it', function () {
    $roomType = RoomType::query()->firstOrFail();

    $from = pageProps($this->get(route('site.rooms.show', $roomType)))['roomType']['from'];

    expect($from['amount'])->toBe(Tax::inclusive($roomType->fromPrice()))
        ->and($from['net'])->toBe(number_format((float) $roomType->fromPrice(), 2, '.', ''))
        ->and($from['includes_tax'])->toBeTrue()
        ->and($from['show_separately'])->toBeFalse()
        /*
         * The rate the hotel sets and the rate a guest reads are deliberately not
         * the same number. If they ever are, the tax has stopped being applied.
         */
        ->and((float) $from['amount'])->toBeGreaterThan((float) $from['net']);
});

test('every rate on a room page is the price a guest pays for it', function () {
    $roomType = RoomType::query()->firstOrFail();

    $page = pageProps($this->get(route('site.rooms.show', $roomType)));

    $byBase = $page['roomType']['base'];
    $byWeekend = $page['roomType']['weekend'];

    expect($byBase['amount'])->toBe(Tax::inclusive((string) $roomType->base_price));

    // The weekend rate is optional, so it is only priced when there is one.
    if ($roomType->weekend_price === null) {
        expect($byWeekend)->toBeNull();
    } else {
        expect($byWeekend['amount'])->toBe(Tax::inclusive((string) $roomType->weekend_price));
    }
});

test('the room on the list is the same price as the room on its own page', function () {
    $card = collect(pageProps($this->get('/rooms'))['roomTypes'])
        ->firstWhere('slug', RoomType::query()->firstOrFail()->slug);

    $roomType = RoomType::query()->firstOrFail();

    expect($card['from']['amount'])->toBe(Tax::inclusive($roomType->fromPrice()));
});

test('a room is quoted inclusive before anything is chosen, and shows no separate tax', function () {
    $offer = pageProps($this->get(route('site.booking.index', futureStay())))['offers'][0] ?? null;

    expect($offer)->not->toBeNull('the search returned nothing to price');

    expect($offer['pricing']['show_separately'])->toBeFalse()
        ->and($offer['pricing']['amount'])->toBe($offer['total'])
        // The room line is the whole charge, because the tax is inside it.
        ->and($offer['pricing']['amount'])->toBe(Tax::inclusive($offer['subtotal']))
        ->and((float) $offer['pricing']['amount'])
        ->toBeGreaterThan((float) $offer['subtotal']);
});

test('the tax a guest is quoted is the tax the folio charges', function () {
    $offer = pageProps($this->get(route('site.booking.index', futureStay())))['offers'][0];

    expect($offer['tax_total'])->toBe(Booking::taxFor($offer['subtotal']))
        ->and($offer['pricing']['tax_total'])->toBe($offer['tax_total']);
});

test('asking for the tax separately breaks it into two lines that add up', function () {
    Setting::store(Tax::SHOW_SEPARATELY_SETTING, '1', 'booking', 'boolean');

    $offer = pageProps($this->get(route('site.booking.index', futureStay())))['offers'][0];

    $pricing = $offer['pricing'];

    expect($pricing['show_separately'])->toBeTrue()
        // VAT and the levy, added together, are exactly the tax total.
        ->and(
            number_format((float) $pricing['vat']['amount'] + (float) $pricing['levy']['amount'], 2, '.', ''),
        )->toBe($pricing['tax_total'])
        // And the room plus that tax is exactly the total.
        ->and(
            number_format((float) $pricing['net'] + (float) $pricing['tax_total'], 2, '.', ''),
        )->toBe($pricing['amount'])
        ->and($pricing['vat']['rate'])->toBe(Tax::vatRate())
        ->and($pricing['levy']['rate'])->toBe(Tax::levyRate());
});

test('switching the breakdown on explains the price without changing it', function () {
    $inclusive = pageProps($this->get(route('site.booking.index', futureStay())))['offers'][0];

    Setting::store(Tax::SHOW_SEPARATELY_SETTING, '1', 'booking', 'boolean');

    $itemised = pageProps($this->get(route('site.booking.index', futureStay())))['offers'][0];

    /*
     * The single most important property of the whole setting: the guest is told
     * the same amount either way. If this ever fails, turning the breakdown on has
     * started costing people money.
     */
    expect($itemised['pricing']['amount'])->toBe($inclusive['pricing']['amount'])
        ->and($itemised['total'])->toBe($inclusive['total']);
});

test('changing a rate changes what a guest is quoted', function () {
    $before = pageProps($this->get(route('site.booking.index', futureStay())))['offers'][0];

    Setting::store(Tax::VAT_SETTING, '30', 'booking', 'decimal');

    $after = pageProps($this->get(route('site.booking.index', futureStay())))['offers'][0];

    expect($after['subtotal'])->toBe($before['subtotal'], 'the room rate itself should not move')
        ->and((float) $after['total'])->toBeGreaterThan((float) $before['total'])
        ->and($after['pricing']['vat']['rate'])->toBe(30.0);

    // And the room prices on the website follow the same rate.
    $roomType = RoomType::query()->firstOrFail();

    expect(pageProps($this->get(route('site.rooms.show', $roomType)))['roomType']['from']['amount'])
        ->toBe(Tax::inclusive($roomType->fromPrice()));
});

test('a discount is taken off in the same terms as the total it comes off', function () {
    /*
     * Rounding VAT and the levy separately can leave a cent adrift, which is why
     * the inclusive discount is derived from the total rather than computed from
     * the rate. A summary whose rows do not add up is worse than one that shows
     * less, so this pins the reconciliation.
     */
    $pricing = Tax::breakdown('1000.00', '150.00');

    expect(
        number_format((float) $pricing['subtotal_gross'] - (float) $pricing['discount_gross'], 2, '.', ''),
    )->toBe($pricing['amount'])
        ->and(
            number_format((float) $pricing['net'] + (float) $pricing['tax_total'], 2, '.', ''),
        )->toBe($pricing['amount'])
        // Tax is charged on what is left after the discount, not before it.
        ->and($pricing['net'])->toBe('850.00');
});

test('a reservation shows the tax inside its total, not on top of it', function () {
    $booking = Booking::factory()->confirmed()->create();

    $reservation = pageProps($this->get(route('site.booking.show', $booking->reference)))['reservation'];

    $pricing = $reservation['pricing'];

    expect($pricing['amount'])->toBe($reservation['total'])
        // What a guest is told is inside the total is what the folio recorded.
        ->and($pricing['tax_total'])->toBe($reservation['tax_total'])
        ->and(
            number_format((float) $pricing['net'] + (float) $pricing['tax_total'], 2, '.', ''),
        )->toBe($reservation['total']);
});

test('the rates come from the settings, and fall back to the statutory ones', function () {
    expect(Tax::vatRate())->toBe(16.5)
        ->and(Tax::levyRate())->toBe(1.0);

    Setting::store(Tax::VAT_SETTING, '12.5', 'booking', 'decimal');
    Setting::store(Tax::LEVY_SETTING, '2.5', 'booking', 'decimal');

    expect(Tax::vatRate())->toBe(12.5)
        ->and(Tax::levyRate())->toBe(2.5)
        ->and(Tax::rate())->toBe(15.0)
        // And a booking charges at those rates, not at the constants.
        ->and(Booking::taxRate())->toBe(15.0);
});

test('the site says the price includes the tax rather than being added later', function () {
    $this->get(route('site.policies'))
        ->assertOk()
        ->assertDontSee('added at checkout');
});
