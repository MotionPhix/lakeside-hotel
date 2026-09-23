<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Support\SitePresenter;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    /**
     * Special offers that are running right now.
     */
    public function index(): Response
    {
        return Inertia::render('public/offers', [
            'offers' => SitePresenter::collection(
                Offer::query()->live()->get(),
                'offer',
            ),
        ]);
    }
}
