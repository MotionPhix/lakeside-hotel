<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::inertia('dashboard', 'dashboard')
        ->middleware('permission:dashboard.view')
        ->name('dashboard');
});

require __DIR__.'/settings.php';

require __DIR__.'/admin.php';
