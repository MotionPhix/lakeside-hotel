<?php

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
