<?php

namespace App\Models\Concerns;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The three image sizes the website actually asks for.
 *
 * - `thumb`  small cards, related items and gallery tiles
 * - `card`   room and offer cards on listing pages
 * - `hero`   full-bleed banners and room detail headers
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
            ->width(600)
            ->height(400)
            ->fit(Fit::Crop)
            ->nonQueued();

        $this->addMediaConversion('card')
            ->width(1000)
            ->height(750)
            ->fit(Fit::Crop)
            ->nonQueued();

        $this->addMediaConversion('hero')
            ->width(1920)
            ->height(1080)
            ->fit(Fit::Crop)
            ->nonQueued();
    }
}
