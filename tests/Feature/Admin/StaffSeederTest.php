<?php

use App\Enums\Role;
use App\Models\User;

test('the seeder creates one demo account per staff role', function () {
    $this->seed();

    expect(User::query()->count())->toBe(count(Role::staff()));

    foreach (Role::staff() as $role) {
        expect(User::query()->where('role', $role->value)->count())->toBe(1);
    }
});

test('the seeded owner can sign in and reach the dashboard', function () {
    $this->seed();

    $this->post(route('login.store'), [
        'email' => 'owner@lakesidehotel.mw',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $this->get(route('dashboard'))->assertOk();
});

test('the seeded front desk account cannot open staff management', function () {
    $this->seed();

    $this->post(route('login.store'), [
        'email' => 'reception@lakesidehotel.mw',
        'password' => 'password',
    ]);

    $this->get(route('admin.users.index'))->assertForbidden();
});
