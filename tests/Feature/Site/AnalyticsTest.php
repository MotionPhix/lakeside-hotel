<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * Site measurement.
 *
 * The site ships measuring nothing. These hold that line: they fail if a tag
 * appears without somebody having configured one, and they check that the tag a
 * provider needs is the tag that is rendered - a plausible script with a Fathom
 * id on it would silently measure nothing at all, which is harder to notice than
 * a broken page.
 */

test('nothing is measured until somebody asks for it', function () {
    $this->seed();

    $html = (string) $this->get('/')->getContent();

    expect($html)
        ->not->toContain('plausible.io')
        ->not->toContain('usefathom')
        ->not->toContain('umami')
        ->not->toContain('googletagmanager');
});

test('the provider that is configured is the one that loads', function (string $provider, string $url) {
    $this->seed();

    Setting::store('analytics.provider', $provider, 'analytics', 'string');
    Setting::store('analytics.measurement_id', 'test-id-123', 'analytics', 'string');

    $response = $this->get('/');
    $html = (string) $response->getContent();

    expect(str_contains($html, $url))
        ->toBeTrue("A: the {$provider} script is missing. html=".strlen($html));

    /*
     * One provider at a time. Two tags on one page would double-count every visit,
     * and a provider's script with another's id measures nothing at all - both
     * quieter failures than a broken page.
     */
    $others = [
        'plausible' => 'https://plausible.io/js/script.js',
        'fathom' => 'https://cdn.usefathom.com/script.js',
        'umami' => 'https://cloud.umami.is/script.js',
        'google' => 'https://www.googletagmanager.com/gtag/js',
    ];

    foreach ($others as $other => $otherUrl) {
        if ($other !== $provider) {
            expect(str_contains($html, $otherUrl))
                ->toBeFalse("B: {$other} loaded alongside {$provider}");
        }
    }
})->with([
    ['plausible', 'https://plausible.io/js/script.js'],
    ['fathom', 'https://cdn.usefathom.com/script.js'],
    ['umami', 'https://cloud.umami.is/script.js'],
    ['google', 'https://www.googletagmanager.com/gtag/js'],
]);

test('the id reaches the script that needs it', function () {
    $this->seed();

    Setting::store('analytics.provider', 'plausible', 'analytics', 'string');
    Setting::store('analytics.measurement_id', 'lakesidehotelmw.net', 'analytics', 'string');

    expect((string) $this->get('/')->getContent())
        ->toContain('data-domain="lakesidehotelmw.net"');
});

test('a half-configured provider loads nothing rather than something broken', function () {
    $this->seed();

    // A provider with no id, and an id with no provider.
    Setting::store('analytics.provider', 'plausible', 'analytics', 'string');

    expect((string) $this->get('/')->getContent())->not->toContain('plausible.io');

    Setting::store('analytics.provider', '', 'analytics', 'string');
    Setting::store('analytics.measurement_id', 'lakesidehotelmw.net', 'analytics', 'string');

    expect((string) $this->get('/')->getContent())
        ->not->toContain('plausible.io')
        ->not->toContain('data-domain');
});

test('an unknown provider loads nothing', function () {
    $this->seed();

    Setting::store('analytics.provider', 'some-tag-manager', 'analytics', 'string');
    Setting::store('analytics.measurement_id', 'abc123', 'analytics', 'string');

    $html = (string) $this->get('/')->getContent();

    /*
     * The id itself does appear in the page's data - the settings are shared with
     * the front end - so what is asserted is that no script was written for a
     * provider nobody recognises.
     */
    expect($html)
        ->not->toContain('googletagmanager')
        ->not->toContain('plausible.io')
        ->not->toContain('usefathom')
        ->not->toContain('cloud.umami.is')
        ->not->toContain('data-domain="abc123"');
});

test('the staff screens are never measured', function () {
    $this->seed();

    Setting::store('analytics.provider', 'plausible', 'analytics', 'string');
    Setting::store('analytics.measurement_id', 'lakesidehotelmw.net', 'analytics', 'string');

    $html = (string) $this->actingAs(User::factory()->create())->get('/dashboard')->getContent();

    expect($html)->not->toContain('plausible.io');
});

test('measurement never delays the page', function () {
    $this->seed();

    Setting::store('analytics.provider', 'plausible', 'analytics', 'string');
    Setting::store('analytics.measurement_id', 'lakesidehotelmw.net', 'analytics', 'string');

    /*
     * No `defer` or `async` would mean a third party's server sits between a guest
     * and the page they asked for. Only the analytics hosts are checked - the
     * Vite client is present in tests and is not something a guest is served.
     */
    preg_match_all(
        '/<script\b[^>]*src="https?:\/\/(?=[^"]*(?:plausible|usefathom|umami|googletagmanager))[^"]*"[^>]*>/',
        (string) $this->get('/')->getContent(),
        $matches,
    );

    expect($matches[0])->not->toBeEmpty('the analytics script did not render at all');

    foreach ($matches[0] as $tag) {
        expect($tag)->toMatch('/\b(defer|async)\b/', "a third-party script blocks the page: {$tag}");
    }
});

test('the google snippet anonymises the address it sends', function () {
    $this->seed();

    Setting::store('analytics.provider', 'google', 'analytics', 'string');
    Setting::store('analytics.measurement_id', 'G-ABC123', 'analytics', 'string');

    expect((string) $this->get('/')->getContent())
        ->toContain('anonymize_ip: true');
});
