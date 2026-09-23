<?php

namespace App\Models;

use Database\Factories\BookingItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One room category on a booking. A reservation can hold several of these, each
 * with its own nightly breakdown.
 *
 * `nightly_rates` keeps the price actually charged for every night of the stay
 * so that a later rate change can never rewrite an existing folio.
 *
 * @property int $id
 * @property int $booking_id
 * @property int $room_type_id
 * @property int|null $room_id
 * @property int|null $rate_plan_id
 * @property int $adults
 * @property int $children
 * @property string $price_per_night
 * @property string $subtotal
 * @property array<string, string>|null $nightly_rates
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'booking_id', 'room_type_id', 'room_id', 'rate_plan_id', 'adults', 'children',
    'price_per_night', 'subtotal', 'nightly_rates',
])]
class BookingItem extends Model
{
    /** @use HasFactory<BookingItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'adults' => 'integer',
            'children' => 'integer',
            'price_per_night' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'nightly_rates' => 'array',
        ];
    }

    /**
     * The booking this line belongs to.
     *
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * The category booked.
     *
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * The allocated physical room, once one is assigned.
     *
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * The rate plan that priced this line.
     *
     * @return BelongsTo<RatePlan, $this>
     */
    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }

    /**
     * The number of nights on the parent booking.
     */
    public function nights(): int
    {
        return $this->booking?->nights ?? 0;
    }

    /**
     * How many guests this line sleeps.
     */
    public function guests(): int
    {
        return $this->adults + $this->children;
    }
}
