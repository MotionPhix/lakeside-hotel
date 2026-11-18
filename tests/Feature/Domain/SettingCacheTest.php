<?php

use App\Models\Setting;
use App\Support\SitePresenter;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * The settings table is read on every request - the currency inside a price, the
 * two tax rates inside a total, the phone number in the header and the footer -
 * so it is cached and then held for the life of the request. These pin both
 * halves of that: that the reads are cheap, and that a write never leaves a
 * stale figure on the page.
 */

test('the settings table is read once per request, not once per key', function () {
    Setting::flushCache();

    DB::flushQueryLog();
    DB::enableQueryLog();

    Setting::value('hotel.phone');
    Setting::value('hotel.currency', 'MWK');
    Setting::value('booking.vat_rate', '17.5');
    SitePresenter::settings();

    // One pluck of the table. Every read after it comes from the array that
    // pluck produced, including the tax rates the presenter asks Tax for.
    expect(DB::getQueryLog())->toHaveCount(1);

    DB::disableQueryLog();
});

test('saving a setting is on the next page, not an hour later', function () {
    $this->seed();

    Setting::value('hotel.phone');

    Setting::store('hotel.phone', '+265 999 000 000', 'contact', 'string');

    expect(Setting::value('hotel.phone'))->toBe('+265 999 000 000');

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('site.contact.phone', '+265 999 000 000'));
});

test('deleting a setting stops it being read', function () {
    $setting = Setting::store('hotel.tagline', 'A tagline that is about to go');

    expect(Setting::value('hotel.tagline'))->toBe('A tagline that is about to go');

    $setting->delete();

    expect(Setting::value('hotel.tagline'))->toBeNull()
        ->and(Setting::value('hotel.tagline', 'fallback'))->toBe('fallback');
});
