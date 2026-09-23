<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One account per staff role, so every corner of the dashboard can be explored.
 * All of them sign in with the password `password`.
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

        foreach ($team as $member) {
            User::factory()->role($member['role'])->create([
                'name' => $member['name'],
                'email' => $member['email'],
                'job_title' => $member['job_title'],
                'phone' => '+265 99 000 0000',
                'email_verified_at' => now(),
            ]);
        }
    }
}
