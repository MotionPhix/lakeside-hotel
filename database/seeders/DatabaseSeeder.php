<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedTeam();
    }

    /**
     * One account per staff role so the dashboard can be explored end to end.
     */
    private function seedTeam(): void
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
