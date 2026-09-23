<?php

namespace App\Models;

use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
     * The menu sections shown on the website.
     *
     * @var list<string>
     */
    public const CATEGORIES = ['starters', 'mains', 'grills', 'desserts', 'cocktails', 'drinks'];

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
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_available', true)->orderBy('sort_order');
    }

    /**
     * Limit the query to one menu section.
     *
     * @param  Builder<MenuItem>  $query
     */
    public function scopeCategory(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }

    /**
     * Limit the query to the signature dishes.
     *
     * @param  Builder<MenuItem>  $query
     */
    public function scopeSignature(Builder $query): void
    {
        $query->where('is_signature', true);
    }
}
