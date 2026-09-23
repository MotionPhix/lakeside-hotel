<?php

namespace App\Models;

use App\Enums\RateAdjustmentType;
use App\Enums\RatePlanType;
use Carbon\CarbonInterface;
use Database\Factories\RatePlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A rule that adjusts the nightly price: a peak season surcharge, a cheaper
 * midweek rate, a holiday premium, a corporate discount or a promotion.
 *
 * A plan scoped to a room category only applies to that category; a plan with no
 * category applies hotel-wide. When several plans cover the same night the one
 * with the highest priority wins.
 *
 * @property int $id
 * @property int|null $room_type_id
 * @property string $name
 * @property string $code
 * @property RatePlanType $type
 * @property RateAdjustmentType $adjustment_type
 * @property string $amount
 * @property int|null $min_nights
 * @property array<int, int>|null $days_of_week
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property int $priority
 * @property bool $is_active
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'room_type_id', 'name', 'code', 'type', 'adjustment_type', 'amount', 'min_nights',
    'days_of_week', 'starts_on', 'ends_on', 'priority', 'is_active', 'notes',
])]
class RatePlan extends Model
{
    /** @use HasFactory<RatePlanFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RatePlanType::class,
            'adjustment_type' => RateAdjustmentType::class,
            'amount' => 'decimal:2',
            'min_nights' => 'integer',
            'days_of_week' => 'array',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The category this plan is limited to, or null for hotel-wide plans.
     *
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Whether the plan applies to a given night.
     */
    public function coversDate(CarbonInterface $date): bool
    {
        if ($this->starts_on !== null && $date->lt($this->starts_on)) {
            return false;
        }

        if ($this->ends_on !== null && $date->gt($this->ends_on)) {
            return false;
        }

        if (is_array($this->days_of_week) && $this->days_of_week !== []) {
            return in_array($date->dayOfWeek, $this->days_of_week, true);
        }

        return true;
    }

    /**
     * Apply the plan to a nightly price.
     */
    public function applyTo(string $nightlyPrice): string
    {
        $price = (float) $nightlyPrice;
        $amount = (float) $this->amount;

        $adjusted = match ($this->adjustment_type) {
            RateAdjustmentType::Override => $amount,
            RateAdjustmentType::Percentage => $price + ($price * $amount / 100),
            RateAdjustmentType::Fixed => $price + $amount,
        };

        return number_format(max($adjusted, 0), 2, '.', '');
    }

    /**
     * Limit the query to live plans, most specific first.
     *
     * @param  Builder<RatePlan>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderByDesc('priority');
    }

    /**
     * Limit the query to plans that could apply to a room category. Plans with
     * no category are hotel-wide and always considered.
     *
     * @param  Builder<RatePlan>  $query
     */
    public function scopeForRoomType(Builder $query, ?int $roomTypeId): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereNull('room_type_id')
            ->orWhere('room_type_id', $roomTypeId));
    }
}
