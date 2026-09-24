<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\HotelMetrics;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The operational overview: today, at a glance.
 *
 * Deliberately a read model rather than a set of queries in the controller, so
 * the reporting module can ask the same questions later and get the same answers.
 */
class DashboardController extends Controller
{
    public function __invoke(HotelMetrics $metrics): Response
    {
        return Inertia::render('dashboard', [
            'overview' => $metrics->overview(),
        ]);
    }
}
