<?php

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
Route::post('webhooks/paychangu', PayChanguWebhookController::class)
    ->name('webhooks.paychangu');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::inertia('dashboard', 'dashboard')
        ->middleware('permission:dashboard.view')
        ->name('dashboard');
});

require __DIR__.'/site.php';

require __DIR__.'/settings.php';

require __DIR__.'/admin.php';
