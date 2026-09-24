<?php

use App\Casts\E164Phone;
use App\Enums\PaymentOption;
use App\Enums\Role;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\RoomType;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/*
 * Phone numbers are how the hotel reaches a guest on the day they arrive, so a
 * mistyped one costs more than a validation message. These tests pin both halves:
 * what is accepted, and what it is stored as.
 *
 * The hotel is in Malawi and most of its guests are not, so a number is accepted
 * either the way reception would say it aloud or in full international form.
 */

beforeEach(function (): void {
    $this->seed();

    config(['hotel.country' => 'MW']);
});

test('a local number is read in the hotel country and stored in one canonical form', function () {
    expect(Phone::normalise('0999 123 456'))->toBe('+265999123456')
        ->and(Phone::normalise('+265 999 123 456'))->toBe('+265999123456')
        ->and(Phone::normalise('0999123456'))->toBe('+265999123456')
        // Two spellings of the same number have to compare equal, which is the
        // whole point of picking one format.
        ->and(Phone::normalise('0999 123 456'))->toBe(Phone::normalise('+265 999 123 456'));
});

test('a number from anywhere else is kept as an international one', function () {
    expect(Phone::normalise('+27 82 123 4567'))->toBe('+27821234567')
        ->and(Phone::normalise('+254 712 345 678'))->toBe('+254712345678')
        ->and(Phone::normalise('+44 161 496 0000'))->toBe('+441614960000');
});

test('an empty value normalises to nothing rather than to emptiness', function () {
    expect(Phone::normalise(null))->toBeNull()
        ->and(Phone::normalise(''))->toBeNull()
        ->and(Phone::normalise('   '))->toBeNull();
});

test('something that is not a number is left exactly as it was', function () {
    // Not silently blanked: an import or a row from before this existed should
    // survive a value the parser does not recognise.
    expect(Phone::normalise('ask for Grace'))->toBe('ask for Grace');
});

test('the rules ask for the hotel country and accept international numbers', function () {
    $rules = Phone::rules();

    expect($rules)->toContain('nullable')
        ->and($rules)->toContain('phone:MW,INTERNATIONAL');

    expect(Phone::rules(required: true))->toContain('required')
        ->and(Phone::rules(required: true))->not->toContain('nullable');
});

test('the cast normalises on the way in and hands back a plain string', function () {
    $guest = Guest::factory()->create(['phone' => '0999 654 321']);

    // Stored canonically...
    expect($guest->getRawOriginal('phone'))->toBe('+265999654321')
        // ...but read as a string, not as a value object, because every consumer
        // here - emails, WhatsApp links, the admin tables - treats it as one.
        ->and($guest->fresh()->phone)->toBeString()
        ->and($guest->fresh()->phone)->toBe('+265999654321');
});

test('a booking without a phone number is refused', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $night = Carbon::parse('2027-03-09');

    $this->post(route('site.booking.store'), [
        'room_type' => $roomType->slug,
        'check_in' => $night->toDateString(),
        'check_out' => $night->copy()->addDays(2)->toDateString(),
        'adults' => 2,
        'children' => 0,
        'first_name' => 'No',
        'last_name' => 'Phone',
        'email' => 'nophone@example.com',
        'payment_option' => PaymentOption::PayAtHotel->value,
        'terms' => true,
    ])->assertSessionHasErrors('phone');
});

test('a booking with a number that is not a number is refused', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $night = Carbon::parse('2027-03-09');

    foreach (['not a phone', '123', '+265', 'please call the office'] as $rubbish) {
        $this->post(route('site.booking.store'), [
            'room_type' => $roomType->slug,
            'check_in' => $night->toDateString(),
            'check_out' => $night->copy()->addDays(2)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'first_name' => 'Bad',
            'last_name' => 'Phone',
            'email' => 'badphone@example.com',
            'phone' => $rubbish,
            'payment_option' => PaymentOption::PayAtHotel->value,
            'terms' => true,
        ])->assertSessionHasErrors('phone');
    }

    expect(Guest::query()->where('email', 'badphone@example.com')->exists())->toBeFalse();
});

test('a guest booking from Malawi gets a dialable number stored', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $night = Carbon::parse('2027-03-09');

    $this->post(route('site.booking.store'), [
        'room_type' => $roomType->slug,
        'check_in' => $night->toDateString(),
        'check_out' => $night->copy()->addDays(2)->toDateString(),
        'adults' => 2,
        'children' => 0,
        'first_name' => 'Chikondi',
        'last_name' => 'Phiri',
        'email' => 'local@example.com',
        'phone' => '0991 234 567',
        'payment_option' => PaymentOption::PayAtHotel->value,
        'terms' => true,
    ])->assertRedirect();

    expect(Guest::query()->where('email', 'local@example.com')->sole()->phone)
        ->toBe('+265991234567');
});

test('a guest booking from overseas is accepted without a Malawi number', function () {
    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $night = Carbon::parse('2027-03-09');

    $this->post(route('site.booking.store'), [
        'room_type' => $roomType->slug,
        'check_in' => $night->toDateString(),
        'check_out' => $night->copy()->addDays(2)->toDateString(),
        'adults' => 2,
        'children' => 0,
        'first_name' => 'Emma',
        'last_name' => 'Clarke',
        'email' => 'overseas@example.com',
        'phone' => '+27 82 123 4567',
        'payment_option' => PaymentOption::PayAtHotel->value,
        'terms' => true,
    ])->assertRedirect();

    // Stored dialable from Malawi, which is what the desk needs on the day.
    expect(Guest::query()->where('email', 'overseas@example.com')->sole()->phone)
        ->toBe('+27821234567');
});

test('a number that is only shaped like one is still refused', function () {
    // +44 7700 900xxx is Ofcom's reserved range for drama, so it parses but is
    // not a real number. The rule asks the library whether the number exists,
    // not merely whether it is well formed - which is the difference between
    // catching a typo and catching a bad number.
    expect(validator(
        ['phone' => '+44 7700 900123'],
        ['phone' => Phone::rules()],
    )->passes())->toBeFalse();

    expect(validator(
        ['phone' => '+44 161 496 0000'],
        ['phone' => Phone::rules()],
    )->passes())->toBeTrue();
});

test('the website enquiry form accepts a local number and refuses nonsense', function () {
    $this->post(route('site.contact'), [
        'name' => 'Tadala',
        'email' => 'tadala@example.com',
        'phone' => '0888 111 222',
        'subject' => 'Availability in April',
        'message' => 'Do you have two rooms for the first weekend of April?',
    ])->assertRedirect();

    expect(Inquiry::query()->where('email', 'tadala@example.com')->sole()->phone)
        ->toBe('+265888111222');

    $this->post(route('site.contact'), [
        'name' => 'Tadala',
        'email' => 'tadala2@example.com',
        'phone' => 'call me maybe',
        'subject' => 'Availability in April',
        'message' => 'Do you have two rooms for the first weekend of April?',
    ])->assertSessionHasErrors('phone');
});

test('the enquiry form still works without a phone number at all', function () {
    // Optional there, and it has to stay optional: plenty of people write in.
    $this->post(route('site.contact'), [
        'name' => 'No Number',
        'email' => 'nonumber@example.com',
        'subject' => 'A question',
        'message' => 'I would rather not leave a number, but I have a question.',
    ])->assertRedirect()
        ->assertSessionHasNoErrors();
});

test('a staff account cannot be given a phone number that is not one', function () {
    $admin = User::factory()->role(Role::SystemAdmin)->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'New Receptionist',
            'email' => 'reception2@example.com',
            'role' => Role::Reception->value,
            'phone' => 'extension 4',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasErrors('phone');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'New Receptionist',
            'email' => 'reception2@example.com',
            'role' => Role::Reception->value,
            'phone' => '0999 000 111',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasNoErrors();

    expect(User::query()->where('email', 'reception2@example.com')->sole()->phone)
        ->toBe('+265999000111');
});

test('an existing guest keeps their number when a second booking leaves it out', function () {
    // The booking form requires a number, but the guest record should not be
    // wiped by a later edit that omits it.
    $guest = Guest::factory()->create([
        'email' => 'returning@example.com',
        'phone' => '+265 991 000 222',
    ]);

    $guest->update(['first_name' => 'Returning']);

    expect($guest->fresh()->phone)->toBe('+265991000222');
});

test('the cast leaves a value it cannot read alone rather than emptying it', function () {
    $cast = new E164Phone;

    $guest = new Guest;

    // Nothing recognisable goes in, the same thing comes out of set().
    expect($cast->set($guest, 'phone', 'unknown', []))->toBe('unknown')
        ->and($cast->set($guest, 'phone', null, []))->toBeNull()
        ->and($cast->get($guest, 'phone', '+265999000111', []))->toBe('+265999000111');
});

test('every form that asks for a number reports the same readable message', function () {
    // The package fails with the key `validation.phone` and ships no wording for
    // it, so this is what keeps the raw key off the page. The wording lives in
    // lang/en/validation.php rather than in each form, which is why all three
    // have to agree here: one guest, one visitor and one administrator should not
    // be told three different things about the same mistake.
    $message = __('validation.phone');

    expect($message)->not->toBe('validation.phone')
        ->and($message)->toContain('0999 123 456');

    $roomType = RoomType::query()->where('slug', 'standard-double')->sole();
    $night = Carbon::parse('2027-03-09');

    $this->post(route('site.booking.store'), [
        'room_type' => $roomType->slug,
        'check_in' => $night->toDateString(),
        'check_out' => $night->copy()->addDays(2)->toDateString(),
        'adults' => 2,
        'children' => 0,
        'first_name' => 'Call',
        'last_name' => 'Me Maybe',
        'email' => 'readable@example.com',
        'phone' => 'call me maybe',
        'payment_option' => PaymentOption::PayAtHotel->value,
        'terms' => true,
    ])->assertSessionHasErrors(['phone' => $message]);

    $this->post(route('site.contact'), [
        'name' => 'Tadala',
        'email' => 'readable@example.com',
        'phone' => 'call me maybe',
        'subject' => 'Availability in April',
        'message' => 'Do you have two rooms for the first weekend of April?',
    ])->assertSessionHasErrors(['phone' => $message]);

    $admin = User::factory()->role(Role::SystemAdmin)->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'New Receptionist',
            'email' => 'readable@example.com',
            'role' => Role::Reception->value,
            'phone' => 'call me maybe',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasErrors(['phone' => $message]);
});

test('the examples in the message are numbers the rule accepts', function () {
    // A message that recommends a number the rule then refuses is worse than no
    // message at all, so the examples it quotes are read back out and put through
    // the rule. This is what ties the wording to the validation instead of
    // letting the two drift apart.
    preg_match_all('/\+?\d[\d\s]{6,}/', __('validation.phone'), $matches);

    $examples = array_map('trim', $matches[0]);

    expect($examples)->not->toBeEmpty();

    foreach ($examples as $example) {
        expect(validator(['phone' => $example], ['phone' => Phone::rules()])->passes())
            ->toBeTrue("the message suggests {$example}, which the rule refuses");
    }
});

test('seeded and factory-made numbers are already canonical', function () {
    // The factories have to keep producing values the cast and the rules agree
    // with, or every test that builds a guest would be resting on bad data.
    $guest = Guest::factory()->create();

    expect($guest->phone)->toStartWith('+265')
        ->and(Phone::normalise($guest->phone))->toBe($guest->phone);

    $user = User::factory()->create();

    expect(Phone::normalise($user->phone))->toBe($user->phone);
});
