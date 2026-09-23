<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Support\SitePresenter;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    /**
     * The photo and video gallery, with the categories actually in use.
     */
    public function index(): Response
    {
        /** @var Collection<int, GalleryItem> $items */
        $items = GalleryItem::query()->active()->get();

        return Inertia::render('public/gallery', [
            'items' => SitePresenter::collection($items, 'galleryItem'),
            'categories' => $items->pluck('category')->unique()->sort()->values()->all(),
        ]);
    }
}
