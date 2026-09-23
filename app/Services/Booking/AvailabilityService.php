<?php

namespace App\Services\Booking;

use App\Models\AvailabilityBlock;
use App\Models\BookingItem;
use App\Models\RoomType;
use Illuminate\Support\Collection;

/**
 * Answers the only question the booking engine really has to get right: for these
 * nights, how many rooms of each category can the hotel still sell?
 *
 * A room is counted out if it is not in service, if an administrative block
 * covers one of the nights, or if a live booking already holds it. Bookings are
 * counted whether or not reception has allocated a physical room yet, because a
 * pending reservation that has not been assigned a door is still a room the
 * hotel can no longer sell.
 */
final class AvailabilityService
{
    public function __construct(private readonly RateCalculator $rates) {}

    /**
     * Every category that can take this party, priced and counted.
     *
     * @return Collection<int, AvailabilityOffer>
     */
    public function search(StayRequest $stay): Collection
    {
        return RoomType::query()
            ->active()
            ->when(
                $stay->roomTypeSlug !== null,
                fn ($query) => $query->where('slug', $stay->roomTypeSlug),
            )
            ->get()
            ->filter(fn (RoomType $roomType): bool => $this->rates->fits($roomType, $stay))
            ->map(fn (RoomType $roomType): AvailabilityOffer => new AvailabilityOffer(
                quote: $this->rates->quote($roomType, $stay),
                available: $this->availableRooms($roomType, $stay),
            ))
            ->values();
    }

    /**
     * Whether the category can take the party for the stay.
     */
    public function isAvailable(RoomType $roomType, StayRequest $stay): bool
    {
        return $this->rates->fits($roomType, $stay)
            && $this->availableRooms($roomType, $stay) > 0;
    }

    /**
     * How many rooms of this category are still free across the whole stay. The
     * count is over the full range rather than per night, so one busy night takes
     * the category out of the results rather than half selling it.
     */
    public function availableRooms(RoomType $roomType, StayRequest $stay): int
    {
        $total = $roomType->rooms()->bookable()->count();

        if ($total === 0) {
            return 0;
        }

        // A block is inclusive of both its dates, and the last night of the stay
        // is the night before check-out.
        $lastNight = $stay->checkOut->copy()->subDay()->startOfDay();

        $blocked = AvailabilityBlock::query()
            ->whereIn('room_id', $roomType->rooms()->select('id'))
            ->overlapping($stay->checkIn, $lastNight)
            ->distinct()
            ->count('room_id');

        $lines = BookingItem::query()
            ->where('room_type_id', $roomType->getKey())
            ->whereHas('booking', fn ($query) => $query
                ->holdingInventory()
                ->where('check_in', '<', $stay->checkOut)
                ->where('check_out', '>', $stay->checkIn))
            ->get(['id', 'room_id']);

        $allocated = $lines->whereNotNull('room_id')->unique('room_id')->count();
        $unallocated = $lines->whereNull('room_id')->count();

        return max($total - $blocked - $allocated - $unallocated, 0);
    }
}
