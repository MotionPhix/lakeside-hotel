<?php

namespace App\Models;

use App\Models\Concerns\RegistersImageConversions;
use Database\Factories\ConferencePackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A priced package for a conference, corporate retreat, wedding or private
 * event, including whatever the day delegate rate covers.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property string|null $tagline
 * @property string $description
 * @property int|null $capacity_min
 * @property int|null $capacity_max
 * @property string $price
 * @property string $price_basis
 * @property array<int, string>|null $includes
 * @property int $sort_order
 * @property bool $is_featured
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'slug', 'type', 'tagline', 'description', 'capacity_min', 'capacity_max',
    'price', 'price_basis', 'includes', 'sort_order', 'is_featured', 'is_active',
])]
class ConferencePackage extends Model implements HasMedia
{
    /** @use HasFactory<ConferencePackageFactory> */
    use HasFactory, InteractsWithMedia, RegistersImageConversions {
        RegistersImageConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    /**
     * The kinds of gathering this package is sold for.
     *
     * @var list<string>
     */
    public const TYPES = ['conference', 'corporate_retreat', 'wedding', 'private_event'];

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
            'capacity_min' => 'integer',
            'capacity_max' => 'integer',
            'price' => 'decimal:2',
            'includes' => 'array',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether a group of the given size fits this package.
     */
    public function fitsGroupOf(int $delegates): bool
    {
        if ($this->capacity_min !== null && $delegates < $this->capacity_min) {
            return false;
        }

        return $this->capacity_max === null || $delegates <= $this->capacity_max;
    }

    /**
     * A readable capacity range, e.g. "10 - 120 delegates".
     */
    public function capacityForHumans(): ?string
    {
        return match (true) {
            $this->capacity_min !== null && $this->capacity_max !== null => "{$this->capacity_min} - {$this->capacity_max} delegates",
            $this->capacity_max !== null => "Up to {$this->capacity_max} delegates",
            $this->capacity_min !== null => "From {$this->capacity_min} delegates",
            default => null,
        };
    }

    /**
     * Limit the query to bookable packages, in display order.
     *
     * @param  Builder<ConferencePackage>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Limit the query to one kind of gathering.
     *
     * @param  Builder<ConferencePackage>  $query
     */
    public function scopeType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }
}
