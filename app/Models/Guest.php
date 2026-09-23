<?php

namespace App\Models;

use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A person who has stayed, or is about to stay, at the hotel. Kept separate
 * from {@see User} because guests book without an account.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $country
 * @property string|null $city
 * @property string|null $address
 * @property string|null $id_number
 * @property string|null $notes
 * @property bool $marketing_opt_in
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id', 'first_name', 'last_name', 'email', 'phone', 'country',
    'city', 'address', 'id_number', 'notes', 'marketing_opt_in',
])]
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'marketing_opt_in' => 'boolean',
        ];
    }

    /**
     * The linked account, when the guest has one.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Every reservation this guest has made.
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest('check_in');
    }

    /**
     * The guest's full name.
     */
    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Total value of everything this guest has booked.
     */
    public function lifetimeValue(): string
    {
        return (string) $this->bookings()->sum('total');
    }

    /**
     * Whether the guest has stayed with us before.
     */
    public function isReturning(): bool
    {
        return $this->bookings()->count() > 1;
    }

    /**
     * Limit the query to guests matching a name, email or phone search term.
     *
     * @param  Builder<Guest>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }
}
