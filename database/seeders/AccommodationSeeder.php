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
 * What the hotel sells and sleeps: the facilities, the five room categories from
 * the website, the physical rooms behind them, the rates that move through the
 * year, and the closures that take a room off sale.
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
     * The facilities shown on the website and listed against each room.
     *
     * @return array<string, Amenity>
     */
    private function seedAmenities(): array
    {
        $amenities = [
            ['Free WiFi', 'wifi', 'general', 'Complimentary high speed internet across the hotel and gardens.', 1],
            ['Restaurant', 'utensils', 'dining', 'The Lakeview Restaurant serves breakfast, lunch and dinner with the lake in view.', 2],
            ['Bar & Lounge', 'wine', 'dining', 'The Anchor Bar & Lounge, plus a poolside bar.', 3],
            ['Conference Facilities', 'presentation', 'business', 'A conference centre seating up to 120 delegates, splitting into two rooms.', 4],
            ['Swimming Pool', 'waves', 'leisure', 'An outdoor pool with a sun deck and poolside service.', 5],
            ['Lake Activities', 'ship', 'leisure', 'Cruises, fishing trips, kayaking and snorkelling, arranged from the jetty.', 6],
            ['Secure Parking', 'car', 'general', 'Free off-street parking inside the hotel grounds.', 7],
            ['Room Service', 'concierge-bell', 'room', 'In-room dining during restaurant hours.', 8],
            ['Event Hosting', 'party-popper', 'business', 'Weddings, private parties and corporate events on the beach and terrace.', 9],
            ['Air Conditioning', 'snowflake', 'room', 'Individually controlled air conditioning in every room.', 10],
            ['En-suite Bathroom', 'shower-head', 'room', 'Private bathroom with hot water throughout the day.', 11],
            ['Satellite TV', 'tv', 'room', 'DSTV with sport and international news channels.', 12],
            ['Laundry Service', 'shirt', 'general', 'Same day laundry, collected from your room in the morning.', 13],
            ['Beach Access', 'umbrella', 'leisure', 'Direct access to our private stretch of lakeshore.', 14],
            ['Mosquito Nets', 'bug-off', 'room', 'Treated mosquito nets over every bed.', 15],
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
     * The five room categories featured on the website.
     *
     * @param  array<string, Amenity>  $amenities
     * @return array<string, RoomType>
     */
    private function seedRoomTypes(array $amenities): array
    {
        $definitions = [
            [
                'name' => 'Standard Rooms',
                'slug' => 'standard-rooms',
                'tagline' => 'Everything you need, with the lake a few steps away',
                'description' => 'Our standard rooms open onto the gardens, a short walk from the water. Each has a queen bed, en-suite bathroom with hot water, air conditioning and satellite television. They are the simplest way to wake up at Senga Bay.',
                'capacity_adults' => 2,
                'capacity_children' => 0,
                'size_sqm' => 26,
                'bed_configuration' => 'One queen bed',
                'base_price' => 95_000,
                'weekend_price' => 115_000,
                'extra_person_price' => 30_000,
                'min_nights' => 1,
                'sort_order' => 1,
                'is_featured' => true,
                'amenities' => ['Free WiFi', 'Air Conditioning', 'En-suite Bathroom', 'Satellite TV', 'Mosquito Nets', 'Room Service', 'Secure Parking'],
            ],
            [
                'name' => 'Deluxe Rooms',
                'slug' => 'deluxe-rooms',
                'tagline' => 'More space, and a private terrace facing the gardens',
                'description' => 'Deluxe rooms are larger, with a seating area and a private terrace looking over the gardens towards the lake. Choose a king bed or two doubles. Ideal for couples and for longer stays.',
                'capacity_adults' => 2,
                'capacity_children' => 1,
                'size_sqm' => 34,
                'bed_configuration' => 'One king bed or two doubles',
                'base_price' => 145_000,
                'weekend_price' => 175_000,
                'extra_person_price' => 35_000,
                'min_nights' => 1,
                'sort_order' => 2,
                'is_featured' => true,
                'amenities' => ['Free WiFi', 'Air Conditioning', 'En-suite Bathroom', 'Satellite TV', 'Mosquito Nets', 'Room Service', 'Beach Access', 'Laundry Service'],
            ],
            [
                'name' => 'Executive Suites',
                'slug' => 'executive-suites',
                'tagline' => 'A separate lounge, and the best view in the house',
                'description' => 'Executive suites have a bedroom, a separate sitting room and a wide veranda looking out over the bay. They are our most requested rooms for honeymoons, anniversaries and long working stays.',
                'capacity_adults' => 2,
                'capacity_children' => 2,
                'size_sqm' => 58,
                'bed_configuration' => 'One king bed with a separate lounge',
                'base_price' => 260_000,
                'weekend_price' => 310_000,
                'extra_person_price' => 45_000,
                'min_nights' => 1,
                'sort_order' => 3,
                'is_featured' => true,
                'amenities' => ['Free WiFi', 'Air Conditioning', 'En-suite Bathroom', 'Satellite TV', 'Mosquito Nets', 'Room Service', 'Beach Access', 'Laundry Service', 'Bar & Lounge'],
            ],
            [
                'name' => 'Family Rooms',
                'slug' => 'family-rooms',
                'tagline' => 'Room for five, right by the pool',
                'description' => 'Family rooms sleep up to five, with a king bed and three singles separated by a curtained area. They sit beside the pool and a short walk from the beach, which tends to be where the children want to be.',
                'capacity_adults' => 2,
                'capacity_children' => 3,
                'size_sqm' => 46,
                'bed_configuration' => 'One king bed and three single beds',
                'base_price' => 185_000,
                'weekend_price' => 215_000,
                'extra_person_price' => 30_000,
                'min_nights' => 1,
                'sort_order' => 4,
                'is_featured' => true,
                'amenities' => ['Free WiFi', 'Air Conditioning', 'En-suite Bathroom', 'Satellite TV', 'Mosquito Nets', 'Swimming Pool', 'Beach Access', 'Room Service'],
            ],
            [
                'name' => 'Lakeside Chalets',
                'slug' => 'lakeside-chalets',
                'tagline' => 'Your own deck over the water',
                'description' => 'Our five chalets stand at the edge of the property, each with a private deck facing the water. Wake up, walk out with a coffee, and watch the fishermen come in. These are the rooms guests ask for by name.',
                'capacity_adults' => 2,
                'capacity_children' => 2,
                'size_sqm' => 72,
                'bed_configuration' => 'One king bed, day bed and private deck',
                'base_price' => 320_000,
                'weekend_price' => 380_000,
                'extra_person_price' => 45_000,
                'min_nights' => 2,
                'sort_order' => 5,
                'is_featured' => true,
                'amenities' => ['Free WiFi', 'Air Conditioning', 'En-suite Bathroom', 'Satellite TV', 'Mosquito Nets', 'Beach Access', 'Room Service', 'Laundry Service', 'Lake Activities'],
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
     * The physical rooms behind each category.
     *
     * @param  array<string, RoomType>  $roomTypes
     */
    private function seedRooms(array $roomTypes): void
    {
        $layout = [
            'standard-rooms' => ['prefix' => 'Room', 'from' => 101, 'count' => 8, 'floor' => 'Ground'],
            'deluxe-rooms' => ['prefix' => 'Room', 'from' => 201, 'count' => 6, 'floor' => 'First'],
            'executive-suites' => ['prefix' => 'Suite', 'from' => 1, 'count' => 3, 'floor' => 'First'],
            'family-rooms' => ['prefix' => 'Room', 'from' => 301, 'count' => 4, 'floor' => 'Ground'],
            'lakeside-chalets' => ['prefix' => 'Chalet', 'from' => 1, 'count' => 5, 'floor' => 'Beach'],
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
                'notes' => 'Applies to negotiated corporate accounts.',
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
                'code' => 'CHALET-LONGSTAY',
                'name' => 'Chalet long stay',
                'type' => RatePlanType::Promotional,
                'adjustment_type' => RateAdjustmentType::Percentage,
                'amount' => -12,
                'min_nights' => 4,
                'room_type_id' => $roomTypes['lakeside-chalets']->id ?? null,
                'notes' => 'Four nights or more in a lakeside chalet.',
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
        $chalet = $roomTypes['lakeside-chalets'] ?? null;
        $standard = $roomTypes['standard-rooms'] ?? null;

        if ($chalet instanceof RoomType) {
            $room = $chalet->rooms()->orderBy('name')->first();

            if ($room !== null) {
                AvailabilityBlock::query()->updateOrCreate(
                    ['room_id' => $room->id, 'starts_on' => now()->addDays(10)->toDateString()],
                    [
                        'ends_on' => now()->addDays(16)->toDateString(),
                        'reason' => AvailabilityBlockReason::Renovation,
                        'notes' => 'Deck resurfacing. Booked as maintenance in the diary.',
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
