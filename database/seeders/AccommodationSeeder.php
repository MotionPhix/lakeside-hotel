<?php

namespace Database\Seeders;

use App\Enums\AvailabilityBlockReason;
use App\Enums\RateAdjustmentType;
use App\Enums\RatePlanType;
use App\Enums\RoomStatus;
use App\Models\Amenity;
use App\Models\AvailabilityBlock;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * What the hotel sells and sleeps, taken from the company profile: 42 rooms
 * across seven categories, the amenities each one offers, the facilities on site,
 * the conference facility list, the rates that move through the year, and the
 * closures that take a room off sale.
 */
class AccommodationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = $this->seedAmenities();
        $roomTypes = $this->seedRoomTypes($amenities);

        $this->seedRooms($roomTypes);
        $this->seedRatePlans($roomTypes);
        $this->seedClosures($roomTypes);
    }

    /**
     * Room amenities, the facilities on site, and the conference facility list.
     *
     * @return array<string, Amenity>
     */
    private function seedAmenities(): array
    {
        $amenities = [
            // In the rooms.
            ['Air Conditioning', 'snowflake', 'room', 'Individually controlled air conditioning in every room.', 1],
            ['Free Wi-Fi', 'wifi', 'room', 'Complimentary high speed internet across the hotel and gardens.', 2],
            ['DSTV', 'tv', 'room', 'Satellite television with sport and international news.', 3],
            ['Mini Fridge', 'refrigerator', 'room', 'A stocked mini fridge in every room.', 4],
            ['Tea and Coffee Kettle', 'coffee', 'room', 'A kettle with tea and coffee replenished daily.', 5],
            ['En-suite Bathroom', 'shower-head', 'room', 'Private bathroom with hot water throughout the day.', 6],
            ['Hair-dryer', 'wind', 'room', 'A hair-dryer in every bathroom.', 7],
            ['Mosquito Nets', 'bug-off', 'room', 'Treated mosquito nets over every bed.', 8],
            ['Breakfast Included', 'utensils', 'room', 'Breakfast for two at the Lakeview Restaurant is included in every rate.', 9],
            ['Lake Views', 'waves', 'room', 'Every room looks out over the gardens towards Lake Malawi.', 10],

            // On site.
            ['Swimming Pool', 'waves', 'general', 'An outdoor pool with a sun deck, gazebo and loungers overlooking the lake.', 11],
            ['Lakeview Restaurant', 'utensils', 'general', 'Our multi-cuisine restaurant, seating close to 400 people.', 12],
            ['Bar & Lounge', 'wine', 'general', 'The bar and lounge, with a pool table and the lake beyond.', 13],
            ['Pool Table', 'target', 'general', 'A full size pool table in the lounge.', 14],
            ['Conference Centre', 'presentation', 'general', 'Four conference halls seating up to 250 delegates.', 15],
            ['Secure Parking', 'car', 'general', 'Free off-street parking inside the hotel grounds.', 16],
            ['Room Service', 'concierge-bell', 'general', 'In-room dining during restaurant hours.', 17],
            ['Beach Access', 'umbrella', 'general', 'Direct access to our private stretch of lakeshore.', 18],
            ['Gardens', 'trees', 'general', 'Lawns and gardens running down to the water, with a thatched gazebo.', 19],
            ['Laundry Service', 'shirt', 'general', 'Same day laundry, collected from your room in the morning.', 20],
            ['Water Sports', 'ship', 'general', 'A 200 horsepower speed boat, water skiing, tubing and parasailing from our jetty.', 21],

            // Conference facility list, from the profile.
            ['Mineral Water and Candy on Table', 'cup-soda', 'conference', 'Still water and sweets set on every table before delegates arrive.', 22],
            ['HD Overhead Projector', 'projector', 'conference', 'An HD overhead projector with a full size screen.', 23],
            ['Cordless and Pin Microphones', 'mic', 'conference', 'Cordless and lapel microphones for speakers and panels.', 24],
            ['Flipcharts', 'file-text', 'conference', 'Flipcharts and markers for break-out sessions.', 25],
            ['Inbuilt HD Sound System', 'speaker', 'conference', 'An inbuilt HD sound system with a mixing desk.', 26],
            ['IDD Telephone', 'phone', 'conference', 'An IDD telephone for international calls.', 27],
            ['High Speed Free Wi-Fi', 'wifi', 'conference', 'High speed Wi-Fi throughout the conference centre.', 28],
            ['Printer, Scanner and Photocopier', 'printer', 'conference', 'A printer, scanner and photocopier available to delegates.', 29],
            ['Meeting Stationery', 'pen', 'conference', 'Notepads and pens at every seat.', 30],
            ['IT Butler Service', 'laptop', 'conference', 'A dedicated technician on hand for the duration of your event.', 31],
        ];

        $models = [];

        foreach ($amenities as [$name, $icon, $category, $description, $sortOrder]) {
            $models[$name] = Amenity::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'icon' => $icon,
                    'category' => $category,
                    'description' => $description,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ],
            );
        }

        return $models;
    }

    /**
     * The seven room categories, from standard doubles to executive suites.
     *
     * @param  array<string, Amenity>  $amenities
     * @return array<string, RoomType>
     */
    private function seedRoomTypes(array $amenities): array
    {
        $standard = ['En-suite Bathroom', 'Air Conditioning', 'Free Wi-Fi', 'DSTV', 'Breakfast Included', 'Mosquito Nets', 'Lake Views'];
        $deluxe = ['En-suite Bathroom', 'Air Conditioning', 'Free Wi-Fi', 'DSTV', 'Mini Fridge', 'Tea and Coffee Kettle', 'Hair-dryer', 'Breakfast Included', 'Mosquito Nets', 'Lake Views'];
        $suite = [...$deluxe, 'Room Service', 'Laundry Service', 'Beach Access'];

        $definitions = [
            [
                'name' => 'Standard Double',
                'slug' => 'standard-double',
                'tagline' => 'Everything you need, with the lake a short walk away',
                'description' => 'Our standard doubles open onto the gardens, a short walk from the water. Each has a double bed, an en-suite shower with hot water, air conditioning, satellite television and free Wi-Fi. Breakfast for two is included, and the lake is a minute away.',
                'capacity_adults' => 2,
                'capacity_children' => 0,
                'size_sqm' => 24,
                'bed_configuration' => 'One double bed',
                'base_price' => 95_000,
                'weekend_price' => 115_000,
                'extra_person_price' => 30_000,
                'min_nights' => 1,
                'sort_order' => 1,
                'is_featured' => true,
                'amenities' => $standard,
            ],
            [
                'name' => 'Deluxe Single',
                'slug' => 'deluxe-single',
                'tagline' => 'A quiet room for the solo traveller',
                'description' => 'A comfortable single room for guests travelling alone on business or passing through Salima, with the same lake views and garden setting as the rest of the hotel.',
                'capacity_adults' => 1,
                'capacity_children' => 0,
                'size_sqm' => 22,
                'bed_configuration' => 'One single bed',
                'base_price' => 110_000,
                'weekend_price' => 130_000,
                'extra_person_price' => 30_000,
                'min_nights' => 1,
                'sort_order' => 2,
                'is_featured' => false,
                'amenities' => $standard,
            ],
            [
                'name' => 'Twin Room',
                'slug' => 'twin-room',
                'tagline' => 'Two beds, two friends, one lake',
                'description' => 'Twin rooms have two single beds and a little more floor space, which makes them the usual choice for friends travelling together or colleagues sharing on a conference booking.',
                'capacity_adults' => 2,
                'capacity_children' => 1,
                'size_sqm' => 30,
                'bed_configuration' => 'Two single beds',
                'base_price' => 145_000,
                'weekend_price' => 175_000,
                'extra_person_price' => 30_000,
                'min_nights' => 1,
                'sort_order' => 3,
                'is_featured' => true,
                'amenities' => $deluxe,
            ],
            [
                'name' => 'Deluxe Double',
                'slug' => 'deluxe-double',
                'tagline' => 'A king-size bed and a view of the water',
                'description' => 'Our most requested room. A king-size bed, a mini fridge, a kettle for morning coffee, and a wide window onto the gardens and the lake beyond.',
                'capacity_adults' => 2,
                'capacity_children' => 1,
                'size_sqm' => 34,
                'bed_configuration' => 'One king-size bed',
                'base_price' => 165_000,
                'weekend_price' => 195_000,
                'extra_person_price' => 35_000,
                'min_nights' => 1,
                'sort_order' => 4,
                'is_featured' => true,
                'amenities' => $deluxe,
            ],
            [
                'name' => 'Deluxe Family',
                'slug' => 'deluxe-family',
                'tagline' => 'Room for five, right by the pool',
                'description' => 'Deluxe family rooms sleep up to five, with a king-size bed and a bunk bed. They sit close to the pool and a short walk from the beach, which tends to be where the children want to be.',
                'capacity_adults' => 2,
                'capacity_children' => 3,
                'size_sqm' => 42,
                'bed_configuration' => 'One king-size bed and a bunk bed',
                'base_price' => 195_000,
                'weekend_price' => 225_000,
                'extra_person_price' => 30_000,
                'min_nights' => 1,
                'sort_order' => 5,
                'is_featured' => true,
                'amenities' => [...$deluxe, 'Swimming Pool'],
            ],
            [
                'name' => 'Honeymoon Suite',
                'slug' => 'honeymoon-suite',
                'tagline' => 'The quietest corner of the property',
                'description' => 'A secluded suite with a king-size bed, a sitting area and a veranda facing the water. We can arrange a private candle-lit dinner on the deck, a boat trip to the islands, and a late check out on your last morning.',
                'capacity_adults' => 2,
                'capacity_children' => 0,
                'size_sqm' => 48,
                'bed_configuration' => 'One king-size bed with a sitting area',
                'base_price' => 285_000,
                'weekend_price' => 330_000,
                'extra_person_price' => 45_000,
                'min_nights' => 2,
                'sort_order' => 6,
                'is_featured' => true,
                'amenities' => [...$suite, 'Bar & Lounge'],
            ],
            [
                'name' => 'Executive Suite',
                'slug' => 'executive-suite',
                'tagline' => 'A separate lounge, for longer stays',
                'description' => 'Designed for business professionals staying a while. A king-size bed, a separate lounge for working or meeting, and the full run of the hotel. Popular with guests combining a conference with a few days on the lake.',
                'capacity_adults' => 2,
                'capacity_children' => 2,
                'size_sqm' => 58,
                'bed_configuration' => 'One king-size bed with a separate lounge',
                'base_price' => 310_000,
                'weekend_price' => 360_000,
                'extra_person_price' => 45_000,
                'min_nights' => 1,
                'sort_order' => 7,
                'is_featured' => true,
                'amenities' => [...$suite, 'Conference Centre', 'Bar & Lounge'],
            ],
        ];

        $models = [];

        foreach ($definitions as $definition) {
            $amenityNames = $definition['amenities'];
            unset($definition['amenities']);

            $roomType = RoomType::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                $definition + ['is_active' => true],
            );

            $roomType->amenities()->sync(
                collect($amenityNames)
                    ->map(fn (string $name): ?int => $amenities[$name]->id ?? null)
                    ->filter()
                    ->all(),
            );

            $models[$roomType->slug] = $roomType;
        }

        return $models;
    }

    /**
     * The 42 physical rooms behind the categories.
     *
     * @param  array<string, RoomType>  $roomTypes
     */
    private function seedRooms(array $roomTypes): void
    {
        // slug => [prefix, first number, how many, floor]. The counts add up to
        // the 42 rooms the company profile advertises.
        $layout = [
            'standard-double' => ['prefix' => 'Room', 'from' => 101, 'count' => 10, 'floor' => 'Ground'],
            'deluxe-single' => ['prefix' => 'Room', 'from' => 111, 'count' => 6, 'floor' => 'Ground'],
            'twin-room' => ['prefix' => 'Room', 'from' => 201, 'count' => 6, 'floor' => 'First'],
            'deluxe-double' => ['prefix' => 'Room', 'from' => 207, 'count' => 8, 'floor' => 'First'],
            'deluxe-family' => ['prefix' => 'Room', 'from' => 215, 'count' => 5, 'floor' => 'First'],
            'honeymoon-suite' => ['prefix' => 'Suite', 'from' => 1, 'count' => 3, 'floor' => 'Second'],
            'executive-suite' => ['prefix' => 'Suite', 'from' => 4, 'count' => 4, 'floor' => 'Second'],
        ];

        foreach ($layout as $slug => $config) {
            $roomType = $roomTypes[$slug] ?? null;

            if (! $roomType instanceof RoomType) {
                continue;
            }

            for ($offset = 0; $offset < $config['count']; $offset++) {
                $number = $config['from'] + $offset;

                Room::query()->updateOrCreate(
                    [
                        'room_type_id' => $roomType->id,
                        'name' => "{$config['prefix']} {$number}",
                    ],
                    [
                        'number' => (string) $number,
                        'floor' => $config['floor'],
                        'status' => RoomStatus::Available,
                    ],
                );
            }
        }
    }

    /**
     * The rates that move through the Malawian year.
     *
     * @param  array<string, RoomType>  $roomTypes
     */
    private function seedRatePlans(array $roomTypes): void
    {
        $year = (int) now()->format('Y');

        $plans = [
            [
                'code' => 'GREEN-SEASON',
                'name' => 'Green season discount',
                'type' => RatePlanType::Seasonal,
                'adjustment_type' => RateAdjustmentType::Percentage,
                'amount' => -20,
                'starts_on' => "{$year}-01-05",
                'ends_on' => "{$year}-03-31",
                'notes' => 'The quiet months after the rains. Lower rates across all room categories.',
            ],
            [
                'code' => 'PEAK-SEASON',
                'name' => 'Peak season surcharge',
                'type' => RatePlanType::Seasonal,
                'adjustment_type' => RateAdjustmentType::Percentage,
                'amount' => 25,
                'starts_on' => "{$year}-07-01",
                'ends_on' => "{$year}-08-31",
                'notes' => 'School holidays and the Malawian winter, our busiest weeks.',
            ],
            [
                'code' => 'FESTIVE',
                'name' => 'Festive season premium',
                'type' => RatePlanType::Holiday,
                'adjustment_type' => RateAdjustmentType::Percentage,
                'amount' => 35,
                'starts_on' => "{$year}-12-20",
                'ends_on' => ($year + 1).'-01-03',
                'notes' => 'Christmas and New Year. Minimum three night stay.',
                'min_nights' => 3,
            ],
            [
                'code' => 'CORPORATE',
                'name' => 'Corporate rate',
                'type' => RatePlanType::Corporate,
                'adjustment_type' => RateAdjustmentType::Percentage,
                'amount' => -15,
                'notes' => 'Applies to negotiated corporate accounts and conference delegates.',
            ],
            [
                'code' => 'MIDWEEK',
                'name' => 'Midweek escape',
                'type' => RatePlanType::Promotional,
                'adjustment_type' => RateAdjustmentType::Percentage,
                'amount' => -10,
                'days_of_week' => [1, 2, 3, 4],
                'notes' => 'Monday to Thursday nights, outside the festive season.',
            ],
            [
                'code' => 'STAY3PAY2',
                'name' => 'Stay 3, pay 2',
                'type' => RatePlanType::Promotional,
                'adjustment_type' => RateAdjustmentType::Percentage,
                'amount' => -33,
                'min_nights' => 3,
                'notes' => 'Three nights or more, applied to the whole stay.',
            ],
            [
                'code' => 'SUITE-LONGSTAY',
                'name' => 'Suite long stay',
                'type' => RatePlanType::Promotional,
                'adjustment_type' => RateAdjustmentType::Percentage,
                'amount' => -12,
                'min_nights' => 4,
                'room_type_id' => $roomTypes['honeymoon-suite']->id ?? null,
                'notes' => 'Four nights or more in the honeymoon suite.',
            ],
        ];

        foreach ($plans as $plan) {
            $type = $plan['type'];

            RatePlan::query()->updateOrCreate(
                ['code' => $plan['code']],
                $plan + [
                    'room_type_id' => $plan['room_type_id'] ?? null,
                    'min_nights' => $plan['min_nights'] ?? null,
                    'days_of_week' => $plan['days_of_week'] ?? null,
                    'starts_on' => $plan['starts_on'] ?? null,
                    'ends_on' => $plan['ends_on'] ?? null,
                    'priority' => $type->defaultPriority(),
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * Rooms currently taken off sale.
     *
     * @param  array<string, RoomType>  $roomTypes
     */
    private function seedClosures(array $roomTypes): void
    {
        $suites = $roomTypes['honeymoon-suite'] ?? null;
        $standard = $roomTypes['standard-double'] ?? null;

        if ($suites instanceof RoomType) {
            $room = $suites->rooms()->orderBy('name')->first();

            if ($room !== null) {
                AvailabilityBlock::query()->updateOrCreate(
                    ['room_id' => $room->id, 'starts_on' => now()->addDays(10)->toDateString()],
                    [
                        'ends_on' => now()->addDays(16)->toDateString(),
                        'reason' => AvailabilityBlockReason::Renovation,
                        'notes' => 'Veranda resurfacing. Booked as maintenance in the diary.',
                    ],
                );
            }
        }

        if ($standard instanceof RoomType) {
            $room = $standard->rooms()->orderBy('name')->first();

            if ($room !== null) {
                AvailabilityBlock::query()->updateOrCreate(
                    ['room_id' => $room->id, 'starts_on' => now()->addDays(3)->toDateString()],
                    [
                        'ends_on' => now()->addDays(3)->toDateString(),
                        'reason' => AvailabilityBlockReason::DeepClean,
                        'notes' => 'Annual deep clean after a long stay.',
                    ],
                );
            }
        }
    }
}
