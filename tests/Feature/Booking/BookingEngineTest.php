<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentOption;
use App\Enums\PaymentRecordStatus;
use App\Enums\RoomStatus;
use App\Exceptions\StayNotAvailable;
use App\Mail\BookingConfirmed;
use App\Mail\BookingReceived;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\RateCalculator;
use App\Services\Booking\ReservationService;
use App\Services\Booking\StayRequest;
use App\Services\Payments\PaymentService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/*
 * The booking engine's arithmetic is the part that costs money when it is wrong,
 * so these tests pin the numbers rather than the shapes: what a night costs, what
 * a room being taken does to the count, and what a second webhook does to a
 * booking that has already been paid for.
 */

beforeEach(function (): void {
    $this->seed();

    config([
        'paychangu.secret_key' => 'sec-test-123',
        'paychangu.webhook_secret' => 'whsec-test-456',
    ]);

    $this->rates = app(RateCalculator::class);
    $this->availability = app(AvailabilityService::class);
    $this->reservations = app(ReservationService::class);
    $this->payments = app(PaymentService::class);
});

/** The next Friday on or after a fixed date, so weekend tests never drift. */
function nextFriday(): Carbon
{
    return Carbon::parse('2026-11-01')->next(CarbonInterface::FRIDAY)->startOfDay();
}

/** A midweek night, which is charged at the base rate. */
function midweek(): Carbon
{
    return Carbon::parse('2026-11-01')->next(CarbonInterface::TUESDAY)->startOfDay();
}

function single(Booking $booking): Booking
{
    return $booking->load('guest', 'items')->refresh();
}

/**
 * Strip the hotel's own rate plans so a pricing test measures the calculator
 * rather than whichever promotion the seeder happens to have running.
 */
function withoutRatePlans(): void
{
    RatePlan::query()->delete();
}

test('a weekend night is charged at the weekend rate', function () {
    withoutRatePlans();

    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $friday = nextFriday();

    $stay = new StayRequest($friday, $friday->copy()->addDays(2), adults: 2);
    $quote = $this->rates->quote($roomType, $stay);

    // Friday and Saturday, both at the weekend price.
    expect($quote->nightly)->toHaveCount(2)
        ->and(array_values($quote->nightly))->each->toBe($roomType->weekend_price)
        ->and($quote->subtotal)->toBe(number_format((float) $roomType->weekend_price * 2, 2, '.', ''));
});

test('a midweek night is charged at the base rate', function () {
    withoutRatePlans();

    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();

    $stay = new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 2);
    $quote = $this->rates->quote($roomType, $stay);

    expect($quote->subtotal)->toBe($roomType->base_price)
        ->and($quote->extraGuests)->toBe(0);
});

test('a guest above the included occupancy pays the extra person supplement each night', function () {
    withoutRatePlans();

    // A single sleeps one; a couple pays the supplement on top of the base rate.
    $roomType = RoomType::query()->where('slug', 'deluxe-single')->sole();
    $tuesday = midweek();

    $stay = new StayRequest($tuesday, $tuesday->copy()->addDays(2), adults: 2);
    $quote = $this->rates->quote($roomType, $stay);

    $expected = ((float) $roomType->base_price + (float) $roomType->extra_person_price) * 2;

    expect($quote->extraGuests)->toBe(1)
        ->and($quote->subtotal)->toBe(number_format($expected, 2, '.', ''));
});

test('the highest priority rate plan covering a night wins', function () {
    withoutRatePlans();

    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();

    RatePlan::factory()->create([
        'room_type_id' => $roomType->getKey(),
        'name' => 'Quiet season',
        'code' => 'QUIET',
        'adjustment_type' => 'percentage',
        'amount' => -20,
        'priority' => 1,
        'is_active' => true,
    ]);

    RatePlan::factory()->create([
        'room_type_id' => $roomType->getKey(),
        'name' => 'Quiet season, deeper',
        'code' => 'QUIETER',
        'adjustment_type' => 'percentage',
        'amount' => -50,
        'priority' => 5,
        'is_active' => true,
    ]);

    $stay = new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 2);
    $quote = $this->rates->quote($roomType, $stay);

    expect($quote->subtotal)->toBe(number_format((float) $roomType->base_price * 0.5, 2, '.', ''));
});

test('a rate plan that does not reach the night is ignored', function () {
    withoutRatePlans();

    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();

    RatePlan::factory()->create([
        'room_type_id' => $roomType->getKey(),
        'name' => 'Next month',
        'code' => 'LATER',
        'adjustment_type' => 'percentage',
        'amount' => -50,
        'starts_on' => $tuesday->copy()->addMonth(),
        'priority' => 9,
        'is_active' => true,
    ]);

    $stay = new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 2);

    expect($this->rates->quote($roomType, $stay)->subtotal)->toBe($roomType->base_price);
});

test('a room taken out of service is not counted as available', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();
    $stay = new StayRequest($tuesday, $tuesday->copy()->addDays(2), adults: 2);

    $before = $this->availability->availableRooms($roomType, $stay);

    $roomType->rooms()->first()->update(['status' => RoomStatus::Maintenance]);

    expect($this->availability->availableRooms($roomType, $stay))->toBe($before - 1);
});

test('a room blocked for maintenance is not counted as available for the nights it covers', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();
    $stay = new StayRequest($tuesday, $tuesday->copy()->addDays(2), adults: 2);

    $before = $this->availability->availableRooms($roomType, $stay);

    AvailabilityBlock::factory()->create([
        'room_id' => $roomType->rooms()->first()->getKey(),
        'starts_on' => $tuesday,
        'ends_on' => $tuesday->copy()->addDay(),
    ]);

    expect($this->availability->availableRooms($roomType, $stay))->toBe($before - 1);
});

test('a block that starts on the checkout morning does not remove the room', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();
    $stay = new StayRequest($tuesday, $tuesday->copy()->addDays(2), adults: 2);

    $before = $this->availability->availableRooms($roomType, $stay);

    AvailabilityBlock::factory()->create([
        'room_id' => $roomType->rooms()->first()->getKey(),
        'starts_on' => $tuesday->copy()->addDays(2),
        'ends_on' => $tuesday->copy()->addDays(3),
    ]);

    expect($this->availability->availableRooms($roomType, $stay))->toBe($before);
});

test('reserving takes a room off sale and prices the stay with tax', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();
    $stay = new StayRequest($tuesday, $tuesday->copy()->addDays(2), adults: 2);

    $before = $this->availability->availableRooms($roomType, $stay);

    $booking = $this->reservations->reserve($roomType, $stay, [
        'first_name' => 'Tiwonge',
        'last_name' => 'Mbewe',
        'email' => 'tiwonge@example.com',
        'phone' => '+265 999 000 111',
    ]);

    expect($booking->reference)->toMatch('/^LH-\d{4}-\d{4}$/')
        ->and($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->nights)->toBe(2)
        ->and($booking->items)->toHaveCount(1)
        ->and($booking->items->first()->nightly_rates)->toHaveCount(2);

    // Tax is the two statutory charges on the discounted subtotal, 17.5%.
    $subtotal = (float) $booking->subtotal;
    expect($booking->tax_total)->toBe(number_format($subtotal * 0.175, 2, '.', ''))
        ->and($booking->total)->toBe(number_format($subtotal * 1.175, 2, '.', ''))
        ->and($booking->payment_status->value)->toBe('unpaid');

    expect($this->availability->availableRooms($roomType, $stay))->toBe($before - 1);
});

test('a category cannot be oversold', function () {
    // Clear the hotel's own occupancy first, so the count under test is the one
    // this test creates rather than whatever the seeder already holds.
    BookingItem::query()->delete();

    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();

    // Leave exactly one room of this category in service.
    $rooms = $roomType->rooms()->get();
    $rooms->skip(1)->each(fn (Room $room) => $room->update(['status' => RoomStatus::Maintenance]));

    $tuesday = midweek();
    $stay = new StayRequest($tuesday, $tuesday->copy()->addDays(2), adults: 2);

    expect($this->availability->availableRooms($roomType, $stay))->toBe(1);

    $guest = [
        'first_name' => 'First',
        'last_name' => 'Guest',
        'email' => 'first@example.com',
        'phone' => '+265 999 000 222',
    ];

    $this->reservations->reserve($roomType, $stay, $guest);

    expect(fn () => $this->reservations->reserve($roomType, $stay, $guest))
        ->toThrow(StayNotAvailable::class);
});

test('a party larger than the category sleeps is not offered', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();

    expect($this->rates->fits($roomType, new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 2)))->toBeTrue()
        // One extra bed is the limit.
        ->and($this->rates->fits($roomType, new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 3)))->toBeTrue()
        ->and($this->rates->fits($roomType, new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 4)))->toBeFalse()
        // It sleeps no children at all.
        ->and($this->rates->fits($roomType, new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 2, children: 1)))->toBeFalse();
});

test('the booking page answers with priced offers for the dates searched', function () {
    $tuesday = midweek();

    $this->get(route('site.booking.index', [
        'check_in' => $tuesday->toDateString(),
        'check_out' => $tuesday->copy()->addDays(2)->toDateString(),
        'adults' => 2,
    ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/booking/index')
            ->where('searched', true)
            ->has('offers')
            ->where('offers.0.nights', 2)
            ->where('offers.0.available', fn ($available) => $available > 0)
            ->has('booking.cancellation_policy'));
});

test('the booking page asks for dates rather than erroring without them', function () {
    $this->get(route('site.booking.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('searched', false)
            ->has('offers', 0));
});

test('a guest can reserve through the form and lands on their confirmation', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();

    $response = $this->post(route('site.booking.store'), [
        'room_type' => $roomType->slug,
        'check_in' => $tuesday->toDateString(),
        'check_out' => $tuesday->copy()->addDays(2)->toDateString(),
        'adults' => 2,
        'children' => 0,
        'first_name' => 'Chikondi',
        'last_name' => 'Banda',
        'email' => 'chikondi@example.com',
        'phone' => '+265 999 000 333',
        'payment_option' => PaymentOption::PayAtHotel->value,
        'terms' => true,
    ]);

    $booking = Booking::query()
        ->whereHas('guest', fn ($query) => $query->where('email', 'chikondi@example.com'))
        ->sole();

    $response->assertRedirect(route('site.booking.show', $booking->reference));

    expect($booking->guest->email)->toBe('chikondi@example.com')
        ->and($booking->source->value)->toBe('website')
        ->and($booking->payment_method)->toBe(PaymentOption::PayAtHotel);
});

test('an invalid discount code is refused rather than quietly dropped', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();

    $before = Booking::query()->count();

    $this->post(route('site.booking.store'), [
        'room_type' => $roomType->slug,
        'check_in' => $tuesday->toDateString(),
        'check_out' => $tuesday->copy()->addDay()->toDateString(),
        'adults' => 2,
        'children' => 0,
        'first_name' => 'No',
        'last_name' => 'Discount',
        'email' => 'nodiscount@example.com',
        'phone' => '+265 999 000 444',
        'payment_option' => PaymentOption::PayAtHotel->value,
        'coupon_code' => 'NOTAREALCODE',
        'terms' => true,
    ])->assertSessionHasErrors('coupon_code');

    expect(Booking::query()->count())->toBe($before);
});

test('a valid discount code reduces the taxable amount', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();
    $stay = new StayRequest($tuesday, $tuesday->copy()->addDays(2), adults: 2);

    $coupon = Coupon::factory()->create([
        'code' => 'STAY10',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'is_active' => true,
    ]);

    $booking = $this->reservations->reserve($roomType, $stay, [
        'first_name' => 'Coupon',
        'last_name' => 'Guest',
        'email' => 'coupon@example.com',
        'phone' => '+265 999 000 555',
    ], coupon: $coupon);

    expect((float) $booking->discount_total)->toBeGreaterThan(0)
        ->and((float) $booking->total)->toBeLessThan(
            (float) $booking->subtotal * 1.175,
        );
});

test('the guest and the desk are both emailed once when a payment settles', function () {
    Mail::fake();

    Setting::store('hotel.email', 'reservations@lakesidehotelmw.net');

    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();

    $booking = $this->reservations->reserve($roomType, new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 2), [
        'first_name' => 'Paying',
        'last_name' => 'Guest',
        'email' => 'paying@example.com',
        'phone' => '+265 999 000 666',
    ], PaymentOption::PayNow);

    $payment = $this->payments->start($booking);

    $this->payments->settle($payment, [
        'status' => 'success',
        'data' => ['status' => 'success', 'mode' => 'airtel_money'],
    ]);

    $booking = single($booking);

    expect($payment->refresh()->status)->toBe(PaymentRecordStatus::Successful)
        ->and($payment->method)->toBe(PaymentMethod::AirtelMoney)
        ->and($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->payment_status->value)->toBe('paid')
        ->and($booking->confirmed_at)->not->toBeNull();

    Mail::assertSent(BookingConfirmed::class, 1);
    Mail::assertSent(BookingReceived::class, 1);
});

test('settling the same payment twice changes nothing and emails nobody twice', function () {
    Mail::fake();

    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();

    $booking = $this->reservations->reserve($roomType, new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 2), [
        'first_name' => 'Twice',
        'last_name' => 'Guest',
        'email' => 'twice@example.com',
        'phone' => '+265 999 000 777',
    ], PaymentOption::PayNow);

    $payment = $this->payments->start($booking);
    $payload = ['status' => 'success', 'data' => ['status' => 'success', 'mode' => 'card']];

    $this->payments->settle($payment, $payload);
    $this->payments->settle($payment, $payload);

    expect($booking->payments()->count())->toBe(1)
        ->and((float) single($booking)->amount_paid)->toBe((float) $booking->total);

    // PayChangu retries three times, so this is the property that keeps a guest
    // from being emailed about the same payment more than once.
    Mail::assertSent(BookingConfirmed::class, 1);
    Mail::assertSent(BookingReceived::class, 1);
});

test('a declined transaction leaves the booking held and unpaid', function () {
    Mail::fake();

    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();

    $booking = $this->reservations->reserve($roomType, new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 2), [
        'first_name' => 'Declined',
        'last_name' => 'Guest',
        'email' => 'declined@example.com',
        'phone' => '+265 999 000 888',
    ], PaymentOption::PayNow);

    $payment = $this->payments->start($booking);

    $this->payments->reconcile($payment, ['status' => 'failed', 'data' => ['status' => 'failed']]);

    $booking = single($booking);

    expect($payment->refresh()->status)->toBe(PaymentRecordStatus::Failed)
        ->and($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->payment_status->value)->toBe('unpaid');

    Mail::assertNothingSent();
});

test('paying online hands the guest to the gateway checkout', function () {
    Http::fake([
        '*/verify-payment/*' => Http::response(['status' => 'success']),
        '*/payment' => Http::response(['status' => 'success', 'data' => ['checkout_url' => 'https://checkout.paychangu.test/abc']]),
    ]);

    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();

    $booking = $this->reservations->reserve($roomType, new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 2), [
        'first_name' => 'Online',
        'last_name' => 'Guest',
        'email' => 'online@example.com',
        'phone' => '+265 999 000 999',
    ], PaymentOption::PayNow);

    $this->post(route('site.booking.pay', $booking->reference))
        ->assertRedirect('https://checkout.paychangu.test/abc');

    expect($booking->payments()->sole()->provider_reference)->toBe($booking->reference.'-P1');
});

test('online payment is withheld when the hotel has switched it off', function () {
    Setting::store('booking.online_payment_enabled', 'false');

    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();

    $booking = $this->reservations->reserve($roomType, new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 2), [
        'first_name' => 'Offline',
        'last_name' => 'Guest',
        'email' => 'offline@example.com',
        'phone' => '+265 999 001 000',
    ], PaymentOption::PayNow);

    $this->post(route('site.booking.pay', $booking->reference))
        ->assertRedirect(route('site.booking.show', $booking->reference));

    expect($booking->payments()->count())->toBe(0);
});

test('a webhook with a bad signature is refused', function () {
    $this->postJson(route('webhooks.paychangu'), ['tx_ref' => 'anything'], [
        'Signature' => 'not-the-right-signature',
    ])->assertStatus(401);
});

test('a signed webhook settles the booking after checking with the gateway', function () {
    Mail::fake();

    Http::fake([
        '*/verify-payment/*' => Http::response([
            'status' => 'success',
            'data' => ['status' => 'success', 'mode' => 'card'],
        ]),
    ]);

    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $tuesday = midweek();

    $booking = $this->reservations->reserve($roomType, new StayRequest($tuesday, $tuesday->copy()->addDay(), adults: 2), [
        'first_name' => 'Webhook',
        'last_name' => 'Guest',
        'email' => 'webhook@example.com',
        'phone' => '+265 999 001 111',
    ], PaymentOption::PayNow);

    $payment = $this->payments->start($booking);

    $body = json_encode(['tx_ref' => $payment->provider_reference, 'status' => 'success']);

    $this->call(
        'POST',
        route('webhooks.paychangu'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SIGNATURE' => hash_hmac('sha256', $body, 'whsec-test-456'),
        ],
        $body,
    )->assertOk();

    expect(single($booking)->status)->toBe(BookingStatus::Confirmed)
        ->and($payment->refresh()->status)->toBe(PaymentRecordStatus::Successful);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'verify-payment'));
});

test('a signed webhook for a transaction we do not know is acknowledged, not retried', function () {
    $body = json_encode(['tx_ref' => 'LH-2026-9999-P1', 'status' => 'success']);

    $this->call(
        'POST',
        route('webhooks.paychangu'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SIGNATURE' => hash_hmac('sha256', $body, 'whsec-test-456'),
        ],
        $body,
    )->assertOk();
});
