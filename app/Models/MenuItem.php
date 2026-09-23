<?php

namespace App\Models;

use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A dish or a drink on a venue's menu, priced in the hotel's trading currency.
 *
 * @property int $id
 * @property int $dining_venue_id
 * @property string $name
 * @property string|null $description
 * @property string $price
 * @property string $category
 * @property bool $is_signature
 * @property bool $is_vegetarian
 * @property bool $is_available
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'dining_venue_id', 'name', 'description', 'price', 'category',
    'is_signature', 'is_vegetarian', 'is_available', 'sort_order',
])]
class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    /**
     * The menu sections printed on the Lakeside menu, in the order they appear
     * on the card. The key is what is stored on the row, the value is what the
     * guest reads, so a section can be renamed without rewriting every dish.
     *
     * @var array<string, string>
     */
    public const CATEGORY_LABELS = [
        'salads' => 'Salads',
        'soups' => 'Soups',
        'light_bites' => 'Light Bites',
        'sandwiches' => 'Sandwiches',
        'burgers' => 'Burgers',
        'appetizers' => 'Appetizers',
        'pasta' => 'Pasta',
        'pizzas' => 'Pizzas',
        'braai' => 'Braai',
        'warm_heart_dishes' => 'Warm Heart Dishes',
        'main_course' => 'Main Course',
        'rice' => 'Rice',
        'noodles' => 'Noodles',
        'indian_gravy' => 'Indian Gravy',
        'tandoor' => 'Tandoor',
        'breads' => 'Breads and Naans',
        'sizzlers' => 'Sizzlers',
        'pappad' => 'Pappad',
        'beverages' => 'Beverages',
        'desserts' => 'Desserts',
    ];

    /**
     * The menu sections shown on the website.
     *
     * @var list<string>
     */
    public const CATEGORIES = [
        'salads', 'soups', 'light_bites', 'sandwiches', 'burgers', 'appetizers',
        'pasta', 'pizzas', 'braai', 'warm_heart_dishes', 'main_course', 'rice',
        'noodles', 'indian_gravy', 'tandoor', 'breads', 'sizzlers', 'pappad',
        'beverages', 'desserts',
    ];

    /**
     * The heading a menu section is shown under. Falls back to a headline-cased
     * version of the stored key so a section added straight to the database
     * still reads properly before its label is registered here.
     */
    public static function labelFor(string $category): string
    {
        return self::CATEGORY_LABELS[$category] ?? Str::headline($category);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_signature' => 'boolean',
            'is_vegetarian' => 'boolean',
            'is_available' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The venue this dish is served at.
     *
     * @return BelongsTo<DiningVenue, $this>
     */
    public function diningVenue(): BelongsTo
    {
        return $this->belongsTo(DiningVenue::class);
    }

    /**
     * Limit the query to dishes the kitchen can actually serve, in display order.
     *
     * @param  Builder<MenuItem>  $query
     */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where('is_available', true)->orderBy('sort_order');
    }

    /**
     * Limit the query to one menu section.
     *
     * @param  Builder<MenuItem>  $query
     */
    #[Scope]
    protected function category(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }

    /**
     * Limit the query to the signature dishes.
     *
     * @param  Builder<MenuItem>  $query
     */
    #[Scope]
    protected function signature(Builder $query): void
    {
        $query->where('is_signature', true);
    }
}
