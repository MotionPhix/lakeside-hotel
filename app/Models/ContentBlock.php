<?php

namespace App\Models;

use App\Models\Concerns\RegistersImageConversions;
use Database\Factories\ContentBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * An editable prose section of the website - the About story, booking policies,
 * the concierge note - addressed by a stable key rather than an id.
 *
 * @property int $id
 * @property string $key
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $body
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'title', 'subtitle', 'body', 'sort_order', 'is_active'])]
class ContentBlock extends Model implements HasMedia
{
    /** @use HasFactory<ContentBlockFactory> */
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
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Look a block up by its key.
     */
    public static function forKey(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }

    /**
     * Limit the query to published blocks.
     *
     * @param  Builder<ContentBlock>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
