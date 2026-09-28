<?php

namespace App\Models\Concerns;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The three image sizes the website actually asks for.
 *
 * - `thumb`  600x400   small cards, related items and gallery tiles
 * - `card`  1000x750   room and offer cards on listing pages
 * - `hero`  1920x1080  full-bleed banners and room detail headers
 *
 * Each is a fixed size, and that is the point of them: the layouts put these in
 * boxes of a known shape, and a browser can only reserve the right space before
 * an image arrives if the size it is told is the size it gets.
 *
 * The dimensions go in the `fit()` call, not in `width()`/`height()`. Those two
 * are separate rescales - each one preserving the aspect ratio - and the second
 * overwrites the first, so `width(1000)->height(750)->fit(Fit::Crop)` silently
 * produced nothing of the sort: every card came out 750 tall and as wide as its
 * own aspect ratio made it, from 625px for a portrait source to 1429px for a wide
 * one. A 625-wide card then had to be stretched to fill a 4:3 box. `fit()` with
 * no dimensions attached is a no-op, which is what made it look plausible.
 *
 * Conversions run inline (`nonQueued`) because the host this ships to has no
 * queue worker.
 *
 * Registering nothing when `media-library.generate_conversions` is off is what
 * keeps the test suite quick: it seeds the database dozens of times a run, and
 * building these sizes for every photograph would add minutes to it to produce
 * files no assertion ever looks at.
 */
trait RegistersImageConversions
{
    public function registerMediaConversions(?Media $media = null): void
    {
        if (! config('media-library.generate_conversions')) {
            return;
        }

        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 600, 400)
            ->nonQueued();

        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 1000, 750)
            ->nonQueued();

        $this->addMediaConversion('hero')
            ->fit(Fit::Crop, 1920, 1080)
            ->nonQueued();
    }
}
