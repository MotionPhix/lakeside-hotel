<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Amenity;
use App\Models\ConferencePackage;
use App\Models\ContentBlock;
use App\Models\DiningVenue;
use App\Models\GalleryItem;
use App\Models\HeroSlide;
use App\Models\NearbyAttraction;
use App\Models\Offer;
use App\Models\RoomType;
use App\Models\Testimonial;
use App\Support\SitePresenter;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The homepage. Every section is driven by content the hotel can edit in the
 * dashboard, so nothing here is hard coded.
 */
class HomeController extends Controller
{
    /**
     * Show the homepage.
     */
    public function index(): Response
    {
        return Inertia::render('public/home', [
            'heroSlides' => SitePresenter::collection(
                HeroSlide::query()->active()->get(),
                'heroSlide',
            ),
            'about' => SitePresenter::contentBlock(ContentBlock::forKey('about')),
            // Every bookable category, for the booking bar's room selector. The
            // showcase below uses only the featured ones, but a guest must be
            // able to ask for any room the hotel actually sells.
            'bookableRoomTypes' => $this->bookableRoomTypes(),
            'roomTypes' => SitePresenter::roomTypes($this->featuredRoomTypes()),
            'amenities' => SitePresenter::amenities(
                Amenity::query()->active()->category('general')->limit(9)->get(),
            ),
            'diningVenues' => $this->diningVenues(),
            'activities' => SitePresenter::collection(
                Activity::query()->active()->featured()->limit(6)->get(),
                'activity',
            ),
            'conferencePackages' => SitePresenter::collection(
                ConferencePackage::query()->active()->featured()->get(),
                'conferencePackage',
            ),
            'gallery' => SitePresenter::collection(
                GalleryItem::query()->active()->featured()->limit(8)->get(),
                'galleryItem',
            ),
            'testimonials' => SitePresenter::collection(
                Testimonial::query()->approved()->featured()->limit(6)->get(),
                'testimonial',
            ),
            'offers' => SitePresenter::collection(
                Offer::query()->live()->featured()->limit(3)->get(),
                'offer',
            ),
            'attractions' => SitePresenter::collection(
                NearbyAttraction::query()->active()->limit(6)->get(),
                'nearbyAttraction',
            ),
        ]);
    }

    /**
     * The room categories to showcase, preferring the ones flagged for the
     * homepage and falling back to the first few so the section is never empty.
     *
     * @return Collection<int, RoomType>
     */
    private function featuredRoomTypes(): Collection
    {
        $featured = RoomType::query()
            ->active()
            ->featured()
            ->with('amenities')
            ->get();

        return $featured->isNotEmpty()
            ? $featured
            : RoomType::query()->active()->with('amenities')->limit(6)->get();
    }

    /**
     * The slug and name of every room category currently on sale.
     *
     * @return list<array{slug: string, name: string}>
     */
    private function bookableRoomTypes(): array
    {
        return RoomType::query()
            ->active()
            ->get(['slug', 'name'])
            ->map(fn (RoomType $roomType): array => [
                'slug' => $roomType->slug,
                'name' => $roomType->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Each venue with its signature dishes, for the dining strip.
     *
     * @return list<array<string, mixed>>
     */
    private function diningVenues(): array
    {
        return array_values(
            DiningVenue::query()
                ->active()
                ->get()
                ->map(fn (DiningVenue $venue): array => SitePresenter::diningVenue($venue, true))
                ->all(),
        );
    }
}
