<?php

namespace Database\Seeders;

use App\Models\SiteSection;
use Illuminate\Database\Seeder;

/**
 * The default arrangement of the public pages.
 *
 * The frontend walks these rows, so every heading here is editable in the
 * dashboard and every section can be reordered, retitled or switched off. The
 * `config` column carries per-section settings such as how many items to show.
 */
class SiteSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->sections() as $index => $section) {
            SiteSection::query()->updateOrCreate(
                ['page' => $section['page'], 'key' => $section['key']],
                $section + ['sort_order' => $index + 1],
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sections(): array
    {
        return [
            [
                'page' => 'home',
                'key' => 'hero',
                'eyebrow' => 'Senga Bay · Lake Malawi',
                'title' => null,
                'description' => null,
                'config' => null,
                'is_active' => true,
            ],
            [
                'page' => 'home',
                'key' => 'booking',
                'eyebrow' => null,
                'title' => null,
                'description' => null,
                'config' => null,
                'is_active' => true,
            ],
            [
                'page' => 'home',
                'key' => 'about',
                'eyebrow' => 'Welcome',
                'title' => 'A lakeside retreat in Senga Bay',
                'description' => 'Warm Malawian hospitality on the shores of Lake Malawi, since 1998.',
                'config' => null,
                'is_active' => true,
            ],
            [
                'page' => 'home',
                'key' => 'accommodation',
                'eyebrow' => 'Accommodation',
                'title' => 'Rooms, suites and lakeside chalets',
                'description' => 'Forty two rooms, from standard doubles to executive suites, each with a view of the lake.',
                'config' => ['limit' => 6],
                'is_active' => true,
            ],
            [
                'page' => 'home',
                'key' => 'amenities',
                'eyebrow' => 'Facilities',
                'title' => 'Everything you need on site',
                'description' => null,
                'config' => ['limit' => 9, 'category' => 'general'],
                'is_active' => true,
            ],
            [
                'page' => 'home',
                'key' => 'dining',
                'eyebrow' => 'Dining',
                'title' => 'Food from the lake and the farm',
                'description' => 'International and local dishes at the Lakeview Restaurant, with the lake in front of you.',
                'config' => null,
                'is_active' => true,
            ],
            [
                'page' => 'home',
                'key' => 'activities',
                'eyebrow' => 'Experiences',
                'title' => 'Days on Lake Malawi',
                'description' => 'Cruises, water sports and island trips, all arranged from our own jetty.',
                'config' => ['limit' => 6],
                'is_active' => true,
            ],
            [
                'page' => 'home',
                'key' => 'conferences',
                'eyebrow' => 'Conferences & events',
                'title' => 'Meetings with a view',
                'description' => 'Conference halls seating up to 250 delegates, plus weddings and private events on the beach.',
                'config' => ['limit' => 3],
                'is_active' => true,
            ],
            [
                'page' => 'home',
                'key' => 'gallery',
                'eyebrow' => 'Gallery',
                'title' => 'Senga Bay in pictures',
                'description' => null,
                'config' => ['limit' => 8],
                'is_active' => true,
            ],
            [
                'page' => 'home',
                'key' => 'testimonials',
                'eyebrow' => 'Guest reviews',
                'title' => 'What guests say',
                'description' => null,
                'config' => ['limit' => 6],
                'is_active' => true,
            ],
            [
                'page' => 'home',
                'key' => 'offers',
                'eyebrow' => 'Special offers',
                'title' => 'Reasons to book direct',
                'description' => 'Better rates than any booking site, and the flexibility to change your dates.',
                'config' => ['limit' => 3],
                'is_active' => true,
            ],
            [
                'page' => 'home',
                'key' => 'location',
                'eyebrow' => 'Location',
                'title' => 'Senga Bay, Salima',
                'description' => 'On the S122 shore road, about an hour and three quarters from Lilongwe.',
                'config' => ['limit' => 6],
                'is_active' => true,
            ],
            [
                'page' => 'home',
                'key' => 'contact',
                'eyebrow' => 'Get in touch',
                'title' => 'Ready to plan your stay?',
                'description' => 'Our reservations team answers within a few hours, every day of the week.',
                'config' => null,
                'is_active' => true,
            ],
        ];
    }
}
