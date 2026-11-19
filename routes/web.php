<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Webhooks\PayChanguWebhookController;
use Illuminate\Support\Facades\Route;

/*
| Gateway callbacks.
|
| Posted to by PayChangu, not by a browser, so this sits outside the site routes
| and outside CSRF (see bootstrap/app.php). It authenticates its callers with the
| Signature header instead, and answers 200 only when the news has been taken in.
|
*/
/*
 * Sixty a minute, per caller. PayChangu sends one notification per payment and
 * retries a failure at half-hour intervals, so real traffic is nowhere near
 * this; what the limit is for is the other use of a public URL - a script
 * guessing at the signature, or simply hammering the endpoint. An HMAC is cheap
 * but the database behind it is not.
 *
 * If a genuine notification ever is caught by the limit, the throttled response
 * is not a 200, so PayChangu retries in thirty minutes, by which time the bucket
 * has long emptied. It is the same retry it already does for a 500.
 */
Route::post('webhooks/paychangu', PayChanguWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.paychangu');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');
});

require __DIR__.'/site.php';

require __DIR__.'/settings.php';

require __DIR__.'/admin.php';
