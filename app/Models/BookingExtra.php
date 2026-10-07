<?php

namespace App\Models;

use Database\Factories\BookingExtraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One priced extra added to a stay.
 *
 * Everything about the price is a copy taken when the guest chose it - the name,
 * the basis and the unit price - so that the folio keeps saying what was agreed
 * even after the catalogue is re-priced, or the activity is retired. This is the
 * same reason a room line keeps its `nightly_rates`.
 *
 * @property int $id
 * @property int $booking_id
 * @property int|null $activity_id
 * @property string $name
 * @property string $price_basis
 * @property string $unit_price
 * @property int $quantity
 * @property string $subtotal
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'booking_id', 'activity_id', 'name', 'price_basis',
    'unit_price', 'quantity', 'subtotal', 'sort_order',
])]
class BookingExtra extends Model
{
    /** @use HasFactory<BookingExtraFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'quantity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The stay this extra was added to.
     *
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * The activity it was chosen from, where that still exists.
     *
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * How the price reads, e.g. "per person".
     */
    public function priceBasisLabel(): string
    {
        return match ($this->price_basis) {
            'per_group' => 'per group',
            'per_hour' => 'per hour',
            'complimentary' => 'complimentary',
            default => 'per person',
        };
    }

    /**
     * The line as a guest should read it, e.g. "Sunset cruise × 2".
     *
     * A group price is not multiplied by anything, so it is not given a
     * quantity to read - "for the group" says it instead.
     */
    public function label(): string
    {
        return $this->price_basis === 'per_group'
            ? $this->name.' (for the group)'
            : $this->name.' × '.$this->quantity;
    }
}
