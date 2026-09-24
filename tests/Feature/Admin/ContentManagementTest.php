<?php

use App\Cms\ResourceRegistry;
use App\Enums\Role;
use App\Models\Amenity;
use App\Models\ContentBlock;
use App\Models\DiningVenue;
use App\Models\MenuItem;
use App\Models\Room;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/*
 * The config-driven admin, exercised through its own schema rather than through
 * hardcoded expectations. Most of these loop over every content type in the
 * registry, because "the generic screen works for all of them" is the property
 * worth protecting - a new definition that the controller cannot render should
 * fail here, not in front of the hotel.
 */

beforeEach(function (): void {
    $this->seed();

    $this->admin = User::factory()->role(Role::SystemAdmin)->create();
    $this->registry = app(ResourceRegistry::class);
});

test('every content type in the registry can be listed', function () {
    foreach ($this->registry->all() as $key => $definition) {
        $this->actingAs($this->admin)
            ->get(route('admin.content.index', $key))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/content/index')
                ->where('resource.key', $key)
                ->has('rows.data'));
    }
});

test('every content type can be opened in a create form and an edit form', function () {
    foreach ($this->registry->all() as $key => $definition) {
        $this->actingAs($this->admin)
            ->get(route('admin.content.create', $key))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/content/form'));

        $record = $definition->model::query()->first();

        if ($record === null) {
            continue;
        }

        $this->actingAs($this->admin)
            ->get(route('admin.content.edit', ['resource' => $key, 'id' => $record->getKey()]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/content/form')
                ->has('values')
                ->has('media'));
    }
});

test('an unknown content type is a 404 rather than an error', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.content.index', 'not-a-real-resource'))
        ->assertNotFound();
});

test('the hub offers only what the account can open', function () {
    // Marketing holds content, promotions and reviews - but not rooms or pricing.
    $marketing = User::factory()->role(Role::Marketing)->create();

    $this->actingAs($marketing)
        ->get(route('admin.content.hub'))
        ->assertOk()
        ->assertInertia(function ($page) use ($marketing) {
            $offered = collect($page->toArray()['props']['groups'])
                ->flatMap(fn (array $group): array => array_column($group['resources'], 'key'));

            // The property that matters: nothing is offered that this account
            // could not open. Asserting against the permission matrix rather than
            // a hardcoded list means this keeps holding as roles change.
            foreach ($offered as $key) {
                expect($marketing->hasPermission($this->registry->find($key)->permission))
                    ->toBeTrue("Hub offered {$key}, which marketing cannot open.");
            }

            // And the filter actually removed something.
            expect($offered->count())->toBeLessThan(count($this->registry->all()))
                ->and($offered)->toContain('menu-items')
                ->and($offered)->not->toContain('room-types');
        });
});

test('a content type belonging to another module is refused', function () {
    // Room types are the rooms module's; marketing has no business there.
    $marketing = User::factory()->role(Role::Marketing)->create();

    $this->actingAs($marketing)
        ->get(route('admin.content.index', 'room-types'))
        ->assertForbidden();

    $this->actingAs($marketing)
        ->get(route('admin.content.index', 'rate-plans'))
        ->assertForbidden();

    // ...but the content it does own still works.
    $this->actingAs($marketing)
        ->get(route('admin.content.index', 'gallery-items'))
        ->assertOk();
});

test('every field of a content type round-trips through the generic form', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.content.store', 'amenities'), [
            'name' => 'Lake-facing balcony',
            'slug' => 'lake-facing-balcony',
            'icon' => 'waves',
            'category' => 'room',
            'description' => 'A private balcony overlooking the water.',
            'is_active' => true,
        ])
        ->assertRedirect();

    $amenity = Amenity::query()->where('slug', 'lake-facing-balcony')->sole();

    expect($amenity->name)->toBe('Lake-facing balcony')
        ->and($amenity->icon)->toBe('waves')
        ->and($amenity->category)->toBe('room')
        ->and($amenity->description)->toBe('A private balcony overlooking the water.')
        ->and($amenity->is_active)->toBeTrue()
        // New records land at the end of the list rather than on top of it.
        ->and($amenity->sort_order)->toBeGreaterThan(0);

    $this->actingAs($this->admin)
        ->put(route('admin.content.update', ['resource' => 'amenities', 'id' => $amenity->getKey()]), [
            'name' => 'Lake-facing balcony and terrace',
            'slug' => 'lake-facing-balcony',
            'icon' => 'sun',
            'category' => 'room',
            'description' => 'A private balcony and terrace overlooking the water.',
            'is_active' => false,
        ])
        ->assertRedirect();

    $amenity->refresh();

    expect($amenity->name)->toBe('Lake-facing balcony and terrace')
        ->and($amenity->icon)->toBe('sun')
        ->and($amenity->is_active)->toBeFalse();
});

test('a slug left blank is built from the name', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.content.store', 'amenities'), [
            'name' => 'Hair dryer on request',
            'slug' => '',
            'category' => 'bathroom',
            'is_active' => true,
        ]);

    expect(Amenity::query()->where('name', 'Hair dryer on request')->sole()->slug)
        ->toBe('hair-dryer-on-request');
});

test('a relation field round-trips as a select of the related records', function () {
    $venue = DiningVenue::query()->where('slug', 'lakeview-restaurant')->sole();

    $this->actingAs($this->admin)
        ->post(route('admin.content.store', 'menu-items'), [
            'dining_venue_id' => (string) $venue->getKey(),
            'name' => 'Grilled tilapia',
            'description' => 'Whole tilapia from the lake, charcoal grilled.',
            'price' => '26000',
            'category' => 'main_course',
            'is_signature' => true,
            'is_vegetarian' => false,
            'is_available' => true,
        ])
        ->assertRedirect();

    $item = MenuItem::query()->where('name', 'Grilled tilapia')->sole();

    expect($item->dining_venue_id)->toBe($venue->getKey())
        ->and((float) $item->price)->toBe(26000.0)
        ->and($item->category)->toBe('main_course')
        ->and($item->is_signature)->toBeTrue();

    // The form is offered the venues to choose from.
    $this->actingAs($this->admin)
        ->get(route('admin.content.create', 'menu-items'))
        ->assertInertia(fn ($page) => $page
            ->has('relations.dining_venue_id')
            ->where('relations.dining_venue_id.0.label', fn ($label) => is_string($label) && $label !== ''));
});

test('a required field left blank is refused', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.content.store', 'amenities'), [
            'name' => '',
            'slug' => 'something',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('name');

    expect(Amenity::query()->where('slug', 'something')->exists())->toBeFalse();
});

test('a slug that collides with another record is refused', function () {
    $existing = Amenity::query()->firstOrFail();

    $this->actingAs($this->admin)
        ->post(route('admin.content.store', 'amenities'), [
            'name' => 'Another amenity',
            'slug' => $existing->slug,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('slug');
});

test('reordering moves a record and leaves a clean running order', function () {
    $before = Amenity::query()->orderBy('sort_order')->orderBy('id')->pluck('id')->all();

    expect(count($before))->toBeGreaterThan(2);

    $last = end($before);

    $this->actingAs($this->admin)
        ->patch(route('admin.content.move', ['resource' => 'amenities', 'id' => $last, 'direction' => 'up']))
        ->assertRedirect();

    $after = Amenity::query()->orderBy('sort_order')->orderBy('id')->pluck('id')->all();

    // One place up, everything else shifted down.
    expect($after[count($after) - 2])->toBe($last)
        ->and(array_slice($after, 0, count($after) - 2))->toBe(array_slice($before, 0, count($before) - 2));

    // Positions are rewritten as 1..n rather than left with ties.
    $positions = Amenity::query()->orderBy('sort_order')->pluck('sort_order')->all();
    expect($positions)->toBe(range(1, count($positions)));
});

test('moving past either end of the list does nothing', function () {
    $first = Amenity::query()->orderBy('sort_order')->firstOrFail();
    $before = Amenity::query()->orderBy('sort_order')->pluck('id')->all();

    $this->actingAs($this->admin)
        ->patch(route('admin.content.move', ['resource' => 'amenities', 'id' => $first->getKey(), 'direction' => 'up']))
        ->assertRedirect();

    expect(Amenity::query()->orderBy('sort_order')->pluck('id')->all())->toBe($before);
});

test('publishing follows whichever column the content type publishes with', function () {
    // is_active
    $amenity = Amenity::query()->where('is_active', true)->firstOrFail();

    $this->actingAs($this->admin)
        ->patch(route('admin.content.publish', ['resource' => 'amenities', 'id' => $amenity->getKey()]));

    expect($amenity->refresh()->is_active)->toBeFalse();

    // is_available, not is_active
    $dish = MenuItem::query()->where('is_available', true)->firstOrFail();

    $this->actingAs($this->admin)
        ->patch(route('admin.content.publish', ['resource' => 'menu-items', 'id' => $dish->getKey()]));

    expect($dish->refresh()->is_available)->toBeFalse();

    // is_approved, because a review is published by approving it
    $review = Testimonial::query()->where('is_approved', true)->firstOrFail();

    $this->actingAs($this->admin)
        ->patch(route('admin.content.publish', ['resource' => 'testimonials', 'id' => $review->getKey()]));

    expect($review->refresh()->is_approved)->toBeFalse();
});

test('a content type that is not publishable has no toggle', function () {
    // A room's state is its status, not a flag.
    $room = Room::query()->firstOrFail();

    $this->actingAs($this->admin)
        ->patch(route('admin.content.publish', ['resource' => 'rooms', 'id' => $room->getKey()]))
        ->assertNotFound();
});

test('a record can be deleted', function () {
    $block = ContentBlock::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.content.destroy', ['resource' => 'content-blocks', 'id' => $block->getKey()]))
        ->assertRedirect(route('admin.content.index', 'content-blocks'));

    expect(ContentBlock::query()->whereKey($block->getKey())->exists())->toBeFalse();
});

test('an upload lands on the bucket disk and can be removed again', function () {
    Storage::fake('bucket');

    $block = ContentBlock::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.content.media.store', ['resource' => 'content-blocks', 'id' => $block->getKey()]), [
            'collection' => 'image',
            'file' => UploadedFile::fake()->image('lake.jpg', 1200, 800),
        ])
        ->assertRedirect();

    $media = $block->refresh()->getMedia('image');

    expect($media)->toHaveCount(1)
        // The media library is configured to write to the bucket disk, which is
        // public/bucket - the directory the hotel's uploads live in.
        ->and(config('media-library.disk_name'))->toBe('bucket');

    Storage::disk('bucket')->assertExists($media->first()->getPathRelativeToRoot());

    $this->actingAs($this->admin)
        ->delete(route('admin.content.media.destroy', [
            'resource' => 'content-blocks',
            'id' => $block->getKey(),
            'mediaId' => $media->first()->getKey(),
        ]))
        ->assertRedirect();

    expect($block->refresh()->getMedia('image'))->toHaveCount(0);
});

test('uploading a replacement cover clears the one before it', function () {
    Storage::fake('bucket');

    $block = ContentBlock::factory()->create();

    foreach (['first.jpg', 'second.jpg'] as $name) {
        $this->actingAs($this->admin)
            ->post(route('admin.content.media.store', ['resource' => 'content-blocks', 'id' => $block->getKey()]), [
                'collection' => 'image',
                'file' => UploadedFile::fake()->image($name, 800, 600),
            ]);
    }

    // A single-file collection holds one image, not a queue of them.
    expect($block->refresh()->getMedia('image'))->toHaveCount(1);
});

test('a collection the content type does not declare is refused', function () {
    Storage::fake('bucket');

    $block = ContentBlock::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.content.media.store', ['resource' => 'content-blocks', 'id' => $block->getKey()]), [
            'collection' => 'gallery',
            'file' => UploadedFile::fake()->image('nope.jpg'),
        ])
        ->assertNotFound();
});

test('a guest account cannot reach the content admin at all', function () {
    $this->actingAs(User::factory()->guest()->create())
        ->get(route('admin.content.hub'))
        ->assertForbidden();

    $this->actingAs(User::factory()->guest()->create())
        ->get(route('admin.content.index', 'amenities'))
        ->assertForbidden();
});
