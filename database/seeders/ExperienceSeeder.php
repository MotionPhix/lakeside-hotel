<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ConferenceHall;
use App\Models\ConferencePackage;
use App\Models\DiningVenue;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Everything the hotel sells that is not a bed: where guests eat and drink, the
 * named conference halls and the packages sold to fill them, and the leisure
 * list from the company profile.
 */
class ExperienceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedDining();
        $this->seedConferenceHalls();
        $this->seedConferencePackages();
        $this->seedActivities();
    }

    /**
     * The Lakeview Restaurant, the bar and the poolside bar.
     */
    private function seedDining(): void
    {
        $restaurant = DiningVenue::query()->updateOrCreate(
            ['slug' => 'lakeview-restaurant'],
            [
                'name' => 'The Lakeview Restaurant',
                'type' => 'restaurant',
                'tagline' => 'Tradition and modernity, with the lake in front of you',
                'description' => 'Our multi-cuisine restaurant combines tradition with modernity in an exceptional way, and seats close to 400 people in one sitting. Even if you are not staying the night, enjoy your meal while gazing at the beauty that is Lake Malawi. International and local dishes, prepared by a team of skilled chefs.',
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
            ['slug' => 'bar-and-lounge'],
            [
                'name' => 'The Bar & Lounge',
                'type' => 'bar',
                'tagline' => 'Cold drinks, a pool table, and the best sunset in Senga Bay',
                'description' => 'The lounge opens onto the gardens, facing west across the water, with a full size pool table and the bar alongside it. Local gins, South African wine, cocktails built around baobab and mango, and a bar snack menu through the afternoon.',
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
                'description' => 'A short menu of grills, salads and cold drinks served at the pool between late morning and sunset. Towels and sun loungers are provided, and the gazebo gives you shade when the sun is at its worst.',
                'opening_hours' => ['daily' => '10:00 - 18:00'],
                'dress_code' => 'Swimwear welcome',
                'sort_order' => 3,
            ],
        );

        $menu = [
            // [venue, category, name, description, price, signature, vegetarian]
            [$restaurant, 'starters', 'Chambo Fish Cakes', 'Lake chambo, cassava crumb, chilli lime mayonnaise', 14_000, true, false],
            [$restaurant, 'starters', 'Seafood Cocktail', 'Prawns and lake fish, avocado, citrus dressing, served chilled', 18_000, true, false],
            [$restaurant, 'starters', 'Mzuzu Mushroom Soup', 'Wild mushrooms from the northern highlands, cream, herb oil', 12_000, false, true],
            [$restaurant, 'starters', 'Roast Maize and Peanut Salad', 'Charred maize, groundnut, tomato, coriander', 11_000, false, true],

            [$restaurant, 'mains', 'Grilled Lake Malawi Chambo', 'Whole chambo off the grill, lemon butter, nsima or chips', 38_000, true, false],
            [$restaurant, 'mains', 'Beef Ndiwo with Nsima', 'Slow cooked beef in a groundnut and tomato relish, with nsima', 32_000, true, false],
            [$restaurant, 'mains', 'Vegetable Ndiwo with Nsima', 'Seasonal greens, groundnut, tomato, with nsima', 24_000, false, true],
            [$restaurant, 'mains', 'Coconut Chicken Curry', 'Chicken thigh, coconut, ginger, steamed rice', 30_000, false, false],
            [$restaurant, 'mains', 'Beef Fillet with Pepper Sauce', 'Local beef fillet, green peppercorn cream, roast potatoes', 45_000, false, false],
            [$restaurant, 'mains', 'Grilled Tilapia Fillets', 'Tilapia fillets, garlic butter, rice and garden salad', 34_000, false, false],

            [$restaurant, 'grills', 'Slider Tower', 'A tower of mini burgers with our house relish, built to share', 42_000, true, false],
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
     * The named conference halls and their delegate capacities.
     *
     * The profile describes four halls but only names three. Only the named ones
     * are seeded; the fourth can be added in the dashboard once it is confirmed.
     */
    private function seedConferenceHalls(): void
    {
        $halls = [
            ['name' => 'Namalenje Hall', 'capacity' => 250, 'layout' => 'Theatre, classroom or banquet'],
            ['name' => 'Mikute Hall', 'capacity' => 100, 'layout' => 'Theatre, classroom or boardroom'],
            ['name' => 'Mbenje Hall', 'capacity' => 50, 'layout' => 'Boardroom or hollow square'],
        ];

        $features = [
            'Mineral water and candy on the table',
            'HD overhead projector',
            'Cordless and pin microphones',
            'Flipcharts',
            'Inbuilt HD sound system',
            'IDD telephone',
            'High speed free Wi-Fi',
            'Printer, scanner and photocopier',
            'Meeting stationery',
            'IT butler service',
        ];

        foreach ($halls as $index => $hall) {
            ConferenceHall::query()->updateOrCreate(
                ['slug' => Str::slug($hall['name'])],
                [
                    'name' => $hall['name'],
                    'capacity' => $hall['capacity'],
                    'layout' => $hall['layout'],
                    'description' => "One of the halls in our conference centre, seating up to {$hall['capacity']} delegates.",
                    'features' => $features,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * Packages sold to fill the halls, plus weddings and private events.
     */
    private function seedConferencePackages(): void
    {
        $packages = [
            [
                'name' => 'Day Delegate Conference',
                'type' => 'conference',
                'tagline' => 'The full working day, catered, looking out over the lake',
                'description' => 'A complete day delegate rate for meetings, workshops and trainings. Includes the hall, the audio-visual equipment, two tea breaks and a buffet lunch at the Lakeview Restaurant.',
                'capacity_min' => 10,
                'capacity_max' => 250,
                'price' => 55_000,
                'price_basis' => 'per_person',
                'includes' => [
                    'Conference hall hire',
                    'HD projector, screen and flipcharts',
                    'Cordless and pin microphones',
                    'Inbuilt HD sound system',
                    'Mid-morning tea and coffee with pastries',
                    'Buffet lunch at the Lakeview Restaurant',
                    'Afternoon tea and coffee',
                    'High speed Wi-Fi throughout',
                    'IT butler service',
                ],
                'is_featured' => true,
            ],
            [
                'name' => 'Residential Conference',
                'type' => 'corporate_retreat',
                'tagline' => 'Bring the team, stay the night, get the work done',
                'description' => 'Conference by day, rooms and dinner by night. Designed for leadership offsites and strategy retreats, with 42 rooms on site so the whole team stays together.',
                'capacity_min' => 8,
                'capacity_max' => 100,
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
                'name' => 'Half Day Meeting',
                'type' => 'conference',
                'tagline' => 'For when the meeting only needs a morning',
                'description' => 'A half day in one of the smaller halls with one tea break and lunch, for board sessions and management meetings.',
                'capacity_min' => 6,
                'capacity_max' => 50,
                'price' => 35_000,
                'price_basis' => 'per_person',
                'includes' => [
                    'Conference hall hire for up to four hours',
                    'Projector and screen',
                    'Mineral water and candy on the table',
                    'Tea, coffee and pastries',
                    'Buffet lunch at the Lakeview Restaurant',
                    'High speed Wi-Fi throughout',
                ],
                'is_featured' => false,
            ],
            [
                'name' => 'Lakeside Wedding Package',
                'type' => 'wedding',
                'tagline' => 'Say it on the sand, celebrate on the terrace',
                'description' => 'A ceremony on our stretch of beach followed by a reception on the terrace or in Namalenje Hall, with the sun going down behind you. Includes the set up, the catering and a coordinator who has run a lot of these.',
                'capacity_min' => 30,
                'capacity_max' => 250,
                'price' => 1_850_000,
                'price_basis' => 'per_event',
                'includes' => [
                    'Beach ceremony set up with seating and archway',
                    'Reception on the terrace or in Namalenje Hall',
                    'Tables, linen and lighting',
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
                'name' => 'Private Event and Party Hire',
                'type' => 'private_event',
                'tagline' => 'Birthdays, anniversaries and celebrations by the lake',
                'description' => 'Take the terrace, the beach or a hall for your own celebration. We will set the space, cater it and staff it, and leave you to enjoy it.',
                'capacity_min' => 20,
                'capacity_max' => 200,
                'price' => 650_000,
                'price_basis' => 'per_event',
                'includes' => [
                    'Venue hire for an evening',
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

    /**
     * The leisure list from the company profile.
     */
    private function seedActivities(): void
    {
        $activities = [
            [
                'name' => 'Island Tour by Speed Boat',
                'description' => 'Out to the islands on our 200 horsepower speed boat, with music on board and a skipper who knows where the fish are. The full day out on the lake.',
                'duration_minutes' => 180,
                'price' => 95_000,
                'price_basis' => 'per_person',
                'min_participants' => 2,
                'max_participants' => 10,
                'is_featured' => true,
            ],
            [
                'name' => 'Water Skiing',
                'description' => 'Water skiing behind the speed boat, with instruction if you have never tried it. Buoyancy aids provided.',
                'duration_minutes' => 30,
                'price' => 45_000,
                'price_basis' => 'per_person',
                'min_participants' => 1,
                'max_participants' => 6,
                'is_featured' => true,
            ],
            [
                'name' => 'Boating on the Bay',
                'description' => 'A relaxed turn around the bay in the speed boat. Good for families with small children and for anyone who wants the water without the effort.',
                'duration_minutes' => 60,
                'price' => 35_000,
                'price_basis' => 'per_person',
                'min_participants' => 2,
                'max_participants' => 10,
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
                'is_featured' => true,
            ],
            [
                'name' => 'Tubing',
                'description' => 'Hold on. A towable ring behind the speed boat, which is as much fun as it sounds and considerably wetter.',
                'duration_minutes' => 20,
                'price' => 30_000,
                'price_basis' => 'per_person',
                'min_participants' => 1,
                'max_participants' => 6,
                'is_featured' => false,
            ],
            [
                'name' => 'Parasailing',
                'description' => 'Up above the bay with the whole of Senga Bay and the lake beneath you. Subject to wind and water conditions.',
                'duration_minutes' => 15,
                'price' => 85_000,
                'price_basis' => 'per_person',
                'min_participants' => 1,
                'max_participants' => 4,
                'is_featured' => true,
            ],
            [
                'name' => 'Fishing off the Islands',
                'description' => 'Head out with a local skipper and fish the water around the islands. Rods and bait provided, and the kitchen will cook whatever you land for your lunch.',
                'duration_minutes' => 210,
                'price' => 140_000,
                'price_basis' => 'per_group',
                'min_participants' => 1,
                'max_participants' => 4,
                'is_featured' => false,
            ],
            [
                'name' => 'Bird Watching and Feeding',
                'description' => 'A guided walk along the shore and the river mouth for fish eagles, kingfishers and herons, with feeding stations set up along the way.',
                'duration_minutes' => 120,
                'price' => 35_000,
                'price_basis' => 'per_person',
                'min_participants' => 1,
                'max_participants' => 8,
                'is_featured' => false,
            ],
            [
                'name' => 'Private Family Cinema',
                'description' => 'A private film on our HD projector, set up for your family in the evening. We will bring the snacks and the cushions.',
                'duration_minutes' => 120,
                'price' => 55_000,
                'price_basis' => 'per_group',
                'min_participants' => 2,
                'max_participants' => 20,
                'is_featured' => false,
            ],
            [
                'name' => 'Kids Golf',
                'description' => 'A putting green and childrens clubs, free for guests. Keeps the small ones busy while the grown ups have lunch.',
                'duration_minutes' => 60,
                'price' => 0,
                'price_basis' => 'complimentary',
                'min_participants' => 1,
                'max_participants' => 10,
                'is_featured' => false,
            ],
            [
                'name' => 'Gaming Lounge',
                'description' => 'A lounge with a gaming zone and board games, open through the day and into the evening. Free for guests.',
                'duration_minutes' => null,
                'price' => 0,
                'price_basis' => 'complimentary',
                'min_participants' => 1,
                'max_participants' => 20,
                'is_featured' => false,
            ],
            [
                'name' => 'Photo and Pre-Wedding Shoots',
                'description' => 'Use of the beach, jetty, gardens and gazebo for photography sessions and pre-wedding shoots, subject to company policy. Our team will show you the best light at each time of day.',
                'duration_minutes' => 180,
                'price' => 150_000,
                'price_basis' => 'per_group',
                'min_participants' => 2,
                'max_participants' => 15,
                'is_featured' => true,
            ],
            [
                'name' => 'Bonfire Night',
                'description' => 'A fire on the beach after dark, with seating around it and drinks service. Popular with conference groups on their last night.',
                'duration_minutes' => 180,
                'price' => 120_000,
                'price_basis' => 'per_group',
                'min_participants' => 6,
                'max_participants' => 60,
                'is_featured' => false,
            ],
            [
                'name' => 'Candle Night Dinner',
                'description' => 'A private candle-lit dinner on the deck or the beach, with a set menu and a waiter looking after your table alone. Popular with honeymooners and anniversaries.',
                'duration_minutes' => 150,
                'price' => 95_000,
                'price_basis' => 'per_group',
                'min_participants' => 2,
                'max_participants' => 8,
                'is_featured' => true,
            ],
            [
                'name' => 'Spa Treatments',
                'description' => 'Massage and beauty treatments arranged on request in your room or at the pool. Please ask reception the day before.',
                'duration_minutes' => 60,
                'price' => 60_000,
                'price_basis' => 'per_person',
                'min_participants' => 1,
                'max_participants' => 4,
                'is_featured' => false,
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
}
