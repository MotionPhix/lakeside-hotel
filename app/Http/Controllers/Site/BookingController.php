<?php

namespace App\Http\Controllers\Site;

use App\Enums\PaymentRecordStatus;
use App\Exceptions\PaymentGatewayException;
use App\Exceptions\StayNotAvailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\BookingSearchRequest;
use App\Http\Requests\Site\BookingStoreRequest;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\RoomType;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\ReservationService;
use App\Services\Payments\PaymentService;
use App\Support\BookingPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public booking flow, in three steps: what is free, who is coming, and the
 * confirmation they keep.
 *
 * Each step is a page rather than a wizard held in the browser, because the whole
 * flow has to survive a guest closing the tab on the payment page and coming
 * back to it from an email. The booking exists in the database from the moment
 * the details are submitted, so nothing depends on session state that the return
 * trip has lost.
 */
class BookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly ReservationService $reservations,
        private readonly PaymentService $payments,
    ) {}

    /**
     * Step one: the dates, and what the hotel can sell for them.
     */
    public function index(BookingSearchRequest $request): Response
    {
        $stay = $request->stay();

        return Inertia::render('public/booking/index', [
            'search' => $this->searchSummary($request),
            'offers' => $stay === null
                ? []
                : $this->availability->search($stay)
                    ->map(fn ($offer): array => BookingPresenter::offer($offer))
                    ->all(),
            'searched' => $stay !== null,
            'roomTypes' => $this->roomTypeOptions(),
            'booking' => BookingPresenter::context(),
        ]);
    }

    /**
     * Step two: the guest's details, for the room they chose.
     */
    public function create(BookingSearchRequest $request): Response|RedirectResponse
    {
        $stay = $request->stay();

        if ($stay === null || $stay->roomTypeSlug === null) {
            return to_route('site.booking.index');
        }

        $offers = $this->availability->search($stay);
        $offer = $offers->first();

        if ($offer === null || $offer->available === 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('That room is no longer free for those dates. Here is what else is available.'),
            ]);

            return to_route('site.booking.index', $request->query());
        }

        return Inertia::render('public/booking/create', [
            'search' => $this->searchSummary($request),
            'offer' => BookingPresenter::offer($offer),
            'booking' => BookingPresenter::context(),
        ]);
    }

    /**
     * Step three: take the reservation.
     *
     * The room is held from here. A guest who chooses to pay online is sent to the
     * gateway; one who chooses to settle at the hotel goes straight to the
     * confirmation, and the desk takes it from there.
     */
    public function store(BookingStoreRequest $request): RedirectResponse
    {
        $stay = $request->stay();

        $roomType = RoomType::query()->where('slug', $stay->roomTypeSlug)->firstOrFail();

        $coupon = null;

        if ($request->filled('coupon_code')) {
            $coupon = Coupon::query()
                ->available()
                ->where('code', $request->input('coupon_code'))
                ->first();

            // A code that does not resolve is sent back to be corrected rather
            // than quietly dropped, so a guest is never charged more than the
            // price they were shown.
            if (! $coupon instanceof Coupon) {
                throw ValidationException::withMessages([
                    'coupon_code' => __('That discount code is not valid, has expired, or has been used up.'),
                ]);
            }
        }

        try {
            $booking = $this->reservations->reserve(
                roomType: $roomType,
                stay: $stay,
                guestAttributes: $request->guestAttributes(),
                paymentOption: $request->paymentOption(),
                coupon: $coupon,
                airportTransfer: $request->boolean('airport_transfer'),
                specialRequests: $request->input('special_requests'),
                transfer: $request->transfer(),
            );
        } catch (StayNotAvailable) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('That room was taken while you were booking. Please choose another.'),
            ]);

            return to_route('site.booking.index', $request->only('check_in', 'check_out', 'adults', 'children'));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Your booking is held. Your reference is :reference.', ['reference' => $booking->reference]),
        ]);

        return to_route('site.booking.show', $booking->reference);
    }

    /**
     * The guest's own copy of the reservation.
     */
    public function show(Booking $booking): Response
    {
        return Inertia::render('public/booking/show', [
            'reservation' => BookingPresenter::booking($booking->load('guest', 'items.roomType')),
            'booking' => BookingPresenter::context(),
        ]);
    }

    /**
     * Send the guest to PayChangu's hosted checkout.
     *
     * Nothing is charged here: the attempt is recorded, the gateway is asked for a
     * checkout page, and the guest leaves the site. What comes back is verified
     * before the booking is treated as paid.
     */
    public function pay(Booking $booking): RedirectResponse
    {
        if ($booking->isPaidInFull()) {
            return to_route('site.booking.show', $booking->reference);
        }

        if (! $this->payments->isOnlinePaymentEnabled()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Online payment is unavailable at the moment. Your booking is held and can be settled at the hotel.'),
            ]);

            return to_route('site.booking.show', $booking->reference);
        }

        $payment = $this->payments->start($booking);

        try {
            $url = $this->payments->checkoutUrl($payment, $booking);
        } catch (PaymentGatewayException) {
            $this->payments->fail($payment, ['error' => 'checkout unavailable']);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('We could not reach the payment provider. Your booking is held - please try again, or settle at the hotel.'),
            ]);

            return to_route('site.booking.show', $booking->reference);
        }

        return redirect()->away($url);
    }

    /**
     * Where the gateway returns the guest.
     *
     * The result is never taken from the query string: the transaction is fetched
     * from PayChangu and that is what decides. The same reconciliation runs from
     * the webhook, and either arriving twice is harmless.
     */
    public function callback(Request $request, Booking $booking): RedirectResponse
    {
        $payment = $this->paymentFor($booking, (string) $request->query('tx_ref', ''));

        if (! $payment instanceof Payment) {
            return to_route('site.booking.show', $booking->reference);
        }

        try {
            $this->payments->reconcile($payment, $this->payments->verify($payment));
        } catch (PaymentGatewayException) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('We could not confirm the payment just yet. If you were charged, it will show here shortly.'),
            ]);
        }

        return to_route('site.booking.show', $booking->reference);
    }

    /**
     * The payment attempt the guest came back from, identified by the reference
     * we sent out, falling back to the newest attempt still open.
     */
    private function paymentFor(Booking $booking, string $reference): ?Payment
    {
        if ($reference !== '') {
            $found = $booking->payments()->where('provider_reference', $reference)->first();

            if ($found instanceof Payment) {
                return $found;
            }
        }

        return $booking->payments()->where('status', PaymentRecordStatus::Pending)->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function searchSummary(Request $request): array
    {
        return [
            'check_in' => (string) $request->input('check_in', ''),
            'check_out' => (string) $request->input('check_out', ''),
            'adults' => (int) $request->input('adults', 2),
            'children' => (int) $request->input('children', 0),
            'room_type' => (string) $request->input('room_type', ''),
        ];
    }

    /**
     * Every category, so the search can be narrowed without leaving the page.
     *
     * @return list<array<string, mixed>>
     */
    private function roomTypeOptions(): array
    {
        return RoomType::query()
            ->active()
            ->get()
            ->map(fn (RoomType $roomType): array => [
                'slug' => $roomType->slug,
                'name' => $roomType->name,
                'from' => $roomType->fromPrice(),
            ])
            ->all();
    }
}
