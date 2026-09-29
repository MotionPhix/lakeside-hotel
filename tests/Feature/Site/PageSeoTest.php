<?php

use App\Models\RoomType;
use App\Models\User;
use App\Support\PageSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

/*
 * What a crawler and a link preview are given.
 *
 * These assert against the raw HTML of the response rather than the rendered
 * page, because that is the whole point: the scrapers behind a WhatsApp or
 * Facebook preview never run the site's JavaScript, so anything they are meant to
 * read has to be in the bytes the server sends.
 */

/**
 * Every public page, by route name.
 *
 * @return array<string, string>
 */
function publicPages(): array
{
    return [
        'home' => '/',
        'rooms' => '/rooms',
        'dining' => '/dining',
        'activities' => '/activities',
        'conferences' => '/conferences-events',
        'gallery' => '/gallery',
        'offers' => '/offers',
        'about' => '/about',
        'policies' => '/booking-policies',
        'contact' => '/contact',
    ];
}

test('every public page carries the tags a link preview reads', function () {
    $this->seed();

    foreach (publicPages() as $name => $path) {
        $response = $this->get($path);

        $response->assertOk();

        $html = (string) $response->getContent();

        expect($html)
            ->toContain('property="og:title"')
            ->toContain('property="og:description"')
            ->toContain('property="og:image"')
            ->toContain('property="og:url"')
            ->toContain('name="twitter:card"')
            ->toContain('rel="canonical"')
            ->toContain('name="description"')
            ->and($html)->toContain('summary_large_image');

        // A relative path is useless to something with no page to resolve it
        // against, which is exactly the situation a scraper is in.
        preg_match('/property="og:image" content="([^"]+)"/', $html, $matches);

        expect($matches[1] ?? '')->toStartWith('http', "the {$name} share image is not an absolute URL");
    }
});

test('the share image is a photograph of the page, not the same picture everywhere', function () {
    $this->seed();

    $images = collect(publicPages())
        ->map(function (string $path): string {
            $html = (string) $this->get($path)->getContent();

            preg_match('/property="og:image" content="([^"]+)"/', $html, $matches);

            return $matches[1] ?? '';
        });

    expect($images->filter()->unique()->count())
        ->toBeGreaterThan(4, 'the public pages are sharing the same one or two pictures');
});

test('a room page describes that room', function () {
    $this->seed();

    $roomType = RoomType::query()->firstOrFail();

    $html = (string) $this->get(route('site.rooms.show', $roomType))->getContent();

    expect($html)
        ->toContain(e($roomType->name))
        ->toContain('property="og:image"')
        ->and($html)->toContain(e((string) $roomType->tagline));
});

test('the booking flow asks not to be indexed', function () {
    $this->seed();

    $this->get('/booking')
        ->assertOk()
        ->assertSee('name="robots" content="noindex, follow"', false);

    /*
     * The other two steps only render once a stay has been chosen - asking for
     * them without one redirects back to the search - so the decision is
     * asserted where it is made rather than through a request that cannot reach
     * the page.
     */
    foreach (['site.booking.index', 'site.booking.create', 'site.booking.show'] as $name) {
        $request = Request::create('/booking');
        $request->setRouteResolver(
            fn () => (new Route(['GET'], '/booking', fn () => null))->name($name),
        );

        expect(PageSeo::for($request)['robots'])->toContain('noindex');
    }

    // The pages that are content should still be indexed.
    $this->get('/rooms')->assertSee('name="robots" content="index, follow"', false);
});

test('the admin has no share card to give away', function () {
    $this->seed();

    $user = User::factory()->create();

    $html = (string) $this->actingAs($user)->get('/dashboard')->getContent();

    expect($html)->not->toContain('property="og:image"');
});

test('the sitemap lists the public pages and leaves the booking flow out', function () {
    $this->seed();

    $response = $this->get('/sitemap.xml');

    $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $xml = (string) $response->getContent();

    expect($xml)
        ->toContain(url('/rooms'))
        ->toContain(url('/conferences-events'))
        ->toContain(url('/contact'))
        /*
         * A transaction is not content, and a confirmation page carries
         * somebody's booking reference. Matched as whole <loc> elements, because
         * "/booking" is a prefix of "/booking-policies" - a page that does belong
         * here.
         */
        ->not->toContain('<loc>'.url('/booking').'</loc>')
        ->not->toContain('<loc>'.url('/booking/reserve').'</loc>')
        ->and($xml)->toContain('<loc>'.url('/booking-policies').'</loc>')
        ->and($xml)->toContain('<lastmod>');
});

test('the sitemap lists every room category', function () {
    $this->seed();

    $xml = (string) $this->get('/sitemap.xml')->getContent();

    foreach (RoomType::query()->pluck('slug') as $slug) {
        expect($xml)->toContain(route('site.rooms.show', $slug));
    }
});

test('robots points crawlers at the sitemap and keeps them out of the admin', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk();

    $body = (string) $response->getContent();

    expect($body)
        ->toContain('Sitemap: '.url('/sitemap.xml'))
        ->toContain('Disallow: /admin')
        ->toContain('Allow: /');
});

/**
 * Pulls every JSON-LD block out of a page and decodes it.
 *
 * Decoding doubles as validation: malformed structured data throws here rather
 * than reaching a search engine as a silent no-op.
 *
 * @return Collection<int, array<string, mixed>>
 */
function structuredDataIn(string $html): Collection
{
    preg_match_all('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $html, $matches);

    return collect($matches[1])->map(
        fn (string $json): array => json_decode($json, true, 512, JSON_THROW_ON_ERROR),
    );
}

test('a room page describes the room, not just the hotel', function () {
    $this->seed();

    $roomType = RoomType::query()->with('amenities')->firstOrFail();

    $schemas = structuredDataIn((string) $this->get(route('site.rooms.show', $roomType))->getContent());

    $room = $schemas->firstWhere('@type', 'HotelRoom');

    expect($room)->not->toBeNull('the room page carries no HotelRoom schema')
        ->and($room['name'])->toBe($roomType->name)
        ->and($room['occupancy']['maxValue'])->toBe($roomType->maxOccupancy())
        ->and($room['offers']['priceCurrency'])->toBe('MWK')
        ->and($room['offers']['price'])->toBe((string) $roomType->fromPrice())
        ->and($room['amenityFeature'])->not->toBeEmpty()
        ->and($room['image'])->not->toBeEmpty();

    // The hotel is still described as well, and the room says where it is.
    expect($schemas->firstWhere('@type', 'Hotel'))->not->toBeNull()
        ->and($room['containedInPlace']['@type'])->toBe('Hotel');

    // A crawler reading structured data has no page to resolve a relative path
    // against, so every photograph has to be absolute.
    foreach ($room['image'] as $url) {
        expect($url)->toStartWith('http');
    }
});

test('only a room page claims to be a room', function () {
    $this->seed();

    foreach (publicPages() as $path) {
        expect((string) $this->get($path)->getContent())
            ->not->toContain('"HotelRoom"', "{$path} is describing a room it does not have");
    }
});

test('every page with structured data produces JSON a search engine can read', function () {
    $this->seed();

    $roomType = RoomType::query()->firstOrFail();

    foreach ([...array_values(publicPages()), route('site.rooms.show', $roomType)] as $path) {
        $schemas = structuredDataIn((string) $this->get($path)->getContent());

        expect($schemas)->not->toBeEmpty("{$path} has no structured data");

        foreach ($schemas as $schema) {
            expect($schema)->toHaveKey('@context')
                ->and($schema)->toHaveKey('@type');
        }
    }
});
