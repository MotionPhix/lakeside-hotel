<?php

use App\Http\Controllers\Site\ActivityController;
use App\Http\Controllers\Site\BookingController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\DiningController;
use App\Http\Controllers\Site\EventController;
use App\Http\Controllers\Site\GalleryController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\NewsletterController;
use App\Http\Controllers\Site\OfferController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\RoomController;
use App\Http\Controllers\Site\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Website
|--------------------------------------------------------------------------
|
| Every page renders from content the hotel edits in the dashboard, so nothing
| in here is hard coded. The homepage keeps the plain `home` route name because
| the dashboard links back to it.
|
| The form submissions are throttled: they write rows from an endpoint that has
| no account behind it.
|
| Booking is three steps - what is free, who is coming, and the copy they keep.
| The reservation is written on the middle step, so the last two are reachable
| again from an email without depending on anything held in the session.
| `/booking/reserve` is declared before the reference route, otherwise "reserve"
| would be read as a booking reference.
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/rooms', [RoomController::class, 'index'])->name('site.rooms.index');
Route::get('/rooms/{roomType:slug}', [RoomController::class, 'show'])->name('site.rooms.show');

Route::get('/dining', [DiningController::class, 'index'])->name('site.dining');
Route::get('/activities', [ActivityController::class, 'index'])->name('site.activities');
Route::get('/conferences-events', [EventController::class, 'index'])->name('site.events');
Route::get('/gallery', [GalleryController::class, 'index'])->name('site.gallery');
Route::get('/offers', [OfferController::class, 'index'])->name('site.offers');
Route::get('/about', [PageController::class, 'about'])->name('site.about');
Route::get('/booking-policies', [PageController::class, 'policies'])->name('site.policies');

Route::get('/contact', [ContactController::class, 'show'])->name('site.contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('site.contact.submit');

Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('site.newsletter');

Route::get('/booking', [BookingController::class, 'index'])->name('site.booking.index');

Route::get('/booking/reserve', [BookingController::class, 'create'])->name('site.booking.create');

Route::post('/booking', [BookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('site.booking.store');

Route::get('/booking/{booking:reference}', [BookingController::class, 'show'])->name('site.booking.show');

Route::post('/booking/{booking:reference}/pay', [BookingController::class, 'pay'])
    ->middleware('throttle:10,1')
    ->name('site.booking.pay');

Route::get('/booking/{booking:reference}/callback', [BookingController::class, 'callback'])
    ->name('site.booking.callback');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('site.sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('site.robots');
