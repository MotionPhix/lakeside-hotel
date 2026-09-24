<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * The dashboard's top bar prints the application's name, which it reads from
 * `config('app.name')` - `APP_NAME` in the environment. These are the two halves
 * of keeping that the only place the name lives: the back end has to send it, and
 * the front end has to have nothing of its own to say.
 */

test('the application name reaches the front end from configuration', function () {
    config(['app.name' => 'Somewhere Else Hotel']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('name', 'Somewhere Else Hotel'));
});

test('the top bar carries no name of its own', function () {
    /*
     * A literal in the component would be a second source of truth, and would win
     * silently the day APP_NAME changed. So the header is checked for the two
     * halves of the arrangement: it reads the shared prop, and it does not name
     * anything itself.
     */
    $header = file_get_contents(resource_path('js/components/app-sidebar-header.tsx'));

    expect($header)
        ->toContain('usePage<SharedData>()')
        ->not->toContain('Lakeside')
        ->not->toContain('Laravel');
});
