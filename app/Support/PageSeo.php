<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\ConferencePackage;
use App\Models\DiningVenue;
use App\Models\GalleryItem;
use App\Models\HeroSlide;
use App\Models\Offer;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * What a search engine and a social network are told about a page.
 *
 * This has to be built on the server. Google will run the site's JavaScript and
 * see the title a page sets for itself, but the scrapers behind a link preview -
 * WhatsApp, Facebook, LinkedIn, Slack, iMessage - run nothing at all, so they read
 * the HTML exactly as it arrives. A share card assembled in the browser shows them
 * nothing, which is how a page can look finished and still unfurl as a bare grey
 * link when somebody sends it to a friend.
 *
 * So there is one description of each page, written here: rendered into the
 * initial HTML by the root Blade template, and handed to the front end as a shared
 * prop so the document title keeps up during client-side navigation and the two
 * cannot drift apart. The wording is the wording the pages already used - this
 * moved it, it did not rewrite it.
 */
class PageSeo
{
    /**
     * Sits between the page's own title and the hotel's name.
     */
    public const SEPARATOR = ' · ';

    /**
     * The pages that ask to be kept out of search results.
     *
     * The booking flow is a transaction, not content: there is nothing on it a
     * guest would want to find in a search result, and the confirmation page
     * carries a reference that has no business being indexed.
     */
    private const NOINDEX = [
        'site.booking.index',
        'site.booking.create',
        'site.booking.show',
    ];

    /**
     * Everything a crawler or a link preview needs, or null for a page that is not
     * part of the public site.
     *
     * @return array{title: string, description: string, canonical: string, image: string, imageAlt: string, robots: string, type: string}|null
     */
    public static function for(Request $request): ?array
    {
        $name = $request->route()?->getName();

        if (! is_string($name)) {
            return null;
        }

        $site = SitePresenter::settings();
        $page = self::page($name, $request, $site);

        if ($page === null) {
            return null;
        }

        $image = self::picture($page['image'] ?? null, $site);

        return [
            'title' => $page['title'].self::SEPARATOR.$site['name'],
            'description' => $page['description'],
            'canonical' => $request->url(),
            'image' => $image['url'],
            'imageAlt' => $image['alt'],
            'robots' => in_array($name, self::NOINDEX, true)
                ? 'noindex, follow'
                : 'index, follow',
            'type' => $name === 'home' ? 'website' : 'article',
        ];
    }

    /**
     * The title, description and photograph for one route.
     *
     * @param  array<string, mixed>  $site
     * @return array{title: string, description: string, image?: ?Media}|null
     */
    private static function page(string $name, Request $request, array $site): ?array
    {
        $address = $site['name'].', Senga Bay, Salima';

        return match ($name) {
            'home' => [
                'title' => $site['tagline'],
                'description' => $site['name'].' in Senga Bay, Salima. Rooms, suites, lakeside '
                    .'chalets, dining, conferences and lake activities on Lake Malawi.',
                'image' => self::firstMedia(HeroSlide::query()->where('is_active', true)->first(), 'image'),
            ],
            'site.rooms.index' => [
                'title' => 'Rooms & Suites',
                'description' => 'Rooms, executive suites, family rooms and lakeside chalets at '
                    .$address.'.',
                'image' => self::firstMedia(RoomType::query()->first(), 'cover'),
            ],
            'site.rooms.show' => self::room($request, $address),
            'site.dining' => [
                'title' => 'Restaurant & Dining',
                'description' => 'Dining at '.$address.': chambo and tilapia from Lake Malawi, a '
                    .'terrace restaurant, the Anchor Bar and a poolside bar.',
                'image' => self::firstMedia(DiningVenue::query()->first(), 'cover'),
            ],
            'site.activities' => [
                'title' => 'Activities & Experiences',
                'description' => 'Lake Malawi activities at Senga Bay: sunset cruises, fishing '
                    .'trips, snorkelling, kayaking, village walks and team building with Lakeside Hotel.',
                'image' => self::firstMedia(Activity::query()->first(), 'cover'),
            ],
            'site.events' => [
                'title' => 'Conferences & Events',
                'description' => 'Conference facilities and event packages at '.$address.'. '
                    .'Meetings, corporate retreats, weddings and private events on Lake Malawi.',
                'image' => self::firstMedia(ConferencePackage::query()->first(), 'cover'),
            ],
            'site.gallery' => [
                'title' => 'Gallery',
                'description' => 'Photographs and video from Lakeside Hotel and Conference Centre '
                    .'in Senga Bay, Salima: the lake, rooms, dining, activities and events.',
                'image' => self::firstMedia(GalleryItem::query()->where('is_active', true)->first(), 'image'),
            ],
            'site.offers' => [
                'title' => 'Special Offers',
                'description' => 'Special offers at '.$address.': weekend specials, stay 3 pay 2, '
                    .'honeymoon packages, corporate rates and green season discounts.',
                'image' => self::firstMedia(Offer::query()->first(), 'image'),
            ],
            'site.about' => [
                'title' => 'About us',
                'description' => 'About '.$site['name'].': a lakeside hotel and conference centre on '
                    .'the shore of Lake Malawi at Senga Bay, Salima.',
                // The second hero: the first is the homepage's lead photograph.
                'image' => self::firstMedia(
                    HeroSlide::query()->where('is_active', true)->skip(1)->first(),
                    'image',
                ),
            ],
            'site.policies' => [
                'title' => 'Booking policies',
                'description' => 'Check in and check out times, deposits, cancellation policy, '
                    .'child policy and airport transfers at '.$address.'.',
            ],
            'site.contact' => [
                'title' => 'Contact us',
                'description' => 'Contact '.$site['name'].' in Senga Bay, Salima: phone, WhatsApp, '
                    .'email, directions and a contact form.',
                'image' => self::firstMedia(
                    GalleryItem::query()->where('title', 'The reception')->first(),
                    'image',
                ),
            ],
            'site.booking.index' => [
                'title' => 'Book your stay',
                'description' => 'Check availability and book a room at '.$address.'.',
            ],
            'site.booking.create' => [
                'title' => 'Your details',
                'description' => 'Confirm your stay at Lakeside Hotel and Conference Centre.',
            ],
            'site.booking.show' => [
                'title' => 'Your booking',
                'description' => 'Your booking at Lakeside Hotel, Senga Bay.',
            ],
            default => null,
        };
    }

    /**
     * A room page describes that room, not rooms in general.
     *
     * @return array{title: string, description: string, image?: ?Media}
     */
    private static function room(Request $request, string $address): array
    {
        $roomType = self::boundModel($request, 'roomType', RoomType::class, 'slug');

        if (! $roomType instanceof RoomType) {
            return [
                'title' => 'Rooms & Suites',
                'description' => 'Rooms, suites and lakeside chalets at '.$address.'.',
            ];
        }

        return [
            'title' => $roomType->name,
            'description' => (string) ($roomType->tagline ?: $roomType->description),
            'image' => self::firstMedia($roomType, 'cover'),
        ];
    }

    /**
     * The route-bound model, whether or not the binding has been substituted yet.
     *
     * Shared props are built before route model binding has necessarily run, so
     * the parameter may still be the raw slug from the URL.
     *
     * @param  class-string  $model
     */
    private static function boundModel(Request $request, string $key, string $model, string $column): mixed
    {
        $parameter = $request->route()?->parameter($key);

        if ($parameter instanceof $model) {
            return $parameter;
        }

        return is_string($parameter)
            ? $model::query()->where($column, $parameter)->first()
            : null;
    }

    private static function firstMedia(mixed $model, string $collection): ?Media
    {
        return $model instanceof HasMedia
            ? $model->getFirstMedia($collection)
            : null;
    }

    /**
     * An absolute URL and something to say about the picture.
     *
     * Absolute, because a scraper reading a share card has no page to resolve a
     * relative path against.
     *
     * @param  array<string, mixed>  $site
     * @return array{url: string, alt: string}
     */
    private static function picture(?Media $media, array $site): array
    {
        $place = $site['name'].', Senga Bay, Lake Malawi';
        $presented = SitePresenter::media($media);

        if ($presented === null) {
            return [
                'url' => $site['url'].SitePresenter::FALLBACK_IMAGE,
                'alt' => $place,
            ];
        }

        $alt = trim((string) $presented['alt']);

        /*
         * `SitePresenter::media` falls back to the media's name when nobody has
         * described the picture, and the name is the file name. That is fine as a
         * label in a staff screen and useless here: a share card was announcing
         * the photograph as "real-room-double-head-on". Where there is no real
         * description, say where the picture was taken instead.
         */
        if ($alt === '' || $alt === $media->name || $alt === $media->file_name) {
            $alt = $place;
        }

        return [
            'url' => $site['url'].$presented['hero'],
            'alt' => $alt,
        ];
    }
}
