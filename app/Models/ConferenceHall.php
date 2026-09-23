<?php

namespace App\Models;

use App\Models\Concerns\RegistersImageConversions;
use Database\Factories\ConferenceHallFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A named conference hall - Namalenje, Mikute, Mbenje - with its own delegate
 * capacity. Distinct from {@see ConferencePackage}, which is what a group is sold.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $capacity
 * @property string|null $layout
 * @property string|null $description
 * @property array<int, string>|null $features
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug', 'capacity', 'layout', 'description', 'features', 'sort_order', 'is_active'])]
class ConferenceHall extends Model implements HasMedia
{
    /** @use HasFactory<ConferenceHallFactory> */
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
            'capacity' => 'integer',
            'features' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * How the room can be laid out, in words.
     */
    public function capacityForHumans(): string
    {
        return $this->capacity.' delegates';
    }

    /**
     * Limit the query to the halls currently on sale, in display order.
     *
     * @param  Builder<ConferenceHall>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
