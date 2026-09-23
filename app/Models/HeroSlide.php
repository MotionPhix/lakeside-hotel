<?php

namespace App\Models;

use App\Models\Concerns\RegistersImageConversions;
use Database\Factories\HeroSlideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A slide in the full-screen homepage hero: either a photograph or a short
 * looping video, with its own call to action.
 *
 * @property int $id
 * @property string $headline
 * @property string|null $subheadline
 * @property string|null $cta_label
 * @property string|null $cta_url
 * @property string|null $secondary_cta_label
 * @property string|null $secondary_cta_url
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'headline', 'subheadline', 'cta_label', 'cta_url',
    'secondary_cta_label', 'secondary_cta_url', 'sort_order', 'is_active',
])]
class HeroSlide extends Model implements HasMedia
{
    /** @use HasFactory<HeroSlideFactory> */
    use HasFactory, InteractsWithMedia, RegistersImageConversions {
        RegistersImageConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether this slide plays a video rather than showing a photograph.
     */
    public function hasVideo(): bool
    {
        return $this->getFirstMedia('video') !== null;
    }

    /**
     * Limit the query to published slides, in display order.
     *
     * @param  Builder<HeroSlide>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
