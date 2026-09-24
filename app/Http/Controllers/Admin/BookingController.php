<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\BookingNotActionable;
use App\Exceptions\RoomNotAvailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRoomRequest;
use App\Http\Requests\Admin\BookingIndexRequest;
use App\Http\Requests\Admin\CancelBookingRequest;
use App\Http\Requests\Admin\RecordPaymentRequest;
use App\Http\Requests\Admin\UpdateBookingNotesRequest;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use App\Services\Booking\BookingLifecycle;
use App\Services\Booking\RoomAllocation;
use App\Services\Payments\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reservations, from the desk's side: find one, look at it, move it along, take
 * the money, hand over a key, keep a note.
 *
 * Every transition goes through {@see BookingLifecycle}, so a move that is not
 * legal from the booking's current state is refused with a reason rather than
 * written. Which physical room a guest gets is decided by {@see RoomAllocation},
 * for the same reason. The detail page is told which moves are available and
 * which rooms could be given out, so it only offers choices that would work.
 */
class BookingController extends Controller
{
    public function __construct(
        private readonly BookingLifecycle $lifecycle,
        private readonly RoomAllocation $allocation,
        private readonly PaymentService $payments,
    ) {}

    /**
     * The reservations list, filtered.
     */
    public function index(BookingIndexRequest $request): Response
    {
        $search = $request->search();
        $status = $request->status();

        $bookings = Booking::query()
            ->with('guest', 'items.roomType')
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner
                    ->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('guest', fn (Builder $guest) => $guest
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")),
            ))
            ->when($status instanceof BookingStatus, fn (Builder $query) => $query->where('status', $status->value))
            ->when($request->from() !== null, fn (Builder $query) => $query->whereDate('check_in', '>=', $request->from()))
            ->when($request->to() !== null, fn (Builder $query) => $query->whereDate('check_in', '<=', $request->to()))
            // What is still owed, rather than what has been paid: this is the
            // filter the desk works from when chasing balances.
            ->when($request->unpaidOnly(), fn (Builder $query) => $query
                ->holdingInventory()
                ->whereColumn('amount_paid', '<', 'total'))
            ->orderByDesc('check_in')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Booking $booking): array => $this->row($booking));

        return Inertia::render('admin/bookings/index', [
            'bookings' => $bookings,
            'filters' => $request->summary(),
            'statuses' => BookingStatus::options(),
            'totals' => [
                'matching' => $bookings->total(),
                'outstanding' => $this->outstanding(),
            ],
        ]);
    }

    /**
     * One reservation in full, with everything the desk needs to act on it.
     */
    public function show(Booking $booking): Response
    {
        $booking->load('guest', 'items.roomType', 'items.ratePlan', 'items.room', 'payments.recorder', 'coupon', 'creator');

        return Inertia::render('admin/bookings/show', [
            'booking' => $this->detail($booking),
            'methods' => PaymentMethod::options(),
        ]);
    }

    /**
     * Confirm a pending reservation.
     */
    public function confirm(Booking $booking): RedirectResponse
    {
        return $this->apply(fn () => $this->lifecycle->confirm($booking), 'Booking confirmed.');
    }

    /**
     * Cancel a reservation, keeping the reason if one was given.
     */
    public function cancel(CancelBookingRequest $request, Booking $booking): RedirectResponse
    {
        return $this->apply(
            fn () => $this->lifecycle->cancel($booking, $request->reason()),
            'Booking cancelled.',
        );
    }

    /**
     * Check the guest in.
     */
    public function checkIn(Booking $booking): RedirectResponse
    {
        return $this->apply(fn () => $this->lifecycle->checkIn($booking), 'Guest checked in.');
    }

    /**
     * Check the guest out, releasing the room.
     */
    public function checkOut(Booking $booking): RedirectResponse
    {
        return $this->apply(fn () => $this->lifecycle->checkOut($booking), 'Guest checked out.');
    }

    /**
     * Mark a guest who never arrived.
     */
    public function noShow(Booking $booking): RedirectResponse
    {
        return $this->apply(fn () => $this->lifecycle->markNoShow($booking), 'Booking marked as a no-show.');
    }

    /**
     * Record money taken at the desk.
     */
    public function storePayment(RecordPaymentRequest $request, Booking $booking): RedirectResponse
    {
        $this->payments->recordManual(
            $booking,
            (string) $request->input('amount'),
            PaymentMethod::from((string) $request->input('method')),
            $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Payment recorded.'),
        ]);

        return back();
    }

    /**
     * Give a guest a room, move them to another one, or take the room back.
     *
     * Sent with no room at all to release it, which is what happens when a
     * booking turns out not to need a door after all.
     */
    public function assignRoom(AssignRoomRequest $request, Booking $booking, BookingItem $item): RedirectResponse
    {
        abort_unless((int) $item->booking_id === (int) $booking->getKey(), 404);

        $roomId = $request->input('room_id');

        $room = $roomId === null || $roomId === ''
            ? null
            : Room::query()->findOrFail((int) $roomId);

        return $this->apply(
            fn () => $this->allocation->assign($item, $room),
            $room === null ? 'Room released.' : 'Room assigned.',
        );
    }

    /**
     * Keep a note against a reservation. Internal, so it never reaches the guest.
     */
    public function updateNotes(UpdateBookingNotesRequest $request, Booking $booking): RedirectResponse
    {
        $booking->internal_notes = $request->notes();
        $booking->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Note saved.'),
        ]);

        return back();
    }

    /**
     * Run a move, turning a refusal into a message rather than an error page.
     * These are ordinary outcomes - the desk clicking a button twice, on a booking
     * somebody else has already moved, or on a room another guest has just been
     * given.
     */
    private function apply(callable $transition, string $success): RedirectResponse
    {
        try {
            $transition();
        } catch (BookingNotActionable|RoomNotAvailable $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($success),
        ]);

        return back();
    }

    /**
     * A row in the reservations list.
     *
     * @return array<string, mixed>
     */
    private function row(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'reference' => $booking->reference,
            'guest' => $booking->guest->fullName(),
            'email' => $booking->guest->email,
            'phone' => $booking->guest->phone,
            'room' => $booking->items->first()?->roomType?->name,
            'rooms' => $booking->items->count(),
            'check_in' => $booking->check_in->toDateString(),
            'check_out' => $booking->check_out->toDateString(),
            'nights' => $booking->nights,
            'adults' => $booking->adults,
            'children' => $booking->children,
            'guests' => $booking->totalGuests(),
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'status_variant' => $booking->status->variant(),
            'payment_status' => $booking->payment_status->value,
            'payment_status_label' => $booking->payment_status->label(),
            'total' => $booking->total,
            'balance' => $booking->balance(),
            'unpaid' => (float) $booking->balance() > 0,
            'source' => $booking->source->label(),
            'airport_transfer' => $booking->airport_transfer,
            'created_at' => $booking->created_at?->toFormattedDateString(),
        ];
    }

    /**
     * The full reservation, including the folio, the payments and what the desk
     * may do next.
     *
     * @return array<string, mixed>
     */
    private function detail(Booking $booking): array
    {
        return $this->row($booking) + [
            'currency' => $booking->currency,
            'subtotal' => $booking->subtotal,
            'discount_total' => $booking->discount_total,
            'tax_total' => $booking->tax_total,
            'amount_paid' => $booking->amount_paid,
            'payment_method' => $booking->payment_method?->label(),
            'coupon' => $booking->coupon?->code,
            'special_requests' => $booking->special_requests,
            'internal_notes' => $booking->internal_notes,
            'transfer_details' => $booking->transfer_details,
            'cancellation_reason' => $booking->cancellation_reason,
            'created_by' => $booking->creator?->name,
            'timeline' => [
                'created' => $booking->created_at?->toDayDateTimeString(),
                'confirmed' => $booking->confirmed_at?->toDayDateTimeString(),
                'checked_in' => $booking->checked_in_at?->toDayDateTimeString(),
                'checked_out' => $booking->checked_out_at?->toDayDateTimeString(),
                'cancelled' => $booking->cancelled_at?->toDayDateTimeString(),
            ],
            'guest_detail' => [
                'name' => $booking->guest->fullName(),
                'email' => $booking->guest->email,
                'phone' => $booking->guest->phone,
                'country' => $booking->guest->country,
                'city' => $booking->guest->city,
                'stays' => $booking->guest->bookings()->count(),
                'marketing_opt_in' => $booking->guest->marketing_opt_in,
            ],
            'items' => $booking->items
                ->map(fn (BookingItem $item): array => [
                    'id' => $item->id,
                    'room_type' => $item->roomType?->name,
                    'room' => $item->room?->number,
                    'room_id' => $item->room_id,
                    'room_name' => $item->room?->name,
                    'adults' => $item->adults,
                    'children' => $item->children,
                    'rate_plan' => $item->ratePlan?->name,
                    'price_per_night' => $item->price_per_night,
                    'subtotal' => $item->subtotal,
                    'nightly_rates' => $item->nightly_rates,
                    // Only the rooms that could take these nights: a door that is
                    // blocked, out of service or already occupied is not a choice
                    // the desk should be offered.
                    'available_rooms' => $this->allocation
                        ->candidates($item)
                        ->map(fn (Room $room): array => [
                            'id' => $room->getKey(),
                            'name' => $room->name,
                        ])
                        ->all(),
                ])
                ->all(),
            'payments' => $booking->payments
                ->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'provider' => $payment->provider,
                    'reference' => $payment->provider_reference,
                    'method' => $payment->method?->label(),
                    'amount' => $payment->amount,
                    'status' => $payment->status->label(),
                    'paid_at' => $payment->paid_at?->toDayDateTimeString(),
                    'recorded_by' => $payment->recorder?->name,
                ])
                ->all(),
            // Handing over a room only means anything while the booking still
            // holds one, so the page is told that alongside the lifecycle moves.
            'can' => $this->lifecycle->availableActions($booking)
                + ['assign_rooms' => $booking->status->holdsInventory()],
        ];
    }

    /**
     * What every live booking still owes.
     */
    private function outstanding(): string
    {
        $bookings = Booking::query()->holdingInventory()->get(['total', 'amount_paid']);

        return number_format(
            max($bookings->sum(fn (Booking $booking): float => (float) $booking->balance()), 0),
            2,
            '.',
            '',
        );
    }
}
