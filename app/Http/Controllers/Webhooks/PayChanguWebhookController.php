<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PayChanguGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * PayChangu's server-to-server notification.
 *
 * Two rules govern this endpoint. First, the Signature header has to check out
 * against the raw body, because the URL is public and anyone can post to it.
 * Second, the body is treated as a pointer and not as evidence: we look up the
 * transaction with the gateway before believing anything was paid, so a replayed
 * or invented callback cannot mark a booking settled.
 *
 * PayChangu retries a failed delivery three times at half-hour intervals, which
 * makes answering honestly important in both directions: 200 when the news has
 * been taken in, and anything else when it has not, so the retry is welcome.
 */
class PayChanguWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentService $payments,
        PayChanguGateway $gateway,
    ): Response {
        if (! $gateway->signatureMatches($request->getContent(), $request->header('Signature'))) {
            return response('Invalid signature', 401);
        }

        $reference = (string) $request->input('data.tx_ref', $request->input('tx_ref', ''));
        $payment = Payment::query()->where('provider_reference', $reference)->first();

        if (! $payment instanceof Payment) {
            // Answer 200 and stop. The row will never appear, so a retry would
            // only cost PayChangu three more deliveries over ninety minutes.
            return response('Unknown transaction', 200);
        }

        // Deliberately uncaught: if the gateway cannot be reached the request
        // fails, PayChangu retries, and nothing is recorded on a guess.
        $payments->reconcile($payment, $payments->verify($payment));

        return response('OK', 200);
    }
}
