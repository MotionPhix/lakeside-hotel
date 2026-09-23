<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\RoomType;
use App\Support\SitePresenter;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    /**
     * Every room category on sale.
     */
    public function index(): Response
    {
        return Inertia::render('public/rooms/index', [
            'roomTypes' => SitePresenter::roomTypes(
                RoomType::query()->active()->with('amenities')->get(),
                true,
            ),
            'amenities' => SitePresenter::amenities(Amenity::query()->active()->get()),
        ]);
    }

    /**
     * One room category in full.
     */
    public function show(RoomType $roomType): Response
    {
        abort_unless($roomType->is_active, 404);

        return Inertia::render('public/rooms/show', [
            'roomType' => SitePresenter::roomType($roomType->load('amenities'), true),
            'otherRoomTypes' => SitePresenter::roomTypes(
                RoomType::query()
                    ->active()
                    ->whereKeyNot($roomType->getKey())
                    ->with('amenities')
                    ->limit(3)
                    ->get(),
                true,
            ),
        ]);
    }
}
