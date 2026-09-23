<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\ConferenceHall;
use App\Models\ConferencePackage;
use App\Models\ContentBlock;
use App\Support\SitePresenter;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * The conference halls, the facility list, and the packages sold for
     * conferences, retreats, weddings and private events.
     */
    public function index(): Response
    {
        $packages = ConferencePackage::query()->active()->get();
        $halls = ConferenceHall::query()->active()->get();

        $for = fn (array $types): array => SitePresenter::collection(
            $packages->filter(fn (ConferencePackage $package): bool => in_array($package->type, $types, true))->values(),
            'conferencePackage',
        );

        return Inertia::render('public/events', [
            'intro' => SitePresenter::contentBlock(ContentBlock::forKey('conferences.intro')),
            'halls' => SitePresenter::collection($halls, 'conferenceHall'),
            'facilities' => SitePresenter::amenities(
                Amenity::query()->active()->category('conference')->get(),
            ),
            'conferencePackages' => $for(['conference', 'corporate_retreat']),
            'weddingPackages' => $for(['wedding', 'private_event']),
            'totalPackages' => $packages->count(),
            'largestCapacity' => max(
                (int) $packages->max('capacity_max'),
                (int) $halls->max('capacity'),
            ),
        ]);
    }
}
