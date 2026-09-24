<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AvailabilityBlockReason;
use App\Exceptions\RoomNotAvailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAvailabilityBlockRequest;
use App\Models\AvailabilityBlock;
use App\Models\Room;
use App\Services\Booking\RoomAllocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Taking a room off sale: servicing, house use, a refurbishment, a broken shower.
 *
 * A block is what stops a room being sold, counted and offered for a range of
 * nights, so this is the screen a manager reaches for when the plumber is coming.
 * It will not close a room a guest is already in - that has to be sorted out
 * first, and the desk is told so while they are still on the phone.
 */
class AvailabilityController extends Controller
{
    public function __construct(private readonly RoomAllocation $allocation) {}

    /**
     * Every closure, what is in force now, and what is coming up.
     */
    public function index(): Response
    {
        $today = Carbon::now()->startOfDay();

        $blocks = AvailabilityBlock::query()
            ->with('room.roomType', 'creator')
            ->orderBy('starts_on')
            ->get();

        // Closure in force and still to come first, in the order they arrive;
        // finished ones drop to the bottom, newest of those first, as a record of
        // what was done rather than something to act on.
        $current = $blocks->filter(fn (AvailabilityBlock $block): bool => $block->ends_on->gte($today));
        $past = $blocks->reject(fn (AvailabilityBlock $block): bool => $block->ends_on->gte($today))->reverse();

        return Inertia::render('admin/availability/index', [
            'blocks' => $current
                ->merge($past)
                ->values()
                ->map(fn (AvailabilityBlock $block): array => $this->row($block, $today))
                ->all(),
            'rooms' => Room::query()
                ->with('roomType')
                ->orderBy('name')
                ->get()
                ->map(fn (Room $room): array => [
                    'value' => (string) $room->getKey(),
                    'label' => $room->roomType === null
                        ? $room->name
                        : $room->name.' · '.$room->roomType->name,
                ])
                ->all(),
            'reasons' => AvailabilityBlockReason::options(),
            'stats' => [
                'closed_tonight' => AvailabilityBlock::query()->overlapping($today, $today)->count(),
                'upcoming' => AvailabilityBlock::query()->where('starts_on', '>', $today)->count(),
                // Rooms withdrawn from sale indefinitely, which is a different
                // decision from a closure with an end date.
                'out_of_service' => Room::query()->outOfService()->count(),
            ],
        ]);
    }

    /**
     * Take a room off sale for a range of nights.
     */
    public function store(StoreAvailabilityBlockRequest $request): RedirectResponse
    {
        $room = Room::query()->findOrFail($request->integer('room_id'));

        $startsOn = $request->startsOn();
        $endsOn = $request->endsOn();

        // The block is inclusive of both dates; allocation speaks in check-in and
        // check-out terms, where the last night is the night before the morning
        // after. So the range a guest is asked about ends the day after the last
        // blocked night.
        if ($this->allocation->isGivenToAGuest($room, $startsOn, $endsOn->copy()->addDay())) {
            // Reported against the room field rather than as a toast: the desk is
            // filling in a form, and the thing that is wrong is one of the answers.
            throw ValidationException::withMessages([
                'room_id' => RoomNotAvailable::givenToAGuest($room, $startsOn, $endsOn)->getMessage(),
            ]);
        }

        AvailabilityBlock::query()->create([
            'room_id' => $room->getKey(),
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'reason' => $request->reason(),
            'notes' => $request->notes(),
            'created_by' => $request->user()?->getKey(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $startsOn->isSameDay($endsOn)
                // A single night said as a range reads like a mistake.
                ? __(':room is off sale on :date.', [
                    'room' => $room->name,
                    'date' => $endsOn->format('j M Y'),
                ])
                : __(':room is off sale from :from to :to.', [
                    'room' => $room->name,
                    'from' => $startsOn->format('j M'),
                    'to' => $endsOn->format('j M Y'),
                ]),
        ]);

        return back();
    }

    /**
     * Put a room back on sale.
     */
    public function destroy(AvailabilityBlock $block): RedirectResponse
    {
        $room = $block->room?->name ?? 'The room';

        $block->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':room is back on sale.', ['room' => $room]),
        ]);

        return back();
    }

    /**
     * One closure as the page needs it.
     *
     * @return array<string, mixed>
     */
    private function row(AvailabilityBlock $block, Carbon $today): array
    {
        return [
            'id' => $block->id,
            'room' => $block->room?->name,
            'room_type' => $block->room?->roomType?->name,
            'starts_on' => $block->starts_on->toDateString(),
            'ends_on' => $block->ends_on->toDateString(),
            'starts_label' => $block->starts_on->format('j M Y'),
            'ends_label' => $block->ends_on->format('j M Y'),
            'nights' => $block->nights(),
            'reason' => $block->reason->value,
            'reason_label' => $block->reason->label(),
            'notes' => $block->notes,
            'created_by' => $block->creator?->name,
            // Which closures are in force matters more to the desk than the dates:
            // a room that is shut tonight cannot be sold tonight.
            'state' => match (true) {
                $block->ends_on->lt($today) => 'past',
                $block->starts_on->lte($today) => 'running',
                default => 'upcoming',
            },
        ];
    }
}
