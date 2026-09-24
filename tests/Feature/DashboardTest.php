<?php

use App\Enums\Role;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('staff can visit the dashboard', function () {
    $this->seed();

    $user = User::factory()->role(Role::HotelManager)->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('overview.date')
            ->has('overview.arrivals')
            ->has('overview.departures')
            ->has('overview.occupancy.sellable')
            ->has('overview.revenue.taken_today')
            ->has('overview.inquiries')
            ->has('overview.arriving_soon'));
});

test('accounts without a staff role cannot visit the dashboard', function () {
    $user = User::factory()->guest()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertForbidden();
});
