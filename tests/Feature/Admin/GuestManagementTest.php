<?php

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/*
 * The guest record: who has stayed, how often, and what they are worth.
 *
 * A guest is built from whatever was typed at booking time, so the two things
 * worth testing are that the history adds up and that the desk can put a mistake
 * right - including the phone number, which is held to the same rule here as it is
 * on the booking form.
 */

beforeEach(function (): void {
    $this->seed();

    $this->manager = User::factory()->role(Role::HotelManager)->create();
    $this->reception = User::factory()->role(Role::Reception)->create();
    $this->marketing = User::factory()->role(Role::Marketing)->create();

    // A guest of its own, so the arithmetic below is the test's rather than
    // whatever the seed data happens to hold.
    $this->guest = Guest::factory()->create([
        'first_name' => 'Tadala',
        'last_name' => 'Banda',
        'email' => 'tadala.banda@example.com',
        'phone' => '0999 123 456',
        'city' => 'Salima',
        'country' => 'Malawi',
    ]);

    $this->stay = fn (string $checkIn, int $nights = 2, BookingStatus $status = BookingStatus::CheckedOut): Booking => Booking::factory()->create([
        'guest_id' => $this->guest->getKey(),
        'status' => $status,
        'check_in' => $checkIn,
        'check_out' => Carbon::parse($checkIn)->addDays($nights)->toDateString(),
        'nights' => $nights,
    ]);

    $this->guests = fn (array $query = []) => $this->actingAs($this->manager)
        ->get(route('admin.guests.index', $query));
});

test('the guest list is readable by the roles that keep it', function () {
    foreach ([$this->manager, $this->reception] as $actor) {
        $this->actingAs($actor)
            ->get(route('admin.guests.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/guests/index')
                ->has('guests.data')
                ->has('filters.search')
                ->has('totals.matching')
                ->has('totals.returning'));
    }
});

test('a role without the permission can neither read nor correct a guest', function () {
    $this->actingAs($this->marketing)
        ->get(route('admin.guests.index'))
        ->assertForbidden();

    $this->actingAs($this->marketing)
        ->get(route('admin.guests.show', $this->guest->getKey()))
        ->assertForbidden();

    $this->actingAs($this->marketing)
        ->patch(route('admin.guests.update', $this->guest->getKey()), [
            'first_name' => 'Taken',
            'last_name' => 'Over',
            'email' => $this->guest->email,
        ])
        ->assertForbidden();

    expect($this->guest->refresh()->first_name)->toBe('Tadala');
});

test('the list can be narrowed to one guest by name, email or phone', function () {
    ($this->guests)(['search' => 'Tadala'])
        ->assertInertia(fn ($page) => $page
            ->has('guests.data', 1)
            ->where('guests.data.0.id', $this->guest->getKey()));

    ($this->guests)(['search' => 'tadala.banda@example.com'])
        ->assertInertia(fn ($page) => $page
            ->has('guests.data', 1)
            ->where('guests.data.0.id', $this->guest->getKey()));

    // Stored as E.164, so the desk finds it by typing the digits off the phone
    // rather than the plus sign they would have to remember.
    ($this->guests)(['search' => '999123456'])
        ->assertInertia(fn ($page) => $page
            ->where('guests.data', fn ($rows): bool => collect($rows)
                ->contains('id', $this->guest->getKey())));
});

test('the list can be narrowed to guests who have been more than once', function () {
    $once = Guest::factory()->create(['first_name' => 'One', 'last_name' => 'Visit']);
    Booking::factory()->create(['guest_id' => $once->getKey(), 'status' => BookingStatus::CheckedOut]);

    ($this->stay)('2026-03-10');
    ($this->stay)('2026-06-02');

    ($this->guests)(['returning' => '1'])
        ->assertInertia(fn ($page) => $page
            ->where('guests.data', fn ($rows): bool => collect($rows)
                ->every(fn (array $row): bool => $row['stays'] > 1)
                && collect($rows)->contains('id', $this->guest->getKey())
                && ! collect($rows)->contains('id', $once->getKey()))
            ->where('totals.returning', fn (int $count): bool => $count >= 1));
});

test('the list can be put in order of what a guest has spent', function () {
    ($this->stay)('2026-03-10');
    ($this->stay)('2026-06-02');

    ($this->guests)(['sort' => 'spend'])
        ->assertInertia(fn ($page) => $page
            ->where('guests.data', function ($rows): bool {
                $spend = collect($rows)->pluck('spend')->map(fn ($value): float => (float) $value);

                return $spend->isNotEmpty()
                    && $spend->values()->all() === $spend->sortDesc()->values()->all();
            }));
});

test('a guest record shows their history and what it adds up to', function () {
    ($this->stay)('2026-03-10');
    ($this->stay)('2026-06-02', nights: 3);
    ($this->stay)('2026-08-01', status: BookingStatus::Cancelled);

    $expected = number_format((float) $this->guest->bookings()->sum('total'), 2, '.', '');

    $this->actingAs($this->manager)
        ->get(route('admin.guests.show', $this->guest->getKey()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/guests/show')
            ->where('guest.name', 'Tadala Banda')
            ->where('guest.email', 'tadala.banda@example.com')
            ->where('guest.phone', '+265999123456')
            ->has('guest.stays', 3)
            ->where('stats.stays', 3)
            ->where('stats.nights', 7)
            ->where('stats.cancelled', 1)
            ->where('stats.spend', $expected));
});

test('a guest who is not on file is a 404', function () {
    $this->actingAs($this->manager)
        ->get(route('admin.guests.show', 999_999))
        ->assertNotFound();
});

test('the record can be corrected, and blank boxes are stored as nothing', function () {
    $this->actingAs($this->manager)
        ->patch(route('admin.guests.update', $this->guest->getKey()), [
            'first_name' => '  Thandiwe  ',
            'last_name' => 'Banda',
            'email' => 'thandiwe.banda@example.com',
            'phone' => '0991 234 567',
            'city' => '',
            'notes' => '  Prefers a lake-facing room.  ',
            'marketing_opt_in' => true,
        ])
        ->assertRedirect();

    $guest = $this->guest->refresh();

    expect($guest->first_name)->toBe('Thandiwe')
        ->and($guest->email)->toBe('thandiwe.banda@example.com')
        ->and($guest->phone)->toBe('+265991234567')
        ->and($guest->city)->toBeNull()
        ->and($guest->notes)->toBe('Prefers a lake-facing room.')
        ->and($guest->marketing_opt_in)->toBeTrue();
});

test('a number that is not one is refused with the same message the guest saw', function () {
    // The desk typing on a guest's behalf is held to the booking form's rule, and
    // reads the booking form's sentence when they get it wrong.
    $this->actingAs($this->manager)
        ->patch(route('admin.guests.update', $this->guest->getKey()), [
            'first_name' => 'Tadala',
            'last_name' => 'Banda',
            'email' => 'tadala.banda@example.com',
            'phone' => 'call me maybe',
        ])
        ->assertSessionHasErrors(['phone' => __('validation.phone')]);

    expect($this->guest->refresh()->phone)->toBe('+265999123456');
});

test('another guest\'s email address cannot be taken', function () {
    Guest::factory()->create(['email' => 'someone.else@example.com']);

    $this->actingAs($this->manager)
        ->patch(route('admin.guests.update', $this->guest->getKey()), [
            'first_name' => 'Tadala',
            'last_name' => 'Banda',
            'email' => 'someone.else@example.com',
        ])
        ->assertSessionHasErrors('email');

    expect($this->guest->refresh()->email)->toBe('tadala.banda@example.com');
});

test('a guest keeps their own email address when the record is saved', function () {
    // The uniqueness rule has to ignore the guest being edited, or every save
    // would collide with the row it is writing.
    $this->actingAs($this->manager)
        ->patch(route('admin.guests.update', $this->guest->getKey()), [
            'first_name' => 'Tadala',
            'last_name' => 'Banda',
            'email' => 'tadala.banda@example.com',
            'phone' => '0999 123 456',
        ])
        ->assertSessionHasNoErrors();

    expect($this->guest->refresh()->email)->toBe('tadala.banda@example.com');
});
