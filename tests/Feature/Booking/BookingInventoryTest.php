<?php

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Exceptions\BookingNotActionable;
use App\Exceptions\RoomNotAvailable;
use App\Exceptions\StayNotAvailable;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\Booking\BookingLifecycle;
use App\Services\Booking\ReservationService;
use App\Services\Booking\RoomAllocation;
use App\Services\Booking\StayRequest;

test('allocating references reserves different numbers before either booking is inserted', function () {
    $this->travelTo(now()->setDate(2026, 11, 3));

    expect(Booking::generateReference())->toBe('LH-2026-0001');
    expect(Booking::generateReference())->toBe('LH-2026-0002');
});

test('a new sequence continues from the highest numeric existing reference', function () {
    $this->travelTo(now()->setDate(2026, 11, 3));
    Booking::factory()->pending()->create(['reference' => 'LH-2026-9999']);
    Booking::factory()->pending()->create(['reference' => 'LH-2026-10000']);

    expect(Booking::generateReference())->toBe('LH-2026-10001');
});

test('a sequence skips a reference supplied by an import after allocation has started', function () {
    $this->travelTo(now()->setDate(2026, 11, 3));
    Booking::generateReference();
    Booking::factory()->pending()->create(['reference' => 'LH-2026-0002']);

    expect(Booking::generateReference())->toBe('LH-2026-0003');
});

test('a stale staff action cannot restore a cancelled booking inventory hold', function () {
    $booking = Booking::factory()->pending()->create();
    Booking::query()->whereKey($booking->getKey())->update(['status' => BookingStatus::Cancelled]);

    expect(fn () => app(BookingLifecycle::class)->confirm($booking))->toThrow(BookingNotActionable::class);

    $this->assertDatabaseHas('bookings', ['id' => $booking->getKey(), 'status' => 'cancelled']);
});

test('allocation refuses a room that went out of service after the screen loaded', function () {
    $booking = Booking::factory()->confirmed()->create();
    $item = $booking->items()->sole();
    $room = Room::factory()->create(['room_type_id' => $item->room_type_id]);
    Room::query()->whereKey($room->getKey())->update(['status' => RoomStatus::Maintenance]);

    expect(fn () => app(RoomAllocation::class)->assign($item, $room))->toThrow(RoomNotAvailable::class);

    $this->assertDatabaseHas('booking_items', ['id' => $item->getKey(), 'room_id' => null]);
});

test('allocation refuses a booking cancelled after its item was loaded', function () {
    $booking = Booking::factory()->confirmed()->create();
    $item = $booking->items()->with('booking')->sole();
    $room = Room::factory()->create(['room_type_id' => $item->room_type_id]);
    Booking::query()->whereKey($booking->getKey())->update(['status' => BookingStatus::Cancelled]);

    expect(fn () => app(RoomAllocation::class)->assign($item, $room))->toThrow(RoomNotAvailable::class);

    $this->assertDatabaseHas('booking_items', ['id' => $item->getKey(), 'room_id' => null]);
});

test('a stale category cannot be reserved after it has been withdrawn from sale', function () {
    $type = RoomType::factory()->create();
    Room::factory()->create(['room_type_id' => $type->getKey()]);
    RoomType::query()->whereKey($type->getKey())->update(['is_active' => false]);
    $stay = new StayRequest(now()->addDays(30)->startOfDay(), now()->addDays(31)->startOfDay(), adults: 2);

    expect(fn () => app(ReservationService::class)->reserve($type, $stay, [
        'first_name' => 'Inventory', 'last_name' => 'Test', 'email' => 'inventory@example.test',
    ]))->toThrow(StayNotAvailable::class);

    $this->assertDatabaseCount('bookings', 0);
    $this->assertDatabaseCount('guests', 0);
});
