<?php

namespace App\Models;

use App\Models\Concerns\RegistersImageConversions;
use Carbon\CarbonInterface;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A special offer advertised on the website: a weekend special, a holiday
 * package, a corporate discount, a honeymoon package.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $subtitle
 * @property string $description
 * @property string|null $highlight
 * @property string|null $discount_label
 * @property string $type
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property string|null $coupon_code
 * @property string|null $terms
 * @property int $sort_order
 * @property bool $is_featured
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'title', 'slug', 'subtitle', 'description', 'highlight', 'discount_label', 'type',
    'starts_on', 'ends_on', 'coupon_code', 'terms', 'sort_order', 'is_featured', 'is_active',
])]
class Offer extends Model implements HasMedia
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory, InteractsWithMedia, RegistersImageConversions {
        RegistersImageConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    /**
     * The categories of offer the hotel runs.
     *
     * @var list<string>
     */
    public const TYPES = ['weekend', 'holiday', 'corporate', 'honeymoon', 'seasonal', 'package'];

    /**
     * Register the media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether the offer is running on a given date.
     */
    public function isLiveOn(CarbonInterface $date): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_on !== null && $date->lt($this->starts_on)) {
            return false;
        }

        return $this->ends_on === null || $date->lte($this->ends_on);
    }

    /**
     * Whether the offer has already run its course.
     */
    public function hasExpired(): bool
    {
        return $this->ends_on !== null && $this->ends_on->isPast();
    }

    /**
     * Limit the query to offers currently running, in display order.
     *
     * @param  Builder<Offer>  $query
     */
    #[Scope]
    protected function live(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('starts_on')
                ->orWhere('starts_on', '<=', now()->toDateString()))
            ->where(fn (Builder $query) => $query
                ->whereNull('ends_on')
                ->orWhere('ends_on', '>=', now()->toDateString()))
            ->orderBy('sort_order');
    }

    /**
     * Limit the query to the offers highlighted on the homepage.
     *
     * @param  Builder<Offer>  $query
     */
    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }
}
