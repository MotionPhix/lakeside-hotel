<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\GalleryItem;
use App\Models\HeroSlide;
use App\Models\NearbyAttraction;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * The hotel's own words and details, taken from the Lakeside Hotel company
 * profile. Everything here is editable in the dashboard.
 *
 * Two things are deliberately left blank because the profile does not give them:
 * the social media URLs, and the exact map coordinates. The footer hides a social
 * link until it is filled in, so nothing renders broken in the meantime.
 */
class SiteContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedSettings();
        $this->seedContentBlocks();
        $this->seedHeroSlides();
        $this->seedGallery();
        $this->seedNearbyAttractions();
    }

    /**
     * Hotel details used across the site, the emails and the structured data.
     */
    private function seedSettings(): void
    {
        $settings = [
            // [key, value, section, type]
            ['hotel.name', 'Lakeside Hotel and Conference Centre', 'general', 'string'],
            ['hotel.tagline', 'Your Lakeside Escape in Senga Bay', 'general', 'string'],
            ['hotel.established', '1998', 'general', 'string'],
            ['hotel.address', 'Senga Bay, Salima District, Malawi', 'contact', 'string'],
            ['hotel.reservations_office', 'Infinity Complex, Sir Glyn Joones Rd., Area 3, Lilongwe', 'contact', 'string'],
            ['hotel.phone', '+265 1 263 400', 'contact', 'string'],
            ['hotel.phone_alt', '+265 884 484 999', 'contact', 'string'],
            ['hotel.phone_alt_2', '+265 986 100 309', 'contact', 'string'],
            ['hotel.whatsapp', '+265 999 311 228', 'contact', 'string'],
            ['hotel.email', 'reservations@lakesidehotelmw.net', 'contact', 'email'],
            ['hotel.marketing_email', 'marketing@lakesidehotelmw.net', 'contact', 'email'],
            ['hotel.events_email', 'reservations@lakesidehotelmw.net', 'contact', 'email'],
            ['hotel.website', 'https://www.lakesidehotelmw.net', 'contact', 'string'],
            ['hotel.latitude', '-13.716700', 'contact', 'string'],
            ['hotel.longitude', '34.616700', 'contact', 'string'],
            ['hotel.map_zoom', '14', 'contact', 'integer'],
            ['hotel.check_in_time', '14:00', 'booking', 'time'],
            ['hotel.check_out_time', '11:00', 'booking', 'time'],
            ['hotel.currency', 'MWK', 'booking', 'string'],
            ['hotel.rooms_total', '42', 'general', 'integer'],
            ['booking.vat_rate', '16.5', 'booking', 'decimal'],
            ['booking.tourism_levy_rate', '1', 'booking', 'decimal'],
            ['booking.deposit_percentage', '50', 'booking', 'integer'],
            ['booking.pay_at_hotel_enabled', '1', 'booking', 'boolean'],
            ['booking.online_payment_enabled', '1', 'booking', 'boolean'],
            ['booking.cancellation_policy', 'Free cancellation up to 7 days before arrival. Within 7 days the first night is charged. No shows are charged in full.', 'booking', 'text'],
            ['booking.child_policy', 'Children under 12 stay free when sharing with two adults. Cots and extra beds are available on request.', 'booking', 'text'],
            ['booking.transfer_note', 'We can collect you from Kamuzu International Airport in Lilongwe, or from our reservations office in Area 3. The drive to Senga Bay takes about one hour forty-five minutes. Please request your transfer at least 24 hours before arrival.', 'booking', 'text'],
            ['restaurant.capacity', '400', 'general', 'integer'],
            // Left blank on purpose: the profile shows social icons but no URLs.
            ['social.facebook', '', 'social', 'string'],
            ['social.instagram', '', 'social', 'string'],
            ['social.tripadvisor', '', 'social', 'string'],
            ['seo.default_title', 'Lakeside Hotel and Conference Centre | Senga Bay, Salima', 'seo', 'string'],
            ['seo.default_description', 'Book a lakeside room, suite or family room at Lakeside Hotel and Conference Centre in Senga Bay, Salima. Lake Malawi views, the Lakeview Restaurant, four conference halls and lake activities.', 'seo', 'text'],
            ['analytics.google_id', '', 'seo', 'string'],
        ];

        foreach ($settings as [$key, $value, $section, $type]) {
            Setting::store($key, $value, $section, $type);
        }
    }

    /**
     * The editable prose sections of the website.
     */
    private function seedContentBlocks(): void
    {
        $blocks = [
            [
                'key' => 'about',
                'title' => 'A lakeside retreat in Senga Bay',
                'subtitle' => 'Warm Malawian hospitality on the shores of Lake Malawi, since 1998',
                'sort_order' => 1,
                'body' => 'Established in 1998, Lakeside Hotel and Conference Centre is one of the most highly appreciated getaways in Salima. With vast years of experience, we have valuable insight in the industry, local knowledge and cultural sensitivity. We share the connection we have with the surrounding communities with our guests, creating a unique experience.

We sit on the shore of Lake Malawi at Senga Bay, where the water is clear, the sunsets are long, and the pace of the day is set by the lake itself. We are a short drive from Salima town and about an hour and three quarters from Lilongwe, which makes us an easy weekend away from the capital and a natural stopping point on the road north.

Our 42 rooms look out across gardens or water. The Lakeview Restaurant cooks what the lake and the surrounding farms give us. And our team has been welcoming travellers to this stretch of coast for over twenty five years.',
            ],
            [
                'key' => 'mission',
                'title' => 'Our mission',
                'subtitle' => 'Why we do this',
                'sort_order' => 2,
                'body' => 'Lakeside Hotel, with its history, is devoted to establishing abiding relationships with our clients by providing highly personalised services and pleasant hospitality in a comfortable and elegant setting.',
            ],
            [
                'key' => 'vision',
                'title' => 'Our vision',
                'subtitle' => 'Where we are going',
                'sort_order' => 3,
                'body' => 'We aim to have our name known as one of the premier luxury destinations in the country and be the destination of choice for communities and investors. We want to create a personalised experience for every guest, that they will treasure forever.',
            ],
            [
                'key' => 'about.lake',
                'title' => 'On the water',
                'subtitle' => 'Lake Malawi, right on the doorstep',
                'sort_order' => 4,
                'body' => 'Lake Malawi holds more fish species than any other lake in the world. You can snorkel over the rock shelves at the edge of the bay, watch fish eagles hunt from the jetty, or take our speed boat out to the islands and be back in time for lunch.',
            ],
            [
                'key' => 'dining.intro',
                'title' => 'The Lakeview Restaurant',
                'subtitle' => 'Tradition and modernity, with the lake in front of you',
                'sort_order' => 5,
                'body' => 'We cordially invite you to our Lakeview Restaurant, which combines tradition with modernity in an exceptional way.

Even if you are not staying the night, enjoy your meal while gazing at the beauty that is Lake Malawi. We cater to our guests however they wish to be served: are you coming for a day and just want lunch and a swim, or hosting a business conference and need us to cater for your colleagues?

We serve international and local dishes, prepared by a team of skilled chefs. Our multi-cuisine restaurant seats close to 400 people in one sitting, giving you dining service in an environment right on the lake.',
            ],
            [
                'key' => 'conferences.intro',
                'title' => 'Conference facilities at the lake',
                'subtitle' => 'Four halls, up to 250 delegates, Wi-Fi throughout',
                'sort_order' => 6,
                'body' => 'Our conference facilities offer a high-class and stylish environment for social get-togethers, in-residence conferences and business meetings.

The ultra-modern, contemporarily furnished conference centre seats up to 250 delegates, with Namalenje, Mikute and Mbenje halls taking 250, 100 and 50 respectively. Each hall can be set up theatre style, as a classroom or as a boardroom, and they combine for larger gatherings.

The facility is backed up with Wi-Fi internet connectivity, the latest audio-visual devices, professional support and warm hospitality.',
            ],
            [
                'key' => 'leisure.intro',
                'title' => 'Leisure on the lake',
                'subtitle' => 'Water sports, island trips and quiet evenings',
                'sort_order' => 7,
                'body' => 'We offer a range of water activities including water skiing, boating, snorkelling and an island tour on a 200 horsepower speed boat, music included.

There is tubing, parasailing, fishing off the island, and bird watching and feeding. Back on land there is a private family cinema with an HD projector, kids golf, a lounge with a gaming zone, photo and pre-wedding shoots, bonfire nights, candle-lit dinners and spa treatments on request.',
            ],
            [
                'key' => 'booking.policies',
                'title' => 'Booking policies',
                'subtitle' => 'Everything you need to know before you reserve',
                'sort_order' => 8,
                'body' => 'Rates are quoted per room per night in Malawian Kwacha and include breakfast for two. Government value added tax of 16.5% and the 1% tourism levy are added at checkout.

Check in is from 14:00 and check out is by 11:00. Later check out can be arranged at reception, subject to availability.

Free cancellation applies up to 7 days before arrival. Within 7 days the first night is charged. No shows are charged in full.

We accept card payments and mobile money through Airtel Money and TNM Mpamba, or you can reserve now and settle at the hotel.',
            ],
            [
                'key' => 'transfers',
                'title' => 'Getting here',
                'subtitle' => 'From Lilongwe to the lake, arranged for you',
                'sort_order' => 9,
                'body' => 'Senga Bay is on the S122 shore road, about 25 km from Salima town and roughly an hour and three quarters from Lilongwe.

Our reservations office is at Infinity Complex, Sir Glyn Joones Rd., Area 3, Lilongwe - before the Lilongwe Waterboard - where you are welcome to call in.

We can collect you from Kamuzu International Airport. Please request your transfer when you book, or at least 24 hours before arrival.',
            ],
        ];

        foreach ($blocks as $block) {
            ContentBlock::query()->updateOrCreate(['key' => $block['key']], $block + ['is_active' => true]);
        }
    }

    /**
     * The full-screen homepage carousel.
     */
    private function seedHeroSlides(): void
    {
        $slides = [
            [
                'headline' => 'Your Lakeside Escape in Senga Bay',
                'subheadline' => 'Wake up to Lake Malawi at your window. Forty two rooms, from standard doubles to executive suites, on a quiet stretch of shoreline.',
                'cta_label' => 'Book Your Stay',
                'cta_url' => '/booking',
                'secondary_cta_label' => 'Explore Rooms',
                'secondary_cta_url' => '/rooms',
                'sort_order' => 1,
            ],
            [
                'headline' => 'Sunsets over the water',
                'subheadline' => 'Dinner at the Lakeview Restaurant, a drink at the bar, and the sun going down across the bay.',
                'cta_label' => 'See Dining',
                'cta_url' => '/dining',
                'secondary_cta_label' => 'View Gallery',
                'secondary_cta_url' => '/gallery',
                'sort_order' => 2,
            ],
            [
                'headline' => 'Days on Lake Malawi',
                'subheadline' => 'Island tours on our 200 horsepower speed boat, water skiing, snorkelling, or nothing at all.',
                'cta_label' => 'Explore Activities',
                'cta_url' => '/activities',
                'secondary_cta_label' => 'Check Availability',
                'secondary_cta_url' => '/booking',
                'sort_order' => 3,
            ],
        ];

        foreach ($slides as $slide) {
            HeroSlide::query()->updateOrCreate(['headline' => $slide['headline']], $slide + ['is_active' => true]);
        }
    }

    /**
     * The gallery grid. Photographs are attached to these tiles in a later pass.
     */
    private function seedGallery(): void
    {
        $tiles = [
            ['The swimming pool', 'The pool and sun deck overlooking the lake', 'pool', true],
            ['Beachfront at Senga Bay', 'Our stretch of shoreline in the early morning', 'beach', true],
            ['Sunset over the bay', 'The view from the terrace at dusk', 'lake', true],
            ['Namalenje Hall', 'Our largest conference hall set for a plenary', 'events', true],
            ['The Lakeview Restaurant', 'Dinner service on the terrace', 'dining', true],
            ['Deluxe Double', 'A deluxe double with the lake in view', 'rooms', true],
            ['The bar and lounge', 'Drinks, a pool table and the lake beyond', 'dining', true],
            ['Island tour by speed boat', 'Out on the water as the light goes', 'activities', true],
            ['Gazebo and gardens', 'The thatched gazebo by the pool', 'hotel', true],
            ['Wedding on the beach', 'A ceremony on the sand at golden hour', 'weddings', true],
            ['Deluxe Family', 'A family room set up for five', 'rooms', false],
            ['Standard Double', 'A standard double room', 'rooms', false],
            ['Grilled chambo', 'Chambo straight off the grill with nsima', 'dining', false],
            ['Water skiing', 'Out on the lake behind the speed boat', 'activities', false],
            ['Birdlife on the shore', 'Fish eagles and kingfishers along the bay', 'lake', false],
            ['Conference break-out room', 'Mikute Hall set for a workshop', 'events', false],
            ['Poolside at midday', 'Loungers, shade and cold drinks', 'pool', false],
            ['Bonfire on the beach', 'Evenings on the sand', 'beach', false],
        ];

        foreach ($tiles as $index => [$title, $caption, $category, $featured]) {
            GalleryItem::query()->updateOrCreate(
                ['title' => $title],
                [
                    'caption' => $caption,
                    'category' => $category,
                    'type' => 'image',
                    'sort_order' => $index + 1,
                    'is_featured' => $featured,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * What guests can reach from the hotel.
     */
    private function seedNearbyAttractions(): void
    {
        $attractions = [
            ['Lizard Island', 'nature', 'A short boat ride from our jetty, with clear water for snorkelling over the rock shelves and monitor lizards on the rocks.', 3.5, 15],
            ['Senga Bay Market', 'market', 'Fresh fish, vegetables and everyday supplies, best visited in the morning when the boats come in.', 2.0, 10],
            ['Kuti Wildlife Reserve', 'nature', 'A community run reserve near Salima with zebra, sable antelope, giraffe and over 300 bird species.', 32.0, 45],
            ['Nkhotakota Wildlife Reserve', 'nature', 'One of Malawi\'s oldest and largest reserves, now restocked with elephant and buffalo.', 88.0, 105],
            ['Lake Malawi National Park', 'nature', 'A UNESCO World Heritage Site at Cape Maclear, protecting the rock-dwelling cichlid fish of the lake.', 135.0, 165],
            ['Salima Town Centre', 'town', 'The district capital, with banks, a hospital and the main road link north.', 25.0, 30],
            ['Mua Mission', 'culture', 'A mission founded in 1903 with the Kungoni Centre of Culture and Art, and Malawi\'s finest wood carving.', 95.0, 120],
            ['Lingadzi River mouth', 'nature', 'Where the river meets the lake, popular with birdwatchers and fishermen.', 8.0, 20],
        ];

        foreach ($attractions as $index => [$name, $category, $description, $distance, $minutes]) {
            NearbyAttraction::query()->updateOrCreate(
                ['name' => $name],
                [
                    'category' => $category,
                    'description' => $description,
                    'distance_km' => $distance,
                    'travel_time_minutes' => $minutes,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
