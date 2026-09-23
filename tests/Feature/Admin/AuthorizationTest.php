<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;

test('a guest account holds no permissions', function () {
    expect(Role::Guest->permissions())->toBe([]);
    expect(Role::Guest->isStaff())->toBeFalse();
});

test('the system administrator holds every permission', function () {
    expect(Role::SystemAdmin->permissions())->toHaveCount(count(Permission::cases()));
});

test('only the system administrator can manage system settings', function () {
    $withSystemAccess = array_values(array_filter(
        Role::staff(),
        fn (Role $role): bool => $role->hasPermission(Permission::ManageSystem),
    ));

    expect($withSystemAccess)->toBe([Role::SystemAdmin]);
});

test('front desk staff cannot change rooms, rates or website content', function () {
    expect(Role::Reception->hasPermission(Permission::ManageRooms))->toBeFalse();
    expect(Role::Reception->hasPermission(Permission::ManagePricing))->toBeFalse();
    expect(Role::Reception->hasPermission(Permission::ManageContent))->toBeFalse();
});

test('accounts without a staff role cannot reach the dashboard', function () {
    $user = User::factory()->guest()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('every staff role can reach the dashboard', function (Role $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
})->with([
    Role::SystemAdmin,
    Role::Admin,
    Role::HotelManager,
    Role::Reception,
    Role::Marketing,
]);

test('guests are redirected from the dashboard to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('staff management is closed to roles without the users view permission', function (Role $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
})->with([
    Role::Reception,
    Role::Marketing,
]);

test('managers and above can open staff management', function (Role $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertOk();
})->with([
    Role::SystemAdmin,
    Role::Admin,
    Role::HotelManager,
]);

test('a deactivated account cannot sign in', function () {
    $user = User::factory()->inactive()->create([
        'email' => 'dormant@lakesidehotel.mw',
        'password' => 'password',
    ]);

    $this->post(route('login.store'), [
        'email' => 'dormant@lakesidehotel.mw',
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('a deactivated account that is still signed in gets logged out', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $user->forceFill(['is_active' => false])->save();

    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('a manager cannot manage an account at or above their own level', function () {
    $manager = User::factory()->role(Role::HotelManager)->create();
    $admin = User::factory()->role(Role::Admin)->create();

    expect($manager->canManage($admin))->toBeFalse();
    expect($admin->canManage($manager))->toBeTrue();
});

test('nobody can manage their own account from staff management', function () {
    $admin = User::factory()->role(Role::Admin)->create();

    expect($admin->canManage($admin))->toBeFalse();
});

test('the system administrator can manage any account', function () {
    $systemAdmin = User::factory()->role(Role::SystemAdmin)->create();
    $admin = User::factory()->role(Role::Admin)->create();

    expect($systemAdmin->canManage($admin))->toBeTrue();
});
