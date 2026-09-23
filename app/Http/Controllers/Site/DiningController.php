<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\DiningVenue;
use App\Support\SitePresenter;
use Inertia\Inertia;
use Inertia\Response;

class DiningController extends Controller
{
    /**
     * The restaurants and bars, each with its full menu.
     */
    public function index(): Response
    {
        return Inertia::render('public/dining', [
            'intro' => SitePresenter::contentBlock(ContentBlock::forKey('dining.intro')),
            'venues' => array_values(
                DiningVenue::query()
                    ->active()
                    ->get()
                    ->map(fn (DiningVenue $venue): array => SitePresenter::diningVenue($venue, true))
                    ->all(),
            ),
        ]);
    }
}
