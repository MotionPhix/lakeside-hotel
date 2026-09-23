<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ConferenceHall;
use App\Models\ConferencePackage;
use App\Models\DiningVenue;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use LogicException;

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
                'section_notes' => $this->sectionNotes(),
                'menu_note' => 'Prices are tax inclusive.',
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

        /*
         * Clear the boards before restocking. Dishes seeded from the placeholder
         * menu carry prices the kitchen never agreed to, so they are removed
         * rather than left behind on a re-seed.
         */
        MenuItem::query()
            ->whereIn('dining_venue_id', [$restaurant->id, $bar->id, $poolBar->id])
            ->delete();

        $sortOrder = 0;

        foreach ($this->menuSections() as $category => $section) {
            foreach ($section['items'] as $item) {
                [$name, $vegetarian] = $item;
                $options = $item[2] ?? [];

                // Most sections are priced once at the heading, the way the card
                // prints them; only the exceptions carry their own price.
                $price = $options['price'] ?? $section['price'];

                if ($price === null) {
                    throw new LogicException("The menu item [{$name}] has no price.");
                }

                MenuItem::query()->create([
                    'dining_venue_id' => $restaurant->id,
                    'name' => $name,
                    'description' => null,
                    'price' => $price,
                    'category' => $category,
                    'is_signature' => $options['signature'] ?? false,
                    'is_vegetarian' => $vegetarian,
                    'is_available' => true,
                    'sort_order' => ++$sortOrder,
                ]);
            }
        }
    }

    /**
     * The Lakeside menu, transcribed from the printed card.
     *
     * Sections are priced under their heading, as on the card, so `price` is the
     * section price and an item only carries its own when the card breaks the
     * rule — a quarter chicken, a biryani, the desserts. Each item is
     * `[name, vegetarian]`, with an optional third element holding a price
     * override or the signature flag.
     *
     * @return array<string, array{price: int|null, note?: string, items: list<array{0: string, 1: bool, 2?: array<string, int|bool>}>}>
     */
    private function menuSections(): array
    {
        return [
            'salads' => [
                'price' => 10_000,
                'items' => [
                    ['Greek Salad', true],
                    ['Green Salad', true],
                    ['Onion Salad', true],
                ],
            ],

            'soups' => [
                'price' => 12_000,
                'items' => [
                    ['Sweet Corn Soup (Veg/Chicken/Beef)', false],
                    ['Hot-n-Sour Soup (Veg/Chicken/Beef)', false],
                    ['Manchow Soup (Veg/Chicken/Beef)', false],
                    ['Lakeside Special Soup (Veg/Chicken/Beef)', false, ['signature' => true]],
                    ['Tomato Soup', true],
                    ['Cream of Mushroom Soup', true],
                ],
            ],

            'light_bites' => [
                'price' => 12_000,
                'items' => [
                    ['Bruschetta (Italian) — Veg/Chicken', false],
                    ['Garlic Bread (Italian) — Veg/Chicken', false],
                    ['Cheese Garlic Bread (Italian) — Veg/Chicken', false],
                    ['Spring Rolls (Chinese)', false],
                    ['American Corn Chaat (Indian/Chinese)', true],
                    ['Cheesy French Fries', true],
                ],
            ],

            'sandwiches' => [
                'price' => 18_000,
                'note' => 'All served with chips. Extra cheese MK 2,000.',
                'items' => [
                    ['Veg Sandwich', true],
                    ['Club Sandwich — Veg/Chicken/Cheese', false],
                    ['Grilled Sandwich — Veg/Chicken/Cheese', false],
                ],
            ],

            'burgers' => [
                'price' => 18_000,
                'note' => 'All served with chips. Extra cheese MK 2,000.',
                'items' => [
                    ['Veg Burger', true],
                    ['Chicken Burger', false],
                    ['Zinger Burger', false],
                    ['Fish Burger', false],
                    ['Beef Burger', false],
                ],
            ],

            'appetizers' => [
                'price' => 18_000,
                'items' => [
                    ['Veg Manchurian (Dry or Semi Gravy)', true],
                    ['Kung Pao Potato', true],
                    ['Crispy Chilli Corn', true],
                    ['Honey Chilli Potato', true],
                    ['Crispy Chilli Chicken', false],
                    ['Chicken Lollipop', false],
                    ['Honey Lemon Chicken', false],
                    ['Honey Garlic Chicken', false],
                    ['Chicken Tit Bits', false],
                    ['Beef Tit Bits', false],
                    ['Crispy Chilli Fish', false],
                    ['Crispy Lemonfish', false],
                    ['Fish Fingers', false],
                    ['Vada Pav', true],
                    ['Cheese Balls', true],
                    ['Quesadillas', false],
                    ['Paneer Chilli (Dry or Semi Gravy)', true],
                    ['Paneer Tikka', true],
                ],
            ],

            'pasta' => [
                'price' => 24_000,
                'note' => 'Extra cheese MK 2,000.',
                'items' => [
                    ['Arabiata Pasta (Red Sauce) — Veg/Chicken', false],
                    ['Alfredo Pasta (White Sauce) — Veg/Chicken', false],
                    ['Pesto Pasta (Green Sauce) — Veg/Chicken', false],
                    ['Mix Sauce Pasta — Veg/Chicken', false],
                ],
            ],

            'pizzas' => [
                'price' => 35_000,
                'note' => 'Extra cheese MK 4,000.',
                'items' => [
                    ['Margherita', true],
                    ['Chicken Pizza', false],
                    ['Beef Pizza', false],
                    ['Chicken with Corn Pizza', false],
                    ['4 Season Pizza', false],
                ],
            ],

            'braai' => [
                'price' => 25_000,
                'items' => [
                    ['Half Chicken Braai with Chips and Salad', false],
                    ['T-Bone with Chips and Salad', false],
                    ['Chambo with Chips and Salad', false, ['signature' => true]],
                    ['Quarter Chicken Braai with Chips', false, ['price' => 18_000]],
                ],
            ],

            'warm_heart_dishes' => [
                'price' => 25_000,
                'note' => 'Served with chips, nsima or rice, and salad.',
                'items' => [
                    ['Chambo Stew', false],
                    ['Open Fried Chambo', false],
                    ['Whole Fried Chambo', false, ['signature' => true]],
                    ['Fillet Chambo in Bread Crumbs', false],
                    ['Fillet Chambo in Batter', false],
                    ['Fillet Chambo in Lemon Butter Sauce', false, ['signature' => true]],
                    ['Half Chicken in Bread Crumbs', false],
                    ['Half Chicken Peri-Peri', false],
                    ['Half Chicken Grilled', false],
                    ['Quarter Chicken', false, ['price' => 18_000]],
                ],
            ],

            'main_course' => [
                'price' => 25_000,
                'items' => [
                    ['Chicken in Garlic Sauce', false],
                    ['Sweet-n-Sour Chicken', false],
                    ['Chicken with Green Pepper', false],
                    ['Chilli Chicken', false],
                    ['Chicken Schezwan', false],
                    ['Mongolian Chicken', false],
                    ['Beef Garlic Sauce', false],
                    ['Beef Schezwan', false],
                    ['Beef Chilli', false],
                    ['Beef with Green Pepper', false],
                    ['Lemon Beef', false],
                    ['Mongolian Beef', false],
                ],
            ],

            'rice' => [
                'price' => 18_000,
                'items' => [
                    ['Fried Rice (Veg/Chicken/Beef)', false],
                    ['Schezwan Rice (Veg/Chicken/Beef)', false],
                    ['Jeera Rice', true],
                    ['Egg Fried Rice', false],
                    ['Plain Rice', true],
                    ['Triple Schezwan Rice', false, ['price' => 25_000]],
                    ['Biryani (Veg/Chicken/Beef)', false, ['price' => 30_000]],
                    ['Pulao (Veg/Chicken)', false, ['price' => 30_000]],
                ],
            ],

            'noodles' => [
                'price' => 18_000,
                'items' => [
                    ['Schezwan Noodles (Veg/Chicken/Beef)', false],
                    ['Fried Noodles (Veg/Chicken/Beef)', false],
                    ['Crispy Noodles (Veg/Chicken/Beef)', false],
                    ['Crispy Schezwan Noodles (Veg/Chicken/Beef)', false],
                    ['Hakka Noodles (Veg/Chicken/Beef)', false],
                ],
            ],

            'indian_gravy' => [
                'price' => 20_000,
                'items' => [
                    ['Butter Chicken', false],
                    ['Chicken Kadai', false],
                    ['Chicken Tikka Masala', false],
                    ['Chicken Bharta', false],
                    ['Chicken Lajaawab', false],
                    ['Chicken Kolapuri', false],
                    ['Egg Curry', false],
                    ['Egg Masala', false],
                    ['Beef Kadai', false],
                    ['Beef Masala', false],
                    ['Fish Curry', false],
                    ['Dal Tadka', true],
                    ['Dal Fry', true],
                    ['Veg Manchurian', true],
                    ['Mix Veg', true],
                    ['Veg Jhalfarezi', true],
                    ['Veg Makhani', true],
                    ['Veg Kolapuri', true],
                    ['Veg Kadai', true],
                    ['Veg Handi', true],
                    ['Green Peas Masala', true],
                    ['Aloo Dum Masala', true],
                    ['Paneer Manchurian', true],
                    ['Paneer Kadai', true],
                    ['Paneer Butter Masala', true],
                    ['Paneer Tikka Masala', true],
                    ['Paneer Kolapuri', true],
                ],
            ],

            'tandoor' => [
                'price' => 25_000,
                'items' => [
                    ['Chicken Tikka Kebab', false],
                    ['Chicken Tandoori (Half)', false],
                    ['Chicken Tangdi Kebab', false],
                    ['Chicken Seekh Kebab', false],
                    ['Chicken Cheese Kebab', false],
                    ['Fish Tandoori', false],
                    ['Potato Tikka Kebab', true],
                ],
            ],

            'breads' => [
                'price' => 5_000,
                'items' => [
                    ['Tandoori Roti', true],
                    ['Butter Tandoori Roti', true],
                    ['Plain Naan', true],
                    ['Butter Naan', true],
                    ['Garlic Naan', true],
                    ['Butter Paratha', true],
                ],
            ],

            'sizzlers' => [
                'price' => 35_000,
                'items' => [
                    ['Veg Sizzler', true],
                    ['Indian Sizzler', false],
                    ['Fish Sizzler', false],
                    ['Chicken Sizzler', false],
                    ['Beef Sizzler', false],
                ],
            ],

            'pappad' => [
                'price' => 2_500,
                'items' => [
                    ['Masala Pappad', true],
                ],
            ],

            'beverages' => [
                'price' => 2_000,
                'items' => [
                    ['Coke', true],
                    ['Sprite', true],
                    ['Fanta', true],
                    ['Cherry Plum', true],
                    ['Cocopina', true],
                    ['Mineral Water', true],
                ],
            ],

            // Sweet tooth. The card prices these individually, so there is no
            // section price to fall back on.
            'desserts' => [
                'price' => null,
                'items' => [
                    ['Ice Cream — Cone or Cup', true, ['price' => 5_000]],
                    ['Fried Banana with Vanilla Ice Cream', true, ['price' => 15_000]],
                    ['Sizzling Brownie', true, ['price' => 15_000]],
                    ['Fried Ice Cream', true, ['price' => 15_000]],
                ],
            ],
        ];
    }

    /**
     * The notes the card prints under a section heading, keyed by section. Stated
     * once per section rather than repeated against every dish.
     *
     * @return array<string, string>
     */
    private function sectionNotes(): array
    {
        $notes = [];

        foreach ($this->menuSections() as $category => $section) {
            if (isset($section['note'])) {
                $notes[$category] = $section['note'];
            }
        }

        return $notes;
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
