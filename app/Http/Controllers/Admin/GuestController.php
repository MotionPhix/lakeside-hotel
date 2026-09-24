<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuestIndexRequest;
use App\Http\Requests\Admin\UpdateGuestRequest;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Guests: who has stayed, what they are worth, and how to reach them.
 *
 * Deliberately separate from reservations. A reservation is about one visit; this
 * is the record the desk reaches for when somebody rings a year later and cannot
 * remember their reference, and the one a manager uses to answer "how many people
 * have stayed with us more than once".
 */
class GuestController extends Controller
{
    /**
     * The guest list, filtered.
     */
    public function index(GuestIndexRequest $request): Response
    {
        $search = $request->search();

        $guests = Guest::query()
            /*
             * Counted in the query rather than per row: every guest on the page
             * shows their stay count and their spend, and asking for those one
             * guest at a time is how a list of twenty turns into sixty queries.
             */
            ->withCount('bookings')
            ->withSum('bookings', 'total')
            ->withMax('bookings', 'check_in')
            ->when($search !== '', fn (Builder $query) => $query->search($search))
            ->when($request->returningOnly(), fn (Builder $query) => $query->has('bookings', '>', 1))
            ->when(
                $request->sort() === 'spend',
                fn (Builder $query) => $query->orderByDesc('bookings_sum_total'),
                // Guests who have never booked sink to the bottom either way,
                // rather than blocking the top of the list.
                fn (Builder $query) => $query->orderByDesc('bookings_max_check_in'),
            )
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Guest $guest): array => $this->row($guest));

        return Inertia::render('admin/guests/index', [
            'guests' => $guests,
            'filters' => $request->summary(),
            'totals' => [
                'matching' => $guests->total(),
                'returning' => Guest::query()->has('bookings', '>', 1)->count(),
            ],
        ]);
    }

    /**
     * One guest in full: their record, their history and what it adds up to.
     */
    public function show(Guest $guest): Response
    {
        $guest->load([
            'bookings' => fn ($query) => $query->orderByDesc('check_in'),
            'bookings.items.roomType',
        ]);

        return Inertia::render('admin/guests/show', [
            'guest' => $this->detail($guest),
            'stats' => $this->stats($guest),
        ]);
    }

    /**
     * Correct a guest's record.
     *
     * There is no create here. Guests appear by booking, and a guest added by hand
     * on the desk's side has no history to be the point of the record.
     */
    public function update(UpdateGuestRequest $request, Guest $guest): RedirectResponse
    {
        $guest->fill([
            'first_name' => trim((string) $request->input('first_name')),
            'last_name' => trim((string) $request->input('last_name')),
            'email' => trim((string) $request->input('email')),
            'phone' => $request->value('phone'),
            'country' => $request->value('country'),
            'city' => $request->value('city'),
            'address' => $request->value('address'),
            'id_number' => $request->value('id_number'),
            'notes' => $request->value('notes'),
            'marketing_opt_in' => $request->boolean('marketing_opt_in'),
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Guest record updated.'),
        ]);

        return back();
    }

    /**
     * One guest as the list needs them.
     *
     * @return array<string, mixed>
     */
    private function row(Guest $guest): array
    {
        $stays = (int) $guest->bookings_count;

        return [
            'id' => $guest->getKey(),
            'name' => $guest->fullName(),
            'email' => $guest->email,
            'phone' => $guest->phone,
            'location' => $this->location($guest),
            'stays' => $stays,
            'returning' => $stays > 1,
            'last_stay' => $guest->bookings_max_check_in,
            'spend' => number_format((float) $guest->bookings_sum_total, 2, '.', ''),
            'marketing_opt_in' => (bool) $guest->marketing_opt_in,
        ];
    }

    /**
     * One guest as the record page needs them.
     *
     * @return array<string, mixed>
     */
    private function detail(Guest $guest): array
    {
        return [
            'id' => $guest->getKey(),
            'name' => $guest->fullName(),
            'first_name' => $guest->first_name,
            'last_name' => $guest->last_name,
            'email' => $guest->email,
            'phone' => $guest->phone,
            'country' => $guest->country,
            'city' => $guest->city,
            'address' => $guest->address,
            'id_number' => $guest->id_number,
            'notes' => $guest->notes,
            'marketing_opt_in' => (bool) $guest->marketing_opt_in,
            'since' => $guest->created_at?->toDateString(),
            'stays' => $guest->bookings
                ->map(fn (Booking $booking): array => $this->stay($booking))
                ->all(),
        ];
    }

    /**
     * One visit, as the history lists it.
     *
     * @return array<string, mixed>
     */
    private function stay(Booking $booking): array
    {
        return [
            'reference' => $booking->reference,
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'check_in' => $booking->check_in->toDateString(),
            'check_out' => $booking->check_out->toDateString(),
            'nights' => $booking->nights,
            'rooms' => $booking->items
                ->map(fn (BookingItem $item): string => $item->roomType?->name ?? __('Room'))
                ->all(),
            'total' => $booking->total,
            'payment_status' => $booking->payment_status->label(),
        ];
    }

    /**
     * What the guest's history adds up to.
     *
     * @return array<string, mixed>
     */
    private function stats(Guest $guest): array
    {
        $bookings = $guest->bookings;

        return [
            'stays' => $bookings->count(),
            'nights' => (int) $bookings->sum('nights'),
            'spend' => number_format((float) $bookings->sum('total'), 2, '.', ''),
            // Counted rather than forgotten: a guest who cancels twice is worth
            // knowing about before taking a third booking.
            'cancelled' => $bookings
                ->filter(fn (Booking $booking): bool => $booking->status === BookingStatus::Cancelled)
                ->count(),
            'first_stay' => $bookings->min('check_in')?->toDateString(),
            'last_stay' => $bookings->max('check_in')?->toDateString(),
        ];
    }

    /**
     * Where the guest says they are from, as one line.
     */
    private function location(Guest $guest): ?string
    {
        $parts = array_filter([$guest->city, $guest->country]);

        return $parts === [] ? null : implode(', ', $parts);
    }
}
