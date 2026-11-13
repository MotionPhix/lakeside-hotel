<?php

namespace Database\Seeders;

use App\Models\DiningVenue;
use Database\Seeders\Concerns\AttachesPhotographs;
use Illuminate\Database\Seeder;

class DiningPhotographySeeder extends Seeder
{
    use AttachesPhotographs;

    public function run(): void
    {
        $photographs = [
            'lakeview-restaurant' => ['real-restaurant.jpg', 'A table overlooking Lake Malawi at the Lakeview Restaurant'],
            'bar-and-lounge' => ['real-bar.jpg', 'The bar and lounge at Lakeside Hotel'],
            'poolside-bar' => ['real-pool-day.jpg', 'The pool and lakeside seating at Lakeside Hotel'],
        ];

        foreach ($photographs as $slug => [$file, $alt]) {
            $venue = DiningVenue::query()->where('slug', $slug)->first();

            // Fill missing covers without replacing photographs chosen in the CMS.
            if ($venue === null || $venue->hasMedia('cover')) {
                continue;
            }

            $this->attachPhotograph($venue, $file, 'cover', $alt);
        }
    }
}
