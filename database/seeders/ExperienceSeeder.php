<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ConferencePackage;
use App\Models\DiningVenue;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Everything the hotel sells that is not a bed: where guests eat and drink, what
 * they can do on the lake, and the packages for conferences and weddings.
 */
class ExperienceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedDining();
        $this->seedActivities();
        $this->seedConferencePackages();
    }

    /**
     * Restaurants, bars and their menus.
     */
    private function seedDining(): void
    {
        $restaurant = DiningVenue::query()->updateOrCreate(
            ['slug' => 'lakeview-restaurant'],
            [
                'name' => 'The Lakeview Restaurant',
                'type' => 'restaurant',
                'tagline' => 'Chambo, tilapia and produce from around Salima',
                'description' => 'Our main restaurant opens onto the terrace, with tables set along the water. Breakfast runs from early for the fishermen, lunch is light, and dinner is where the kitchen shows what it can do with the day\'s catch.',
                'opening_hours' => [
                    'breakfast' => '06:30 - 10:00',
                    'lunch' => '12:00 - 15:00',
                    'dinner' => '18:30 - 22:00',
                ],
                'dress_code' => 'Resort casual',
                'sort_order' => 1,
            ],
        );

        $bar = DiningVenue::query()->updateOrCreate(
            ['slug' => 'anchor-bar-lounge'],
            [
                'name' => 'The Anchor Bar & Lounge',
                'type' => 'bar',
                'tagline' => 'Cold drinks, lake views, and the best sunset in Senga Bay',
                'description' => 'The bar opens onto the gardens, facing west across the water. Local gins, South African wine, cocktails built around baobab and mango, and a bar snack menu through the afternoon.',
                'opening_hours' => ['daily' => '11:00 - 23:00'],
                'dress_code' => null,
                'sort_order' => 2,
            ],
        );

        $poolBar = DiningVenue::query()->updateOrCreate(
            ['slug' => 'poolside-bar'],
            [
                'name' => 'Poolside Bar',
                'type' => 'pool_bar',
                'tagline' => 'Lunch and drinks without leaving the sun deck',
                'description' => 'A short menu of grills, salads and cold drinks served at the pool between late morning and sunset. Towels and sun loungers are provided.',
                'opening_hours' => ['daily' => '10:00 - 18:00'],
                'dress_code' => 'Swimwear welcome',
                'sort_order' => 3,
            ],
        );

        $menu = [
            // [venue, category, name, description, price, signature, vegetarian]
            [$restaurant, 'starters', 'Chambo Fish Cakes', 'Lake chambo, cassava crumb, chilli lime mayonnaise', 14_000, true, false],
            [$restaurant, 'starters', 'Mzuzu Mushroom Soup', 'Wild mushrooms from the northern highlands, cream, herb oil', 12_000, false, true],
            [$restaurant, 'starters', 'Roast Maize and Peanut Salad', 'Charred maize, groundnut, tomato, coriander', 11_000, false, true],
            [$restaurant, 'starters', 'Grilled Tilapia Skewers', 'Marinated tilapia, pepper, onion, served warm', 15_000, false, false],

            [$restaurant, 'mains', 'Grilled Lake Malawi Chambo', 'Whole chambo off the grill, lemon butter, nsima or chips', 38_000, true, false],
            [$restaurant, 'mains', 'Beef Ndiwo with Nsima', 'Slow cooked beef in a groundnut and tomato relish, with nsima', 32_000, true, false],
            [$restaurant, 'mains', 'Vegetable Ndiwo with Nsima', 'Seasonal greens, groundnut, tomato, with nsima', 24_000, false, true],
            [$restaurant, 'mains', 'Coconut Chicken Curry', 'Chicken thigh, coconut, ginger, steamed rice', 30_000, false, false],
            [$restaurant, 'mains', 'Beef Fillet with Pepper Sauce', 'Local beef fillet, green peppercorn cream, roast potatoes', 45_000, false, false],
            [$restaurant, 'mains', 'Grilled Tilapia Fillets', 'Tilapia fillets, garlic butter, rice and garden salad', 34_000, false, false],

            [$restaurant, 'grills', 'Lake Platter for Two', 'Chambo, tilapia and prawns, grilled with lemon and herbs', 78_000, true, false],
            [$restaurant, 'grills', 'Barbecue Chicken Half', 'Half chicken marinated in peri-peri, with chips and slaw', 28_000, false, false],

            [$restaurant, 'desserts', 'Mango Sorbet', 'Mango from the lakeshore, lime, mint', 10_000, false, true],
            [$restaurant, 'desserts', 'Coconut Rice Pudding', 'Coconut milk, cardamom, toasted coconut', 11_000, false, true],
            [$restaurant, 'desserts', 'Malawian Coffee and Chocolate Tart', 'Dark chocolate, local coffee, cream', 13_000, false, true],

            [$bar, 'cocktails', 'Sunset Over Senga Bay', 'Malawi gin, mango, lime, grenadine', 18_000, true, false],
            [$bar, 'cocktails', 'Lake Breeze', 'White rum, baobab, soda, mint', 17_000, false, false],
            [$bar, 'cocktails', 'Malawi Gin and Tonic', 'Local gin, tonic, lime, juniper', 15_000, false, false],
            [$bar, 'drinks', 'Fresh Baobab Juice', 'Pressed to order', 8_000, false, true],
            [$bar, 'drinks', 'Malawian Coffee', 'Grown in the north, roasted weekly', 7_000, false, true],
            [$bar, 'drinks', 'Sobo Squash', 'Hibiscus, ginger, served over ice', 6_000, false, true],

            [$poolBar, 'mains', 'Pool Burger', 'Beef patty, cheddar, tomato relish, chips', 26_000, false, false],
            [$poolBar, 'mains', 'Grilled Chicken Salad', 'Grilled chicken, avocado, tomato, garden leaves', 24_000, false, false],
            [$poolBar, 'mains', 'Fish and Chips', 'Battered chambo, chips, tartare', 28_000, false, false],
            [$poolBar, 'drinks', 'Sun Deck Cooler', 'Pineapple, lime, ginger, soda', 9_000, true, true],
        ];

        foreach ($menu as $index => [$venue, $category, $name, $description, $price, $signature, $vegetarian]) {
            MenuItem::query()->updateOrCreate(
                ['dining_venue_id' => $venue->id, 'name' => $name],
                [
                    'description' => $description,
                    'price' => $price,
                    'category' => $category,
                    'is_signature' => $signature,
                    'is_vegetarian' => $vegetarian,
                    'is_available' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }

    /**
     * Things to do on the water and around the bay.
     */
    private function seedActivities(): void
    {
        $activities = [
            [
                'name' => 'Sunset Lake Cruise',
                'description' => 'Out past the rocks on a covered boat for the two hours either side of sunset. Drinks and snacks on board, and the best light of the day over the water.',
                'duration_minutes' => 120,
                'price' => 65_000,
                'price_basis' => 'per_person',
                'min_participants' => 2,
                'max_participants' => 16,
                'is_featured' => true,
            ],
            [
                'name' => 'Boat Trip to Lizard Island',
                'description' => 'A short crossing to the island just offshore, where the water is clear over the rock shelves. Snorkelling gear included. Back in time for lunch.',
                'duration_minutes' => 120,
                'price' => 55_000,
                'price_basis' => 'per_person',
                'min_participants' => 2,
                'max_participants' => 12,
                'is_featured' => true,
            ],
            [
                'name' => 'Fishing Trip at Dawn',
                'description' => 'Head out before first light with a local skipper. Rods, bait and a flask of coffee provided. Whatever you catch, the kitchen will cook for your lunch.',
                'duration_minutes' => 210,
                'price' => 140_000,
                'price_basis' => 'per_group',
                'min_participants' => 1,
                'max_participants' => 4,
                'is_featured' => true,
            ],
            [
                'name' => 'Kayaking the Bay',
                'description' => 'Single and double kayaks available from the beach. Paddle the sheltered water along the shoreline at your own pace.',
                'duration_minutes' => 60,
                'price' => 25_000,
                'price_basis' => 'per_hour',
                'min_participants' => 1,
                'max_participants' => 8,
                'is_featured' => false,
            ],
            [
                'name' => 'Snorkelling the Rock Shelves',
                'description' => 'Guided snorkelling over the cichlid colonies at the edge of the bay. Mask, fins and a guide who knows where the fish are.',
                'duration_minutes' => 90,
                'price' => 40_000,
                'price_basis' => 'per_person',
                'min_participants' => 2,
                'max_participants' => 10,
                'is_featured' => false,
            ],
            [
                'name' => 'Beach Volleyball',
                'description' => 'A net and a ball on the sand, free to use. Guests regularly end up with a match running through the afternoon.',
                'duration_minutes' => 60,
                'price' => 0,
                'price_basis' => 'complimentary',
                'min_participants' => 4,
                'max_participants' => 20,
                'is_featured' => false,
            ],
            [
                'name' => 'Team Building Day',
                'description' => 'A full programme on the beach and the water for corporate groups: boat races, raft building, a lake swim and lunch on the terrace. Run by our activities team.',
                'duration_minutes' => 300,
                'price' => 320_000,
                'price_basis' => 'per_group',
                'min_participants' => 10,
                'max_participants' => 60,
                'is_featured' => true,
            ],
            [
                'name' => 'Senga Bay Village and Market Walk',
                'description' => 'A guided walk to the fishing village and the market with a local guide. Watch the boats come in, see how the nets are mended, and buy from the stalls.',
                'duration_minutes' => 180,
                'price' => 45_000,
                'price_basis' => 'per_person',
                'min_participants' => 2,
                'max_participants' => 10,
                'is_featured' => false,
            ],
            [
                'name' => 'Kuti Wildlife Reserve Safari',
                'description' => 'A half day trip to Kuti, a community run reserve near Salima, with zebra, sable antelope, giraffe and over three hundred bird species. Transport and a guide included.',
                'duration_minutes' => 300,
                'price' => 180_000,
                'price_basis' => 'per_person',
                'min_participants' => 2,
                'max_participants' => 8,
                'is_featured' => true,
            ],
        ];

        foreach ($activities as $index => $activity) {
            Activity::query()->updateOrCreate(
                ['slug' => Str::slug($activity['name'])],
                $activity + [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * Packages for conferences, retreats, weddings and private events.
     */
    private function seedConferencePackages(): void
    {
        $packages = [
            [
                'name' => 'Day Delegate Conference',
                'type' => 'conference',
                'tagline' => 'The full working day, catered, looking out over the lake',
                'description' => 'A complete day delegate rate for meetings and workshops. Includes the venue, equipment, two tea breaks and a buffet lunch. The room seats 120 theatre style or splits into two smaller spaces.',
                'capacity_min' => 10,
                'capacity_max' => 120,
                'price' => 55_000,
                'price_basis' => 'per_person',
                'includes' => [
                    'Conference venue hire',
                    'Projector, screen and flip charts',
                    'Mid-morning tea and coffee with pastries',
                    'Buffet lunch on the terrace',
                    'Afternoon tea and coffee',
                    'Dedicated events coordinator',
                    'Free WiFi throughout',
                ],
                'is_featured' => true,
            ],
            [
                'name' => 'Residential Conference',
                'type' => 'corporate_retreat',
                'tagline' => 'Bring the team, stay the night, get the work done',
                'description' => 'Conference by day, rooms and dinner by night. Designed for leadership offsites and strategy retreats, with the lake as the incentive to stay an extra day.',
                'capacity_min' => 8,
                'capacity_max' => 60,
                'price' => 285_000,
                'price_basis' => 'per_person',
                'includes' => [
                    'Accommodation in a deluxe room',
                    'Full day delegate conference package',
                    'Three course dinner',
                    'Breakfast the following morning',
                    'One team building activity on the water',
                    'Airport or city transfers on request',
                ],
                'is_featured' => true,
            ],
            [
                'name' => 'Lakeside Wedding Package',
                'type' => 'wedding',
                'tagline' => 'Say it on the sand, celebrate on the terrace',
                'description' => 'A ceremony on our stretch of beach followed by a reception on the terrace, with the sun going down behind you. Includes the set up, the catering and a coordinator who has run a lot of these.',
                'capacity_min' => 30,
                'capacity_max' => 200,
                'price' => 1_850_000,
                'price_basis' => 'per_event',
                'includes' => [
                    'Beach ceremony set up with seating and archway',
                    'Terrace reception with tables, linen and lighting',
                    'Three course plated dinner or buffet for 100 guests',
                    'Welcome drinks and a toast',
                    'Wedding cake table and cake stand',
                    'Sound system and microphone',
                    'Dedicated wedding coordinator',
                    'Discounted rates for guest rooms',
                ],
                'is_featured' => true,
            ],
            [
                'name' => 'Half Day Meeting',
                'type' => 'conference',
                'tagline' => 'For when the meeting only needs a morning',
                'description' => 'A half day in the conference room with one tea break and lunch, for smaller meetings and board sessions.',
                'capacity_min' => 6,
                'capacity_max' => 40,
                'price' => 35_000,
                'price_basis' => 'per_person',
                'includes' => [
                    'Conference venue hire for up to four hours',
                    'Projector and screen',
                    'Tea, coffee and pastries',
                    'Buffet lunch on the terrace',
                    'Free WiFi throughout',
                ],
                'is_featured' => false,
            ],
            [
                'name' => 'Private Event and Party Hire',
                'type' => 'private_event',
                'tagline' => 'Birthdays, anniversaries and celebrations by the lake',
                'description' => 'Take the terrace or the beach for your own celebration. We will set the space, cater it and staff it, and leave you to enjoy it.',
                'capacity_min' => 20,
                'capacity_max' => 150,
                'price' => 650_000,
                'price_basis' => 'per_event',
                'includes' => [
                    'Terrace or beach venue hire for an evening',
                    'Set up, tables, linen and lighting',
                    'Bar service with a dedicated barman',
                    'Sound system',
                    'Event coordinator and service staff',
                    'Catering quoted separately to your menu',
                ],
                'is_featured' => false,
            ],
        ];

        foreach ($packages as $index => $package) {
            ConferencePackage::query()->updateOrCreate(
                ['slug' => Str::slug($package['name'])],
                $package + [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
