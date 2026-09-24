<?php

namespace App\Cms;

use App\Enums\Permission;
use App\Enums\RateAdjustmentType;
use App\Enums\RatePlanType;
use App\Enums\RoomStatus;
use App\Models\Activity;
use App\Models\Amenity;
use App\Models\ConferenceHall;
use App\Models\ConferencePackage;
use App\Models\ContentBlock;
use App\Models\Coupon;
use App\Models\DiningVenue;
use App\Models\GalleryItem;
use App\Models\HeroSlide;
use App\Models\MenuItem;
use App\Models\NearbyAttraction;
use App\Models\Offer;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\SiteSection;
use App\Models\Testimonial;
use Illuminate\Support\Str;

/**
 * Every content type the hotel can edit, in one place.
 *
 * Adding a content type is a method here and nothing else: the controller, the
 * validation, the list, the form and the reorder controls are all generic and
 * read whatever this returns. If a new type needs a new *kind* of control, that
 * is a new constant on {@see Field} plus a branch in the React field renderer -
 * not a new screen.
 */
final class ResourceRegistry
{
    /**
     * @return array<string, ResourceDefinition>
     */
    public function all(): array
    {
        $definitions = array_map(
            fn (string $method): ResourceDefinition => $this->{$method}(),
            [
                'heroSlides', 'contentBlocks', 'siteSections',
                'roomTypes', 'rooms', 'amenities', 'ratePlans',
                'diningVenues', 'menuItems',
                'activities', 'conferenceHalls', 'conferencePackages', 'nearbyAttractions',
                'galleryItems', 'testimonials', 'offers', 'coupons',
            ],
        );

        $keyed = [];

        foreach ($definitions as $definition) {
            $keyed[$definition->key] = $definition;
        }

        return $keyed;
    }

    public function find(string $key): ?ResourceDefinition
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * The content types grouped for the hub screen, in reading order.
     *
     * @return list<array{group: string, resources: list<array<string, mixed>>}>
     */
    public function groupedForHub(): array
    {
        $groups = [];

        foreach ($this->all() as $definition) {
            $groups[$definition->group][] = [
                'key' => $definition->key,
                'label' => $definition->label,
                'singular' => $definition->singular,
                'description' => $definition->description,
                'count' => $definition->model::query()->count(),
            ];
        }

        $ordered = [];

        foreach ($groups as $group => $resources) {
            $ordered[] = ['group' => $group, 'resources' => $resources];
        }

        return $ordered;
    }

    // ---------------------------------------------------------------- homepage

    private function heroSlides(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'hero-slides',
            model: HeroSlide::class,
            label: 'Hero slides',
            singular: 'Hero slide',
            group: 'Homepage',
            permission: Permission::ManageContent,
            description: 'The full-screen images behind the headline on the homepage, and their buttons.',
            columns: ['headline', 'subheadline', 'cta_label', 'is_active'],
            searchable: ['headline', 'subheadline'],
            media: ['image' => ['label' => 'Image', 'multiple' => false], 'video' => ['label' => 'Video', 'multiple' => false]],
            fields: [
                Field::text('headline', 'Headline', required: true),
                Field::text('subheadline', 'Subheadline'),
                Field::text('cta_label', 'Button label', help: 'Leave blank to hide the button.'),
                Field::text('cta_url', 'Button link'),
                Field::text('secondary_cta_label', 'Second button label'),
                Field::text('secondary_cta_url', 'Second button link'),
                Field::media('image', 'Image'),
                Field::media('video', 'Video', 'Optional. Plays instead of the image when one is uploaded.'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    private function contentBlocks(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'content-blocks',
            model: ContentBlock::class,
            label: 'Content blocks',
            singular: 'Content block',
            group: 'Homepage',
            permission: Permission::ManageContent,
            description: 'Reusable pieces of copy the pages pull in by key.',
            columns: ['title', 'key', 'is_active'],
            searchable: ['title', 'key', 'body'],
            titleColumn: 'title',
            media: ['image' => ['label' => 'Image', 'multiple' => false]],
            fields: [
                Field::text('key', 'Key', required: true, help: 'How the page refers to this block. Changing it may break a page that uses it.'),
                Field::text('title', 'Title', required: true),
                Field::text('subtitle', 'Subtitle'),
                Field::area('body', 'Body'),
                Field::media('image', 'Image'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    private function siteSections(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'site-sections',
            model: SiteSection::class,
            label: 'Page sections',
            singular: 'Page section',
            group: 'Homepage',
            permission: Permission::ManageContent,
            description: 'Which sections each page shows, in what order, with their own wording.',
            columns: ['title', 'page', 'key', 'is_active'],
            searchable: ['title', 'key', 'page'],
            titleColumn: 'title',
            fields: [
                Field::pick('page', 'Page', ['home' => 'Homepage'], required: true),
                Field::pick('key', 'Section', $this->labels(SiteSection::HOME_SECTIONS), required: true),
                Field::text('eyebrow', 'Eyebrow', help: 'The small line above the heading.'),
                Field::text('title', 'Heading', required: true),
                Field::area('description', 'Description'),
                Field::map('config', 'Settings', 'One per line, as key: value. e.g. limit: 6 controls how many items the section shows.'),
                Field::bool('is_active', 'Published', 'Switching this off hides the section from the page.'),
            ],
        );
    }

    // ----------------------------------------------------------- accommodation

    private function roomTypes(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'room-types',
            model: RoomType::class,
            label: 'Room types',
            singular: 'Room type',
            group: 'Accommodation',
            permission: Permission::ManageRooms,
            description: 'The categories of room the hotel sells, and what each one costs.',
            columns: ['name', 'base_price', 'weekend_price', 'capacity_adults', 'is_active'],
            searchable: ['name', 'tagline'],
            media: ['cover' => ['label' => 'Cover photo', 'multiple' => false], 'images' => ['label' => 'Gallery', 'multiple' => true]],
            fields: [
                Field::text('name', 'Name', required: true),
                Field::slug('slug', 'Web address'),
                Field::text('tagline', 'Tagline'),
                Field::area('description', 'Description'),
                Field::num('capacity_adults', 'Adults included', required: true, nullable: false, help: 'How many adults the base rate covers.'),
                Field::num('capacity_children', 'Children free', nullable: false),
                Field::num('size_sqm', 'Size in square metres'),
                Field::text('bed_configuration', 'Bed configuration', help: 'e.g. One king, or two doubles.'),
                Field::money('base_price', 'Midweek rate', required: true, nullable: false),
                Field::money('weekend_price', 'Weekend rate', help: 'Friday and Saturday. Leave blank to use the midweek rate.'),
                Field::money('extra_person_price', 'Extra person supplement', help: 'Charged per night for each adult above the included occupancy.'),
                Field::num('min_nights', 'Minimum nights'),
                Field::media('cover', 'Cover photo'),
                Field::media('images', 'Gallery'),
                Field::bool('is_featured', 'Feature on the homepage'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    private function rooms(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'rooms',
            model: Room::class,
            label: 'Rooms',
            singular: 'Room',
            group: 'Accommodation',
            permission: Permission::ManageRooms,
            description: 'The individual rooms, which is what availability is counted from.',
            columns: ['number', 'name', 'room_type_id', 'status'],
            searchable: ['number', 'name'],
            sortable: false,
            publishColumn: null,
            titleColumn: 'number',
            fields: [
                Field::link('room_type_id', 'Room type', RoomType::class, required: true),
                Field::text('number', 'Room number', required: true),
                Field::text('name', 'Name'),
                Field::num('floor', 'Floor'),
                Field::pick('status', 'Status', RoomStatus::options(), required: true, help: 'Only rooms that are available can be sold.'),
                Field::area('notes', 'Internal notes'),
            ],
        );
    }

    private function amenities(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'amenities',
            model: Amenity::class,
            label: 'Amenities',
            singular: 'Amenity',
            group: 'Accommodation',
            permission: Permission::ManageContent,
            description: 'The facilities listed against rooms and the hotel, each with an icon.',
            columns: ['name', 'category', 'icon', 'is_active'],
            searchable: ['name'],
            fields: [
                Field::text('name', 'Name', required: true),
                Field::slug('slug', 'Web address'),
                Field::icon('icon', 'Icon'),
                Field::text('category', 'Category', help: 'e.g. room, bathroom, hotel.'),
                Field::area('description', 'Description'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    private function ratePlans(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'rate-plans',
            model: RatePlan::class,
            label: 'Rate plans',
            singular: 'Rate plan',
            group: 'Rates',
            permission: Permission::ManagePricing,
            description: 'Seasonal, weekend, corporate and promotional adjustments to the room rates.',
            columns: ['name', 'code', 'type', 'adjustment_type', 'amount', 'is_active'],
            searchable: ['name', 'code'],
            sortable: false,
            fields: [
                Field::link('room_type_id', 'Room type', RoomType::class, help: 'Leave blank to apply to every room type.'),
                Field::text('name', 'Name', required: true),
                Field::text('code', 'Code'),
                Field::pick('type', 'Type', RatePlanType::options(), required: true),
                Field::pick('adjustment_type', 'Adjustment', RateAdjustmentType::options(), required: true),
                Field::money('amount', 'Amount', required: true, nullable: false),
                Field::num('min_nights', 'Minimum nights'),
                Field::list('days_of_week', 'Days of the week', 'One weekday number per line, 0 (Sunday) to 6 (Saturday). Leave blank for every day.'),
                Field::date('starts_on', 'Starts'),
                Field::date('ends_on', 'Ends'),
                Field::num('priority', 'Priority', nullable: false, help: 'Where two plans cover a night, the higher number wins.'),
                Field::area('notes', 'Internal notes'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    // ----------------------------------------------------------------- dining

    private function diningVenues(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'dining-venues',
            model: DiningVenue::class,
            label: 'Dining venues',
            singular: 'Dining venue',
            group: 'Dining',
            permission: Permission::ManageContent,
            description: 'The restaurant, bar and pool bar, with their hours and menus.',
            columns: ['name', 'type', 'is_active'],
            searchable: ['name', 'tagline'],
            media: ['cover' => ['label' => 'Cover photo', 'multiple' => false], 'gallery' => ['label' => 'Gallery', 'multiple' => true]],
            fields: [
                Field::text('name', 'Name', required: true),
                Field::slug('slug', 'Web address'),
                Field::pick('type', 'Type', ['restaurant' => 'Restaurant', 'bar' => 'Bar', 'pool_bar' => 'Pool bar'], required: true),
                Field::text('tagline', 'Tagline'),
                Field::area('description', 'Description'),
                Field::map('opening_hours', 'Opening hours', 'One per line, as meal: time — e.g. lunch: 12:00 - 15:00.'),
                Field::map('section_notes', 'Menu section notes', 'One per line, as section: note — e.g. sandwiches: All served with chips.'),
                Field::text('menu_note', 'Menu note', help: 'Shown once above the menu, e.g. Prices are tax inclusive.'),
                Field::text('dress_code', 'Dress code'),
                Field::media('cover', 'Cover photo'),
                Field::media('gallery', 'Gallery'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    private function menuItems(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'menu-items',
            model: MenuItem::class,
            label: 'Menu items',
            singular: 'Menu item',
            group: 'Dining',
            permission: Permission::ManageContent,
            description: 'Every dish and drink, priced and filed under its menu section.',
            columns: ['name', 'category', 'price', 'is_available'],
            searchable: ['name', 'description'],
            // The menu card has no is_active; a dish is simply available or not.
            publishColumn: 'is_available',
            fields: [
                Field::link('dining_venue_id', 'Venue', DiningVenue::class, required: true),
                Field::text('name', 'Name', required: true),
                Field::area('description', 'Description'),
                Field::money('price', 'Price', required: true, nullable: false),
                Field::pick('category', 'Menu section', MenuItem::CATEGORY_LABELS, required: true),
                Field::bool('is_signature', 'Signature dish'),
                Field::bool('is_vegetarian', 'Vegetarian'),
                Field::bool('is_available', 'Available'),
            ],
        );
    }

    // ------------------------------------------------------------ experiences

    private function activities(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'activities',
            model: Activity::class,
            label: 'Activities',
            singular: 'Activity',
            group: 'Experiences',
            permission: Permission::ManageContent,
            description: 'Boat trips, guided walks and everything else guests can book.',
            columns: ['name', 'price', 'price_basis', 'is_active'],
            searchable: ['name', 'description'],
            media: ['cover' => ['label' => 'Cover photo', 'multiple' => false], 'gallery' => ['label' => 'Gallery', 'multiple' => true]],
            fields: [
                Field::text('name', 'Name', required: true),
                Field::slug('slug', 'Web address'),
                Field::area('description', 'Description'),
                Field::num('duration_minutes', 'Duration in minutes'),
                Field::money('price', 'Price'),
                Field::pick('price_basis', 'Priced', $this->labels(Activity::PRICE_BASES)),
                Field::num('min_participants', 'Minimum guests'),
                Field::num('max_participants', 'Maximum guests'),
                Field::media('cover', 'Cover photo'),
                Field::media('gallery', 'Gallery'),
                Field::bool('is_featured', 'Feature on the homepage'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    private function conferenceHalls(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'conference-halls',
            model: ConferenceHall::class,
            label: 'Conference halls',
            singular: 'Conference hall',
            group: 'Experiences',
            permission: Permission::ManageContent,
            description: 'The rooms available for conferences, meetings and events.',
            columns: ['name', 'capacity', 'layout', 'is_active'],
            searchable: ['name', 'description'],
            media: ['cover' => ['label' => 'Cover photo', 'multiple' => false], 'gallery' => ['label' => 'Gallery', 'multiple' => true]],
            fields: [
                Field::text('name', 'Name', required: true),
                Field::slug('slug', 'Web address'),
                Field::num('capacity', 'Seats', required: true, nullable: false),
                Field::text('layout', 'Layout', help: 'e.g. Theatre, or classroom.'),
                Field::area('description', 'Description'),
                Field::list('features', 'Features', 'One per line — e.g. Projector.'),
                Field::media('cover', 'Cover photo'),
                Field::media('gallery', 'Gallery'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    private function conferencePackages(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'conference-packages',
            model: ConferencePackage::class,
            label: 'Conference packages',
            singular: 'Conference package',
            group: 'Experiences',
            permission: Permission::ManageContent,
            description: 'Day delegate rates, retreats and wedding packages.',
            columns: ['name', 'type', 'price', 'is_active'],
            searchable: ['name', 'tagline'],
            media: ['cover' => ['label' => 'Cover photo', 'multiple' => false], 'gallery' => ['label' => 'Gallery', 'multiple' => true]],
            fields: [
                Field::text('name', 'Name', required: true),
                Field::slug('slug', 'Web address'),
                Field::pick('type', 'Type', $this->labels(ConferencePackage::TYPES), required: true),
                Field::text('tagline', 'Tagline'),
                Field::area('description', 'Description'),
                Field::num('capacity_min', 'Minimum guests'),
                Field::num('capacity_max', 'Maximum guests'),
                Field::money('price', 'Price'),
                Field::pick('price_basis', 'Priced', $this->labels(['per_person', 'per_group', 'per_event', 'complimentary'])),
                Field::list('includes', 'What is included', 'One per line.'),
                Field::media('cover', 'Cover photo'),
                Field::media('gallery', 'Gallery'),
                Field::bool('is_featured', 'Feature on the homepage'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    private function nearbyAttractions(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'nearby-attractions',
            model: NearbyAttraction::class,
            label: 'Nearby attractions',
            singular: 'Nearby attraction',
            group: 'Experiences',
            permission: Permission::ManageContent,
            description: 'What is worth seeing around Senga Bay, shown on the location page.',
            columns: ['name', 'category', 'distance_km', 'is_active'],
            searchable: ['name', 'description'],
            media: ['image' => ['label' => 'Image', 'multiple' => false]],
            fields: [
                Field::text('name', 'Name', required: true),
                Field::text('category', 'Category'),
                Field::area('description', 'Description'),
                Field::decimal('distance_km', 'Distance in km'),
                Field::num('travel_time_minutes', 'Travel time in minutes'),
                Field::media('image', 'Image'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    // --------------------------------------------------------------- website

    private function galleryItems(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'gallery-items',
            model: GalleryItem::class,
            label: 'Gallery',
            singular: 'Gallery item',
            group: 'Website',
            permission: Permission::ManageMedia,
            description: 'The photographs and films in the gallery wall.',
            columns: ['title', 'category', 'type', 'is_active'],
            searchable: ['title', 'caption'],
            titleColumn: 'title',
            media: ['image' => ['label' => 'Image', 'multiple' => false], 'video' => ['label' => 'Video file', 'multiple' => false]],
            fields: [
                Field::text('title', 'Title', required: true),
                Field::text('caption', 'Caption'),
                Field::pick('category', 'Category', $this->labels(GalleryItem::CATEGORIES), required: true),
                Field::pick('type', 'Type', ['image' => 'Image', 'video' => 'Video'], required: true),
                Field::text('video_url', 'Hosted video link', help: 'Only needed when the video is hosted elsewhere rather than uploaded.'),
                Field::media('image', 'Image'),
                Field::media('video', 'Video file'),
                Field::bool('is_featured', 'Feature on the homepage'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    private function testimonials(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'testimonials',
            model: Testimonial::class,
            label: 'Guest reviews',
            singular: 'Guest review',
            group: 'Website',
            permission: Permission::ModerateReviews,
            description: 'What guests said, and whether it is shown on the site.',
            columns: ['guest_name', 'rating', 'title', 'is_approved'],
            searchable: ['guest_name', 'title', 'quote'],
            sortable: false,
            // A review is published by approving it, not by an is_active flag.
            publishColumn: 'is_approved',
            titleColumn: 'title',
            media: ['avatar' => ['label' => 'Photo', 'multiple' => false]],
            fields: [
                Field::text('guest_name', 'Guest name', required: true),
                Field::text('guest_country', 'Country'),
                Field::num('rating', 'Rating out of five', nullable: false),
                Field::text('title', 'Title'),
                Field::area('quote', 'Review', required: true),
                Field::date('stayed_on', 'Stayed on'),
                Field::pick('source', 'Source', $this->labels(Testimonial::SOURCES)),
                Field::area('response', 'Your reply'),
                Field::media('avatar', 'Photo'),
                Field::bool('is_featured', 'Feature on the homepage'),
                Field::bool('is_approved', 'Approved', 'Only approved reviews are shown on the website.'),
            ],
        );
    }

    private function offers(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'offers',
            model: Offer::class,
            label: 'Special offers',
            singular: 'Special offer',
            group: 'Website',
            permission: Permission::ManagePromotions,
            description: 'The packages and deals promoted on the site.',
            columns: ['title', 'discount_label', 'ends_on', 'is_active'],
            searchable: ['title', 'subtitle'],
            titleColumn: 'title',
            media: ['image' => ['label' => 'Image', 'multiple' => false]],
            fields: [
                Field::text('title', 'Title', required: true),
                Field::slug('slug', 'Web address'),
                Field::text('subtitle', 'Subtitle'),
                Field::area('description', 'Description'),
                Field::text('highlight', 'Highlight'),
                Field::text('discount_label', 'Discount label', help: 'e.g. Save 20%.'),
                Field::pick('type', 'Type', $this->labels(Offer::TYPES), required: true),
                Field::date('starts_on', 'Starts'),
                Field::date('ends_on', 'Ends'),
                Field::text('coupon_code', 'Discount code', help: 'The code a guest types at checkout.'),
                Field::area('terms', 'Terms'),
                Field::media('image', 'Image'),
                Field::bool('is_featured', 'Feature on the homepage'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    // ------------------------------------------------------------- promotions

    private function coupons(): ResourceDefinition
    {
        return new ResourceDefinition(
            key: 'coupons',
            model: Coupon::class,
            label: 'Discount codes',
            singular: 'Discount code',
            group: 'Promotions',
            permission: Permission::ManagePromotions,
            description: 'Codes a guest can enter when booking to reduce the price.',
            columns: ['code', 'discount_value', 'used_count', 'is_active'],
            searchable: ['code', 'description'],
            sortable: false,
            titleColumn: 'code',
            fields: [
                Field::text('code', 'Code', required: true, help: 'What the guest types. Not case sensitive.'),
                Field::text('description', 'Description'),
                Field::pick('discount_type', 'Discount type', ['percentage' => 'Percentage off', 'fixed' => 'Fixed amount off'], required: true),
                Field::money('discount_value', 'Discount', required: true, nullable: false),
                Field::num('min_nights', 'Minimum nights'),
                Field::money('min_spend', 'Minimum spend'),
                Field::link('room_type_id', 'Restricted to', RoomType::class, help: 'Leave blank for every room type.'),
                Field::date('valid_from', 'Valid from'),
                Field::date('valid_until', 'Valid until'),
                Field::num('usage_limit', 'Usage limit', help: 'How many times the code can be used in total.'),
                Field::bool('is_active', 'Published'),
            ],
        );
    }

    /**
     * Turn a list of raw values into value => readable label options.
     *
     * @param  list<string>  $values
     * @return array<string, string>
     */
    private function labels(array $values): array
    {
        $options = [];

        foreach ($values as $value) {
            $options[$value] = Str::headline($value);
        }

        return $options;
    }
}
