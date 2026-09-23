<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Builds a complete, browsable Lakeside Hotel: the staff team, the website
 * content, the accommodation and rates, the dining and experiences, the
 * marketing material, and a working set of guests and reservations.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            TeamSeeder::class,
            SiteContentSeeder::class,
            AccommodationSeeder::class,
            ExperienceSeeder::class,
            MarketingSeeder::class,
            GuestSeeder::class,
        ]);
    }
}
