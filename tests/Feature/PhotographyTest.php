<?php

use App\Models\GalleryItem;
use App\Models\HeroSlide;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * The photographs the site is built on.
 *
 * A seeder that quietly skips a photograph - because the file moved, or because
 * somebody cleared the collection to re-add it - leaves the homepage on its
 * gradient fallback, which looks deliberate and is not. These assert that what a
 * visitor sees is a photograph.
 */

test('every hero slide leads with a photograph on the bucket disk', function () {
    $this->seed();

    $slides = HeroSlide::query()->get();

    expect($slides)->not->toBeEmpty();

    foreach ($slides as $slide) {
        $image = $slide->getFirstMedia('image');

        expect($image)->not->toBeNull("the slide '{$slide->headline}' has no photograph")
            ->and($image->disk)->toBe('bucket')
            ->and($image->file_name)->toEndWith('.jpg')
            // The file itself, not just the row: the media table would still say
            // the right thing if the upload directory were wiped.
            ->and(file_exists($image->getPath()))->toBeTrue();
    }
});

test('the homepage shows the hero photographs rather than its fallback', function () {
    $this->seed();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('heroSlides', 3)
            ->where('heroSlides.0.image.hero', fn (?string $url): bool => $url !== null && str_contains($url, 'real-hero-pool-garden'))
            ->where('heroSlides.1.image.hero', fn (?string $url): bool => $url !== null && str_contains($url, 'real-hero-terrace-dusk')));
});

test('the hero slides lead with the hotel\'s own photography', function () {
    /*
     * The three heroes were AI stand-ins until the hotel supplied real
     * photographs. A stand-in is a picture of a place the guest is not going to,
     * so this holds the slides on the real ones and fails if a placeholder
     * quietly comes back.
     */
    $this->seed();

    foreach (HeroSlide::query()->get() as $slide) {
        $file = $slide->getFirstMedia('image')?->file_name;

        expect($file)->toStartWith('real-', "the slide '{$slide->headline}' is not using the hotel's own photograph");
    }
});

test('every gallery tile has its photograph', function () {
    $this->seed();

    $tiles = GalleryItem::query()->get();

    expect($tiles)->not->toBeEmpty();

    foreach ($tiles as $tile) {
        expect($tile->getFirstMedia('image'))->not->toBeNull("the tile '{$tile->title}' has no photograph");
    }
});

test('every room category has a cover photograph', function () {
    $this->seed();

    $categories = RoomType::query()->get();

    expect($categories)->not->toBeEmpty();

    foreach ($categories as $category) {
        expect($category->getFirstMedia('cover'))->not->toBeNull("the category '{$category->name}' has no cover");
    }
});

test('the seeded photographs are the repository\'s own, not copies the seeder ate', function () {
    /*
     * The media library deletes the file it is handed unless it is told to keep
     * it, which turns a committed photograph into a one-shot: seeding consumes it
     * and every seed after that attaches nothing. The seeder ran above, so if the
     * originals are still here, they survived it.
     */
    $this->seed();

    $photographs = glob(database_path('seeders/photography/*.jpg'));

    expect($photographs)->not->toBeEmpty();

    foreach ($photographs as $photograph) {
        expect(filesize($photograph))->toBeGreaterThan(0, basename($photograph).' was emptied by seeding');
    }
});
