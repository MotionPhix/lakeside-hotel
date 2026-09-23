<?php

use App\Enums\Role;
use App\Models\RoomType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('every public page renders for a visitor', function (string $routeName) {
    $this->seed();

    $this->get(route($routeName))->assertOk();
})->with([
    'home',
    'site.rooms.index',
    'site.dining',
    'site.activities',
    'site.events',
    'site.gallery',
    'site.offers',
    'site.about',
    'site.policies',
    'site.contact',
]);

test('the public pages do not require a signed in account', function () {
    $this->seed();

    $this->assertGuest();

    $this->get(route('home'))->assertOk();
    $this->get(route('site.rooms.index'))->assertOk();
});

test('the homepage carries the content its sections need', function () {
    $this->seed();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->has('heroSlides', 3)
            ->where('heroSlides.0.headline', 'Your Lakeside Escape in Senga Bay')
            ->has('roomTypes', 5)
            ->has('amenities', 15)
            ->has('diningVenues', 3)
            ->has('activities')
            ->has('conferencePackages')
            ->has('gallery', 8)
            ->has('testimonials', 6)
            ->has('offers')
            ->has('attractions', 6)
            ->where('about.key', 'about')
            ->where('about.title', 'A lakeside retreat in Senga Bay')
        );
});

test('a room category page resolves by its slug', function () {
    $this->seed();

    $roomType = RoomType::query()->where('slug', 'lakeside-chalets')->firstOrFail();

    $this->get(route('site.rooms.show', $roomType))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/rooms/show')
            ->where('roomType.name', 'Lakeside Chalets')
            ->where('roomType.total_rooms', 5)
            ->where('roomType.base_price', '320000.00')
            ->where('roomType.from_price', '320000.00')
            ->has('roomType.amenities')
            ->has('otherRoomTypes', 3)
        );
});

test('a room category that is off sale is not reachable', function () {
    $roomType = RoomType::factory()->inactive()->create();

    $this->get(route('site.rooms.show', $roomType))->assertNotFound();
});

test('hotel settings are shared with every page', function () {
    $this->seed();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('site.name', 'Lakeside Hotel and Conference Centre')
            ->where('site.tagline', 'Your Lakeside Escape in Senga Bay')
            ->where('site.contact.email', 'reservations@lakesidehotel.mw')
            ->where('site.contact.latitude', -13.7167)
            ->where('site.booking.vat_rate', 16.5)
            ->where('site.booking.currency', 'MWK')
            ->where('site.whatsapp_link', 'https://wa.me/265995123400')
        );
});

test('the dining page lists each venue with a menu grouped by section', function () {
    $this->seed();

    $this->get(route('site.dining'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/dining')
            ->has('venues', 3)
            ->where('venues.0.name', 'The Lakeview Restaurant')
            ->has('venues.0.menu')
            ->has('venues.0.signature_dishes')
            ->where('intro.key', 'dining.intro')
        );
});

test('the events page separates conference packages from weddings', function () {
    $this->seed();

    $this->get(route('site.events'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/events')
            ->has('conferencePackages')
            ->has('weddingPackages')
            ->where('largestCapacity', 200)
        );
});

test('the gallery page exposes the categories in use', function () {
    $this->seed();

    $this->get(route('site.gallery'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/gallery')
            ->has('items', 18)
            ->has('categories')
        );
});

test('the offers page only shows offers that are running', function () {
    $this->seed();

    $this->get(route('site.offers'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/offers')
            ->has('offers')
        );
});

test('a missing room slug returns a 404', function () {
    $this->seed();

    $this->get('/rooms/does-not-exist')->assertNotFound();
});

test('the sitemap lists every public page and each room category', function () {
    $this->seed();

    $response = $this->get(route('site.sitemap'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    foreach ([
        route('home'),
        route('site.rooms.index'),
        route('site.dining'),
        route('site.events'),
        route('site.offers'),
        route('site.contact'),
    ] as $location) {
        $response->assertSee($location, false);
    }

    foreach (RoomType::query()->active()->get() as $roomType) {
        $response->assertSee(route('site.rooms.show', $roomType), false);
    }
});

test('robots.txt points at the sitemap and keeps staff pages out of search', function () {
    $this->get(route('site.robots'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /admin')
        ->assertSee('Disallow: /settings')
        ->assertSee('Sitemap: '.route('site.sitemap'));
});

test('the public pages carry hotel structured data for search engines', function () {
    $this->seed();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type": "Hotel"', false)
        ->assertSee('Senga Bay', false);
});

test('the structured data keeps its schema.org context key', function () {
    $this->seed();

    $response = $this->get(route('home'));
    $response->assertOk();

    /*
     * Asserted against the rendered HTML rather than the array, because this is
     * where the bug lives: Blade consumes a literal `@context` key as a
     * directive and substitutes compiled PHP, which drops the context, leaves the
     * markup useless to search engines and ships PHP source to every visitor.
     */
    $response->assertSee('"@context": "https://schema.org"', false);
    $response->assertDontSee('$__contextArgs', false);
    $response->assertDontSee('<?php', false);
});

test('the staff dashboard does not carry the public structured data', function () {
    $this->seed();

    $user = User::factory()->role(Role::Admin)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('application/ld+json', false);
});
