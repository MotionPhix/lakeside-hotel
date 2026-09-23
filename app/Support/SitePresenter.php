<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\Amenity;
use App\Models\ConferenceHall;
use App\Models\ConferencePackage;
use App\Models\ContentBlock;
use App\Models\DiningVenue;
use App\Models\GalleryItem;
use App\Models\HeroSlide;
use App\Models\MenuItem;
use App\Models\NearbyAttraction;
use App\Models\Offer;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Maps Eloquent models onto the shapes the public website consumes.
 *
 * Images are always resolved here and handed over as ready URLs, so no component
 * needs to know about the media library or the bucket disk.
 *
 * The TypeScript counterparts live in `resources/js/types/hotel.ts`. If a field
 * is added here, add it there too.
 */
class SitePresenter
{
    /**
     * The default social share image, used when a page has no picture of its own.
     */
    public const FALLBACK_IMAGE = '/bucket/lakeside_hotel_logo.png';

    /**
     * Hotel details, booking rules and social links, read in a single query.
     *
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        $values = Setting::query()->pluck('value', 'key')->all();

        $get = fn (string $key, string $default = ''): string => (string) ($values[$key] ?? $default);

        $whatsapp = preg_replace('/[^0-9]/', '', $get('hotel.whatsapp')) ?? '';

        return [
            'name' => $get('hotel.name', 'Lakeside Hotel and Conference Centre'),
            'tagline' => $get('hotel.tagline', 'Your Lakeside Escape in Senga Bay'),
            'contact' => [
                'address' => $get('hotel.address'),
                'phone' => $get('hotel.phone'),
                'whatsapp' => $get('hotel.whatsapp'),
                'email' => $get('hotel.email'),
                'events_email' => $get('hotel.events_email'),
                'latitude' => (float) $get('hotel.latitude', '-13.7167'),
                'longitude' => (float) $get('hotel.longitude', '34.6167'),
                'map_zoom' => (int) $get('hotel.map_zoom', '14'),
                'check_in_time' => $get('hotel.check_in_time', '14:00'),
                'check_out_time' => $get('hotel.check_out_time', '11:00'),
            ],
            'booking' => [
                'currency' => $get('hotel.currency', 'MWK'),
                'vat_rate' => (float) $get('booking.vat_rate', '16.5'),
                'tourism_levy_rate' => (float) $get('booking.tourism_levy_rate', '1'),
                'deposit_percentage' => (int) $get('booking.deposit_percentage', '50'),
                'online_payment_enabled' => $get('booking.online_payment_enabled', '1') === '1',
                'pay_at_hotel_enabled' => $get('booking.pay_at_hotel_enabled', '1') === '1',
                'cancellation_policy' => $get('booking.cancellation_policy'),
                'child_policy' => $get('booking.child_policy'),
                'transfer_note' => $get('booking.transfer_note'),
            ],
            'social' => [
                'facebook' => $get('social.facebook'),
                'instagram' => $get('social.instagram'),
                'tripadvisor' => $get('social.tripadvisor'),
            ],
            'logo' => '/bucket/lakeside_hotel_logo.png',
            'whatsapp_link' => $whatsapp === '' ? '' : 'https://wa.me/'.$whatsapp,
            'url' => rtrim((string) config('app.url'), '/'),
        ];
    }

    /**
     * schema.org structured data describing the hotel.
     *
     * Built here rather than in the Blade partial on purpose: Blade treats a
     * literal `@context` key as a directive and replaces it with compiled PHP,
     * which silently drops the required context and leaves the markup useless to
     * search engines.
     *
     * @return array<string, mixed>
     */
    public static function structuredData(): array
    {
        $site = self::settings();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Hotel',
            'name' => $site['name'],
            'description' => $site['tagline'],
            'url' => $site['url'],
            'logo' => $site['url'].$site['logo'],
            'image' => $site['url'].$site['logo'],
            'telephone' => $site['contact']['phone'],
            'email' => $site['contact']['email'],
            'currenciesAccepted' => $site['booking']['currency'],
            'checkinTime' => $site['contact']['check_in_time'],
            'checkoutTime' => $site['contact']['check_out_time'],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $site['contact']['address'],
                'addressLocality' => 'Senga Bay, Salima',
                'addressRegion' => 'Salima',
                'addressCountry' => 'MW',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $site['contact']['latitude'],
                'longitude' => $site['contact']['longitude'],
            ],
            'amenityFeature' => Amenity::query()
                ->active()
                ->get()
                ->map(fn (Amenity $amenity): array => [
                    '@type' => 'LocationFeatureSpecification',
                    'name' => $amenity->name,
                    'value' => true,
                ])
                ->all(),
            'sameAs' => array_values(array_filter([
                $site['social']['facebook'],
                $site['social']['instagram'],
                $site['social']['tripadvisor'],
            ])),
        ];
    }

    /**
     * A single media item as a set of ready URLs.
     *
     * @return array<string, mixed>|null
     */
    public static function media(?Media $media): ?array
    {
        if (! $media instanceof Media) {
            return null;
        }

        $url = function (string $conversion) use ($media): string {
            // Falls back to the original when a conversion has not been generated
            // yet, so a page never renders a broken image.
            return $media->hasGeneratedConversion($conversion)
                ? $media->getUrl($conversion)
                : $media->getUrl();
        };

        return [
            'id' => $media->getKey(),
            'alt' => (string) ($media->getCustomProperty('alt') ?? $media->name),
            'url' => $media->getUrl(),
            'thumb' => $url('thumb'),
            'card' => $url('card'),
            'hero' => $url('hero'),
        ];
    }

    /**
     * The first image in a collection, presented.
     *
     * @return array<string, mixed>|null
     */
    public static function cover(HasMedia $model, string $collection = 'cover'): ?array
    {
        return self::media($model->getFirstMedia($collection));
    }

    /**
     * Every image in a collection, presented.
     *
     * @return list<array<string, mixed>>
     */
    public static function gallery(HasMedia $model, string $collection = 'images'): array
    {
        return array_values(
            $model->getMedia($collection)
                ->map(fn (Media $media): ?array => self::media($media))
                ->filter()
                ->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function heroSlide(HeroSlide $slide): array
    {
        return [
            'id' => $slide->getKey(),
            'headline' => $slide->headline,
            'subheadline' => $slide->subheadline,
            'cta_label' => $slide->cta_label,
            'cta_url' => $slide->cta_url,
            'secondary_cta_label' => $slide->secondary_cta_label,
            'secondary_cta_url' => $slide->secondary_cta_url,
            'image' => self::cover($slide, 'image'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function contentBlock(?ContentBlock $block): ?array
    {
        if (! $block instanceof ContentBlock) {
            return null;
        }

        return [
            'key' => $block->key,
            'title' => $block->title,
            'subtitle' => $block->subtitle,
            'body' => $block->body,
            'image' => self::cover($block, 'image'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function amenity(Amenity $amenity): array
    {
        return [
            'id' => $amenity->getKey(),
            'name' => $amenity->name,
            'slug' => $amenity->slug,
            'icon' => $amenity->icon,
            'description' => $amenity->description,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function amenities(Collection $amenities): array
    {
        return array_values($amenities->map(fn (Amenity $amenity): array => self::amenity($amenity))->all());
    }

    /**
     * @return array<string, mixed>
     */
    public static function roomType(RoomType $roomType, bool $withDetail = false): array
    {
        $payload = [
            'id' => $roomType->getKey(),
            'name' => $roomType->name,
            'slug' => $roomType->slug,
            'tagline' => $roomType->tagline,
            'description' => $roomType->description,
            'capacity_adults' => $roomType->capacity_adults,
            'capacity_children' => $roomType->capacity_children,
            'max_occupancy' => $roomType->maxOccupancy(),
            'size_sqm' => $roomType->size_sqm,
            'bed_configuration' => $roomType->bed_configuration,
            'from_price' => $roomType->fromPrice(),
            'base_price' => (string) $roomType->base_price,
            'weekend_price' => $roomType->weekend_price === null ? null : (string) $roomType->weekend_price,
            'min_nights' => $roomType->min_nights,
            'is_featured' => $roomType->is_featured,
            'cover' => self::cover($roomType),
            'images' => self::gallery($roomType),
            'amenities' => self::amenities($roomType->amenities),
        ];

        if ($withDetail) {
            $payload['total_rooms'] = $roomType->rooms()->count();
            $payload['url'] = route('site.rooms.show', $roomType);
        }

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function roomTypes(Collection $roomTypes, bool $withDetail = false): array
    {
        return array_values(
            $roomTypes->map(fn (RoomType $roomType): array => self::roomType($roomType, $withDetail))->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function menuItem(MenuItem $item): array
    {
        return [
            'id' => $item->getKey(),
            'name' => $item->name,
            'description' => $item->description,
            'price' => (string) $item->price,
            'category' => $item->category,
            'is_signature' => $item->is_signature,
            'is_vegetarian' => $item->is_vegetarian,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function diningVenue(DiningVenue $venue, bool $withMenu = false): array
    {
        $payload = [
            'id' => $venue->getKey(),
            'name' => $venue->name,
            'slug' => $venue->slug,
            'type' => $venue->type,
            'tagline' => $venue->tagline,
            'description' => $venue->description,
            'opening_hours' => $venue->opening_hours,
            'dress_code' => $venue->dress_code,
            'cover' => self::cover($venue),
            'gallery' => self::gallery($venue, 'gallery'),
            'signature_dishes' => [],
            'menu' => [],
        ];

        if ($withMenu) {
            $items = $venue->menuItems()->available()->get();

            $payload['signature_dishes'] = array_values(
                $items->where('is_signature', true)
                    ->map(fn (MenuItem $item): array => self::menuItem($item))
                    ->all(),
            );

            // Grouped by menu section, in the order the sections are declared.
            $payload['menu'] = array_values(
                $items->groupBy('category')
                    ->map(fn (Collection $group, string $category): array => [
                        'key' => $category,
                        'label' => ucfirst($category),
                        'items' => array_values($group->map(fn (MenuItem $item): array => self::menuItem($item))->all()),
                    ])
                    ->all(),
            );
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function activity(Activity $activity): array
    {
        return [
            'id' => $activity->getKey(),
            'name' => $activity->name,
            'slug' => $activity->slug,
            'description' => $activity->description,
            'duration' => $activity->durationForHumans(),
            'duration_minutes' => $activity->duration_minutes,
            'price' => $activity->price === null ? null : (string) $activity->price,
            'price_basis' => $activity->price_basis,
            'is_complimentary' => $activity->isComplimentary(),
            'min_participants' => $activity->min_participants,
            'max_participants' => $activity->max_participants,
            'cover' => self::cover($activity),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function conferencePackage(ConferencePackage $package): array
    {
        return [
            'id' => $package->getKey(),
            'name' => $package->name,
            'slug' => $package->slug,
            'type' => $package->type,
            'tagline' => $package->tagline,
            'description' => $package->description,
            'capacity_min' => $package->capacity_min,
            'capacity_max' => $package->capacity_max,
            'capacity_label' => $package->capacityForHumans(),
            'price' => (string) $package->price,
            'price_basis' => $package->price_basis,
            'includes' => array_values($package->includes ?? []),
            'cover' => self::cover($package),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function conferenceHall(ConferenceHall $hall): array
    {
        return [
            'id' => $hall->getKey(),
            'name' => $hall->name,
            'slug' => $hall->slug,
            'capacity' => $hall->capacity,
            'capacity_label' => $hall->capacityForHumans(),
            'layout' => $hall->layout,
            'description' => $hall->description,
            'features' => array_values($hall->features ?? []),
            'cover' => self::cover($hall),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function galleryItem(GalleryItem $item): array
    {
        return [
            'id' => $item->getKey(),
            'title' => $item->title,
            'caption' => $item->caption,
            'category' => $item->category,
            'type' => $item->type,
            'video_url' => $item->video_url,
            'is_video' => $item->isVideo(),
            'image' => self::cover($item, 'image') ?? self::cover($item, 'video'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function testimonial(Testimonial $testimonial): array
    {
        return [
            'id' => $testimonial->getKey(),
            'guest_name' => $testimonial->guest_name,
            'guest_country' => $testimonial->guest_country,
            'rating' => $testimonial->rating,
            'stars' => $testimonial->stars(),
            'title' => $testimonial->title,
            'quote' => $testimonial->quote,
            'stayed_on' => $testimonial->stayed_on?->toFormattedDateString(),
            'source' => $testimonial->source,
            'response' => $testimonial->response,
            'avatar' => self::cover($testimonial, 'avatar'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function offer(Offer $offer): array
    {
        return [
            'id' => $offer->getKey(),
            'title' => $offer->title,
            'slug' => $offer->slug,
            'subtitle' => $offer->subtitle,
            'description' => $offer->description,
            'highlight' => $offer->highlight,
            'discount_label' => $offer->discount_label,
            'type' => $offer->type,
            'starts_on' => $offer->starts_on?->toFormattedDateString(),
            'ends_on' => $offer->ends_on?->toFormattedDateString(),
            'coupon_code' => $offer->coupon_code,
            'terms' => $offer->terms,
            'is_featured' => $offer->is_featured,
            'image' => self::cover($offer, 'image'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function nearbyAttraction(NearbyAttraction $attraction): array
    {
        return [
            'id' => $attraction->getKey(),
            'name' => $attraction->name,
            'category' => $attraction->category,
            'description' => $attraction->description,
            'distance_km' => $attraction->distance_km,
            'travel_time_minutes' => $attraction->travel_time_minutes,
            'image' => self::cover($attraction, 'image'),
        ];
    }

    /**
     * Present a collection of models with a presenter method.
     *
     * @param  Collection<int, mixed>  $models
     * @return list<array<string, mixed>>
     */
    public static function collection(Collection $models, string $method): array
    {
        return array_values(
            $models->map(fn (mixed $model): array => self::$method($model))->all(),
        );
    }
}
