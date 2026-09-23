<?php

namespace App\Models;

use App\Models\Concerns\RegistersImageConversions;
use Carbon\CarbonInterface;
use Database\Factories\RoomTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A bookable room category - Standard, Deluxe, Executive Suite, Family Room or
 * Lakeside Chalet - with its own capacity, amenities and photography.
 *
 * Physical units live in {@see Room}; the prices here are the walk-up rates that
 * a {@see RatePlan} then adjusts for a given night.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $tagline
 * @property string $description
 * @property int $capacity_adults
 * @property int $capacity_children
 * @property int|null $size_sqm
 * @property string|null $bed_configuration
 * @property string $base_price
 * @property string|null $weekend_price
 * @property string $extra_person_price
 * @property int $min_nights
 * @property int $sort_order
 * @property bool $is_featured
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'slug', 'tagline', 'description', 'capacity_adults', 'capacity_children',
    'size_sqm', 'bed_configuration', 'base_price', 'weekend_price', 'extra_person_price',
    'min_nights', 'sort_order', 'is_featured', 'is_active',
])]
class RoomType extends Model implements HasMedia
{
    /** @use HasFactory<RoomTypeFactory> */
    use HasFactory, InteractsWithMedia, RegistersImageConversions {
        RegistersImageConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    /**
     * The nights charged at the weekend rate: Friday and Saturday.
     *
     * @var list<int>
     */
    public const WEEKEND_NIGHTS = [CarbonInterface::FRIDAY, CarbonInterface::SATURDAY];

    /**
     * Register the media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('images');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity_adults' => 'integer',
            'capacity_children' => 'integer',
            'size_sqm' => 'integer',
            'base_price' => 'decimal:2',
            'weekend_price' => 'decimal:2',
            'extra_person_price' => 'decimal:2',
            'min_nights' => 'integer',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * What this room category includes.
     *
     * @return BelongsToMany<Amenity, $this>
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class)->orderBy('sort_order');
    }

    /**
     * The physical rooms sold under this category.
     *
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * The rate plans that apply to this category.
     *
     * @return HasMany<RatePlan, $this>
     */
    public function ratePlans(): HasMany
    {
        return $this->hasMany(RatePlan::class);
    }

    /**
     * The bookings that include this category.
     *
     * @return HasMany<BookingItem, $this>
     */
    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * The coupons restricted to this category.
     *
     * @return HasMany<Coupon, $this>
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * The most guests this category can sleep.
     */
    public function maxOccupancy(): int
    {
        return $this->capacity_adults + $this->capacity_children;
    }

    /**
     * Whether the given night is charged at the weekend rate.
     */
    public function usesWeekendRateFor(CarbonInterface $date): bool
    {
        return $this->weekend_price !== null
            && in_array($date->dayOfWeek, self::WEEKEND_NIGHTS, true);
    }

    /**
     * The walk-up nightly rate for a given night, before any rate plan applies.
     */
    public function nightlyRateFor(CarbonInterface $date): string
    {
        return $this->usesWeekendRateFor($date) && $this->weekend_price !== null
            ? $this->weekend_price
            : $this->base_price;
    }

    /**
     * The cheapest nightly rate advertised on the website.
     */
    public function fromPrice(): string
    {
        $prices = array_filter([$this->base_price, $this->weekend_price], fn (?string $price): bool => $price !== null);

        return (string) min($prices);
    }

    /**
     * A URL for this room category's cover image, if one has been uploaded.
     */
    public function heroUrl(string $conversion = 'card'): ?string
    {
        return $this->getFirstMediaUrl('cover', $conversion) ?: null;
    }

    /**
     * Limit the query to bookable categories, in display order.
     *
     * @param  Builder<RoomType>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Limit the query to the categories highlighted on the homepage.
     *
     * @param  Builder<RoomType>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }
}
