<?php

namespace App\Models;

use App\Models\Concerns\RegistersImageConversions;
use Database\Factories\DiningVenueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A place to eat or drink: the lakeside restaurant, the bar and lounge, the
 * pool bar.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property string|null $tagline
 * @property string|null $description
 * @property array<string, string>|null $opening_hours
 * @property array<string, string>|null $section_notes
 * @property string|null $menu_note
 * @property string|null $dress_code
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'slug', 'type', 'tagline', 'description', 'opening_hours',
    'section_notes', 'menu_note', 'dress_code', 'sort_order', 'is_active',
])]
class DiningVenue extends Model implements HasMedia
{
    /** @use HasFactory<DiningVenueFactory> */
    use HasFactory, InteractsWithMedia, RegistersImageConversions {
        RegistersImageConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    /**
     * Register the media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('gallery');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'section_notes' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Everything on the menu here.
     *
     * @return HasMany<MenuItem, $this>
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    /**
     * The dishes the kitchen is known for.
     *
     * @return HasMany<MenuItem, $this>
     */
    public function signatureDishes(): HasMany
    {
        return $this->menuItems()->where('is_signature', true);
    }

    /**
     * Limit the query to open venues, in display order.
     *
     * @param  Builder<DiningVenue>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
