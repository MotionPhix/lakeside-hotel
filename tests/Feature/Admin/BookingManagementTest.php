<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Exceptions\BookingNotActionable;
use App\Models\Booking;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingLifecycle;
use App\Services\Booking\StayRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/*
 * The desk's side of a reservation: moving it along, and taking the money. The
 * point of most of these is that an illegal move is refused rather than written,
 * because a booking that quietly jumps from pending to checked out loses the
 * record of what actually happened.
 */

beforeEach(function (): void {
    $this->seed();

    $this->manager = User::factory()->role(Role::HotelManager)->create();
    $this->day = Carbon::parse('2026-11-10');

    /*
     * The factory builds its own room lines and totals them, so a total passed in
     * would be overwritten. The folio is pinned afterwards instead, which is what
     * makes the money assertions below mean anything.
     */
    $this->booking = function (BookingStatus $status, array $attributes = []): Booking {
        $booking = Booking::factory()->create([
            'status' => $status,
            'check_in' => $this->day->toDateString(),
            'check_out' => $this->day->copy()->addDays(2)->toDateString(),
            'nights' => 2,
        ]);

        $booking->forceFill(array_merge([
            'total' => 200_000,
            'amount_paid' => 0,
            'payment_status' => 'unpaid',
        ], $attributes))->save();

        return $booking->refresh();
    };

    $this->availability = app(AvailabilityService::class);
});

test('the reservations list is readable by a role that holds the permission', function () {
    $this->actingAs($this->manager)
        ->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/bookings/index')
            ->has('bookings.data')
            ->has('statuses')
            ->has('totals.outstanding'));
});

test('an account with no staff role cannot reach the reservations list', function () {
    $this->actingAs(User::factory()->guest()->create())
        ->get(route('admin.bookings.index'))
        ->assertForbidden();
});

test('reading reservations does not grant the right to move them', function () {
    // Marketing may look at bookings but has no business confirming them.
    $marketing = User::factory()->role(Role::Marketing)->create();
    $booking = ($this->booking)(BookingStatus::Pending);

    $this->actingAs($marketing)
        ->patch(route('admin.bookings.confirm', $booking->reference))
        ->assertForbidden();

    expect($booking->refresh()->status)->toBe(BookingStatus::Pending);
});

test('confirming a pending booking records when it was confirmed', function () {
    $booking = ($this->booking)(BookingStatus::Pending);

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.confirm', $booking->reference))
        ->assertRedirect();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->confirmed_at)->not->toBeNull();
});

test('a booking that has already moved on cannot be confirmed again', function () {
    $booking = ($this->booking)(BookingStatus::Confirmed);

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.confirm', $booking->reference))
        ->assertRedirect();

    // Refused rather than written: the booking is left exactly where it was.
    expect($booking->refresh()->status)->toBe(BookingStatus::Confirmed);
});

test('the lifecycle refuses every move that does not apply, and offers none', function () {
    $lifecycle = app(BookingLifecycle::class);
    $booking = ($this->booking)(BookingStatus::CheckedOut);

    expect($lifecycle->availableActions($booking))
        ->toBe([
            'confirm' => false,
            'cancel' => false,
            'check_in' => false,
            'check_out' => false,
            'no_show' => false,
        ]);

    // The same map decides what is offered and what is permitted, so a button can
    // never appear for something the service would refuse.
    foreach (['confirm', 'cancel', 'checkIn', 'checkOut', 'markNoShow'] as $method) {
        expect(fn () => $lifecycle->{$method}($booking))
            ->toThrow(BookingNotActionable::class);
    }
});

test('cancelling a booking gives its room back', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $stay = new StayRequest($this->day, $this->day->copy()->addDays(2), adults: 2);

    $booked = $this->availability->availableRooms($roomType, $stay);

    $booking = ($this->booking)(BookingStatus::Confirmed);
    $booking->items()->create([
        'room_type_id' => $roomType->getKey(),
        'adults' => 2,
        'children' => 0,
        'price_per_night' => 95_000,
        'subtotal' => 190_000,
    ]);

    expect($this->availability->availableRooms($roomType, $stay))->toBe($booked - 1);

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.cancel', $booking->reference), [
            'reason' => 'Guest changed plans',
        ])
        ->assertRedirect();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->cancellation_reason)->toBe('Guest changed plans')
        ->and($this->availability->availableRooms($roomType, $stay))->toBe($booked);
});

test('a guest cannot be checked in from pending', function () {
    $booking = ($this->booking)(BookingStatus::Pending);

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.check-in', $booking->reference))
        ->assertRedirect();

    expect($booking->refresh()->status)->toBe(BookingStatus::Pending);
});

test('checking in and out records both moments', function () {
    $booking = ($this->booking)(BookingStatus::Confirmed);

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.check-in', $booking->reference));

    expect($booking->refresh()->status)->toBe(BookingStatus::CheckedIn)
        ->and($booking->checked_in_at)->not->toBeNull();

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.check-out', $booking->reference));

    expect($booking->refresh()->status)->toBe(BookingStatus::CheckedOut)
        ->and($booking->checked_out_at)->not->toBeNull();
});

test('a booking cannot be marked as a no-show before the arrival date', function () {
    $future = ($this->booking)(BookingStatus::Confirmed, [
        'check_in' => $this->day->copy()->addDays(10)->toDateString(),
        'check_out' => $this->day->copy()->addDays(12)->toDateString(),
    ]);

    $this->travelTo($this->day->copy()->setTime(9, 0));

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.no-show', $future->reference))
        ->assertRedirect();

    // The room is still theirs: they may simply be late.
    expect($future->refresh()->status)->toBe(BookingStatus::Confirmed);
});

test('a no-show can be marked once the arrival date has come', function () {
    $booking = ($this->booking)(BookingStatus::Confirmed);

    $this->travelTo($this->day->copy()->setTime(23, 0));

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.no-show', $booking->reference))
        ->assertRedirect();

    expect($booking->refresh()->status)->toBe(BookingStatus::NoShow);
});

test('recording a desk payment settles the folio and confirms the booking', function () {
    $booking = ($this->booking)(BookingStatus::Pending);

    $this->actingAs($this->manager)
        ->post(route('admin.bookings.payments.store', $booking->reference), [
            'amount' => '200000',
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertRedirect();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and((float) $booking->amount_paid)->toBe(200_000.0)
        ->and($booking->payment_status->value)->toBe('paid')
        ->and((float) $booking->balance())->toBe(0.0);

    $payment = $booking->payments()->sole();

    expect($payment->method)->toBe(PaymentMethod::Cash)
        ->and($payment->recorded_by)->toBe($this->manager->getKey());
});

test('a deposit leaves the balance owing and the booking partly paid', function () {
    $booking = ($this->booking)(BookingStatus::Confirmed);

    $this->actingAs($this->manager)
        ->post(route('admin.bookings.payments.store', $booking->reference), [
            'amount' => '50000',
            'method' => PaymentMethod::BankTransfer->value,
        ]);

    $booking->refresh();

    expect((float) $booking->balance())->toBe(150_000.0)
        ->and($booking->payment_status->value)->toBe('deposit_paid');
});

test('a desk payment larger than the balance is refused', function () {
    $booking = ($this->booking)(BookingStatus::Confirmed);

    $this->actingAs($this->manager)
        ->post(route('admin.bookings.payments.store', $booking->reference), [
            'amount' => '250000',
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertSessionHasErrors('amount');

    expect($booking->refresh()->payments()->count())->toBe(0);
});

test('a booking that has settled cannot be paid again', function () {
    $booking = ($this->booking)(BookingStatus::Confirmed, [
        'amount_paid' => 200_000,
        'payment_status' => 'paid',
    ]);

    $this->actingAs($this->manager)
        ->post(route('admin.bookings.payments.store', $booking->reference), [
            'amount' => '1000',
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertSessionHasErrors('amount');
});
