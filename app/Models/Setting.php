<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A single editable value for the hotel: contact details, map coordinates,
 * booking policy copy and the like.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $section
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'value', 'section', 'type'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /**
     * Read a setting, falling back to a default when it has never been saved.
     */
    public static function value(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    /**
     * Create or update a setting in one call.
     */
    public static function store(string $key, ?string $value, string $section = 'general', string $type = 'string'): self
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'section' => $section, 'type' => $type],
        );
    }

    /**
     * Limit the query to one section of the settings screen.
     *
     * @param  Builder<Setting>  $query
     */
    #[Scope]
    protected function section(Builder $query, string $section): void
    {
        $query->where('section', $section);
    }
}
