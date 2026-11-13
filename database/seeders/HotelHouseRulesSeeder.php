<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\DiningVenue;
use App\Models\Offer;
use App\Models\Setting;
use App\Support\HotelProfile;
use Illuminate\Database\Seeder;

class HotelHouseRulesSeeder extends Seeder
{
    public function run(): void
    {
        Setting::store('booking.house_rules', HotelProfile::HOUSE_RULES, 'booking', 'text');
        DiningVenue::query()->where('slug', 'bar-and-lounge')->update([
            'name' => 'The Lounge',
            'description' => 'The lounge opens onto the gardens, with a pool table and views across the lake. Enjoy mocktails, shisha and other non-alcoholic refreshments. Alcohol is not served or permitted on the property.',
        ]);
        Amenity::query()->where('slug', 'bar-lounge')->update([
            'icon' => 'cup-soda',
            'description' => 'Mocktails, shisha and a pool table in our alcohol-free lounge.',
        ]);
        Offer::query()->where('coupon_code', 'WEEKEND15')->update([
            'description' => 'Arrive on Friday, wake up to the lake twice, and leave after a late breakfast on Sunday. Includes a complimentary sunset cruise for two.',
        ]);
    }
}
