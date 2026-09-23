<?php

namespace App\Models;

use App\Enums\RoomStatus;
use Carbon\CarbonInterface;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A single physical room or chalet. Availability is counted against these, so a
 * room closed for maintenance stops being sold without touching its category.
 *
 * @property int $id
 * @property int $room_type_id
 * @property string $name
 * @property string|null $number
 * @property string|null $floor
 * @property RoomStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['room_type_id', 'name', 'number', 'floor', 'status', 'notes'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RoomStatus::class,
        ];
    }

    /**
     * The category this room belongs to.
     *
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Maintenance and house-use closures for this room.
     *
     * @return HasMany<AvailabilityBlock, $this>
     */
    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(AvailabilityBlock::class);
    }

    /**
     * The booking lines allocated to this room.
     *
     * @return HasMany<BookingItem, $this>
     */
    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * Whether the room is currently sellable.
     */
    public function isBookable(): bool
    {
        return $this->status->isBookable();
    }

    /**
     * Whether an administrative block covers any night in the given range.
     */
    public function isBlockedBetween(CarbonInterface $startsOn, CarbonInterface $endsOn): bool
    {
        return $this->availabilityBlocks()
            ->overlapping($startsOn, $endsOn)
            ->exists();
    }

    /**
     * Limit the query to rooms that can be sold.
     *
     * @param  Builder<Room>  $query
     */
    #[Scope]
    protected function bookable(Builder $query): void
    {
        $query->where('status', RoomStatus::Available);
    }

    /**
     * Limit the query to rooms closed for maintenance or taken out of service.
     *
     * @param  Builder<Room>  $query
     */
    #[Scope]
    protected function outOfService(Builder $query): void
    {
        $query->whereIn('status', [RoomStatus::Maintenance, RoomStatus::OutOfService]);
    }
}
