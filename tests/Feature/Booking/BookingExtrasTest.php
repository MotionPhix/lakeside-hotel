<?php

use App\Enums\PaymentOption;
use App\Enums\Role;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/*
 * The priced extras a guest adds to a stay.
 *
 * Two things are being protected here. One is the arithmetic: that what the folio
 * says is what the catalogue said, and that adding an extra does not quietly change
 * the accommodation's own tax. The other is the record: that a line written to a
 * folio keeps its price through a later re-pricing, because a guest who agreed to
 * pay one figure must not be shown another.
 */

beforeEach(function (): void {
    $this->seed();
});

/** The payload the booking form posts, with whatever is added to it. */
function stayPayload(array $extra = []): array
{
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $night = Carbon::parse('2027-03-09');

    return array_merge([
        'room_type' => $roomType->slug,
        'check_in' => $night->toDateString(),
        'check_out' => $night->copy()->addDays(2)->toDateString(),
        'adults' => 2,
        'children' => 0,
        'first_name' => 'Ada',
        'last_name' => 'Banda',
        'email' => 'ada@example.com',
        'phone' => '+265999654321',
        'payment_option' => PaymentOption::PayAtHotel->value,
        'terms' => true,
    ], $extra);
}

/**
 * The booking the last request created.
 *
 * The seeder fills the hotel with stays of its own, so this reaches for the newest
 * rather than assuming it is the only one.
 */
function lastBooking(): Booking
{
    return Booking::query()->latest('id')->firstOrFail();
}

/** An activity that can be added to a stay. */
function sellableExtra(array $attributes = []): Activity
{
    return Activity::factory()->create(array_merge([
        'name' => 'Sunset cruise',
        'price' => 25000,
        'price_basis' => 'per_person',
        'max_participants' => 4,
        'is_active' => true,
    ], $attributes));
}

test('an extra is written to the stay with the price it was sold at', function () {
    $cruise = sellableExtra();

    $this->post(route('site.booking.store'), stayPayload([
        'extras' => [['id' => $cruise->id, 'quantity' => 3]],
    ]))->assertRedirect();

    $booking = lastBooking();

    expect($booking->extras)->toHaveCount(1);

    $extra = $booking->extras->first();

    expect($extra->name)->toBe('Sunset cruise')
        ->and($extra->price_basis)->toBe('per_person')
        ->and($extra->unit_price)->toBe('25000.00')
        ->and($extra->quantity)->toBe(3)
        ->and($extra->subtotal)->toBe('75000.00')
        ->and($extra->activity_id)->toBe($cruise->id);
});

test('the total carries the extras while the accommodation tax stays put', function () {
    $cruise = sellableExtra();

    $this->post(route('site.booking.store'), stayPayload([
        'extras' => [['id' => $cruise->id, 'quantity' => 3]],
    ]))->assertRedirect();

    $booking = lastBooking();

    $rooms = (float) $booking->subtotal;
    $tax = (float) $booking->tax_total;

    /*
     * The tourism levy is a levy on accommodation, so extras are added to the stay
     * rather than folded into the base it is charged on. The tax is exactly what it
     * would have been for the room on its own.
     */
    expect($tax)->toBe((float) Booking::taxFor($booking->subtotal))
        ->and((float) $booking->total)->toBe(round($rooms + $tax + 75000, 2));
});

test('a group price is charged once however many come', function () {
    $boat = sellableExtra([
        'name' => 'Private boat',
        'price' => 120000,
        'price_basis' => 'per_group',
    ]);

    $this->post(route('site.booking.store'), stayPayload([
        'extras' => [['id' => $boat->id, 'quantity' => 1]],
    ]))->assertRedirect();

    $extra = lastBooking()->extras->first();

    expect($extra->subtotal)->toBe('120000.00')
        ->and($extra->quantity)->toBe(1)
        ->and($extra->label())->toBe('Private boat (for the group)');
});

test('per hour is multiplied by the hours asked for', function () {
    $kayak = sellableExtra([
        'name' => 'Kayak hire',
        'price' => 8000,
        'price_basis' => 'per_hour',
    ]);

    $this->post(route('site.booking.store'), stayPayload([
        'extras' => [['id' => $kayak->id, 'quantity' => 4]],
    ]))->assertRedirect();

    $extra = lastBooking()->extras->first();

    expect($extra->subtotal)->toBe('32000.00')
        ->and($extra->label())->toBe('Kayak hire × 4');
});

test('a quantity beyond what the extra allows is refused', function () {
    $cruise = sellableExtra(); // carries at most four
    $before = Booking::query()->count();

    $this->post(route('site.booking.store'), stayPayload([
        'extras' => [['id' => $cruise->id, 'quantity' => 5]],
    ]))->assertSessionHasErrors('extras.0.quantity');

    expect(Booking::query()->count())->toBe($before);
});

test('an extra that is no longer offered is refused', function () {
    $retired = sellableExtra(['is_active' => false]);
    $before = Booking::query()->count();

    $this->post(route('site.booking.store'), stayPayload([
        'extras' => [['id' => $retired->id, 'quantity' => 1]],
    ]))->assertSessionHasErrors('extras.0.id');

    expect(Booking::query()->count())->toBe($before);
});

test('a complimentary activity is not something to add as a priced extra', function () {
    $free = sellableExtra(['name' => 'Free welcome drink', 'price_basis' => 'complimentary']);
    $before = Booking::query()->count();

    $this->post(route('site.booking.store'), stayPayload([
        'extras' => [['id' => $free->id, 'quantity' => 1]],
    ]))->assertSessionHasErrors('extras.0.id');

    expect(Booking::query()->count())->toBe($before);
});

test('the same extra cannot be added twice', function () {
    $cruise = sellableExtra();
    $before = Booking::query()->count();

    $this->post(route('site.booking.store'), stayPayload([
        'extras' => [
            ['id' => $cruise->id, 'quantity' => 1],
            ['id' => $cruise->id, 'quantity' => 2],
        ],
    ]))->assertSessionHasErrors('extras.1.id');

    expect(Booking::query()->count())->toBe($before);
});

test('an extra that does not exist is refused', function () {
    $this->post(route('site.booking.store'), stayPayload([
        'extras' => [['id' => 999999, 'quantity' => 1]],
    ]))->assertSessionHasErrors('extras.0.id');
});

test('re-pricing an activity does not rewrite a folio that has already been taken', function () {
    $cruise = sellableExtra();

    $this->post(route('site.booking.store'), stayPayload([
        'extras' => [['id' => $cruise->id, 'quantity' => 2]],
    ]))->assertRedirect();

    $booking = lastBooking();
    $before = (float) $booking->total;

    // A season later the cruise costs twice as much.
    $cruise->update(['price' => 50000]);

    $booking->refresh()->load('extras');

    expect($booking->extras->first()->unit_price)->toBe('25000.00')
        ->and($booking->extras->first()->subtotal)->toBe('50000.00')
        ->and((float) $booking->total)->toBe($before);
});

test('the guest is shown the extras on their own confirmation', function () {
    $cruise = sellableExtra();

    $this->post(route('site.booking.store'), stayPayload([
        'extras' => [['id' => $cruise->id, 'quantity' => 2]],
    ]))->assertRedirect();

    $booking = lastBooking();

    $reservation = $this->get(route('site.booking.show', $booking->reference))
        ->viewData('page')['props']['reservation'];

    expect($reservation['extras'])->toHaveCount(1)
        ->and($reservation['extras'][0]['label'])->toBe('Sunset cruise × 2')
        ->and($reservation['extras'][0]['subtotal'])->toBe('50000.00')
        ->and($reservation['extras_total'])->toBe('50000.00')
        /*
         * The accommodation keeps its own figures, so the summary can show the room
         * and its tax, then the extras, and add them up.
         */
        ->and($reservation['pricing']['amount'])->toBe(number_format(
            (float) $booking->subtotal - (float) $booking->discount_total + (float) $booking->tax_total,
            2,
            '.',
            '',
        ))
        ->and((float) $reservation['total'])->toBe((float) $reservation['pricing']['amount'] + 50000);
});

test('the desk sees the extras when it opens the reservation', function () {
    $cruise = sellableExtra();

    $this->post(route('site.booking.store'), stayPayload([
        'extras' => [['id' => $cruise->id, 'quantity' => 2]],
    ]))->assertRedirect();

    $booking = lastBooking();

    $detail = $this->actingAs(User::factory()->role(Role::SystemAdmin)->create())
        ->get(route('admin.bookings.show', $booking->reference))
        ->viewData('page')['props']['booking'];

    expect($detail['extras'])->toHaveCount(1)
        ->and($detail['extras'][0]['label'])->toBe('Sunset cruise × 2')
        ->and($detail['extras'][0]['subtotal'])->toBe('50000.00')
        ->and($detail['extras_total'])->toBe('50000.00');
});

test('the reserve page hands the form a total it can add the extras up with', function () {
    $props = $this->get(route('site.booking.create', [
        'check_in' => '2027-03-09',
        'check_out' => '2027-03-11',
        'adults' => 2,
        'children' => 0,
        'room_type' => 'standard-double',
    ]))->viewData('page')['props'];

    /*
     * The form adds the extras to this figure as the guest chooses, so it has to be
     * a number. `formatMoney` renders an empty one as "On request", which is right
     * for a rate nobody has set and wrong for a room that has been priced - a total
     * the guest cannot read is the one number on the page they came for.
     */
    expect($props['offer']['pricing']['amount'])->toBeString()
        ->and($props['offer']['pricing']['amount'])->not->toBeEmpty()
        ->and((float) $props['offer']['pricing']['amount'])->toBeGreaterThan(0);
});

test('a stay with no extras is left exactly as it was', function () {
    $this->post(route('site.booking.store'), stayPayload())->assertRedirect();

    $booking = lastBooking();

    expect($booking->extras)->toHaveCount(0)
        ->and((float) $booking->extrasTotal())->toBe(0.0)
        ->and((float) $booking->total)->toBe(
            round((float) $booking->subtotal - (float) $booking->discount_total + (float) $booking->tax_total, 2),
        );
});
