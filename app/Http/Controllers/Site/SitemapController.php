<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Response;

/**
 * The XML sitemap for the public website. Room categories are listed
 * individually because they are the pages most likely to be searched for.
 */
class SitemapController extends Controller
{
    /**
     * Render the sitemap.
     */
    public function index(): Response
    {
        $urls = [
            $this->url(route('home'), '1.0', 'weekly'),
            $this->url(route('site.rooms.index'), '0.9', 'weekly'),
            $this->url(route('site.dining'), '0.8', 'monthly'),
            $this->url(route('site.activities'), '0.8', 'monthly'),
            $this->url(route('site.events'), '0.8', 'monthly'),
            $this->url(route('site.gallery'), '0.6', 'monthly'),
            $this->url(route('site.offers'), '0.8', 'weekly'),
            $this->url(route('site.about'), '0.6', 'yearly'),
            $this->url(route('site.policies'), '0.5', 'yearly'),
            $this->url(route('site.contact'), '0.7', 'yearly'),
        ];

        foreach (RoomType::query()->active()->get() as $roomType) {
            $urls[] = $this->url(
                route('site.rooms.show', $roomType),
                '0.8',
                'monthly',
                $roomType->updated_at?->toAtomString(),
            );
        }

        // The XML declaration is prepended here rather than written into the
        // template. In the template it has to be echoed through a Blade raw block
        // to stop Blade treating it as a tag, and on the production host the served
        // document began with the literal `{!! '' !!}` and a declaration that was
        // missing altogether - not valid XML, and the first thing a crawler reads.
        // Built here, the declaration is an ordinary PHP string: no Blade block, no
        // compiled view and no INI setting stands between it and the response.
        return response(
            '<?xml version="1.0" encoding="UTF-8"?>'."\n".view('sitemap', ['urls' => $urls])->render(),
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }

    /**
     * Serve robots.txt.
     *
     * Served from the application rather than a static file so the sitemap URL
     * follows APP_URL instead of being hard coded to one environment.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# The dashboard and settings are for hotel staff only.',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /settings',
            '',
            'Sitemap: '.route('site.sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Build one sitemap entry.
     *
     * @return array{loc: string, priority: string, changefreq: string, lastmod: string|null}
     */
    private function url(string $location, string $priority, string $changefreq, ?string $lastmod = null): array
    {
        return [
            'loc' => $location,
            'priority' => $priority,
            'changefreq' => $changefreq,
            'lastmod' => $lastmod,
        ];
    }
}
