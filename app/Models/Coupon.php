<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A discount code a guest can enter at checkout.
 *
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property string $discount_type
 * @property string $discount_value
 * @property int|null $min_nights
 * @property string|null $min_spend
 * @property int|null $room_type_id
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property int|null $usage_limit
 * @property int $used_count
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'code', 'description', 'discount_type', 'discount_value', 'min_nights', 'min_spend',
    'room_type_id', 'valid_from', 'valid_until', 'usage_limit', 'used_count', 'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_nights' => 'integer',
            'min_spend' => 'decimal:2',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The category this coupon is restricted to, if any.
     *
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Whether the coupon takes a percentage or a flat amount off.
     */
    public function isPercentage(): bool
    {
        return $this->discount_type === 'percentage';
    }

    /**
     * The saving this coupon gives on a subtotal.
     */
    public function discountFor(string $subtotal): string
    {
        $subtotal = (float) $subtotal;
        $value = (float) $this->discount_value;

        $discount = $this->isPercentage()
            ? $subtotal * $value / 100
            : $value;

        return number_format(min($discount, $subtotal), 2, '.', '');
    }

    /**
     * Whether the coupon has been used as often as it is allowed to be.
     */
    public function isExhausted(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /**
     * Whether the coupon can be applied to a stay right now.
     */
    public function isRedeemable(int $nights, string $subtotal, ?CarbonInterface $on = null): bool
    {
        if (! $this->is_active || $this->isExhausted()) {
            return false;
        }

        $on ??= now();

        if ($this->valid_from !== null && $on->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until !== null && $on->gt($this->valid_until)) {
            return false;
        }

        if ($this->min_nights !== null && $nights < $this->min_nights) {
            return false;
        }

        if ($this->min_spend !== null && (float) $subtotal < (float) $this->min_spend) {
            return false;
        }

        return true;
    }

    /**
     * Record a redemption.
     */
    public function redeem(): void
    {
        $this->increment('used_count');
    }

    /**
     * Limit the query to coupons that are switched on and still have uses left.
     *
     * @param  Builder<Coupon>  $query
     */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('valid_from')
                ->orWhere('valid_from', '<=', now()->toDateString()))
            ->where(fn (Builder $query) => $query
                ->whereNull('valid_until')
                ->orWhere('valid_until', '>=', now()->toDateString()))
            ->where(fn (Builder $query) => $query
                ->whereNull('usage_limit')
                ->orWhereColumn('used_count', '<', 'usage_limit'));
    }
}
