<?php

namespace App\Models;

use App\Models\Concerns\RegistersImageConversions;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Something to do on the lake or the beach: a boat ride, a sunset cruise, a
 * fishing trip, a team-building afternoon.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property int|null $duration_minutes
 * @property string|null $price
 * @property string $price_basis
 * @property int|null $min_participants
 * @property int|null $max_participants
 * @property int $sort_order
 * @property bool $is_featured
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'slug', 'description', 'duration_minutes', 'price', 'price_basis',
    'min_participants', 'max_participants', 'sort_order', 'is_featured', 'is_active',
])]
class Activity extends Model implements HasMedia
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory, InteractsWithMedia, RegistersImageConversions {
        RegistersImageConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    /**
     * How the price is quoted.
     *
     * @var list<string>
     */
    public const PRICE_BASES = ['per_person', 'per_group', 'per_hour', 'complimentary'];

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
            'duration_minutes' => 'integer',
            'price' => 'decimal:2',
            'min_participants' => 'integer',
            'max_participants' => 'integer',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether this experience is free of charge.
     */
    public function isComplimentary(): bool
    {
        return $this->price_basis === 'complimentary' || (float) $this->price === 0.0;
    }

    /**
     * The duration in a human readable form, e.g. "2 hours".
     */
    public function durationForHumans(): ?string
    {
        if ($this->duration_minutes === null) {
            return null;
        }

        if ($this->duration_minutes < 60) {
            return "{$this->duration_minutes} minutes";
        }

        $hours = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        return $minutes === 0
            ? $hours.' '.($hours === 1 ? 'hour' : 'hours')
            : "{$hours}h {$minutes}m";
    }

    /**
     * Limit the query to bookable experiences, in display order.
     *
     * @param  Builder<Activity>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Limit the query to the experiences highlighted on the homepage.
     *
     * @param  Builder<Activity>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }
}
