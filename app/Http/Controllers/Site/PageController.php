<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\NearbyAttraction;
use App\Support\SitePresenter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pages whose copy lives in editable content blocks rather than a table of their
 * own.
 */
class PageController extends Controller
{
    /**
     * The story of the hotel and what surrounds it.
     */
    public function about(): Response
    {
        return Inertia::render('public/about', [
            'about' => SitePresenter::contentBlock(ContentBlock::forKey('about')),
            'lake' => SitePresenter::contentBlock(ContentBlock::forKey('about.lake')),
            'attractions' => SitePresenter::collection(
                NearbyAttraction::query()->active()->get(),
                'nearbyAttraction',
            ),
        ]);
    }

    /**
     * Booking terms, check in times and the cancellation policy.
     */
    public function policies(): Response
    {
        return Inertia::render('public/policies', [
            'policies' => SitePresenter::contentBlock(ContentBlock::forKey('booking.policies')),
            'transfers' => SitePresenter::contentBlock(ContentBlock::forKey('transfers')),
        ]);
    }
}
