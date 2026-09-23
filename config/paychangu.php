<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayChangu
    |--------------------------------------------------------------------------
    |
    | The hotel settles online through PayChangu's Standard Checkout: the guest
    | is handed to a hosted page that takes cards, Airtel Money and TNM Mpamba,
    | and comes back to us. No card data ever reaches this application, which is
    | what keeps it out of PCI DSS scope.
    |
    | Which environment we are talking to is decided by the key itself - a test
    | key addresses the sandbox and a live key moves real money - so there is no
    | separate switch to remember to flip before going live.
    |
    */

    'secret_key' => env('PAYCHANGU_SECRET_KEY'),

    /*
     * Webhooks arrive with a Signature header: a SHA-256 HMAC of the raw request
     * body, keyed with the webhook secret. Without the secret a callback cannot
     * be told apart from anyone on the internet posting to the same URL.
     */
    'webhook_secret' => env('PAYCHANGU_WEBHOOK_SECRET'),

    'base_url' => env('PAYCHANGU_BASE_URL', 'https://api.paychangu.com'),

    /*
     * Seconds to wait on the gateway. A guest is standing at the checkout page
     * while this runs, so it is short on purpose.
     */
    'timeout' => (int) env('PAYCHANGU_TIMEOUT', 20),

];
