<?php

use App\Cms\SettingSchema;
use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * The settings screen, and the two things that make it safe to leave open: only
 * the role that already owns the hotel's configuration can reach it, and only the
 * values that were declared can be written through it.
 *
 * The submitted names are the escaped ones - `booking__vat_rate` rather than
 * `booking.vat_rate` - because Laravel reads a dot in a rule name as a step into a
 * nested array. These use the schema's own escaping rather than spelling it out,
 * so the test cannot drift from the thing it is testing.
 */

function adminSettingsPayload(array $settings): array
{
    return ['settings' => $settings];
}

beforeEach(function (): void {
    // The rates under test are the ones a seeded hotel ships with.
    $this->seed();
});

/** The administrator who owns the hotel's configuration. */
function systemAdmin(): User
{
    return User::factory()->role(Role::SystemAdmin)->create();
}

test('the settings screen is closed to everyone but the system administrator', function (Role $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('admin.system.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('admin.system.update'), adminSettingsPayload([
            SettingSchema::wireName(Tax::VAT_SETTING) => '20',
        ]))
        ->assertForbidden();
})->with([
    Role::Admin,
    Role::HotelManager,
    Role::Reception,
    Role::Marketing,
]);

test('a signed out visitor is sent to sign in', function () {
    $this->get(route('admin.system.index'))->assertRedirect(route('login'));
});

test('the screen offers the rates and the toggle', function () {
    $this->actingAs(User::factory()->role(Role::SystemAdmin)->create())
        ->get(route('admin.system.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/system/index')
            ->has('sections')
        );
});

test('the tax rates are the first thing on the screen', function () {
    $sections = SettingSchema::forDisplay();

    // The section somebody comes here to change, and the first field in it.
    expect($sections[0]['key'])->toBe('booking')
        ->and($sections[0]['fields'][0]['key'])->toBe(Tax::VAT_SETTING);
});

test('a new rate is saved and changes what the hotel charges', function () {
    $this->actingAs(User::factory()->role(Role::SystemAdmin)->create())
        ->patch(route('admin.system.update'), adminSettingsPayload([
            SettingSchema::wireName(Tax::VAT_SETTING) => '20',
            SettingSchema::wireName(Tax::LEVY_SETTING) => '2',
        ]))
        ->assertRedirect();

    expect(Setting::value(Tax::VAT_SETTING))->toBe('20')
        ->and(Setting::value(Tax::LEVY_SETTING))->toBe('2')
        // And the site follows: 22% on top of a net amount.
        ->and(Tax::rate())->toBe(22.0)
        ->and(Tax::inclusive('100.00'))->toBe('122.00');
});

test('the toggle can be switched on and off again', function () {
    $admin = User::factory()->role(Role::SystemAdmin)->create();
    $wire = SettingSchema::wireName(Tax::SHOW_SEPARATELY_SETTING);

    $this->actingAs($admin)
        ->patch(route('admin.system.update'), adminSettingsPayload([$wire => true]));

    expect(Setting::value(Tax::SHOW_SEPARATELY_SETTING))->toBe('1')
        ->and(Tax::showSeparately())->toBeTrue();

    /*
     * The case a form gets wrong. An unchecked box sends nothing at all, so a
     * screen that posted only what changed could never turn a setting back off.
     */
    $this->actingAs($admin)
        ->patch(route('admin.system.update'), adminSettingsPayload([$wire => false]));

    expect(Setting::value(Tax::SHOW_SEPARATELY_SETTING))->toBe('0')
        ->and(Tax::showSeparately())->toBeFalse();
});

test('a rate outside nought to a hundred is refused, under the name the form uses', function () {
    $this->actingAs(User::factory()->role(Role::SystemAdmin)->create())
        ->patch(route('admin.system.update'), adminSettingsPayload([
            SettingSchema::wireName(Tax::VAT_SETTING) => '600',
        ]))
        ->assertSessionHasErrors('settings.'.SettingSchema::wireName(Tax::VAT_SETTING));

    expect(Setting::value(Tax::VAT_SETTING))->toBe('16.5');
});

test('a rate that is not a number is refused', function () {
    $this->actingAs(User::factory()->role(Role::SystemAdmin)->create())
        ->patch(route('admin.system.update'), adminSettingsPayload([
            SettingSchema::wireName(Tax::LEVY_SETTING) => 'about one',
        ]))
        ->assertSessionHasErrors('settings.'.SettingSchema::wireName(Tax::LEVY_SETTING));

    expect(Setting::value(Tax::LEVY_SETTING))->toBe('1');
});

test('a setting nobody declared cannot be written through the screen', function () {
    $this->actingAs(User::factory()->role(Role::SystemAdmin)->create())
        ->patch(route('admin.system.update'), adminSettingsPayload([
            'hotel__secret_discount' => '90',
        ]));

    expect(Setting::query()->where('key', 'hotel.secret_discount')->exists())->toBeFalse();
});

test('an edit says so, and says when it has moved the prices', function () {
    /*
     * Safer to assert than it looks: the rates decide what every guest is charged,
     * and a change that quietly took effect would be found only after somebody had
     * been quoted a price the hotel did not intend.
     */
    $this->actingAs(systemAdmin())
        ->patch(route('admin.system.update'), adminSettingsPayload([
            SettingSchema::wireName(Tax::VAT_SETTING) => '18',
        ]))
        ->assertSessionHas('inertia.flash_data.toast.type', 'success')
        ->assertSessionHas(
            'inertia.flash_data.toast.message',
            fn (string $message): bool => str_contains($message, 'Prices'),
        );
});

test('the contact details are edited on the same screen and reach the website', function () {
    $this->actingAs(User::factory()->role(Role::SystemAdmin)->create())
        ->patch(route('admin.system.update'), adminSettingsPayload([
            SettingSchema::wireName('hotel.phone') => '+265 1 999 000',
        ]))
        ->assertRedirect();

    expect(Setting::value('hotel.phone'))->toBe('+265 1 999 000');
});

test('every setting the screen shows has a rule that accepts it', function () {
    // A field offered without a rule is a control that can be filled in and then
    // silently dropped, so the two lists have to match exactly.
    $offered = collect(SettingSchema::forDisplay())
        ->flatMap(fn (array $section): array => $section['fields'])
        ->pluck('wire')
        ->all();

    $rules = SettingSchema::rules();

    foreach ($offered as $wire) {
        expect($rules)->toHaveKey("settings.{$wire}");
    }
});
