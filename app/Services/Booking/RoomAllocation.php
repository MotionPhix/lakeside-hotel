<?php

namespace App\Services\Booking;

use App\Exceptions\RoomNotAvailable;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Which door a guest actually gets.
 *
 * A reservation sells a category; the desk hands over a key. This is the step in
 * between, and it is the only place that decides whether a particular room can be
 * given to a particular line - the same way {@see AvailabilityService} is the only
 * place that decides whether a category can be sold at all.
 *
 * The two agree by construction: a room is offered here only if it is in service,
 * nothing is blocking it for those nights, and no other live booking is already
 * sleeping in it. Availability deliberately counts unallocated lines as taken, so
 * a room that is free here is one the hotel has not already promised away.
 */
final class RoomAllocation
{
    /**
     * The rooms of the line's own category that could take this stay.
     *
     * The line's current room is not folded in: the caller has it already, and
     * keeping the two apart is what stops a blocked or retired room from looking
     * like a free choice.
     *
     * @return Collection<int, Room>
     */
    public function candidates(BookingItem $item): Collection
    {
        $booking = $item->booking;

        if (! $booking instanceof Booking || ! $booking->status->holdsInventory()) {
            return new Collection;
        }

        $rooms = Room::query()
            ->where('room_type_id', $item->room_type_id)
            ->bookable()
            ->get();

        $taken = $this->unavailableIds($rooms, $booking, $item);

        return $rooms
            ->reject(fn (Room $room): bool => $taken->contains($room->getKey()))
            ->sortBy('name')
            ->values();
    }

    /**
     * Whether a room can take these nights, ignoring the line being moved.
     */
    public function isFree(
        Room $room,
        CarbonInterface $checkIn,
        CarbonInterface $checkOut,
        ?BookingItem $ignore = null,
    ): bool {
        return $room->isBookable()
            && ! $this->isBlocked($room, $checkIn, $checkOut)
            && ! $this->isTaken($room, $checkIn, $checkOut, $ignore);
    }

    /**
     * Whether a guest has already been given this room for these nights.
     *
     * Asked before a room is taken off sale. Closing a room for maintenance over
     * nights somebody is asleep in helps nobody, and the desk needs to hear that
     * while they are still on the phone rather than on the morning of the arrival.
     *
     * Read in booking terms, so `$checkOut` is the morning after the last night in
     * question rather than that night itself.
     */
    public function isGivenToAGuest(Room $room, CarbonInterface $checkIn, CarbonInterface $checkOut): bool
    {
        return $this->isTaken($room, $checkIn, $checkOut);
    }

    /**
     * Put a line in a room, or take it out of one when given nothing.
     *
     * Refusals are checked here rather than left to the caller, because a room is
     * a physical thing: two guests cannot be sent to the same one, and a room
     * closed for maintenance cannot be handed over no matter what the folio says.
     */
    public function assign(BookingItem $item, ?Room $room): BookingItem
    {
        $booking = $item->booking;

        if (! $booking instanceof Booking) {
            throw RoomNotAvailable::released($item->booking()->firstOrFail());
        }

        if (! $booking->status->holdsInventory()) {
            throw RoomNotAvailable::released($booking);
        }

        if ($room === null) {
            $item->room_id = null;
            $item->save();

            return $item;
        }

        if ((int) $room->room_type_id !== (int) $item->room_type_id) {
            throw RoomNotAvailable::wrongType($room, $item);
        }

        if (! $room->isBookable()) {
            throw RoomNotAvailable::notSellable($room);
        }

        if ($this->isBlocked($room, $booking->check_in, $booking->check_out)) {
            throw RoomNotAvailable::blocked($room, $booking->check_in, $booking->check_out);
        }

        if ($this->isTaken($room, $booking->check_in, $booking->check_out, $item)) {
            throw RoomNotAvailable::taken($room, $booking->check_in, $booking->check_out);
        }

        $item->room_id = $room->getKey();
        $item->save();

        return $item;
    }

    /**
     * Whether an administrative block covers any night of the stay.
     *
     * A block is inclusive of its dates and the last night of a stay is the night
     * before check-out, which is how {@see AvailabilityService} reads it too.
     */
    private function isBlocked(Room $room, CarbonInterface $checkIn, CarbonInterface $checkOut): bool
    {
        return $room->isBlockedBetween($checkIn, $checkOut->copy()->subDay()->startOfDay());
    }

    /**
     * Whether another live booking is already in this room over those nights.
     */
    private function isTaken(
        Room $room,
        CarbonInterface $checkIn,
        CarbonInterface $checkOut,
        ?BookingItem $ignore = null,
    ): bool {
        return BookingItem::query()
            ->where('room_id', $room->getKey())
            ->when($ignore?->getKey(), fn ($query, $id) => $query->whereKeyNot($id))
            ->whereHas('booking', fn ($query) => $query
                ->holdingInventory()
                ->where('check_in', '<', $checkOut)
                ->where('check_out', '>', $checkIn))
            ->exists();
    }

    /**
     * The rooms in the given set that this stay cannot have: they are blocked, or
     * another live booking has them. Asked as two queries rather than one per
     * room, because the dashboard asks this for every line on the page.
     *
     * @param  Collection<int, Room>  $rooms
     * @return Collection<int, int>
     */
    private function unavailableIds(Collection $rooms, Booking $booking, BookingItem $item): Collection
    {
        $ids = $rooms->map(fn (Room $room): int => $room->getKey());

        if ($ids->isEmpty()) {
            return new Collection;
        }

        $blocked = AvailabilityBlock::query()
            ->whereIn('room_id', $ids)
            ->overlapping($booking->check_in, $booking->check_out->copy()->subDay()->startOfDay())
            ->pluck('room_id');

        $taken = BookingItem::query()
            ->whereIn('room_id', $ids)
            ->whereKeyNot($item->getKey())
            ->whereHas('booking', fn ($query) => $query
                ->holdingInventory()
                ->where('check_in', '<', $booking->check_out)
                ->where('check_out', '>', $booking->check_in))
            ->pluck('room_id');

        return $blocked->merge($taken)->map(fn ($id): int => (int) $id)->unique()->values();
    }
}
