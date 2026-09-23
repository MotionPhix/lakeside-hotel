<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\NewsletterSubscriber;
use App\Models\Offer;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Guest voice and marketing: the offers on the website, the coupon codes behind
 * them, reviews waiting to be moderated next to the ones already published, and
 * the newsletter list.
 */
class MarketingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedCoupons();
        $this->seedOffers();
        $this->seedTestimonials();
        $this->seedNewsletter();
    }

    /**
     * The codes guests can enter at checkout.
     */
    private function seedCoupons(): void
    {
        $year = (int) now()->format('Y');

        $coupons = [
            ['code' => 'WEEKEND15', 'description' => '15% off a weekend stay of two nights or more.', 'discount_type' => 'percentage', 'discount_value' => 15, 'min_nights' => 2, 'valid_until' => "{$year}-12-31"],
            ['code' => 'GREEN20', 'description' => '20% off during the green season, January to March.', 'discount_type' => 'percentage', 'discount_value' => 20, 'valid_from' => "{$year}-01-01", 'valid_until' => "{$year}-03-31"],
            ['code' => 'CORPORATE10', 'description' => '10% off for negotiated corporate accounts.', 'discount_type' => 'percentage', 'discount_value' => 10, 'valid_until' => null],
            ['code' => 'STAY3PAY2', 'description' => 'Three nights for the price of two.', 'discount_type' => 'percentage', 'discount_value' => 33, 'min_nights' => 3, 'valid_until' => "{$year}-12-20"],
            ['code' => 'HONEYMOON25', 'description' => '25% off for honeymoon stays, on request.', 'discount_type' => 'percentage', 'discount_value' => 25, 'min_nights' => 3, 'valid_until' => "{$year}-12-31"],
            ['code' => 'LONGSTAY10', 'description' => 'MWK 250,000 off stays of five nights or more.', 'discount_type' => 'fixed', 'discount_value' => 250_000, 'min_nights' => 5, 'min_spend' => 600_000, 'valid_until' => "{$year}-12-31"],
            ['code' => 'FESTIVE24', 'description' => 'Early booking rate for the festive season. Closed.', 'discount_type' => 'percentage', 'discount_value' => 10, 'valid_from' => "{$year}-09-01", 'valid_until' => "{$year}-10-31", 'is_active' => false],
        ];

        foreach ($coupons as $coupon) {
            Coupon::query()->updateOrCreate(
                ['code' => $coupon['code']],
                $coupon + [
                    'min_nights' => $coupon['min_nights'] ?? null,
                    'min_spend' => $coupon['min_spend'] ?? null,
                    'valid_from' => $coupon['valid_from'] ?? now()->subMonth()->toDateString(),
                    'usage_limit' => null,
                    'used_count' => 0,
                    'is_active' => $coupon['is_active'] ?? true,
                ],
            );
        }
    }

    /**
     * The special offers advertised on the website.
     */
    private function seedOffers(): void
    {
        $year = (int) now()->format('Y');
        $month = (int) now()->format('n');

        // Keep the seeded offers relevant whenever the seeder is run.
        $seasonStart = $month >= 4 && $month <= 9
            ? now()->subWeeks(2)
            : now()->subMonths(3);

        $offers = [
            [
                'title' => 'Weekend Lakeside Special',
                'type' => 'weekend',
                'subtitle' => 'Two nights by the water, Friday to Sunday',
                'description' => 'Arrive on Friday, wake up to the lake twice, and leave after a late breakfast on Sunday. Includes a complimentary sunset cruise for two and a bottle of wine in your room on arrival.',
                'highlight' => 'Save 15%',
                'discount_label' => '15% off',
                'coupon_code' => 'WEEKEND15',
                'sort_order' => 1,
                'is_featured' => true,
            ],
            [
                'title' => 'Stay 3, Pay 2',
                'type' => 'package',
                'subtitle' => 'A third night on us',
                'description' => 'Book three nights or more and only pay for two. The longer you stay, the more of the lake you see - and the more time you have to do absolutely nothing on the beach.',
                'highlight' => 'Third night free',
                'discount_label' => '33% off',
                'coupon_code' => 'STAY3PAY2',
                'sort_order' => 2,
                'is_featured' => true,
            ],
            [
                'title' => 'Festive Season at the Lake',
                'type' => 'holiday',
                'subtitle' => 'Christmas and New Year in Senga Bay',
                'description' => 'Spend the holidays on the water. Christmas lunch on the terrace, a New Year braai on the beach, and activities for the children every morning. Minimum three night stay.',
                'highlight' => 'Christmas and New Year',
                'discount_label' => 'From MWK 240,000',
                'coupon_code' => null,
                'starts_on' => "{$year}-12-20",
                'ends_on' => ($year + 1).'-01-03',
                'sort_order' => 3,
                'is_featured' => true,
            ],
            [
                'title' => 'Honeymoon on the Lake',
                'type' => 'honeymoon',
                'subtitle' => 'An executive suite, a private dinner, and the sunset',
                'description' => 'Three nights in an executive suite with a private dinner served on your veranda, a couples boat trip to Lizard Island, and a late check out so you are not rushing on the last morning.',
                'highlight' => 'Save 25%',
                'discount_label' => '25% off',
                'coupon_code' => 'HONEYMOON25',
                'sort_order' => 4,
                'is_featured' => true,
            ],
            [
                'title' => 'Corporate Retreat Rate',
                'type' => 'corporate',
                'subtitle' => 'For teams that work better away from the office',
                'description' => 'Negotiated room rates for corporate bookings, plus discounted conference packages and complimentary use of the conference room for every fifteen rooms booked.',
                'highlight' => 'Save 15%',
                'discount_label' => '15% off',
                'coupon_code' => 'CORPORATE10',
                'sort_order' => 5,
                'is_featured' => false,
            ],
            [
                'title' => 'Green Season Escape',
                'type' => 'seasonal',
                'subtitle' => 'January to March, when the lake is at its fullest',
                'description' => 'The quiet months are the greenest. Lower rates, an emptier beach, and the best birdwatching of the year around the river mouth.',
                'highlight' => 'Save 20%',
                'discount_label' => '20% off',
                'coupon_code' => 'GREEN20',
                'starts_on' => "{$year}-01-05",
                'ends_on' => "{$year}-03-31",
                'sort_order' => 6,
                'is_featured' => false,
            ],
        ];

        foreach ($offers as $offer) {
            Offer::query()->updateOrCreate(
                ['slug' => Str::slug($offer['title'])],
                $offer + [
                    'starts_on' => $offer['starts_on'] ?? $seasonStart->toDateString(),
                    'ends_on' => $offer['ends_on'] ?? now()->addMonths(4)->toDateString(),
                    'terms' => 'Subject to availability. Cannot be combined with other offers. Blackout dates apply over public holidays and the festive season.',
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * Guest reviews, including a few still waiting for moderation.
     */
    private function seedTestimonials(): void
    {
        $owner = User::query()->where('role', 'admin')->first();

        $reviews = [
            ['Thandiwe M.', 'Malawi', 5, 'The best weekend we have had in years', 'We drove up from Lilongwe on a Friday and did not want to leave. The chalet deck over the water is worth every kwacha. Breakfast on the terrace watching the fishermen come in was the highlight.', 21, 'google', true, true, 'Thank you Thandiwe - the deck is our favourite spot too. Come back soon.', true],
            ['James H.', 'United Kingdom', 5, 'A proper lakeside escape', 'We stayed four nights as part of a longer Malawi trip and this was the part we talk about most. The staff arranged a fishing trip at short notice and cooked our catch for lunch the next day.', 48, 'tripadvisor', true, true, 'It was a pleasure - we will let the skipper know. Safe travels home.', true],
            ['Karin S.', 'Germany', 5, 'Peaceful, warm and beautifully run', 'The gardens run right down to the water and there is always somewhere quiet to sit. Our room was spotless and the mosquito nets meant we slept well. The chambo at dinner was superb.', 72, 'booking_com', true, true, null, false],
            ['Peter B.', 'South Africa', 4, 'Excellent value for a lakeside hotel', 'Rooms are simple but very comfortable and the location cannot be beaten. The pool bar menu is good. Only note is that the WiFi is slower than at home, which honestly did us good.', 35, 'google', true, true, null, false],
            ['Chikondi P.', 'Malawi', 5, 'Our conference went perfectly', 'We hosted sixty delegates for two days. The conference room splits in two which we needed, the sound system worked, and the catering was on time for every break. The team made it easy.', 14, 'website', true, true, 'Thank you for trusting us with the event - we look forward to the next one.', true],
            ['Aisha K.', 'Kenya', 4, 'Beautiful sunsets and kind people', 'The sunset cruise was the best part of our trip. The boat crew were lovely with our children. Breakfast could start a little earlier for guests heading out on early activities.', 60, 'tripadvisor', true, true, 'Noted on the early breakfast - we now open at 06:00 for guests going out early. Thank you for the suggestion.', true],
            ['Michael O.', 'United States', 5, 'We got married here', 'We had the ceremony on the beach and the reception on the terrace. The coordinator handled everything, including a rain plan we thankfully did not need. Guests are still talking about the food.', 96, 'website', true, true, 'Congratulations again - it was an honour to host you both.', true],
            ['Linda M.', 'Zambia', 4, 'Lovely family stay', 'Our three children lived in the pool. The family room was big enough for all of us and the walk to the beach is short. We would happily come back.', 27, 'booking_com', true, false, null, false],
            ['Grace T.', 'Malawi', 5, 'Booked the chalets for a family reunion', 'Sixteen of us across four chalets. The team set up a long table on the beach for dinner on the Saturday, which nobody expected and everybody loved.', 40, 'website', true, false, null, false],
            ['David R.', 'Australia', 3, 'Good stay, a few small things', 'Great location and friendly staff. The air conditioning in our first room was struggling and it took a day to sort out, but they moved us and were apologetic. Would still recommend.', 55, 'tripadvisor', true, false, null, false],
            ['Fatima N.', 'Tanzania', 5, 'The green season was a bargain', 'Came in February on the green season rate. Quiet, lush, and we had most of the beach to ourselves. Best value trip we have taken in years.', 110, 'google', false, false, null, false],
            ['Joseph K.', 'Malawi', 4, 'Great spot for a birthday', 'Held my wife\'s fortieth on the terrace. Food, music and setting were all excellent. Waiting to hear back about a date next year.', 5, 'website', false, false, null, false],
        ];

        foreach ($reviews as [$name, $country, $rating, $title, $quote, $daysAgo, $source, $approved, $featured, $response, $responded]) {
            Testimonial::query()->updateOrCreate(
                ['guest_name' => $name, 'title' => $title],
                [
                    'guest_country' => $country,
                    'rating' => $rating,
                    'quote' => $quote,
                    'stayed_on' => now()->subDays($daysAgo)->toDateString(),
                    'source' => $source,
                    'is_approved' => $approved,
                    'is_featured' => $featured,
                    'response' => $responded ? $response : null,
                    'responded_at' => $responded ? now()->subDays(max($daysAgo - 3, 1)) : null,
                    'responded_by' => $responded ? $owner?->id : null,
                ],
            );
        }
    }

    /**
     * The newsletter list.
     */
    private function seedNewsletter(): void
    {
        $names = [
            'Chikondi Banda', 'Thandiwe Moyo', 'Yamikani Zulu', 'Mphatso Gondwe', 'Tamara Nkhoma',
            'Limbani Kachale', 'Grace Banda', 'Peter Nyirenda', 'Aisha Kamanga', 'John Chirwa',
            'Memory Tembo', 'Fatsani Mwale', 'Blessings Kalua', 'Ruth Selemani', 'Daniel Mvula',
            'Alinafe Phiri', 'Katie Miller', 'Sipho Dlamini', 'Emily Carter', 'Mwai Kamoto',
            'Tiwonge Msiska', 'Isaac Nkosi', 'Hannah Wright', 'Patricia Longwe',
        ];

        foreach ($names as $index => $name) {
            NewsletterSubscriber::subscribe(
                email: Str::slug($name, '.').'@example.com',
                name: $name,
                source: $index % 5 === 0 ? 'offers_page' : ($index % 3 === 0 ? 'booking' : 'footer'),
            );
        }

        // A couple who have since left the list.
        NewsletterSubscriber::subscribe('unsubscribed.one@example.com', 'Former Guest', 'footer')->unsubscribe();
        NewsletterSubscriber::subscribe('unsubscribed.two@example.com', 'Another Guest', 'footer')->unsubscribe();
    }
}
