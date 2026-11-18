<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

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
     * The whole table, cached, under one key.
     *
     * Settings are read on every request - the currency inside a price, the two
     * tax rates inside a total, the phone number in the header and the footer -
     * and read one key at a time they were one query each. `SitePresenter`
     * already plucked the table once per request and then called `value()` five
     * more times for the tax rates, so a single homepage load asked the same
     * table six times for the same rows.
     */
    private const CACHE_KEY = 'hotel.settings';

    /**
     * An hour, so a row changed by hand in SQL - outside any of the events below
     * - corrects itself. Everything the application and the dashboard write goes
     * through the model and clears the cache immediately.
     */
    private const CACHE_TTL = 3600;

    /**
     * The same rows again, held for the life of the application instance.
     *
     * The cache store in production is the database, so reading the cache is
     * itself a query. Caching without this would have turned six settings
     * queries per request into six cache queries - the same table pressure,
     * moved. Held here, a request asks once and every later `value()` in the
     * same request is free.
     *
     * `loadedFor` is the container the array was read for. A test starts a new
     * application between cases and a worker may swap containers, and a static
     * that outlived its application would hand one request's settings to the
     * next.
     *
     * @var array<string, string|null>|null
     */
    private static ?array $loaded = null;

    private static ?int $loadedFor = null;

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * Read a setting, falling back to a default when it has never been saved.
     */
    public static function value(string $key, ?string $default = null): ?string
    {
        return static::values()[$key] ?? $default;
    }

    /**
     * Every setting, keyed by its key.
     *
     * Deliberately not called `all()`: that is Eloquent's, and returning an array
     * where a collection of models is expected would break any caller that ever
     * reached for it.
     *
     * @return array<string, string|null>
     */
    public static function values(): array
    {
        $application = spl_object_id(app());

        if (self::$loaded !== null && self::$loadedFor === $application) {
            return self::$loaded;
        }

        self::$loaded = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            fn (): array => static::query()->pluck('value', 'key')->all(),
        );
        self::$loadedFor = $application;

        return self::$loaded;
    }

    /**
     * Forget the cached table, and the copy held for this application.
     *
     * Called by the model's own events; also here for the seeders and for any
     * code that writes with the query builder and so fires no event.
     */
    public static function flushCache(): void
    {
        self::$loaded = null;
        self::$loadedFor = null;

        Cache::forget(self::CACHE_KEY);
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
