<?php

namespace App\Models;

use Database\Factories\SiteSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One section of a public page: which section it is, the heading copy it shows,
 * where it sits in the order, and whether it is published.
 *
 * The frontend walks this list rather than hardcoding markup, which is what lets
 * the hotel reorder sections, change their wording or switch one off without a
 * deploy. The `config` column carries per-section settings such as how many items
 * to show.
 *
 * @property int $id
 * @property string $page
 * @property string $key
 * @property string|null $eyebrow
 * @property string|null $title
 * @property string|null $description
 * @property array<string, mixed>|null $config
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['page', 'key', 'eyebrow', 'title', 'description', 'config', 'sort_order', 'is_active'])]
class SiteSection extends Model
{
    /** @use HasFactory<SiteSectionFactory> */
    use HasFactory;

    /**
     * The sections the homepage can render, in the order they appear by default.
     *
     * @var list<string>
     */
    public const HOME_SECTIONS = [
        'hero', 'booking', 'about', 'accommodation', 'amenities', 'dining',
        'activities', 'conferences', 'gallery', 'testimonials', 'offers', 'location', 'contact',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * A per-section setting, with a default when the hotel has not set one.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * How many items the section should show.
     */
    public function limit(int $default): int
    {
        return (int) $this->setting('limit', $default);
    }

    /**
     * Limit the query to published sections of one page, in display order.
     *
     * @param  Builder<SiteSection>  $query
     */
    #[Scope]
    protected function published(Builder $query, string $page): void
    {
        $query->where('page', $page)->where('is_active', true)->orderBy('sort_order');
    }
}
