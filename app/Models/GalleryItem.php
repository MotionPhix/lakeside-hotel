<?php

namespace App\Models;

use App\Models\Concerns\RegistersImageConversions;
use Database\Factories\GalleryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * One tile in the masonry gallery. Holds either an uploaded photo, an uploaded
 * clip, or a link to a hosted video.
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $caption
 * @property string $category
 * @property string $type
 * @property string|null $video_url
 * @property int $sort_order
 * @property bool $is_featured
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'caption', 'category', 'type', 'video_url', 'sort_order', 'is_featured', 'is_active'])]
class GalleryItem extends Model implements HasMedia
{
    /** @use HasFactory<GalleryItemFactory> */
    use HasFactory, InteractsWithMedia, RegistersImageConversions {
        RegistersImageConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    /**
     * The gallery filters offered on the website.
     *
     * @var list<string>
     */
    public const CATEGORIES = [
        'hotel', 'rooms', 'dining', 'activities', 'pool', 'beach', 'events', 'weddings', 'lake',
    ];

    /**
     * Register the media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
        $this->addMediaCollection('video')->singleFile();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether this tile is a video rather than a photograph.
     */
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    /**
     * Limit the query to published tiles, in display order.
     *
     * @param  Builder<GalleryItem>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Limit the query to one gallery category.
     *
     * @param  Builder<GalleryItem>  $query
     */
    public function scopeCategory(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }

    /**
     * Limit the query to the tiles featured on the homepage.
     *
     * @param  Builder<GalleryItem>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }
}
