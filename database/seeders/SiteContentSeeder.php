<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\GalleryItem;
use App\Models\HeroSlide;
use App\Models\NearbyAttraction;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * The hotel's own words: contact details, the About story, booking policies, the
 * homepage hero, the gallery structure and what is worth seeing nearby.
 *
 * Photographs are attached to these records in a later pass, once the lakeside
 * image set exists.
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
            ['hotel.name', 'Lakeside Hotel', 'general', 'string'],
            ['hotel.tagline', 'Your Lakeside Escape in Senga Bay', 'general', 'string'],
            ['hotel.address', 'Senga Bay, Salima District, Malawi', 'contact', 'string'],
            ['hotel.phone', '+265 1 252 148', 'contact', 'string'],
            ['hotel.whatsapp', '+265 99 512 3400', 'contact', 'string'],
            ['hotel.email', 'reservations@lakesidehotel.mw', 'contact', 'email'],
            ['hotel.events_email', 'events@lakesidehotel.mw', 'contact', 'email'],
            ['hotel.latitude', '-13.716700', 'contact', 'string'],
            ['hotel.longitude', '34.616700', 'contact', 'string'],
            ['hotel.map_zoom', '14', 'contact', 'integer'],
            ['hotel.check_in_time', '14:00', 'booking', 'time'],
            ['hotel.check_out_time', '11:00', 'booking', 'time'],
            ['hotel.currency', 'MWK', 'booking', 'string'],
            ['booking.vat_rate', '16.5', 'booking', 'decimal'],
            ['booking.tourism_levy_rate', '1', 'booking', 'decimal'],
            ['booking.deposit_percentage', '50', 'booking', 'integer'],
            ['booking.pay_at_hotel_enabled', '1', 'booking', 'boolean'],
            ['booking.online_payment_enabled', '1', 'booking', 'boolean'],
            ['booking.cancellation_policy', 'Free cancellation up to 7 days before arrival. Within 7 days the first night is charged. No shows are charged in full.', 'booking', 'text'],
            ['booking.child_policy', 'Children under 12 stay free when sharing with two adults. Cots are available on request.', 'booking', 'text'],
            ['booking.transfer_note', 'Airport transfers from Lilongwe Kamuzu International Airport take about 1 hour 45 minutes. Please book at least 24 hours ahead.', 'booking', 'text'],
            ['social.facebook', 'https://www.facebook.com/lakesidehotelsengabay', 'social', 'string'],
            ['social.instagram', 'https://www.instagram.com/lakesidehotelsengabay', 'social', 'string'],
            ['social.tripadvisor', 'https://www.tripadvisor.com/', 'social', 'string'],
            ['seo.default_title', 'Lakeside Hotel | Lakeside accommodation in Senga Bay, Salima', 'seo', 'string'],
            ['seo.default_description', 'Book a lakeside room, chalet or suite at Lakeside Hotel in Senga Bay, Salima. Lake Malawi views, restaurant, pool, conference facilities and lake activities.', 'seo', 'text'],
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
                'subtitle' => 'Warm Malawian hospitality on the shores of Lake Malawi',
                'sort_order' => 1,
                'body' => 'Lakeside Hotel sits on the quiet shoreline at Senga Bay, where the water is clear, the sunsets are long, and the pace of the day is set by the lake itself.

We are a short drive from Salima and about an hour and three quarters from Lilongwe, which makes us an easy weekend escape from the capital and a natural stopping point on the way north.

Our rooms, suites and chalets all look out over gardens or water. Our kitchen cooks what the lake and the surrounding farms give us. And our team has been welcoming travellers to this stretch of coast for years - whether you are here for a family holiday, a conference, or a wedding on the beach.',
            ],
            [
                'key' => 'about.lake',
                'title' => 'On the water',
                'subtitle' => 'Lake Malawi, right on the doorstep',
                'sort_order' => 2,
                'body' => 'Lake Malawi holds more fish species than any other lake in the world. You can snorkel over the rock shelves at the edge of the bay, watch fish eagles hunt from the jetty, or take a boat out to Lizard Island and be back in time for lunch.',
            ],
            [
                'key' => 'dining.intro',
                'title' => 'Food from the lake and the farm',
                'subtitle' => 'Chambo, tilapia, and produce from around Salima',
                'sort_order' => 3,
                'body' => 'Our kitchen is built around chambo and tilapia landed the same morning, vegetables from growers around Salima, and the flavours of Malawian home cooking. Breakfast, lunch and dinner are served on the terrace overlooking the water.',
            ],
            [
                'key' => 'conferences.intro',
                'title' => 'Meetings with a view',
                'subtitle' => 'Conference, retreat and event facilities at the lake',
                'sort_order' => 4,
                'body' => 'Our conference centre looks out over the gardens to the water. It seats up to 120 delegates theatre style, splits into two smaller rooms, and comes with a dedicated events coordinator, projector and screen, and full catering. Teams come here to work, and stay for the sunsets.',
            ],
            [
                'key' => 'booking.policies',
                'title' => 'Booking policies',
                'subtitle' => 'Everything you need to know before you reserve',
                'sort_order' => 5,
                'body' => 'Rates are quoted per room per night in Malawian Kwacha and include breakfast for two. Government value added tax of 16.5% and the 1% tourism levy are added at checkout.

Check in is from 14:00 and check out is by 11:00. Later check out can be arranged at reception, subject to availability.

Free cancellation applies up to 7 days before arrival. Within 7 days the first night is charged. No shows are charged in full.

We accept card payments and mobile money through Airtel Money and TNM Mpamba, or you can reserve now and settle at the hotel.',
            ],
            [
                'key' => 'transfers',
                'title' => 'Airport transfers',
                'subtitle' => 'From Lilongwe to the lake, arranged for you',
                'sort_order' => 6,
                'body' => 'We can collect you from Kamuzu International Airport in Lilongwe. The drive to Senga Bay takes about one hour forty-five minutes. Please request your transfer when you book, or at least 24 hours before arrival.',
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
                'subheadline' => 'Wake up to Lake Malawi at your window. Rooms, suites and chalets on a quiet stretch of shoreline, with warm Malawian hospitality.',
                'cta_label' => 'Book Your Stay',
                'cta_url' => '/booking',
                'secondary_cta_label' => 'Explore Rooms',
                'secondary_cta_url' => '/rooms',
                'sort_order' => 1,
            ],
            [
                'headline' => 'Sunsets over the water',
                'subheadline' => 'Dinner on the terrace, a cold drink at the pool bar, and the sun going down across the bay.',
                'cta_label' => 'See Dining',
                'cta_url' => '/dining',
                'secondary_cta_label' => 'View Gallery',
                'secondary_cta_url' => '/gallery',
                'sort_order' => 2,
            ],
            [
                'headline' => 'Days on Lake Malawi',
                'subheadline' => 'Sunset cruises, fishing trips, snorkelling over the rock shelves, or nothing at all.',
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
     * The gallery grid. Photographs are attached to these tiles later.
     */
    private function seedGallery(): void
    {
        $tiles = [
            ['Lakeside gardens', 'The lawns running down to the water', 'hotel', true],
            ['Beachfront at Senga Bay', 'Our stretch of shoreline in the early morning', 'beach', true],
            ['Sunset over the bay', 'The view from the terrace at dusk', 'lake', true],
            ['Deluxe room', 'Garden facing deluxe rooms', 'rooms', true],
            ['Lakeside chalet', 'A chalet with its own deck over the water', 'rooms', true],
            ['The pool', 'The swimming pool and sun deck', 'pool', true],
            ['Lakeview Restaurant', 'Dinner service on the terrace', 'dining', true],
            ['Anchor Bar & Lounge', 'Drinks at the bar', 'dining', false],
            ['Sunset cruise', 'Out on the water as the light goes', 'activities', true],
            ['Fishing at dawn', 'Heading out before the heat', 'activities', true],
            ['Conference centre', 'The main conference room set for a plenary', 'events', true],
            ['Wedding on the beach', 'A ceremony on the sand at golden hour', 'weddings', true],
            ['Lizard Island', 'The short boat ride to the island', 'lake', false],
            ['Jetty and gardens', 'Walking down to the jetty', 'hotel', false],
            ['Family room', 'A family room set up for four', 'rooms', false],
            ['Grilled chambo', 'Chambo straight off the grill', 'dining', false],
            ['Tea on the terrace', 'Afternoon tea overlooking the lake', 'hotel', false],
            ['Pool bar', 'The poolside bar at midday', 'pool', false],
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
            ['Lizard Island', 'nature', 'A short boat ride from the hotel jetty, with clear water for snorkelling and a colony of monitor lizards on the rocks.', 3.5, 15],
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
