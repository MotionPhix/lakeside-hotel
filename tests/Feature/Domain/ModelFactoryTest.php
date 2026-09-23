<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Activity;
use App\Models\Amenity;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\ConferenceHall;
use App\Models\ConferencePackage;
use App\Models\ContentBlock;
use App\Models\Coupon;
use App\Models\DiningVenue;
use App\Models\GalleryItem;
use App\Models\Guest;
use App\Models\HeroSlide;
use App\Models\Inquiry;
use App\Models\MenuItem;
use App\Models\NearbyAttraction;
use App\Models\NewsletterSubscriber;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\SiteSection;
use App\Models\Testimonial;
use App\Models\User;

test('every domain model can be created from its factory', function (string $model) {
    $instance = $model::factory()->create();

    expect($instance->exists)->toBeTrue();
    expect($model::query()->count())->toBeGreaterThanOrEqual(1);
})->with([
    'setting' => Setting::class,
    'content block' => ContentBlock::class,
    'hero slide' => HeroSlide::class,
    'gallery item' => GalleryItem::class,
    'nearby attraction' => NearbyAttraction::class,
    'amenity' => Amenity::class,
    'room type' => RoomType::class,
    'room' => Room::class,
    'rate plan' => RatePlan::class,
    'availability block' => AvailabilityBlock::class,
    'guest' => Guest::class,
    'coupon' => Coupon::class,
    'booking' => Booking::class,
    'booking item' => BookingItem::class,
    'payment' => Payment::class,
    'dining venue' => DiningVenue::class,
    'menu item' => MenuItem::class,
    'activity' => Activity::class,
    'conference package' => ConferencePackage::class,
    'testimonial' => Testimonial::class,
    'offer' => Offer::class,
    'inquiry' => Inquiry::class,
    'newsletter subscriber' => NewsletterSubscriber::class,
]);

test('a booking created from the factory always carries a priced room line', function () {
    $booking = Booking::factory()->confirmed()->create();

    expect($booking->items)->toHaveCount(1)
        ->and((float) $booking->subtotal)->toBeGreaterThan(0)
        ->and((float) $booking->total)->toBeGreaterThan((float) $booking->subtotal);
});

test('the database seeder builds a complete, browsable hotel', function () {
    $this->seed();

    expect(User::query()->count())->toBe(count(Role::staff()))
        // The seven room categories and the 42 rooms the company profile advertises.
        ->and(RoomType::query()->active()->count())->toBe(7)
        ->and(Room::query()->count())->toBe(42)
        ->and(Setting::value('hotel.rooms_total'))->toBe('42')
        ->and(Amenity::query()->active()->count())->toBe(31)
        ->and(Amenity::query()->category('conference')->count())->toBe(10)
        ->and(RatePlan::query()->active()->count())->toBe(7)
        ->and(AvailabilityBlock::query()->count())->toBe(2)
        ->and(DiningVenue::query()->active()->count())->toBe(3)
        // The restaurant's printed card, transcribed: 20 sections, 145 dishes.
        ->and(MenuItem::query()->count())->toBe(145)
        ->and(MenuItem::query()->distinct()->count('category'))->toBe(20)
        ->and(Activity::query()->active()->count())->toBe(15)
        ->and(ConferenceHall::query()->active()->count())->toBe(3)
        ->and(ConferencePackage::query()->active()->count())->toBe(5)
        ->and(Offer::query()->count())->toBe(6)
        ->and(Coupon::query()->count())->toBe(7)
        ->and(Testimonial::query()->approved()->count())->toBe(10)
        ->and(Testimonial::query()->awaitingModeration()->count())->toBe(2)
        ->and(GalleryItem::query()->active()->count())->toBe(18)
        ->and(NearbyAttraction::query()->active()->count())->toBe(8)
        ->and(Setting::query()->count())->toBe(35)
        ->and(ContentBlock::query()->count())->toBe(9)
        ->and(SiteSection::query()->published('home')->count())->toBe(13)
        ->and(Booking::query()->count())->toBe(52)
        ->and(Booking::query()->holdingInventory()->count())->toBe(27)
        ->and(Payment::query()->count())->toBe(41)
        ->and(Inquiry::query()->count())->toBe(14)
        ->and(Inquiry::query()->open()->count())->toBe(8)
        ->and(NewsletterSubscriber::query()->subscribed()->count())->toBe(24);
});

test('the Lakeside menu is transcribed with the prices the card prints', function () {
    $this->seed();

    $restaurant = DiningVenue::query()->where('slug', 'lakeview-restaurant')->sole();

    $price = fn (string $name): string => (string) $restaurant->menuItems()->where('name', $name)->sole()->price;

    expect($restaurant->menuItems()->count())->toBe(145)
        // Priced under the section heading, the way the card prints them.
        ->and($price('Greek Salad'))->toBe('10000.00')
        ->and($price('Sweet Corn Soup (Veg/Chicken/Beef)'))->toBe('12000.00')
        ->and($price('Beef Burger'))->toBe('18000.00')
        ->and($price('Margherita'))->toBe('35000.00')
        ->and($price('Paneer Kolapuri'))->toBe('20000.00')
        ->and($price('Butter Naan'))->toBe('5000.00')
        ->and($price('Masala Pappad'))->toBe('2500.00')
        ->and($price('Coke'))->toBe('2000.00')
        // Dishes the card breaks out on their own line.
        ->and($price('Quarter Chicken Braai with Chips'))->toBe('18000.00')
        ->and($price('Quarter Chicken'))->toBe('18000.00')
        ->and($price('Triple Schezwan Rice'))->toBe('25000.00')
        ->and($price('Biryani (Veg/Chicken/Beef)'))->toBe('30000.00')
        ->and($price('Pulao (Veg/Chicken)'))->toBe('30000.00')
        ->and($price('Ice Cream — Cone or Cup'))->toBe('5000.00')
        ->and($price('Fried Ice Cream'))->toBe('15000.00')
        // Notes are held once per section and once for the card.
        ->and($restaurant->section_notes)->toHaveKeys(['sandwiches', 'burgers', 'pasta', 'pizzas', 'warm_heart_dishes'])
        ->and($restaurant->section_notes['sandwiches'])->toBe('All served with chips. Extra cheese MK 2,000.')
        ->and($restaurant->menu_note)->toBe('Prices are tax inclusive.')
        // The bars have no invented prices sitting on them.
        ->and(MenuItem::query()->where('dining_venue_id', '!=', $restaurant->getKey())->count())->toBe(0);
});

test('seeded room categories are priced, amenitied and stocked with rooms', function () {
    $this->seed();

    foreach (RoomType::query()->active()->get() as $roomType) {
        expect((float) $roomType->base_price)->toBeGreaterThan(0)
            ->and($roomType->amenities)->not->toBeEmpty()
            ->and($roomType->rooms)->not->toBeEmpty();
    }
});

test('seeded bookings carry priced lines and consistent money', function () {
    $this->seed();

    foreach (Booking::query()->with('items')->get() as $booking) {
        expect($booking->items)->not->toBeEmpty()
            ->and((float) $booking->subtotal)->toBeGreaterThan(0)
            ->and((float) $booking->total)->toBeGreaterThanOrEqual((float) $booking->subtotal);

        if ($booking->status === BookingStatus::Cancelled) {
            expect($booking->payment_status)->toBe(PaymentStatus::Refunded);

            continue;
        }

        if ($booking->status === BookingStatus::Pending) {
            expect($booking->payment_status)->toBe(PaymentStatus::Unpaid);
        }
    }
});
