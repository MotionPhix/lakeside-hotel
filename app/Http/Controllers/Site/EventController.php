<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ConferencePackage;
use App\Models\ContentBlock;
use App\Support\SitePresenter;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * Conference, retreat, wedding and private event packages, grouped by the
     * kind of gathering they suit.
     */
    public function index(): Response
    {
        $packages = ConferencePackage::query()->active()->get();

        $for = fn (array $types): array => SitePresenter::collection(
            $packages->filter(fn (ConferencePackage $package): bool => in_array($package->type, $types, true))->values(),
            'conferencePackage',
        );

        return Inertia::render('public/events', [
            'intro' => SitePresenter::contentBlock(ContentBlock::forKey('conferences.intro')),
            'conferencePackages' => $for(['conference', 'corporate_retreat']),
            'weddingPackages' => $for(['wedding', 'private_event']),
            'totalPackages' => $packages->count(),
            'largestCapacity' => (int) $packages->max('capacity_max'),
        ]);
    }
}
