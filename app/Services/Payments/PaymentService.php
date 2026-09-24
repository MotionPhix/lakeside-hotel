<?php

namespace App\Services\Payments;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Exceptions\PaymentGatewayException;
use App\Mail\BookingConfirmed;
use App\Mail\BookingReceived;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Turns what the gateway says into what the booking says.
 *
 * The gateway is only ever asked, never believed on its own say-so: both the
 * guest's return from the checkout page and the webhook come through here, and
 * both fetch the transaction from PayChangu before anything is marked paid. The
 * two paths therefore agree, and either one arriving twice changes nothing.
 */
final class PaymentService
{
    public function __construct(private readonly PayChanguGateway $gateway) {}

    /**
     * Whether the hotel can take money online at all.
     */
    public function isOnlinePaymentEnabled(): bool
    {
        return $this->gateway->isConfigured()
            && filter_var(
                Setting::value('booking.online_payment_enabled', 'true'),
                FILTER_VALIDATE_BOOLEAN,
            );
    }

    /**
     * Open a payment attempt. Nothing is charged yet: the row exists so the
     * transaction has a reference of ours before the guest leaves the site, which
     * is also what makes the webhook findable when it comes back.
     */
    public function start(Booking $booking, ?string $amount = null): Payment
    {
        $attempt = $booking->payments()->count() + 1;

        return $booking->payments()->create([
            'provider' => Payment::PROVIDER_PAYCHANGU,
            'provider_reference' => sprintf('%s-P%d', $booking->reference, $attempt),
            'amount' => $amount ?? $booking->balance(),
            'currency' => $booking->currency,
            'status' => PaymentRecordStatus::Pending,
        ]);
    }

    /**
     * Record money taken at the desk: cash, a card machine, or a bank transfer
     * confirmed by the bank.
     *
     * Settled immediately, because the person entering it is holding the money or
     * has seen it land. It confirms a pending booking for the same reason a
     * gateway payment does - the guest has paid, so the room is theirs.
     */
    public function recordManual(
        Booking $booking,
        string $amount,
        PaymentMethod $method,
        ?User $takenBy = null,
    ): Payment {
        $payment = $booking->payments()->create([
            'provider' => Payment::PROVIDER_MANUAL,
            'provider_reference' => sprintf('%s-M%d', $booking->reference, $booking->payments()->count() + 1),
            'method' => $method,
            'amount' => $amount,
            'currency' => $booking->currency,
            'status' => PaymentRecordStatus::Successful,
            'paid_at' => now(),
            'recorded_by' => $takenBy?->getKey(),
        ]);

        $booking->syncPaymentStatus();

        if ($booking->status === BookingStatus::Pending) {
            $booking->status = BookingStatus::Confirmed;
            $booking->confirmed_at = now();
        }

        $booking->save();

        return $payment;
    }

    /**
     * Ask the gateway for a hosted checkout page for an attempt.
     *
     * @throws PaymentGatewayException
     */
    public function checkoutUrl(Payment $payment, Booking $booking): string
    {
        return $this->gateway->initiate($payment, $booking);
    }

    /**
     * Ask the gateway what became of an attempt. This is what both the guest's
     * return and the webhook act on, so neither trusts what it was handed.
     *
     * @return array<string, mixed>
     *
     * @throws PaymentGatewayException
     */
    public function verify(Payment $payment): array
    {
        return $this->gateway->verify($payment->provider_reference);
    }

    /**
     * Confirm a transaction with the gateway and record the outcome.
     *
     * Returns the payment either way, so a caller can look at its status rather
     * than catching anything: a declined card is an ordinary outcome, not an
     * error.
     */
    public function reconcile(Payment $payment, array $payload): Payment
    {
        if (! $this->gateway->isSuccessful($payload)) {
            return $this->fail($payment, $payload);
        }

        return $this->settle($payment, $payload);
    }

    /**
     * Record that the money arrived and confirm the booking.
     *
     * @param  array<string, mixed>  $payload
     */
    public function settle(Payment $payment, array $payload, ?PaymentMethod $method = null): Payment
    {
        // PayChangu retries a webhook three times and the guest's browser lands
        // on the return URL as well, so this is reached twice as often as a
        // payment is actually made. The second call must do nothing.
        if ($payment->isSuccessful()) {
            return $payment;
        }

        $payment->markSuccessful($method ?? $this->gateway->methodFrom($payload), $payload);

        $booking = $payment->booking;
        $booking->syncPaymentStatus();

        if ($booking->status === BookingStatus::Pending) {
            $booking->status = BookingStatus::Confirmed;
            $booking->confirmed_at = now();
        }

        $booking->save();

        // Only reached on the transition from unpaid, so the guest hears once.
        Mail::to($booking->guest->email)->send(new BookingConfirmed($booking));
        $this->notifyHotel($booking);

        return $payment;
    }

    /**
     * Record that an attempt did not go through. The booking is left alone: the
     * guest still has a reservation, they simply have not paid for it.
     *
     * @param  array<string, mixed>  $payload
     */
    public function fail(Payment $payment, array $payload): Payment
    {
        if ($payment->isSuccessful()) {
            return $payment;
        }

        $payment->markFailed($payload);

        return $payment;
    }

    /**
     * Tell the reservations desk. Sent to the address the hotel edits in the
     * dashboard, so a booking never lands somewhere nobody reads.
     */
    private function notifyHotel(Booking $booking): void
    {
        $inbox = Setting::value('hotel.email', config('mail.from.address'));

        if ($inbox === null || $inbox === '') {
            return;
        }

        Mail::to($inbox)->send(new BookingReceived($booking));
    }
}
