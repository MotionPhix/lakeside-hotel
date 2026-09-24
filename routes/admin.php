<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Hotel Administration
|--------------------------------------------------------------------------
|
| Every route here needs a verified, active staff account. Each module adds
| the `permission:` middleware for the capability it requires, so the
| navigation, the routes and the seed data all stay in step.
|
*/

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function (): void {
    /*
    | Reservations
    |
    | Reading the list and one reservation are separate permissions, but moving a
    | booking along and taking money are both `bookings.manage`: they are the same
    | job, and a desk that can do one almost always needs the other.
    |
    */
    Route::get('bookings', [BookingController::class, 'index'])
        ->middleware('permission:bookings.view')
        ->name('bookings.index');

    Route::get('bookings/{booking:reference}', [BookingController::class, 'show'])
        ->middleware('permission:bookings.view')
        ->name('bookings.show');

    Route::patch('bookings/{booking:reference}/confirm', [BookingController::class, 'confirm'])
        ->middleware('permission:bookings.manage')
        ->name('bookings.confirm');

    Route::patch('bookings/{booking:reference}/cancel', [BookingController::class, 'cancel'])
        ->middleware('permission:bookings.manage')
        ->name('bookings.cancel');

    Route::patch('bookings/{booking:reference}/check-in', [BookingController::class, 'checkIn'])
        ->middleware('permission:bookings.manage')
        ->name('bookings.check-in');

    Route::patch('bookings/{booking:reference}/check-out', [BookingController::class, 'checkOut'])
        ->middleware('permission:bookings.manage')
        ->name('bookings.check-out');

    Route::patch('bookings/{booking:reference}/no-show', [BookingController::class, 'noShow'])
        ->middleware('permission:bookings.manage')
        ->name('bookings.no-show');

    Route::post('bookings/{booking:reference}/payments', [BookingController::class, 'storePayment'])
        ->middleware('permission:bookings.manage')
        ->name('bookings.payments.store');

    /*
    | Content
    |
    | One route set serves every content type: `{resource}` is a key in
    | App\Cms\ResourceRegistry, and which permission is needed travels with the
    | definition rather than the route, because one route serves seventeen types
    | with four different owners. The controller enforces it.
    |
    | `{id}` is declared last within the prefix so `create` is not read as one.
    |
    */
    Route::get('content', [ResourceController::class, 'hub'])->name('content.hub');

    Route::prefix('content/{resource}')->name('content.')->group(function (): void {
        Route::get('/', [ResourceController::class, 'index'])->name('index');
        Route::get('create', [ResourceController::class, 'create'])->name('create');
        Route::post('/', [ResourceController::class, 'store'])->name('store');

        Route::get('{id}/edit', [ResourceController::class, 'edit'])->name('edit');
        Route::put('{id}', [ResourceController::class, 'update'])->name('update');
        Route::delete('{id}', [ResourceController::class, 'destroy'])->name('destroy');
        Route::patch('{id}/move/{direction}', [ResourceController::class, 'move'])->name('move');
        Route::patch('{id}/publish', [ResourceController::class, 'toggle'])->name('publish');
        Route::post('{id}/media', [ResourceController::class, 'storeMedia'])->name('media.store');
        Route::delete('{id}/media/{mediaId}', [ResourceController::class, 'destroyMedia'])->name('media.destroy');
    });

    Route::get('users', [UserController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('users.index');

    Route::get('users/create', [UserController::class, 'create'])
        ->middleware('permission:users.manage')
        ->name('users.create');

    Route::post('users', [UserController::class, 'store'])
        ->middleware('permission:users.manage')
        ->name('users.store');

    Route::get('users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:users.manage')
        ->name('users.edit');

    Route::patch('users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.manage')
        ->name('users.update');

    Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])
        ->middleware('permission:users.manage')
        ->name('users.status');

    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.manage')
        ->name('users.destroy');
});
