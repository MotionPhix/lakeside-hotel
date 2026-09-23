<?php

use App\Enums\Role;
use App\Models\Activity;
use App\Models\Amenity;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingItem;
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
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Support\Carbon;

/**
 * The project convention is the Laravel 13 `#[Scope]` attribute rather than the
 * legacy `scopeXxx` method names, recorded in `.ai/rules/models.md`.
 *
 * These guards matter because a `#[Scope]` whose import is missing does not fail
 * loudly: PHP does not resolve attribute classes while compiling, so the method
 * quietly stops being a scope and `$query->type('x')` degrades into a dynamic
 * `whereType('x')`, returning wrong rows instead of raising an error.
 */
$domainModels = [
    Setting::class,
    ContentBlock::class,
    HeroSlide::class,
    GalleryItem::class,
    NearbyAttraction::class,
    Amenity::class,
    RoomType::class,
    Room::class,
    RatePlan::class,
    AvailabilityBlock::class,
    Guest::class,
    Coupon::class,
    Booking::class,
    BookingItem::class,
    Payment::class,
    DiningVenue::class,
    MenuItem::class,
    Activity::class,
    ConferencePackage::class,
    Testimonial::class,
    Offer::class,
    Inquiry::class,
    NewsletterSubscriber::class,
    User::class,
];

/**
 * Only the methods written in the model's own file. Traits and parent classes are
 * excluded, because Spatie's media trait ships its own `scopeWhereHasMedia` and
 * would otherwise look like a convention violation.
 *
 * @return list<ReflectionMethod>
 */
$ownMethods = function (string $class): array {
    $reflection = new ReflectionClass($class);
    $file = $reflection->getFileName();

    return array_values(array_filter(
        $reflection->getMethods(),
        fn (ReflectionMethod $method): bool => $method->getFileName() === $file,
    ));
};

test('no model declares a legacy scope prefix method', function (string $class) use ($ownMethods) {
    $legacy = array_values(array_map(
        fn (ReflectionMethod $method): string => $method->getName(),
        array_filter(
            $ownMethods($class),
            fn (ReflectionMethod $method): bool => str_starts_with($method->getName(), 'scope'),
        ),
    ));

    expect($legacy)->toBe([]);
})->with($domainModels);

test('every attribute used in a model is the fully qualified Scope attribute', function (string $class) use ($ownMethods) {
    $offenders = [];

    foreach ($ownMethods($class) as $method) {
        foreach ($method->getAttributes() as $attribute) {
            if ($attribute->getName() !== Scope::class) {
                $offenders[] = $class.'::'.$method->getName().' uses '.$attribute->getName();
            }
        }
    }

    expect($offenders)->toBe([]);
})->with($domainModels);

test('every Scope attribute is reachable as a named scope', function (string $class) use ($ownMethods) {
    $model = new $class;
    $scopes = [];

    foreach ($ownMethods($class) as $method) {
        if ($method->getAttributes(Scope::class) !== []) {
            $scopes[] = $method->getName();
        }
    }

    $unresolved = array_values(array_filter(
        $scopes,
        fn (string $name): bool => ! $model->hasNamedScope($name),
    ));

    expect($unresolved)->toBe([]);

    // Models that are known to carry scopes must not pass this guard vacuously.
    if (in_array($class, [Booking::class, RoomType::class, Testimonial::class, Inquiry::class, User::class], true)) {
        expect($scopes)->not->toBeEmpty();
    }
})->with($domainModels);

test('attribute scopes constrain the query rather than falling through to a dynamic where', function () {
    $booking = Booking::factory()->confirmed()->forStay('2026-07-10', '2026-07-13')->create();

    expect(Booking::query()->arrivingOn(Carbon::parse('2026-07-10'))->pluck('id')->all())->toBe([$booking->id])
        ->and(Booking::query()->arrivingOn(Carbon::parse('2026-07-11'))->count())->toBe(0)
        ->and(Booking::query()->coveringDate(Carbon::parse('2026-07-11'))->pluck('id')->all())->toBe([$booking->id])
        ->and(Booking::query()->coveringDate(Carbon::parse('2026-07-13'))->count())->toBe(0);
});

test('a booking line still reads plain columns that share a scope name elsewhere', function () {
    $item = BookingItem::factory()->create(['adults' => 2, 'children' => 1]);

    expect($item->guests())->toBe(3)
        ->and($item->booking)->not->toBeNull();
});

test('the staff scopes used by the dashboard behave', function () {
    $receptionist = User::factory()->role(Role::Reception)->create();
    User::factory()->role(Role::Reception)->inactive()->create();
    User::factory()->guest()->create();

    expect(User::query()->active()->count())->toBe(2)
        ->and(User::query()->staff()->count())->toBe(2)
        ->and(User::query()->search($receptionist->name)->count())->toBe(1)
        ->and(User::query()->search('nobody at all')->count())->toBe(0);
});
