<?php

namespace App\Http\Controllers\Site;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreInquiryRequest;
use App\Models\Inquiry;
use App\Models\NearbyAttraction;
use App\Support\SitePresenter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * How to reach the hotel, and how to find it.
     */
    public function show(): Response
    {
        return Inertia::render('public/contact', [
            'attractions' => SitePresenter::collection(
                NearbyAttraction::query()->active()->get(),
                'nearbyAttraction',
            ),
        ]);
    }

    /**
     * Record a message from the website so the events team can reply to it.
     */
    public function store(StoreInquiryRequest $request): RedirectResponse
    {
        Inquiry::query()->create([
            ...$request->validated(),
            'type' => InquiryType::General,
            'status' => InquiryStatus::New,
            'source_page' => '/contact',
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Thank you - our reservations team will reply shortly.'),
        ]);

        return back();
    }
}
