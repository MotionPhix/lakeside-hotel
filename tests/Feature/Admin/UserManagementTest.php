<?php

use App\Enums\Role;
use App\Models\User;

test('the staff list shows each account with its role', function () {
    $admin = User::factory()->role(Role::Admin)->create();
    $receptionist = User::factory()->role(Role::Reception)->create(['name' => 'Thandiwe Moyo']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->has('users.data', 2)
            ->where('users.data.1.name', 'Thandiwe Moyo')
            ->where('users.data.1.role_label', 'Reception Staff')
        );
});

test('the staff list can be searched by name', function () {
    $admin = User::factory()->role(Role::Admin)->create();
    User::factory()->role(Role::Reception)->create(['name' => 'Chikondi Phiri']);
    User::factory()->role(Role::Reception)->create(['name' => 'Thandiwe Moyo']);

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'Chikondi']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

test('an administrator can add a staff account', function () {
    $admin = User::factory()->role(Role::Admin)->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Yamikani Zulu',
            'email' => 'yamikani@lakesidehotel.mw',
            'role' => Role::Marketing->value,
            'job_title' => 'Marketing Officer',
            'phone' => '+265 99 123 4567',
            'is_active' => true,
            'password' => 'lakeside-lodge-2026',
            'password_confirmation' => 'lakeside-lodge-2026',
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'yamikani@lakesidehotel.mw')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(Role::Marketing)
        ->and($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});

test('a new staff account can sign in straight away', function () {
    $admin = User::factory()->role(Role::Admin)->create();

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Yamikani Zulu',
        'email' => 'yamikani@lakesidehotel.mw',
        'role' => Role::Marketing->value,
        'is_active' => true,
        'password' => 'lakeside-lodge-2026',
        'password_confirmation' => 'lakeside-lodge-2026',
    ]);

    auth()->logout();

    $this->post(route('login.store'), [
        'email' => 'yamikani@lakesidehotel.mw',
        'password' => 'lakeside-lodge-2026',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
});

test('a staff email address cannot be used twice', function () {
    $admin = User::factory()->role(Role::Admin)->create();
    User::factory()->role(Role::Reception)->create(['email' => 'taken@lakesidehotel.mw']);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Duplicate',
            'email' => 'taken@lakesidehotel.mw',
            'role' => Role::Reception->value,
            'is_active' => true,
            'password' => 'lakeside-lodge-2026',
            'password_confirmation' => 'lakeside-lodge-2026',
        ])
        ->assertSessionHasErrors('email');
});

test('an administrator cannot grant a role at or above their own level', function () {
    $admin = User::factory()->role(Role::Admin)->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Wants More Access',
            'email' => 'climber@lakesidehotel.mw',
            'role' => Role::SystemAdmin->value,
            'is_active' => true,
            'password' => 'lakeside-lodge-2026',
            'password_confirmation' => 'lakeside-lodge-2026',
        ])
        ->assertSessionHasErrors('role');
});

test('a manager cannot promote somebody above their own level', function () {
    $manager = User::factory()->role(Role::HotelManager)->create();

    $this->actingAs($manager)
        ->post(route('admin.users.store'), [
            'name' => 'Wants More Access',
            'email' => 'climber@lakesidehotel.mw',
            'role' => Role::Admin->value,
            'is_active' => true,
            'password' => 'lakeside-lodge-2026',
            'password_confirmation' => 'lakeside-lodge-2026',
        ])
        ->assertSessionHasErrors('role');
});

test('an account cannot be left without a staff role', function () {
    $admin = User::factory()->role(Role::Admin)->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'No Role',
            'email' => 'norole@lakesidehotel.mw',
            'role' => Role::Guest->value,
            'is_active' => true,
            'password' => 'lakeside-lodge-2026',
            'password_confirmation' => 'lakeside-lodge-2026',
        ])
        ->assertSessionHasErrors('role');
});

test('an administrator can update a staff account and reset its password', function () {
    $admin = User::factory()->role(Role::Admin)->create();
    $receptionist = User::factory()->role(Role::Reception)->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.update', $receptionist), [
            'name' => 'Thandiwe Moyo',
            'email' => $receptionist->email,
            'role' => Role::HotelManager->value,
            'job_title' => 'Operations Manager',
            'is_active' => true,
            'password' => 'brand-new-secret-2026',
            'password_confirmation' => 'brand-new-secret-2026',
        ])
        ->assertRedirect(route('admin.users.index'));

    $receptionist->refresh();

    expect($receptionist->name)->toBe('Thandiwe Moyo')
        ->and($receptionist->role)->toBe(Role::HotelManager)
        ->and($receptionist->job_title)->toBe('Operations Manager')
        ->and(Illuminate\Support\Facades\Hash::check('brand-new-secret-2026', $receptionist->password))->toBeTrue();
});

test('an administrator can deactivate and reactivate a staff account', function () {
    $admin = User::factory()->role(Role::Admin)->create();
    $receptionist = User::factory()->role(Role::Reception)->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.status', $receptionist))
        ->assertRedirect();

    expect($receptionist->refresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->patch(route('admin.users.status', $receptionist))
        ->assertRedirect();

    expect($receptionist->refresh()->is_active)->toBeTrue();
});

test('an administrator cannot deactivate or delete their own account', function () {
    $admin = User::factory()->role(Role::Admin)->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.status', $admin))
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertForbidden();
});

test('a manager cannot delete an administrator', function () {
    $manager = User::factory()->role(Role::HotelManager)->create();
    $admin = User::factory()->role(Role::Admin)->create();

    $this->actingAs($manager)
        ->delete(route('admin.users.destroy', $admin))
        ->assertForbidden();

    expect($admin->fresh())->not->toBeNull();
});

test('an administrator can remove a staff account', function () {
    $admin = User::factory()->role(Role::Admin)->create();
    $receptionist = User::factory()->role(Role::Reception)->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $receptionist))
        ->assertRedirect(route('admin.users.index'));

    expect(User::query()->find($receptionist->id))->toBeNull();
});

test('the create form only offers roles the administrator may hand out', function () {
    $admin = User::factory()->role(Role::Admin)->create();

    $this->actingAs($admin)
        ->get(route('admin.users.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/create')
            ->has('roles', 4)
        );
});
