<?php

use App\Enums\Role;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('staff can visit the dashboard', function () {
    $user = User::factory()->role(Role::HotelManager)->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
});

test('accounts without a staff role cannot visit the dashboard', function () {
    $user = User::factory()->guest()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertForbidden();
});
