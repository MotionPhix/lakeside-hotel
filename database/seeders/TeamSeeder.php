<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * One account per staff role, so every corner of the dashboard can be explored.
 *
 * The accounts are created directly rather than through the factory. Factories
 * lean on `fake()`, and `fakerphp/faker` is a development dependency: on a
 * production install built with `composer install --no-dev` there is no faker to
 * call, and seeding died on "Call to undefined function Database\Factories\fake()".
 *
 * The password is not `password` on a live site. Set `SEED_TEAM_PASSWORD` to
 * choose it; without that, production gets a random one per run and it is printed
 * once so it can be recorded. Local and test seeding keep the predictable
 * password the rest of the project's fixtures assume.
 */
class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $team = [
            ['name' => 'System Administrator', 'email' => 'developer@lakesidehotel.mw', 'role' => Role::SystemAdmin, 'job_title' => 'System Administrator'],
            ['name' => 'Grace Banda', 'email' => 'owner@lakesidehotel.mw', 'role' => Role::Admin, 'job_title' => 'Owner'],
            ['name' => 'Chikondi Phiri', 'email' => 'manager@lakesidehotel.mw', 'role' => Role::HotelManager, 'job_title' => 'Hotel Manager'],
            ['name' => 'Thandiwe Moyo', 'email' => 'reception@lakesidehotel.mw', 'role' => Role::Reception, 'job_title' => 'Front Desk Supervisor'],
            ['name' => 'Yamikani Zulu', 'email' => 'marketing@lakesidehotel.mw', 'role' => Role::Marketing, 'job_title' => 'Marketing Officer'],
        ];

        $password = env('SEED_TEAM_PASSWORD') ?: (app()->isProduction() ? Str::password(20) : 'password');

        if (app()->isProduction() && ! env('SEED_TEAM_PASSWORD')) {
            $this->command?->warn("SEED_TEAM_PASSWORD is not set. These accounts were given the password: {$password}");
        }

        foreach ($team as $member) {
            // Keyed on the email so running this twice repairs the accounts rather
            // than failing on the unique index half way through the list.
            $user = User::firstOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'password' => $password,
                    'role' => $member['role'],
                    'phone' => '+265 99 000 0000',
                    'job_title' => $member['job_title'],
                    'is_active' => true,
                ],
            );

            // Not mass-assignable, so it is set on the model rather than passed in.
            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }
    }
}
