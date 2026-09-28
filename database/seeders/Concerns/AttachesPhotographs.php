<?php

namespace Database\Seeders\Concerns;

use Spatie\MediaLibrary\HasMedia;

/**
 * Putting the photographs that ship with the project onto the models.
 *
 * The photographs live in `database/seeders/photography` so that a checkout has
 * everything it needs to seed a fully illustrated site, without reaching for a
 * remote address that might be slow, changed, or gone.
 *
 * `preservingOriginal` is the part worth not getting wrong twice. Left off, the
 * media library moves the file it is handed onto the disk and deletes the
 * original, so seeding would consume the very photographs the repository is
 * there to provide: the site would come up illustrated once, and every seed
 * after that would silently find nothing left to attach.
 */
trait AttachesPhotographs
{
    /**
     * Attach a photograph from the seeded set, replacing whatever was there.
     *
     * Cleared before adding rather than added to, because these are single-file
     * collections: a second file on one of them throws. Clearing first also means
     * re-seeding after a photograph is swapped leaves the new one in place rather
     * than the one it replaced.
     */
    protected function attachPhotograph(HasMedia $model, ?string $file, string $collection = 'image'): void
    {
        if ($file === null) {
            return;
        }

        $path = database_path('seeders/photography/'.$file);

        // A missing file is not worth failing a seed over - the rest of the site
        // is still worth having - and the pages that use it fall back to their own
        // layout rather than to a broken image.
        if (! is_file($path)) {
            return;
        }

        $model->clearMediaCollection($collection);
        $model->addMedia($path)->preservingOriginal()->toMediaCollection($collection);
    }
}
