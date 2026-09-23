<?php

namespace App\Models;

use App\Models\Concerns\RegistersImageConversions;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Guest feedback, with the hotel's public reply. Only approved reviews are ever
 * shown on the website.
 *
 * @property int $id
 * @property int|null $booking_id
 * @property string $guest_name
 * @property string|null $guest_country
 * @property int $rating
 * @property string|null $title
 * @property string $quote
 * @property Carbon|null $stayed_on
 * @property string $source
 * @property bool $is_approved
 * @property bool $is_featured
 * @property string|null $response
 * @property Carbon|null $responded_at
 * @property int|null $responded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'booking_id', 'guest_name', 'guest_country', 'rating', 'title', 'quote',
    'stayed_on', 'source', 'is_approved', 'is_featured', 'response',
    'responded_at', 'responded_by',
])]
class Testimonial extends Model implements HasMedia
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory, InteractsWithMedia, RegistersImageConversions {
        RegistersImageConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    /**
     * Where the review came from.
     *
     * @var list<string>
     */
    public const SOURCES = ['website', 'google', 'tripadvisor', 'booking_com', 'manual'];

    /**
     * Register the media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'stayed_on' => 'date',
            'is_approved' => 'boolean',
            'is_featured' => 'boolean',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * The booking this review came from, when it was submitted on site.
     *
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * The staff member who replied.
     *
     * @return BelongsTo<User, $this>
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    /**
     * Whether the hotel has written a public reply.
     */
    public function hasResponse(): bool
    {
        return filled($this->response);
    }

    /**
     * The rating drawn as stars, e.g. "★★★★☆".
     */
    public function stars(): string
    {
        $rating = max(0, min(5, $this->rating));

        return str_repeat('★', $rating).str_repeat('☆', 5 - $rating);
    }

    /**
     * Limit the query to reviews guests may see.
     *
     * @param  Builder<Testimonial>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('is_approved', true);
    }

    /**
     * Limit the query to the reviews in the homepage carousel.
     *
     * @param  Builder<Testimonial>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * Limit the query to reviews still waiting for moderation.
     *
     * @param  Builder<Testimonial>  $query
     */
    public function scopeAwaitingModeration(Builder $query): void
    {
        $query->where('is_approved', false);
    }
}
