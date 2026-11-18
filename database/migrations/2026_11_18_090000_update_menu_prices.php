<?php

use App\Models\DiningVenue;
use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/**
 * The 2026 price revision for the Lakeview Restaurant menu.
 *
 * The printed card prices most sections once, under the heading, and only a
 * handful of dishes carry their own figure. The website stores the price on
 * every row, so the section price is written to each row in that section and
 * the exceptions are applied afterwards.
 *
 * Written as absolute values rather than percentages so the migration is
 * idempotent: running it twice leaves the same numbers behind.
 */
return new class extends Migration
{
    /**
     * The section price, as printed under each heading.
     *
     * @var array<string, int>
     */
    private const PRICES = [
        'salads' => 15_000,
        'soups' => 20_000,
        'light_bites' => 22_500,
        'sandwiches' => 32_000,
        'burgers' => 34_000,
        'appetizers' => 32_000,
        'pasta' => 36_000,
        'pizzas' => 58_000,
        'braai' => 40_000,
        'warm_heart_dishes' => 40_000,
        'main_course' => 40_000,
        'rice' => 29_000,
        'noodles' => 32_000,
        'indian_gravy' => 34_000,
        'tandoor' => 40_000,
        'breads' => 8_000,
        'sizzlers' => 55_000,
        'pappad' => 5_000,
        'beverages' => 4_000,
    ];

    /**
     * Dishes the card prices individually, overriding their section.
     *
     * @var array<string, array<string, int>>
     */
    private const EXCEPTIONS = [
        'braai' => [
            'Quarter Chicken Braai with Chips' => 30_000,
        ],
        'warm_heart_dishes' => [
            'Quarter Chicken' => 30_000,
        ],
        'rice' => [
            'Triple Schezwan Rice' => 40_000,
            'Biryani (Veg/Chicken/Beef)' => 50_000,
            'Pulao (Veg/Chicken)' => 50_000,
        ],
        'desserts' => [
            'Ice Cream — Cone or Cup' => 10_000,
        ],
    ];

    /**
     * What each section carried before this revision, for `down()`.
     *
     * @var array<string, int>
     */
    private const PREVIOUS_PRICES = [
        'salads' => 10_000,
        'soups' => 12_000,
        'light_bites' => 12_000,
        'sandwiches' => 18_000,
        'burgers' => 18_000,
        'appetizers' => 18_000,
        'pasta' => 24_000,
        'pizzas' => 35_000,
        'braai' => 25_000,
        'warm_heart_dishes' => 25_000,
        'main_course' => 25_000,
        'rice' => 18_000,
        'noodles' => 18_000,
        'indian_gravy' => 20_000,
        'tandoor' => 25_000,
        'breads' => 5_000,
        'sizzlers' => 35_000,
        'pappad' => 2_500,
        'beverages' => 2_000,
    ];

    /**
     * @var array<string, array<string, int>>
     */
    private const PREVIOUS_EXCEPTIONS = [
        'braai' => [
            'Quarter Chicken Braai with Chips' => 18_000,
        ],
        'warm_heart_dishes' => [
            'Quarter Chicken' => 18_000,
        ],
        'rice' => [
            'Triple Schezwan Rice' => 25_000,
            'Biryani (Veg/Chicken/Beef)' => 30_000,
            'Pulao (Veg/Chicken)' => 30_000,
        ],
        'desserts' => [
            'Ice Cream — Cone or Cup' => 5_000,
        ],
    ];

    public function up(): void
    {
        $this->apply(self::PRICES, self::EXCEPTIONS);
        $this->rewritePizzaExtraCheese('MK 4,000', 'MK 15,000');
    }

    public function down(): void
    {
        $this->apply(self::PREVIOUS_PRICES, self::PREVIOUS_EXCEPTIONS);
        $this->rewritePizzaExtraCheese('MK 15,000', 'MK 4,000');
    }

    /**
     * Write one price list over the menu.
     *
     * @param  array<string, int>  $prices
     * @param  array<string, array<string, int>>  $exceptions
     */
    private function apply(array $prices, array $exceptions): void
    {
        foreach ($prices as $category => $price) {
            MenuItem::query()
                ->where('category', $category)
                ->update(['price' => $price]);
        }

        foreach ($exceptions as $category => $items) {
            foreach ($items as $name => $price) {
                MenuItem::query()
                    ->where('category', $category)
                    ->where('name', $name)
                    ->update(['price' => $price]);
            }
        }
    }

    /**
     * The pizzas note quotes the extra-cheese price, so it moves with the menu.
     */
    private function rewritePizzaExtraCheese(string $from, string $to): void
    {
        DiningVenue::query()
            ->where('slug', 'lakeview-restaurant')
            ->get()
            ->each(function (DiningVenue $venue) use ($from, $to): void {
                $notes = $venue->section_notes ?? [];

                if (! isset($notes['pizzas']) || ! str_contains($notes['pizzas'], $from)) {
                    return;
                }

                $notes['pizzas'] = str_replace($from, $to, $notes['pizzas']);
                $venue->update(['section_notes' => $notes]);
            });
    }
};
