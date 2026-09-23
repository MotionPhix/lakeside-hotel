<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Support\SitePresenter;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    /**
     * Everything guests can do on the lake and around the bay.
     */
    public function index(): Response
    {
        return Inertia::render('public/activities', [
            'activities' => SitePresenter::collection(
                Activity::query()->active()->get(),
                'activity',
            ),
        ]);
    }
}
