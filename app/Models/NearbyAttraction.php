<?php

namespace App\Models;

use App\Models\Concerns\RegistersImageConversions;
use Database\Factories\NearbyAttractionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Something worth visiting near Senga Bay, shown in the location section of the
 * website.
 *
 * @property int $id
 * @property string $name
 * @property string $category
 * @property string|null $description
 * @property string|null $distance_km
 * @property int|null $travel_time_minutes
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'category', 'description', 'distance_km', 'travel_time_minutes', 'sort_order', 'is_active'])]
class NearbyAttraction extends Model implements HasMedia
{
    /** @use HasFactory<NearbyAttractionFactory> */
    use HasFactory, InteractsWithMedia, RegistersImageConversions {
        RegistersImageConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

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
            'distance_km' => 'decimal:2',
            'travel_time_minutes' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Limit the query to published attractions, in display order.
     *
     * @param  Builder<NearbyAttraction>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
