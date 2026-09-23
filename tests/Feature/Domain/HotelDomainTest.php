<?php

use App\Enums\InquiryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\RateAdjustmentType;
use App\Enums\Role;
use App\Models\Amenity;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Coupon;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\NewsletterSubscriber;
use App\Models\Payment;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

/**
 * A Friday in July 2026 and the following Tuesday, used to pin down which nights
 * attract the weekend rate.
 */
const WEEKEND_NIGHT = '2026-07-03';

const WEEKDAY_NIGHT = '2026-07-07';

test('a room category uses the weekend rate on Friday and Saturday nights only', function () {
    $roomType = RoomType::factory()->create([
        'base_price' => 200_000,
        'weekend_price' => 240_000,
    ]);

    expect($roomType->nightlyRateFor(Carbon::parse(WEEKEND_NIGHT)))->toBe('240000.00')
        ->and($roomType->nightlyRateFor(Carbon::parse('2026-07-04')))->toBe('240000.00')
        ->and($roomType->nightlyRateFor(Carbon::parse(WEEKDAY_NIGHT)))->toBe('200000.00')
        ->and($roomType->fromPrice())->toBe('200000.00');
});

test('a room category without a weekend price always charges its base price', function () {
    $roomType = RoomType::factory()->create([
        'base_price' => 150_000,
        'weekend_price' => null,
    ]);

    expect($roomType->nightlyRateFor(Carbon::parse(WEEKEND_NIGHT)))->toBe('150000.00');
});

test('a room category exposes its amenities, rooms, rates and bookings', function () {
    $roomType = RoomType::factory()->create();
    $roomType->amenities()->attach(Amenity::factory()->count(3)->create()->pluck('id'));

    Room::factory()->count(2)->create(['room_type_id' => $roomType->id]);
    RatePlan::factory()->create(['room_type_id' => $roomType->id]);
    Coupon::factory()->create(['room_type_id' => $roomType->id]);
    BookingItem::factory()->create(['room_type_id' => $roomType->id]);

    $roomType->refresh();

    expect($roomType->amenities)->toHaveCount(3)
        ->and($roomType->rooms)->toHaveCount(2)
        ->and($roomType->ratePlans)->toHaveCount(1)
        ->and($roomType->coupons)->toHaveCount(1)
        ->and($roomType->bookingItems)->toHaveCount(1)
        ->and($roomType->maxOccupancy())->toBe(2);
});

test('a rate plan only covers the dates and weekdays it declares', function () {
    $weekend = RatePlan::factory()->weekend()->create();
    $july = RatePlan::factory()->season('2026-07-01', '2026-07-31')->create();

    expect($weekend->coversDate(Carbon::parse(WEEKEND_NIGHT)))->toBeTrue()
        ->and($weekend->coversDate(Carbon::parse(WEEKDAY_NIGHT)))->toBeFalse()
        ->and($july->coversDate(Carbon::parse('2026-07-15')))->toBeTrue()
        ->and($july->coversDate(Carbon::parse('2026-08-15')))->toBeFalse();
});

test('a rate plan adjusts a nightly price by amount, percentage or override', function () {
    $plan = RatePlan::factory()->create([
        'adjustment_type' => RateAdjustmentType::Percentage,
        'amount' => 20,
    ]);

    expect($plan->applyTo('100000.00'))->toBe('120000.00');

    $plan->update(['adjustment_type' => RateAdjustmentType::Fixed, 'amount' => -15_000]);

    expect($plan->applyTo('100000.00'))->toBe('85000.00');

    $plan->update(['adjustment_type' => RateAdjustmentType::Override, 'amount' => 200_000]);

    expect($plan->applyTo('100000.00'))->toBe('200000.00');
});

test('a rate plan never pushes a nightly price below zero', function () {
    $plan = RatePlan::factory()->create([
        'adjustment_type' => RateAdjustmentType::Fixed,
        'amount' => -500_000,
    ]);

    expect($plan->applyTo('100000.00'))->toBe('0.00');
});

test('an availability block takes a room off sale for exactly its dates', function () {
    $room = Room::factory()->create();

    AvailabilityBlock::factory()->maintenance()->create([
        'room_id' => $room->id,
        'starts_on' => '2026-07-10',
        'ends_on' => '2026-07-14',
    ]);

    $room->refresh();

    expect($room->isBlockedBetween(Carbon::parse('2026-07-10'), Carbon::parse('2026-07-11')))->toBeTrue()
        ->and($room->isBlockedBetween(Carbon::parse('2026-07-12'), Carbon::parse('2026-07-15')))->toBeTrue()
        ->and($room->isBlockedBetween(Carbon::parse('2026-07-15'), Carbon::parse('2026-07-20')))->toBeFalse()
        ->and($room->isBookable())->toBeTrue();
});

test('a room in maintenance cannot be sold', function () {
    $room = Room::factory()->maintenance()->create();

    expect($room->isBookable())->toBeFalse()
        ->and(Room::query()->bookable()->count())->toBe(0)
        ->and(Room::query()->outOfService()->count())->toBe(1);
});

test('a booking totals its room lines and adds Malawian accommodation taxes', function () {
    $booking = Booking::factory()->pending()->create(['nights' => 3]);
    $booking->items()->delete();
    $booking->items()->create([
        'room_type_id' => RoomType::factory()->create()->id,
        'adults' => 2,
        'price_per_night' => 100_000,
        'subtotal' => 300_000,
    ]);

    $booking->recalculateTotals()->save();
    $booking->refresh();

    // 16.5% VAT plus the 1% tourism levy on the discounted subtotal.
    expect($booking->subtotal)->toBe('300000.00')
        ->and($booking->discount_total)->toBe('0.00')
        ->and($booking->tax_total)->toBe('52500.00')
        ->and($booking->total)->toBe('352500.00')
        ->and($booking->balance())->toBe('352500.00')
        ->and($booking->isPaidInFull())->toBeFalse();
});

test('a coupon reduces the taxable part of a booking', function () {
    $coupon = Coupon::factory()->percentage(10)->create();

    $booking = Booking::factory()->pending()->create(['coupon_id' => $coupon->id]);
    $booking->items()->delete();
    $booking->items()->create([
        'room_type_id' => RoomType::factory()->create()->id,
        'adults' => 2,
        'price_per_night' => 100_000,
        'subtotal' => 200_000,
    ]);

    $booking->recalculateTotals()->save();
    $booking->refresh();

    expect($booking->subtotal)->toBe('200000.00')
        ->and($booking->discount_total)->toBe('20000.00')
        ->and($booking->tax_total)->toBe('31500.00')
        ->and($booking->total)->toBe('211500.00');
});

test('a coupon only discounts when it is actually redeemable', function () {
    $valid = Coupon::factory()->percentage(10)->create();
    $expired = Coupon::factory()->expired()->create();
    $exhausted = Coupon::factory()->exhausted()->create();
    $disabled = Coupon::factory()->inactive()->create();

    expect($valid->discountFor('200000.00'))->toBe('20000.00')
        ->and($valid->isRedeemable(2, '200000.00'))->toBeTrue()
        ->and($expired->isRedeemable(2, '200000.00'))->toBeFalse()
        ->and($exhausted->isRedeemable(2, '200000.00'))->toBeFalse()
        ->and($disabled->isRedeemable(2, '200000.00'))->toBeFalse()
        ->and(Coupon::query()->available()->count())->toBe(1);
});

test('a coupon with a minimum spend or stay is refused below the threshold', function () {
    $coupon = Coupon::factory()->percentage(10)->create([
        'min_nights' => 3,
        'min_spend' => 500_000,
    ]);

    expect($coupon->isRedeemable(2, '600000.00'))->toBeFalse()
        ->and($coupon->isRedeemable(3, '400000.00'))->toBeFalse()
        ->and($coupon->isRedeemable(3, '600000.00'))->toBeTrue();
});

test('a flat coupon never discounts more than the subtotal', function () {
    $coupon = Coupon::factory()->create([
        'discount_type' => 'fixed',
        'discount_value' => 500_000,
    ]);

    expect($coupon->discountFor('120000.00'))->toBe('120000.00');
});

test('booking references follow the hotel sequence', function () {
    $this->travelTo('2026-03-01');

    Booking::factory()->create()->forceFill(['reference' => 'LH-2026-0001'])->save();

    expect(Booking::generateReference())->toBe('LH-2026-0002');
});

test('a successful payment settles the booking', function () {
    $booking = Booking::factory()->pending()->create();
    $booking->recalculateTotals()->save();

    $payment = Payment::factory()->create([
        'booking_id' => $booking->id,
        'amount' => $booking->total,
        'status' => PaymentRecordStatus::Pending,
    ]);

    $payment->markSuccessful(PaymentMethod::AirtelMoney);
    $booking->refresh()->syncPaymentStatus()->save();

    expect($payment->refresh()->isSuccessful())->toBeTrue()
        ->and($payment->method)->toBe(PaymentMethod::AirtelMoney)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($booking->payment_status)->toBe(PaymentStatus::Paid)
        ->and($booking->balance())->toBe('0.00')
        ->and($booking->isPaidInFull())->toBeTrue();
});

test('a part payment leaves the booking in deposit paid', function () {
    $booking = Booking::factory()->pending()->create();
    $booking->recalculateTotals()->save();

    Payment::factory()->successful()->create([
        'booking_id' => $booking->id,
        'amount' => 50_000,
    ]);

    $booking->refresh()->syncPaymentStatus()->save();

    expect($booking->payment_status)->toBe(PaymentStatus::DepositPaid)
        ->and((float) $booking->amount_paid)->toBe(50_000.0)
        ->and((float) $booking->balance())->toBeGreaterThan(0);
});

test('a refund moves the booking to refunded', function () {
    $booking = Booking::factory()->pending()->create();
    $booking->recalculateTotals()->save();

    $payment = Payment::factory()->successful()->create([
        'booking_id' => $booking->id,
        'amount' => $booking->total,
    ]);

    $booking->refresh()->syncPaymentStatus()->save();
    expect($booking->payment_status)->toBe(PaymentStatus::Paid);

    $payment->markRefunded();
    $booking->refresh()->syncPaymentStatus()->save();

    expect($booking->payment_status)->toBe(PaymentStatus::Refunded)
        ->and($booking->balance())->toBe($booking->total);
});

test('booking scopes find arrivals, departures and in house guests', function () {
    Booking::factory()->confirmed()->forStay('2026-07-10', '2026-07-13')->create();
    Booking::factory()->confirmed()->forStay('2026-07-20', '2026-07-23')->create();
    Booking::factory()->checkedIn()->forStay('2026-07-08', '2026-07-10')->create();
    Booking::factory()->cancelled()->forStay('2026-07-10', '2026-07-12')->create();

    expect(Booking::query()->arrivingOn(Carbon::parse('2026-07-10'))->count())->toBe(1)
        ->and(Booking::query()->departingOn(Carbon::parse('2026-07-10'))->count())->toBe(1)
        ->and(Booking::query()->coveringDate(Carbon::parse('2026-07-11'))->count())->toBe(1)
        ->and(Booking::query()->holdingInventory()->count())->toBe(3);
});

test('a booking knows how many guests it covers', function () {
    $booking = Booking::factory()->create(['adults' => 2, 'children' => 3]);

    expect($booking->totalGuests())->toBe(5);
});

test('a guest becomes returning once they have booked more than once', function () {
    $guest = Guest::factory()->create();

    expect($guest->isReturning())->toBeFalse();

    Booking::factory()->count(2)->create(['guest_id' => $guest->id]);
    $guest->refresh();

    expect($guest->isReturning())->toBeTrue()
        ->and($guest->bookings)->toHaveCount(2)
        ->and((float) $guest->lifetimeValue())->toBeGreaterThan(0);
});

test('guests can be found by name, email or phone', function () {
    Guest::factory()->create(['first_name' => 'Chikondi', 'last_name' => 'Phiri']);
    Guest::factory()->create(['email' => 'thandiwe@example.com']);

    expect(Guest::query()->search('Chikondi')->count())->toBe(1)
        ->and(Guest::query()->search('thandiwe@example.com')->count())->toBe(1)
        ->and(Guest::query()->search('nobody')->count())->toBe(0);
});

test('subscribing to the newsletter is idempotent and reversible', function () {
    $first = NewsletterSubscriber::subscribe('Guest@Example.com', 'Grace');
    $second = NewsletterSubscriber::subscribe('guest@example.com');

    expect(NewsletterSubscriber::query()->count())->toBe(1)
        ->and($second->is($first))->toBeTrue()
        ->and($second->name)->toBe('Grace')
        ->and($second->isSubscribed())->toBeTrue();

    $second->unsubscribe();

    expect($second->refresh()->isSubscribed())->toBeFalse()
        ->and(NewsletterSubscriber::query()->subscribed()->count())->toBe(0);
});

test('an inquiry can be answered and closed by staff', function () {
    $receptionist = User::factory()->role(Role::Reception)->create();
    $inquiry = Inquiry::factory()->create();

    expect(Inquiry::query()->open()->count())->toBe(1);

    $inquiry->markResponded('We have availability that week.', $receptionist);

    expect($inquiry->refresh()->status)->toBe(InquiryStatus::Responded)
        ->and($inquiry->hasResponse())->toBeTrue()
        ->and($inquiry->responded_at)->not->toBeNull()
        ->and($inquiry->assigned_to)->toBe($receptionist->id)
        ->and(Inquiry::query()->open()->count())->toBe(0);
});

test('settings can be written and read back with a default', function () {
    Setting::store('contact.phone', '+265 99 123 4567', 'contact');

    expect(Setting::value('contact.phone'))->toBe('+265 99 123 4567')
        ->and(Setting::value('contact.missing', 'fallback'))->toBe('fallback')
        ->and(Setting::query()->section('contact')->count())->toBe(1);
});

test('room category photography is stored on the bucket disk', function () {
    $roomType = RoomType::factory()->create();

    $media = $roomType
        ->addMedia(UploadedFile::fake()->image('chalet.jpg', 2000, 1400))
        ->toMediaCollection('images');

    expect($media->disk)->toBe('bucket')
        ->and(file_exists(public_path("bucket/{$media->id}/chalet.jpg")))->toBeTrue()
        ->and($media->getUrl())->toBe("/bucket/{$media->id}/chalet.jpg");

    // `cover` is a single-file collection, so the second upload replaces the first.
    $roomType->addMedia(UploadedFile::fake()->image('cover-a.jpg', 2000, 1400))->toMediaCollection('cover');
    $roomType->addMedia(UploadedFile::fake()->image('cover-b.jpg', 2000, 1400))->toMediaCollection('cover');

    $roomType->refresh();

    expect($roomType->getMedia('cover'))->toHaveCount(1)
        ->and($roomType->heroUrl())->toContain('/bucket/');

    $roomType->clearMediaCollection('images');
    $roomType->clearMediaCollection('cover');
});

test('a booking line keeps the nightly prices it was sold at', function () {
    $booking = Booking::factory()->pending()->create(['nights' => 2]);

    $item = $booking->items()->create([
        'room_type_id' => RoomType::factory()->create()->id,
        'adults' => 2,
        'price_per_night' => 180_000,
        'subtotal' => 360_000,
        'nightly_rates' => [
            '2026-07-03' => '180000.00',
            '2026-07-04' => '180000.00',
        ],
    ]);

    expect($item->refresh()->nightly_rates)
        ->toBe(['2026-07-03' => '180000.00', '2026-07-04' => '180000.00'])
        ->and($item->guests())->toBe(2)
        ->and($item->nights())->toBe(2);
});
