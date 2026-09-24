<?php

use App\Enums\AvailabilityBlockReason;
use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\RoomAllocation;
use App\Services\Booking\StayRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/*
 * Taking a room off sale, and putting it back.
 *
 * A closure only means anything if it changes what the hotel can sell, so most of
 * these check the effect on availability rather than the row itself. The other
 * half is the refusal: a room cannot be closed over nights a guest is already
 * asleep in, and the desk has to be told that while they are still on the phone.
 */

beforeEach(function (): void {
    $this->seed();

    $this->manager = User::factory()->role(Role::HotelManager)->create();
    $this->reception = User::factory()->role(Role::Reception)->create();
    $this->marketing = User::factory()->role(Role::Marketing)->create();

    $this->day = Carbon::parse('2026-11-10');

    /*
     * A category with rooms of its own, so the arithmetic is the test's rather
     * than whatever the seed data happens to hold.
     */
    $this->roomType = RoomType::factory()->create([
        'name' => 'Closure Test Double',
        'slug' => 'closure-test-double',
        'is_active' => true,
    ]);

    $this->rooms = Room::factory()->count(3)->create([
        'room_type_id' => $this->roomType->getKey(),
    ]);

    $this->availability = app(AvailabilityService::class);
    $this->allocation = app(RoomAllocation::class);

    $this->stay = fn (int $startsInDays = 0, int $nights = 2): StayRequest => new StayRequest(
        checkIn: $this->day->copy()->addDays($startsInDays),
        checkOut: $this->day->copy()->addDays($startsInDays + $nights),
        adults: 2,
    );

    $this->booking = function (int $startsInDays = 0, int $nights = 2): Booking {
        $checkIn = $this->day->copy()->addDays($startsInDays);

        $booking = Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
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

    $this->close = fn (int $roomIndex, int $startsInDays, int $nights = 1, array $overrides = []) => $this
        ->actingAs($this->manager)
        ->post(route('admin.availability.store'), array_merge([
            'room_id' => $this->rooms[$roomIndex]->getKey(),
            'starts_on' => $this->day->copy()->addDays($startsInDays)->toDateString(),
            'ends_on' => $this->day->copy()->addDays($startsInDays + $nights - 1)->toDateString(),
            'reason' => AvailabilityBlockReason::Maintenance->value,
            'notes' => null,
        ], $overrides));
});

test('the availability page is readable by the roles that manage it', function () {
    foreach ([$this->manager, $this->reception] as $actor) {
        $this->actingAs($actor)
            ->get(route('admin.availability.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/availability/index')
                ->has('blocks')
                ->has('rooms')
                ->has('reasons')
                ->has('stats.closed_tonight')
                ->has('stats.out_of_service'));
    }
});

test('a role that may not manage availability can neither see nor change it', function () {
    $block = AvailabilityBlock::factory()->create([
        'room_id' => $this->rooms[0]->getKey(),
        'starts_on' => $this->day->toDateString(),
        'ends_on' => $this->day->addDay()->toDateString(),
    ]);

    $this->actingAs($this->marketing)
        ->get(route('admin.availability.index'))
        ->assertForbidden();

    $this->actingAs($this->marketing)
        ->post(route('admin.availability.store'), [
            'room_id' => $this->rooms[1]->getKey(),
            'starts_on' => $this->day->toDateString(),
            'ends_on' => $this->day->toDateString(),
            'reason' => AvailabilityBlockReason::Maintenance->value,
        ])
        ->assertForbidden();

    $this->actingAs($this->marketing)
        ->delete(route('admin.availability.destroy', $block->getKey()))
        ->assertForbidden();

    expect($block->fresh())->not->toBeNull();
});

test('closing a room takes it out of what can be sold', function () {
    $stay = ($this->stay)();

    expect($this->availability->availableRooms($this->roomType, $stay))->toBe(3);

    // A single night, and it is the last night of the stay: the block is
    // inclusive of its end date, so this has to take the room out of the whole
    // range rather than leave it half sellable.
    ($this->close)(0, startsInDays: 1)->assertRedirect();

    expect($this->availability->availableRooms($this->roomType, $stay))->toBe(2);

    // The allocation side sees the same thing, because a room that is closed
    // cannot be given to anybody either.
    expect($this->allocation->isFree(
        $this->rooms[0],
        $this->day,
        $this->day->copy()->addDays(2),
    ))->toBeFalse();
});

test('the nights either side of a closure stay on sale', function () {
    // The other side of the inclusive end date: a one-night closure must not take
    // the room out for the nights around it.
    $stay = ($this->stay)(startsInDays: 3);

    ($this->close)(0, startsInDays: 0, nights: 1)->assertRedirect();

    expect($this->availability->availableRooms($this->roomType, $stay))->toBe(3);
});

test('a closure records why it was made and who made it', function () {
    ($this->close)(1, startsInDays: 4, nights: 3, overrides: [
        'reason' => AvailabilityBlockReason::Renovation->value,
        'notes' => '  Replacing the bathroom window.  ',
    ])->assertRedirect();

    // The seeded closures are in the way, so this is the one just made.
    $block = AvailabilityBlock::query()
        ->where('room_id', $this->rooms[1]->getKey())
        ->sole();

    expect($block->reason)->toBe(AvailabilityBlockReason::Renovation)
        ->and($block->notes)->toBe('Replacing the bathroom window.')
        ->and($block->nights())->toBe(3)
        ->and($block->created_by)->toBe($this->manager->getKey());
});

test('a closure with no note stores nothing rather than an empty string', function () {
    ($this->close)(2, startsInDays: 1, overrides: ['notes' => '   '])->assertRedirect();

    expect(AvailabilityBlock::query()->where('room_id', $this->rooms[2]->getKey())->sole()->notes)
        ->toBeNull();
});

test('the closure is described in words the desk can read back', function () {
    // A single night is not a range, and "from the 10th to the 10th" makes the
    // desk wonder whether they filled the form in wrong.
    ($this->close)(0, startsInDays: 0, nights: 1)
        ->assertSessionHas(
            'inertia.flash_data.toast.message',
            sprintf('%s is off sale on %s.', $this->rooms[0]->name, $this->day->format('j M Y')),
        );

    ($this->close)(1, startsInDays: 5, nights: 3)
        ->assertSessionHas(
            'inertia.flash_data.toast.message',
            sprintf(
                '%s is off sale from %s to %s.',
                $this->rooms[1]->name,
                $this->day->copy()->addDays(5)->format('j M'),
                $this->day->copy()->addDays(7)->format('j M Y'),
            ),
        );
});

test('a closure that ends before it starts is refused', function () {
    $before = AvailabilityBlock::query()->count();

    $this->actingAs($this->manager)
        ->post(route('admin.availability.store'), [
            'room_id' => $this->rooms[0]->getKey(),
            'starts_on' => $this->day->copy()->addDays(5)->toDateString(),
            'ends_on' => $this->day->copy()->addDays(2)->toDateString(),
            'reason' => AvailabilityBlockReason::Maintenance->value,
        ])
        ->assertSessionHasErrors('ends_on');

    expect(AvailabilityBlock::query()->count())->toBe($before);
});

test('a room a guest is already in cannot be closed', function () {
    $booking = ($this->booking)();
    $this->allocation->assign($booking->items->first(), $this->rooms[0]);

    $before = AvailabilityBlock::query()->count();

    // Dates that cover the stay the guest is in.
    ($this->close)(0, startsInDays: 1)->assertSessionHasErrors('room_id');

    expect(AvailabilityBlock::query()->count())->toBe($before);

    // And the room is still theirs.
    expect($booking->items->first()->refresh()->room_id)->toBe($this->rooms[0]->getKey());
});

test('a room can still be closed for nights the guest is not in it', function () {
    $booking = ($this->booking)(startsInDays: 5);
    $this->allocation->assign($booking->items->first(), $this->rooms[0]);

    // Two nights before that guest arrives.
    ($this->close)(0, startsInDays: 2, nights: 2)->assertSessionHasNoErrors();

    expect(AvailabilityBlock::query()->where('room_id', $this->rooms[0]->getKey())->exists())
        ->toBeTrue();
});

test('a room booked but not yet given a door can still be closed', function () {
    // Nobody has been put in a physical room, so no guest is being disturbed -
    // the closure is allowed, and the desk sees the effect on availability.
    $booking = ($this->booking)();

    ($this->close)(0, startsInDays: 0, nights: 2)->assertSessionHasNoErrors();

    expect($this->allocation->candidates($booking->items->first()))->toHaveCount(2);
});

test('reopening a room puts it back on sale', function () {
    $stay = ($this->stay)();

    ($this->close)(0, startsInDays: 0, nights: 2)->assertRedirect();

    $block = AvailabilityBlock::query()->where('room_id', $this->rooms[0]->getKey())->sole();

    expect($this->availability->availableRooms($this->roomType, $stay))->toBe(2);

    $this->actingAs($this->manager)
        ->delete(route('admin.availability.destroy', $block->getKey()))
        ->assertRedirect();

    expect(AvailabilityBlock::query()->whereKey($block->getKey())->exists())->toBeFalse()
        ->and($this->availability->availableRooms($this->roomType, $stay))->toBe(3);
});

test('the page says which closures are in force and which have finished', function () {
    $this->travelTo($this->day->copy()->setTime(9, 0));

    $running = AvailabilityBlock::factory()->create([
        'room_id' => $this->rooms[0]->getKey(),
        'starts_on' => $this->day->copy()->subDay()->toDateString(),
        'ends_on' => $this->day->copy()->addDay()->toDateString(),
    ]);

    $finished = AvailabilityBlock::factory()->create([
        'room_id' => $this->rooms[1]->getKey(),
        'starts_on' => $this->day->copy()->subDays(9)->toDateString(),
        'ends_on' => $this->day->copy()->subDays(7)->toDateString(),
    ]);

    $upcoming = AvailabilityBlock::factory()->create([
        'room_id' => $this->rooms[2]->getKey(),
        'starts_on' => $this->day->copy()->addDays(5)->toDateString(),
        'ends_on' => $this->day->copy()->addDays(6)->toDateString(),
    ]);

    $this->actingAs($this->manager)
        ->get(route('admin.availability.index'))
        ->assertInertia(fn ($page) => $page
            ->where('stats.closed_tonight', 1)
            /*
             * Read by id rather than by position: the seed data has closures of
             * its own, and which side of today they fall on depends on when the
             * suite runs.
             */
            ->where('blocks', function ($blocks) use ($running, $finished, $upcoming): bool {
                $byId = collect($blocks)->keyBy('id');

                return $byId[$running->getKey()]['state'] === 'running'
                    && $byId[$upcoming->getKey()]['state'] === 'upcoming'
                    && $byId[$finished->getKey()]['state'] === 'past'
                    // What is still to come is listed ahead of what is finished.
                    && ($byId->first()['state'] ?? null) !== 'past';
            }));
});
