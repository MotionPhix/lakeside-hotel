<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * Extras sold at the desk.
 *
 * Guests choose extras while they book. The desk sells them too - over the phone,
 * at check-in, at the bar - so the same rules have to hold on this side, and a line
 * the desk adds has to land on the folio exactly as a chosen one does. Which is
 * what most of these are about: the arithmetic, and the two doors agreeing.
 */

beforeEach(function (): void {
    $this->seed();
});

/** The front desk: the people this is for, and the only ones who may do it. */
function desk(): User
{
    return User::factory()->role(Role::Reception)->create();
}

/** Marketing can look at a reservation and may not touch its folio. */
function marketing(): User
{
    return User::factory()->role(Role::Marketing)->create();
}

function cruise(array $attributes = []): Activity
{
    return Activity::factory()->create(array_merge([
        'name' => 'Sunset cruise',
        'price' => 25000,
        'price_basis' => 'per_person',
        'max_participants' => 4,
        'is_active' => true,
    ], $attributes));
}

/** A confirmed stay with a room line and its money already totalled. */
function stay(): Booking
{
    return Booking::factory()->confirmed()->create();
}

test('the desk can sell an extra onto a stay', function () {
    $booking = stay();
    $before = (float) $booking->total;

    $this->actingAs(desk())
        ->post(route('admin.bookings.extras.store', $booking->reference), [
            'activity_id' => cruise()->id,
            'quantity' => 3,
        ])
        ->assertRedirect();

    $booking->refresh()->load('extras');

    $extra = $booking->extras->first();

    expect($booking->extras)->toHaveCount(1)
        ->and($extra->name)->toBe('Sunset cruise')
        ->and($extra->quantity)->toBe(3)
        ->and($extra->unit_price)->toBe('25000.00')
        ->and($extra->subtotal)->toBe('75000.00')
        ->and((float) $booking->total)->toBe(round($before + 75000, 2));
});

test('an extra sold at the desk is priced exactly as the booking form prices it', function () {
    $booking = stay();
    $boat = cruise(['name' => 'Private boat', 'price' => 120000, 'price_basis' => 'per_group']);

    $this->actingAs(desk())
        ->post(route('admin.bookings.extras.store', $booking->reference), [
            'activity_id' => $boat->id,
            'quantity' => 1,
        ])
        ->assertRedirect();

    $extra = $booking->refresh()->extras->first();

    expect($extra->subtotal)->toBe('120000.00')
        /* One group however many come, and the line says so. */
        ->and($extra->quantity)->toBe(1)
        ->and($extra->label())->toBe('Private boat (for the group)');
});

test('selling an extra does not disturb the accommodation tax', function () {
    $booking = stay();
    $tax = (float) $booking->tax_total;

    $this->actingAs(desk())
        ->post(route('admin.bookings.extras.store', $booking->reference), [
            'activity_id' => cruise()->id,
            'quantity' => 2,
        ])
        ->assertRedirect();

    /* The levy is a levy on accommodation, so an extra is added to the stay rather
       than folded into the base it is charged on. */
    expect((float) $booking->refresh()->tax_total)->toBe($tax);
});

test('taking an extra back off leaves the folio as it was', function () {
    $booking = stay();
    $before = (float) $booking->total;

    $this->actingAs(desk())
        ->post(route('admin.bookings.extras.store', $booking->reference), [
            'activity_id' => cruise()->id,
            'quantity' => 3,
        ])
        ->assertRedirect();

    $extra = $booking->refresh()->extras->first();

    $this->actingAs(desk())
        ->delete(route('admin.bookings.extras.destroy', [$booking->reference, $extra->id]))
        ->assertRedirect();

    $booking->refresh()->load('extras');

    expect($booking->extras)->toHaveCount(0)
        ->and((float) $booking->total)->toBe($before);
});

test('an extra sold after the bill was settled leaves a balance owing', function () {
    $booking = stay();

    /* What the guest has paid has to be a real payment: `syncPaymentStatus` works
       the amount out from the payments themselves, so a figure written onto the
       booking would be overwritten the next time anything is recalculated. */
    $this->actingAs(desk())
        ->post(route('admin.bookings.payments.store', $booking->reference), [
            'amount' => $booking->total,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    expect($booking->refresh()->payment_status)->toBe(PaymentStatus::Paid);

    $this->actingAs(desk())
        ->post(route('admin.bookings.extras.store', $booking->reference), [
            'activity_id' => cruise()->id,
            'quantity' => 2,
        ])
        ->assertRedirect();

    $booking->refresh();

    /* The guest has paid for the room and now owes for the cruise. */
    expect((float) $booking->balance())->toBe(50000.0)
        ->and($booking->payment_status)->not->toBe(PaymentStatus::Paid);
});

test('a stay that has ended will not take another extra', function () {
    $booking = Booking::factory()->cancelled()->create();

    $this->actingAs(desk())
        ->post(route('admin.bookings.extras.store', $booking->reference), [
            'activity_id' => cruise()->id,
            'quantity' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    expect($booking->refresh()->extras)->toHaveCount(0);
});

test('a closed folio cannot have an extra taken off it either', function () {
    $booking = Booking::factory()->checkedOut()->create();
    $extra = $booking->extras()->create([
        'name' => 'Sunset cruise',
        'price_basis' => 'per_person',
        'unit_price' => '25000.00',
        'quantity' => 1,
        'subtotal' => '25000.00',
        'sort_order' => 0,
    ]);

    $this->actingAs(desk())
        ->delete(route('admin.bookings.extras.destroy', [$booking->reference, $extra->id]))
        ->assertRedirect()
        ->assertSessionHas('inertia.flash_data.toast.type', 'error');

    expect($booking->refresh()->extras)->toHaveCount(1);
});

test('an extra that is no longer offered cannot be sold', function () {
    $booking = stay();

    $this->actingAs(desk())
        ->post(route('admin.bookings.extras.store', $booking->reference), [
            'activity_id' => cruise(['is_active' => false])->id,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('activity_id');
});

test('a complimentary activity has no price to put on a folio', function () {
    $booking = stay();

    $this->actingAs(desk())
        ->post(route('admin.bookings.extras.store', $booking->reference), [
            'activity_id' => cruise(['price_basis' => 'complimentary'])->id,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('activity_id');
});

test('the desk cannot sell more places than the extra has', function () {
    $booking = stay();

    $this->actingAs(desk())
        ->post(route('admin.bookings.extras.store', $booking->reference), [
            'activity_id' => cruise()->id, // carries at most four
            'quantity' => 5,
        ])
        ->assertSessionHasErrors('quantity');

    expect($booking->refresh()->extras)->toHaveCount(0);
});

test('somebody who may not manage reservations cannot sell an extra', function () {
    $booking = stay();

    $this->actingAs(marketing())
        ->post(route('admin.bookings.extras.store', $booking->reference), [
            'activity_id' => cruise()->id,
            'quantity' => 1,
        ])
        ->assertForbidden();

    expect($booking->refresh()->extras)->toHaveCount(0);
});

test('the catalogue of what may be sold goes only to somebody who can sell it', function () {
    $booking = stay();

    $offered = fn (User $user) => $this->actingAs($user)
        ->get(route('admin.bookings.show', $booking->reference))
        ->viewData('page')['props']['extras_available'];

    expect($offered(desk()))->not->toBeEmpty()
        /* Marketing can read the reservation and has no use for the price list. */
        ->and($offered(marketing()))->toBe([]);
});

test('an extra belonging to another booking cannot be removed through this one', function () {
    $mine = Booking::factory()->confirmed()->create();
    $theirs = Booking::factory()->confirmed()->create();

    $extra = $theirs->extras()->create([
        'name' => 'Sunset cruise',
        'price_basis' => 'per_person',
        'unit_price' => '25000.00',
        'quantity' => 1,
        'subtotal' => '25000.00',
        'sort_order' => 0,
    ]);

    $this->actingAs(desk())
        ->delete(route('admin.bookings.extras.destroy', [$mine->reference, $extra->id]))
        ->assertNotFound();

    expect($theirs->refresh()->extras)->toHaveCount(1);
});
