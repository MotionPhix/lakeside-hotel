<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Exceptions\PaymentGatewayException;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Talks to PayChangu's Standard Checkout.
 *
 * The guest is handed to PayChangu's own hosted page and comes back to us, so no
 * card detail ever touches this application. That is deliberate: it keeps the
 * hotel out of PCI DSS scope while still offering cards, Airtel Money and TNM
 * Mpamba from one integration.
 *
 * This class only speaks HTTP. Deciding what a response means for a booking is
 * {@see PaymentService}'s job.
 */
final class PayChanguGateway
{
    /**
     * Whether a secret key is present. Without one the site takes bookings to be
     * settled at the hotel rather than offering online payment it cannot honour.
     */
    public function isConfigured(): bool
    {
        return filled(config('paychangu.secret_key'));
    }

    /**
     * Open a checkout page and return the URL to send the guest to.
     */
    public function initiate(Payment $payment, Booking $booking): string
    {
        $guest = $booking->guest;

        $response = $this->client()->post('/payment', [
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'email' => $guest->email,
            'first_name' => $guest->first_name,
            'last_name' => $guest->last_name,
            'tx_ref' => $payment->provider_reference,
            'callback_url' => route('site.booking.callback', $booking->reference),
            'return_url' => route('site.booking.show', $booking->reference),
            'customization' => [
                'title' => 'Lakeside Hotel',
                'description' => 'Booking '.$booking->reference,
            ],
        ]);

        if ($response->failed()) {
            throw PaymentGatewayException::rejected('checkout', $response->status(), $response->body());
        }

        $url = $response->json('data.checkout_url');

        if (! is_string($url) || $url === '') {
            throw PaymentGatewayException::unexpected('checkout', (array) $response->json());
        }

        return $url;
    }

    /**
     * Ask the gateway what actually became of a transaction.
     *
     * This is the only answer worth trusting. A guest can close the checkout page
     * without paying, and a webhook can arrive twice or not at all, so both paths
     * end up here before a booking is treated as settled.
     *
     * @return array<string, mixed>
     */
    public function verify(string $reference): array
    {
        $response = $this->client()->get('/verify-payment/'.$reference);

        if ($response->failed()) {
            throw PaymentGatewayException::rejected('verify', $response->status(), $response->body());
        }

        return (array) $response->json();
    }

    /**
     * Whether a callback body genuinely came from PayChangu. The header is an
     * HMAC-SHA256 of the raw body, so it has to be checked against the bytes as
     * received rather than against anything re-encoded.
     */
    public function signatureMatches(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('paychangu.webhook_secret');

        if ($secret === '' || $signature === null || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    /**
     * Whether a verify response says the money arrived.
     *
     * PayChangu answers `success` for a settled transaction; the other spellings
     * are accepted so a wording change does not silently start marking paid
     * bookings unpaid, which is the failure that costs money.
     *
     * @param  array<string, mixed>  $payload
     */
    public function isSuccessful(array $payload): bool
    {
        $status = strtolower((string) ($payload['data']['status'] ?? $payload['status'] ?? ''));

        return in_array($status, ['success', 'successful', 'completed', 'paid'], true);
    }

    /**
     * Which instrument the guest paid with, read off the verify response.
     *
     * Anything unrecognised stays `other` rather than being guessed at, and the
     * whole payload is kept on the payment row, so a miscategorised method can be
     * corrected from the evidence later.
     *
     * @param  array<string, mixed>  $payload
     */
    public function methodFrom(array $payload): PaymentMethod
    {
        $data = (array) ($payload['data'] ?? $payload);

        $signals = array_filter([
            $data['mode'] ?? null,
            $data['payment_method'] ?? null,
            $data['authorization']['mode'] ?? null,
            $data['mobile_money']['operator'] ?? null,
        ], is_scalar(...));

        foreach ($signals as $signal) {
            $value = strtolower((string) $signal);

            $method = match (true) {
                str_contains($value, 'airtel') => PaymentMethod::AirtelMoney,
                str_contains($value, 'mpamba'), str_contains($value, 'tnm') => PaymentMethod::TnmMpamba,
                str_contains($value, 'card') => PaymentMethod::Card,
                str_contains($value, 'bank') => PaymentMethod::BankTransfer,
                str_contains($value, 'cash') => PaymentMethod::Cash,
                default => null,
            };

            if ($method instanceof PaymentMethod) {
                return $method;
            }
        }

        return PaymentMethod::Other;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('paychangu.base_url'))
            ->withToken((string) config('paychangu.secret_key'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('paychangu.timeout'));
    }
}
