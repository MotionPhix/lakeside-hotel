<?php

namespace App\Models;

use App\Enums\AvailabilityBlockReason;
use Carbon\CarbonInterface;
use Database\Factories\AvailabilityBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A date range a room is deliberately taken off sale: servicing, house use, a
 * refurbishment. Dates are inclusive of both ends.
 *
 * @property int $id
 * @property int $room_id
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property AvailabilityBlockReason $reason
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['room_id', 'starts_on', 'ends_on', 'reason', 'notes', 'created_by'])]
class AvailabilityBlock extends Model
{
    /** @use HasFactory<AvailabilityBlockFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'reason' => AvailabilityBlockReason::class,
        ];
    }

    /**
     * The room that is blocked.
     *
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * The staff member who created the block.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Whether this block covers any night between the two dates.
     */
    public function overlaps(CarbonInterface $startsOn, CarbonInterface $endsOn): bool
    {
        return $this->starts_on->lte($endsOn) && $this->ends_on->gte($startsOn);
    }

    /**
     * The number of nights this block spans.
     */
    public function nights(): int
    {
        return (int) $this->starts_on->diffInDays($this->ends_on) + 1;
    }

    /**
     * Limit the query to blocks overlapping the given range.
     *
     * @param  Builder<AvailabilityBlock>  $query
     */
    #[Scope]
    protected function overlapping(Builder $query, CarbonInterface $startsOn, CarbonInterface $endsOn): void
    {
        $query->where('starts_on', '<=', $endsOn)
            ->where('ends_on', '>=', $startsOn);
    }
}
