<?php

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Enums\RoomStatus;
use App\Exceptions\RoomNotAvailable;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Booking\RoomAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/*
 * Handing over a key, and keeping a note about the guest.
 *
 * A reservation sells a category; the desk hands over a door. Most of these pin
 * the refusals rather than the happy path, because the dangerous case is a room
 * being given to two people for the same nights - and because a room that is
 * blocked, retired or already taken must not even be offered as a choice.
 */

beforeEach(function (): void {
    $this->seed();

    $this->manager = User::factory()->role(Role::HotelManager)->create();
    $this->day = Carbon::parse('2026-11-10');

    /*
     * A category with rooms of its own, so what is on offer here is not mixed up
     * with whatever the seed data happens to hold.
     */
    $this->roomType = RoomType::factory()->create([
        'name' => 'Lakeview Chalet',
        'slug' => 'lakeview-chalet',
        'is_active' => true,
    ]);

    $this->rooms = Room::factory()->count(3)->create([
        'room_type_id' => $this->roomType->getKey(),
    ]);

    /*
     * The factory builds room lines of its own, so they are cleared out and one
     * line in the test's own category is put in their place - that is what makes
     * the counts below mean something.
     */
    $this->booking = function (BookingStatus $status = BookingStatus::Confirmed, int $startsInDays = 0, int $nights = 2): Booking {
        $checkIn = $this->day->copy()->addDays($startsInDays);

        $booking = Booking::factory()->create([
            'status' => $status,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkIn->copy()->addDays($nights)->toDateString(),
            'nights' => $nights,
        ]);

        $booking->items()->delete();

        $booking->items()->create([
            'room_type_id' => $this->roomType->getKey(),
            'adults' => 2,
            'children' => 0,
            'price_per_night' => 50_000,
            'subtotal' => 50_000 * $nights,
        ]);

        return $booking->refresh();
    };

    $this->allocation = app(RoomAllocation::class);
});

test('the desk is offered the rooms that could take these nights', function () {
    $booking = ($this->booking)();

    $this->actingAs($this->manager)
        ->get(route('admin.bookings.show', $booking->reference))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/bookings/show')
            ->has('booking.items', 1)
            ->has('booking.items.0.available_rooms', 3)
            ->where('booking.can.assign_rooms', true));
});

test('the desk can put a guest in a free room', function () {
    $booking = ($this->booking)();
    $item = $booking->items->first();
    $room = $this->rooms->first();

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.items.room', [$booking->reference, $item->getKey()]), [
            'room_id' => $room->getKey(),
        ])
        ->assertRedirect();

    expect($item->refresh()->room_id)->toBe($room->getKey());
});

test('a room of another category is refused', function () {
    $booking = ($this->booking)();
    $item = $booking->items->first();

    $wrongType = Room::factory()->create([
        'room_type_id' => RoomType::query()->where('slug', 'standard-double')->sole()->getKey(),
    ]);

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.items.room', [$booking->reference, $item->getKey()]), [
            'room_id' => $wrongType->getKey(),
        ])
        ->assertRedirect();

    expect($item->refresh()->room_id)->toBeNull();
});

test('a room another guest is already in is refused', function () {
    $booking = ($this->booking)();
    $item = $booking->items->first();
    $room = $this->rooms->first();

    // Somebody else is in that room over the same nights.
    $other = ($this->booking)();
    $other->items->first()->update(['room_id' => $room->getKey()]);

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.items.room', [$booking->reference, $item->getKey()]), [
            'room_id' => $room->getKey(),
        ])
        ->assertRedirect();

    expect($item->refresh()->room_id)->toBeNull();

    // And asking the service directly says why rather than quietly allowing it.
    expect(fn () => $this->allocation->assign($item, $room))
        ->toThrow(RoomNotAvailable::class);
});

test('a room is free again for nights that do not overlap the guest in it', function () {
    // The other side of the check above: an overlap query that is too eager would
    // take a room out of service for the rest of the year after one stay.
    $room = $this->rooms->first();

    $other = ($this->booking)(BookingStatus::Confirmed, startsInDays: 5);
    $other->items->first()->update(['room_id' => $room->getKey()]);

    $booking = ($this->booking)();
    $item = $booking->items->first();

    expect($this->allocation->isFree($room, $booking->check_in, $booking->check_out, $item))->toBeTrue();

    $this->allocation->assign($item, $room);

    expect($item->refresh()->room_id)->toBe($room->getKey());
});

test('a room another booking no longer holds is free again', function () {
    // Cancelling releases the room, so the same nights can be sold to somebody
    // else against the same door.
    $room = $this->rooms->first();

    $cancelled = ($this->booking)(BookingStatus::Cancelled);
    $cancelled->items->first()->update(['room_id' => $room->getKey()]);

    $booking = ($this->booking)();
    $item = $booking->items->first();

    $this->allocation->assign($item, $room);

    expect($item->refresh()->room_id)->toBe($room->getKey());
});

test('a room blocked for maintenance is neither offered nor given out', function () {
    $booking = ($this->booking)();
    $room = $this->rooms->first();

    AvailabilityBlock::factory()->create([
        'room_id' => $room->getKey(),
        'starts_on' => $this->day->copy()->addDay()->toDateString(),
        'ends_on' => $this->day->copy()->addDay()->toDateString(),
    ]);

    $this->actingAs($this->manager)
        ->get(route('admin.bookings.show', $booking->reference))
        ->assertInertia(fn ($page) => $page->has('booking.items.0.available_rooms', 2));

    expect(fn () => $this->allocation->assign($booking->items->first(), $room))
        ->toThrow(RoomNotAvailable::class);
});

test('a room that is out of service is neither offered nor given out', function () {
    $booking = ($this->booking)();
    $room = $this->rooms->first();

    $room->update(['status' => RoomStatus::Maintenance]);

    $this->actingAs($this->manager)
        ->get(route('admin.bookings.show', $booking->reference))
        ->assertInertia(fn ($page) => $page->has('booking.items.0.available_rooms', 2));

    expect(fn () => $this->allocation->assign($booking->items->first(), $room))
        ->toThrow(RoomNotAvailable::class);
});

test('a block that clears before the arrival does not take the room out', function () {
    // A block is inclusive of its dates, and the last night of a stay is the night
    // before check-out. A block ending the day before arrival covers none of it.
    $booking = ($this->booking)();
    $room = $this->rooms->first();

    AvailabilityBlock::factory()->create([
        'room_id' => $room->getKey(),
        'starts_on' => $this->day->copy()->subDays(3)->toDateString(),
        'ends_on' => $this->day->copy()->subDay()->toDateString(),
    ]);

    $this->allocation->assign($booking->items->first(), $room);

    expect($booking->items->first()->refresh()->room_id)->toBe($room->getKey());
});

test('the desk can take a room back', function () {
    $booking = ($this->booking)();
    $item = $booking->items->first();

    $this->allocation->assign($item, $this->rooms->first());

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.items.room', [$booking->reference, $item->getKey()]), [
            'room_id' => null,
        ])
        ->assertRedirect();

    expect($item->refresh()->room_id)->toBeNull();
});

test('a booking that no longer holds a room cannot be given one', function () {
    $booking = ($this->booking)(BookingStatus::Cancelled);
    $item = $booking->items->first();

    $this->actingAs($this->manager)
        ->get(route('admin.bookings.show', $booking->reference))
        ->assertInertia(fn ($page) => $page
            ->has('booking.items.0.available_rooms', 0)
            ->where('booking.can.assign_rooms', false));

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.items.room', [$booking->reference, $item->getKey()]), [
            'room_id' => $this->rooms->first()->getKey(),
        ])
        ->assertRedirect();

    expect($item->refresh()->room_id)->toBeNull();
});

test('a line belonging to another reservation cannot be moved through this one', function () {
    $booking = ($this->booking)();
    $other = ($this->booking)();

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.items.room', [
            $booking->reference,
            $other->items->first()->getKey(),
        ]), ['room_id' => $this->rooms->first()->getKey()])
        ->assertNotFound();
});

test('a room that does not exist is refused before anything is written', function () {
    $booking = ($this->booking)();
    $item = $booking->items->first();

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.items.room', [$booking->reference, $item->getKey()]), [
            'room_id' => 999_999,
        ])
        ->assertSessionHasErrors('room_id');

    expect($item->refresh()->room_id)->toBeNull();
});

test('the desk can keep a note about a reservation', function () {
    $booking = ($this->booking)();

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.notes', $booking->reference), [
            'internal_notes' => '  Paying in cash, arriving on the 14:00 ferry.  ',
        ])
        ->assertRedirect();

    expect($booking->refresh()->internal_notes)->toBe('Paying in cash, arriving on the 14:00 ferry.');
});

test('clearing the note leaves nothing rather than an empty string', function () {
    $booking = ($this->booking)();
    $booking->forceFill(['internal_notes' => 'Something'])->save();

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.notes', $booking->reference), ['internal_notes' => '   '])
        ->assertRedirect();

    expect($booking->refresh()->internal_notes)->toBeNull();
});

test('a note longer than the field allows is refused', function () {
    $booking = ($this->booking)();

    $this->actingAs($this->manager)
        ->patch(route('admin.bookings.notes', $booking->reference), [
            'internal_notes' => str_repeat('a', 2001),
        ])
        ->assertSessionHasErrors('internal_notes');

    expect($booking->refresh()->internal_notes)->toBeNull();
});

test('marketing may read a reservation but not hand over a key or write a note', function () {
    $marketing = User::factory()->role(Role::Marketing)->create();
    $booking = ($this->booking)();
    $item = $booking->items->first();

    $this->actingAs($marketing)
        ->patch(route('admin.bookings.items.room', [$booking->reference, $item->getKey()]), [
            'room_id' => $this->rooms->first()->getKey(),
        ])
        ->assertForbidden();

    $this->actingAs($marketing)
        ->patch(route('admin.bookings.notes', $booking->reference), [
            'internal_notes' => 'Not allowed',
        ])
        ->assertForbidden();

    expect($item->refresh()->room_id)->toBeNull()
        ->and($booking->refresh()->internal_notes)->toBeNull();
});
